# Getting Started & Operations Runbook

- **Root Navigation**: [Project Overview](../README.md)
- **Architecture Context**: [Architecture Decision Records](dev/specifications/architecture.md)
- **Subsystem Tasks**: [Task Ledger](dev/ai/tasks.md)
- **Release History**: [Changelog](CHANGELOG.md)
- **Attribution & Notice**: [Notice](NOTICE.md)
- **License**: [MIT License](LICENSE.md)

---

## Document Purpose & Audience
- **What this is for**: Executive onboarding portal, quickstart commands, and index for platform handbooks.
- **Who reads this**: Students, developers, system administrators, and evaluation panelists.
- **Last verified**: 2026-11-19 (verified live on Linux 7.2.15-300.fc45.x86_64).

---

## 1. Operating System Onboarding Guides
For complete step-by-step procedures, refer to the [Getting Started Handbook Index](getting-started/README.md) or select your operating system:

| Platform | Comprehensive Guide Path | Focus Topics |
|---|---|---|
| **Handbook Index** | [Getting Started Handbook](getting-started/README.md) | Central handbook index, terminology, and guide layout |
| **Linux (Native)** | [Linux Onboarding Guide](getting-started/linux/installation.md) | Native Docker, systemd, fast loop, non-interactive execution |
| **Windows 11 (WSL2)** | [Windows Onboarding Guide](getting-started/windows/installation.md) | WSL2 setup, Docker Desktop integration, CRLF safety, RAM limits |
| **Classmate AI Troubleshooting** | [Student AI Prompt Templates](getting-started/windows/ai-troubleshooting-prompts.md) | Structured templates to feed errors into AI without breakage |

---

## 2. Fast-Path Quickstart (POSIX / Linux / WSL2)

```bash
# 0. Pre-flight audit: verify Docker, Ports, PHP runtime, and LaTeX toolchains
just doctor

# 1. Initialize environment configuration and storage directories
just setup

# 2. Build and boot containerized stack (includes Docker pre-flight & port collision check)
just build
just up

# 3. Apply schema migrations and initial seed records (with SQLite fallback detection)
just migrate
just seed

# 4. Verify system health and route inventory (adaptive container or host fallback)
just test
```

If Docker is not running, `just up` provides clear recovery steps and `just fix-docker` attempts auto-escalation. EIMS fully supports offline development and testing: running `just test` automatically falls back to the native host PHP runner and local SQLite fallback database (ADR-002) when Docker is inactive.

Application endpoints:
- Web Application Gateway: `http://localhost:8080`
- Health Telemetry Probe: `http://localhost:8080/health`
- Live Sabotage Shield: `http://localhost:8080/diagnostics/sabotage`
- MySQL Primary Database: `localhost:3306`

---

## 2a. Known Design Trade-Offs

### Targeted Account Lockout (3-Day Hold)

**Design Decision**: Accounts locked by failed login attempts remain locked for 3 days of calendar time.

**Rationale**: The restaurant ERP domain requires operational continuity over strict security lockout windows. A 3-day hold prevents rapid enumeration attacks while allowing legitimate operators to regain access via manager override or timer expiration without emergency database intervention.

**Trade-Off**: A compromised account with a known email stays locked for up to 72 hours. An attacker who performs a lockout attack on a competitor's account denies access to that team for up to 3 days. This is acceptable in the restaurant use case because: (1) the ERP is not a public-facing auth system, (2) lockout affects only the targeted email, not the IP (per-IP limits are separate and higher), and (3) 72-hour hold + operational manager escalation paths provide sufficient attack friction without emergency on-call database resets.

**Implementation**: See `src/app/Core/LoginThrottle.php` for per-IP and per-email rate limit configuration.

---

## 3. Dedicated Handbook Directory Structure

```text
docs/
├── GETTING-STARTED.md (This executive landing page)
└── getting-started/
    ├── README.md (Comprehensive handbook index & terminology)
    ├── linux/
    │   ├── prerequisites.md
    │   ├── installation.md
    │   └── verification.md
    └── windows/
        ├── prerequisites.md
        ├── wsl2-setup.md
        ├── installation.md
        ├── troubleshooting.md
        └── ai-troubleshooting-prompts.md
```
