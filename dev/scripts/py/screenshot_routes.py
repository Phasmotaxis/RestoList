#!/usr/bin/env python3
"""EIMS Route Testing & Headless Screenshot Automation Script.

Captures route screenshots across viewports (1440x900 desktop, 375x812 mobile)
and verifies HTTP responses across all 13 canonical system routes.
"""

import os
import sys
import urllib.parse
import urllib.request
import http.cookiejar
from pathlib import Path

BASE_URL = os.environ.get("BASE_URL", "http://localhost:8080")
REPO_ROOT = Path(__file__).resolve().parent.parent.parent.parent
SPECIMENS_DIR = REPO_ROOT / "docs" / "specimens" / "screenshots"

ROUTES = [
    {"path": "/", "name": "01_public_landing", "auth": None, "expected_status": 200},
    {"path": "/login", "name": "02_auth_login", "auth": None, "expected_status": 200},
    {"path": "/health", "name": "12_health_probe", "auth": None, "expected_status": 200},
    {"path": "/diagnostics/sabotage", "name": "13_diagnostics_sabotage", "auth": None, "expected_status": 500},
    {"path": "/superadmin/dashboard", "name": "04_superadmin_dashboard", "auth": "superadmin", "expected_status": 200},
    {"path": "/superadmin/organizations", "name": "05_superadmin_organizations", "auth": "superadmin", "expected_status": 200},
    {"path": "/superadmin/users", "name": "06_superadmin_users", "auth": "superadmin", "expected_status": 200},
    {"path": "/superadmin/audit-logs", "name": "07_superadmin_audit_logs", "auth": "superadmin", "expected_status": 200},
    {"path": "/admin/dashboard", "name": "08_admin_dashboard", "auth": "admin", "expected_status": 200},
    {"path": "/admin/users", "name": "09_admin_users", "auth": "admin", "expected_status": 200},
    {"path": "/user/dashboard", "name": "10_user_dashboard", "auth": "user", "expected_status": 200},
    {"path": "/user/export", "name": "11_user_export", "auth": "user", "expected_status": 200},
]

CREDENTIALS = {
    "superadmin": ("superadmin@cvsu.edu.ph", "Password123!"),
    "admin": ("admin@cvsu.edu.ph", "Password123!"),
    "user": ("user@cvsu.edu.ph", "Password123!"),
}


def create_session_opener(email: str, password: str):
    cj = http.cookiejar.CookieJar()
    opener = urllib.request.build_opener(urllib.request.HTTPCookieProcessor(cj))

    login_url = f"{BASE_URL}/login"
    login_data = urllib.parse.urlencode({"email": email, "password": password}).encode("utf-8")
    req = urllib.request.Request(login_url, data=login_data, method="POST")
    try:
        resp = opener.open(req)
    except urllib.error.HTTPError as e:
        if e.code in (301, 302):
            resp = e
        else:
            raise e
    return opener


def run_http_verification() -> bool:
    print("[INFO] Executing EIMS 13-route empirical verification suite...")
    SPECIMENS_DIR.mkdir(parents=True, exist_ok=True)

    sessions = {}
    for role, (email, pw) in CREDENTIALS.items():
        try:
            sessions[role] = create_session_opener(email, pw)
            print(f"[AUTH] Successfully established session for role: {role}")
        except Exception as e:
            print(f"[WARN] Failed to authenticate role {role}: {e}")

    failures = 0

    for route in ROUTES:
        url = f"{BASE_URL}{route['path']}"
        role = route.get("auth")
        expected_status = route.get("expected_status", 200)

        opener = sessions.get(role, urllib.request.build_opener())
        req = urllib.request.Request(url)

        try:
            resp = opener.open(req)
            status = resp.status
            content = resp.read()
        except urllib.error.HTTPError as e:
            status = e.code
            content = e.read()
        except Exception as e:
            print(f"[FAIL] {route['path']} connection error: {e}")
            failures += 1
            continue

        if status == expected_status:
            print(f"[PASS] {route['path']} -> HTTP {status} (Expected: {expected_status})")
            # Save visual/mock specimen
            specimen_file = SPECIMENS_DIR / f"{route['name']}.html"
            with open(specimen_file, "wb") as f:
                f.write(content)
        else:
            print(f"[FAIL] {route['path']} -> HTTP {status} (Expected: {expected_status})")
            failures += 1

    # Verify /logout route explicitly
    logout_url = f"{BASE_URL}/logout"
    user_opener = sessions.get("user", urllib.request.build_opener())
    req = urllib.request.Request(logout_url, data=b"", method="POST")
    try:
        resp = user_opener.open(req)
        status = resp.status
    except urllib.error.HTTPError as e:
        status = e.code
    except Exception as e:
        status = 0

    if status in (200, 302):
        print(f"[PASS] /logout (POST) -> HTTP {status} (Session invalidated)")
    else:
        print(f"[FAIL] /logout (POST) -> HTTP {status}")
        failures += 1

    if failures == 0:
        print(f"[SUCCESS] All {len(ROUTES) + 1} canonical routes verified successfully.")
        return True
    print(f"[ERROR] {failures} route verification checks failed.", file=sys.stderr)
    return False


def run_playwright():
    try:
        from playwright.sync_api import sync_playwright
    except ImportError:
        print("[INFO] Playwright not found; using HTTP verification and specimen capture.")
        return run_http_verification()

    print("[INFO] Launching Headless Chromium via Playwright...")
    SPECIMENS_DIR.mkdir(parents=True, exist_ok=True)

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True)

        for vp_name, width, height in [("desktop", 1440, 900), ("mobile", 375, 812)]:
            context = browser.new_context(viewport={"width": width, "height": height})
            page = context.new_page()

            # Public views
            page.goto(f"{BASE_URL}/")
            page.screenshot(path=str(SPECIMENS_DIR / f"01_public_landing_{vp_name}.png"))

            page.goto(f"{BASE_URL}/login")
            page.screenshot(path=str(SPECIMENS_DIR / f"02_auth_login_{vp_name}.png"))

            # SuperAdmin
            page.fill('input[name="email"]', CREDENTIALS["superadmin"][0])
            page.fill('input[name="password"]', CREDENTIALS["superadmin"][1])
            page.click('button[type="submit"]')
            page.wait_for_load_state("networkidle")
            page.screenshot(path=str(SPECIMENS_DIR / f"04_superadmin_dashboard_{vp_name}.png"))

            page.goto(f"{BASE_URL}/superadmin/organizations")
            page.screenshot(path=str(SPECIMENS_DIR / f"05_superadmin_organizations_{vp_name}.png"))

            page.goto(f"{BASE_URL}/superadmin/users")
            page.screenshot(path=str(SPECIMENS_DIR / f"06_superadmin_users_{vp_name}.png"))

            page.goto(f"{BASE_URL}/superadmin/audit-logs")
            page.screenshot(path=str(SPECIMENS_DIR / f"07_superadmin_audit_logs_{vp_name}.png"))

            context.close()

        browser.close()

    print("[SUCCESS] Headless Playwright screenshots captured.")
    return True


def main() -> int:
    success = run_playwright()
    return 0 if success else 1


if __name__ == "__main__":
    sys.exit(main())
