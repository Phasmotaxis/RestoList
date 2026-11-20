# Linux Prerequisites: EIMS Development Environment

- **Root Hub**: [Getting Started Runbook](../../GETTING-STARTED.md)
- **Handbook Index**: [Getting Started Handbook](../README.md)
- **Installation Guide**: [Linux Installation](installation.md)
- **Verification Guide**: [Linux Verification](verification.md)
- **Architecture Context**: [Architecture Decision Records](../../dev/specifications/architecture.md)

---

## Document Purpose & Audience
- **What this is for**: Hardware specs and package prerequisites for running EIMS natively on Linux.
- **Who reads this**: Linux developers, system administrators, and evaluation panelists.
- **Last verified**: 2026-11-19 (verified live on Linux 7.2.15-300.fc45.x86_64; Docker 28.0.0, Python 3.14.0a4, just 1.40.0).

---

## Core Definitions
- **Terminal**: A text-based program where you enter commands directly to the operating system rather than clicking buttons in a graphical window.
- **Container**: A lightweight, isolated execution environment that packages an application and all its dependencies together so it runs reliably across different systems.
- **Environment variable**: A named value managed by the shell or operating system that passes configuration (such as database passwords or port settings) to running programs.

---

## 1. System Requirements
- OS: Fedora Linux, Ubuntu 22.04 LTS+, Debian 12+, Arch Linux, or any modern POSIX-compliant distribution.
- Architecture: `x86_64` (AMD64) or `aarch64` (ARM64).
- Hardware Minimums: 2 CPU cores, 4 GB RAM, 10 GB free disk space.

---

## 2. Core Toolchain Dependencies

### Docker & Docker Compose
Ensure Docker Engine and the Compose plugin are installed and the Docker daemon is enabled:
```bash
# Verify Docker installation
docker --version
docker compose version

# Ensure current user is in the docker group (avoids sudo)
sudo usermod -aG docker $USER
newgrp docker
```

### GNU Just Task Runner
`just` provides a streamlined interface for all repository operational commands:
```bash
# Fedora
sudo dnf install just

# Ubuntu / Debian
sudo apt update && sudo apt install -y just

# Arch Linux
sudo pacman -S just

# Fallback pre-built binary
curl --proto '=https' --tlsv1.2 -sSf https://just.systems/install.sh | bash -s -- --to /usr/local/bin
```

### Python 3 & Client Utilities
Python 3.10+ is required for the local database migrator, stress seeder, and benchmark runner:
```bash
python3 --version
curl --version
```
