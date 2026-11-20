#!/usr/bin/env python3
"""Verify that every relative Markdown link in README.md and docs/ resolves to a file or folder.

Checks inline links `[text](target)` only. External URLs (http, https, mailto) and pure
in-page anchors (#section) are skipped. A `#fragment` on a relative link is stripped before
the path check. Exit code 0 when every link resolves, 1 otherwise.

Usage: python3 dev/scripts/py/check_links.py
"""

import re
import sys
from pathlib import Path

REPO_ROOT = Path(__file__).resolve().parent.parent.parent.parent
SCAN_ROOTS = [REPO_ROOT / "README.md", REPO_ROOT / "docs"]
LINK_PATTERN = re.compile(r"\[[^\]]*\]\(([^)\s]+)(?:\s+\"[^\"]*\")?\)")
SKIP_PREFIXES = ("http://", "https://", "mailto:", "#")


def markdown_files() -> list[Path]:
    files: list[Path] = []
    for root in SCAN_ROOTS:
        if root.is_file():
            files.append(root)
        elif root.is_dir():
            files.extend(sorted(root.rglob("*.md")))
    return files


FENCE_PATTERN = re.compile(r"^(```|~~~).*?^\1", re.MULTILINE | re.DOTALL)
INLINE_CODE_PATTERN = re.compile(r"`[^`\n]*`")

# Agent contract tree. The existing `check-links` recipe in the justfile excludes it too.
# Its links were written for an older layout and are not maintained by this check.
EXCLUDED_DIRS = [REPO_ROOT / "docs" / "dev" / "ai" / "condom"]


def strip_code(text: str) -> str:
    """Blank out fenced and inline code, keeping line numbers stable."""
    text = FENCE_PATTERN.sub(lambda m: "\n" * m.group(0).count("\n"), text)
    return INLINE_CODE_PATTERN.sub(lambda m: " " * len(m.group(0)), text)


def is_excluded(md: Path) -> bool:
    return any(md.is_relative_to(d) for d in EXCLUDED_DIRS)


def broken_links() -> list[str]:
    errors: list[str] = []
    for md in markdown_files():
        if is_excluded(md):
            continue
        text = strip_code(md.read_text(encoding="utf-8"))
        for match in LINK_PATTERN.finditer(text):
            target = match.group(1)
            if target.startswith(SKIP_PREFIXES):
                continue
            path_part = target.split("#", 1)[0]
            if path_part == "":
                continue
            resolved = (md.parent / path_part).resolve()
            if not resolved.exists():
                line_no = text.count("\n", 0, match.start()) + 1
                rel = md.relative_to(REPO_ROOT)
                errors.append(f"{rel}:{line_no} -> {target}")
    return errors


def main() -> int:
    files = [f for f in markdown_files() if not is_excluded(f)]
    errors = broken_links()
    if errors:
        print(f"[FAIL] {len(errors)} broken link(s) in {len(files)} file(s):")
        for err in errors:
            print(f"  {err}")
        return 1
    print(f"[PASS] All relative links resolve in {len(files)} Markdown file(s).")
    return 0


if __name__ == "__main__":
    sys.exit(main())
