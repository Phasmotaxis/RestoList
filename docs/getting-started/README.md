# EIMS Getting Started Handbook

- **Root Hub**: [Getting Started Runbook](../GETTING-STARTED.md)
- **Project Overview**: [README](../../README.md)
- **Task Ledger**: [Subsystem Task Registry](../dev/ai/tasks.md)
- **Changelog Tracker**: [Project Changelog](../CHANGELOG.md)

---

## Document Purpose & Audience
- **What this is for**: Central index for operating system onboarding and troubleshooting guides.
- **Who reads this**: Students, developers, and panel evaluators setting up EIMS on Linux or Windows.
- **Last verified**: 2026-11-19 (Linux suite verified live; Windows procedures reviewed for correctness).

---

## Core Concepts & Terminology
Before starting, review three fundamental computing concepts used throughout these guides:

1. **Terminal**: A text-based program where you type commands to instruct the operating system directly, rather than clicking buttons in a graphical window.
2. **Container**: A lightweight, isolated software box that bundles the application with its code, runtime libraries, and configurations so it executes identically on any machine without altering your host system.
3. **Environment variable**: A named setting stored by your operating system or shell (for example, in a `.env` file) that tells applications how to run (such as database passwords or port numbers) without hardcoding values into source code.

---

## Platform Guides

### Linux Onboarding
Native Linux development workflow using Docker Engine and system packages:
- [Linux Prerequisites](linux/prerequisites.md): Hardware requirements and package installations (`docker`, `docker compose`, `just`, `python3`).
- [Linux Installation](linux/installation.md): Environment configuration, container compilation, and initial bootstrap.
- [Linux Verification](linux/verification.md): Automated quality gates, panel sabotage verification, and test suites.

### Windows Onboarding (WSL2)
Windows 10/11 workflow using Windows Subsystem for Linux 2:
- [Windows Prerequisites](windows/prerequisites.md): BIOS virtualization requirements, WSL2 Ubuntu installation, and Docker Desktop setup.
- [WSL2 Configuration](windows/wsl2-setup.md): Resource limits (`.wslconfig`) to prevent laptop memory exhaustion.
- [Windows Installation](windows/installation.md): Running the stack inside WSL2 Ubuntu.
- [Windows Troubleshooting](windows/troubleshooting.md): Fixing CRLF line ending errors, port collisions, and Docker socket issues.
- [AI Troubleshooting Prompts](windows/ai-troubleshooting-prompts.md): Copy-paste prompt templates for debugging assistance without hallucinated regressions.
