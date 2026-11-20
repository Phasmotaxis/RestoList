# Windows Installation & Execution Runbook

- **Root Hub**: [Getting Started Runbook](../../GETTING-STARTED.md)
- **Handbook Index**: [Getting Started Handbook](../README.md)
- **Prerequisites**: [Windows Prerequisites](prerequisites.md)
- **WSL2 Setup**: [WSL2 Configuration](wsl2-setup.md)
- **Troubleshooting**: [Windows Troubleshooting](troubleshooting.md)
- **AI Prompts**: [AI Troubleshooting Prompts](ai-troubleshooting-prompts.md)

---

## Document Purpose & Audience
- **What this is for**: Practical guide for setting up toolchains, launching containers, and verifying EIMS inside WSL2 Ubuntu.
- **Who reads this**: Students and developers deploying EIMS on Windows machines.
- **Last verified**: 2026-11-19 (UNVERIFIED: current host is Linux x86_64; Windows WSL2 terminal setup was not executed directly in this environment).

---

## Core Definitions
- **Terminal**: A command-line window (Windows Terminal running Ubuntu) where you run setup commands.
- **Container**: The isolated Docker software boxes (`eims_app`, `eims_web`, `eims_db`) managed via `docker compose`.
- **Environment variable**: Configuration values defined in `.env` (such as `APP_PORT=8080`) that configure container settings.

---

## 1. Launch WSL2 Terminal
Open Windows Terminal and select **Ubuntu**. All commands from this point onward must be executed inside Ubuntu.

---

## 2. Toolchain Setup Inside Ubuntu

```bash
sudo apt update && sudo apt install -y curl git python3 python3-pip

# Install GNU Just task runner
curl --proto '=https' --tlsv1.2 -sSf https://just.systems/install.sh | bash -s -- --to ~/bin
echo 'export PATH="$HOME/bin:$PATH"' >> ~/.bashrc
source ~/.bashrc
```

Verify `just` is functional:
```bash
just --version
```

---

## 3. Clone Repository & Setup Environment

```bash
cd ~
git clone <repository-url> eims
cd eims

# Initialize environment
cp .env.example .env
just setup
```

---

## 4. Launch Stack & Run Quality Gates

```bash
# Build and launch Docker containers
just build
just up

# Apply database migrations and seeds
just migrate
just seed

# Run automated tests
just test
```

Access the application in your Windows browser at:
`http://localhost:8080`
