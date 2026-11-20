# Windows Prerequisites: WSL2 & Virtualization Foundation

- **Root Hub**: [Getting Started Runbook](../../GETTING-STARTED.md)
- **Handbook Index**: [Getting Started Handbook](../README.md)
- **WSL2 Setup Guide**: [WSL2 Configuration](wsl2-setup.md)
- **Installation**: [Windows Installation](installation.md)
- **Troubleshooting**: [Windows Troubleshooting](troubleshooting.md)
- **AI Prompts**: [AI Troubleshooting Prompts](ai-troubleshooting-prompts.md)

---

## Document Purpose & Audience
- **What this is for**: Explains system requirements, BIOS virtualization settings, and WSL2 prerequisites for Windows users.
- **Who reads this**: Students and developers running Windows 10 or 11.
- **Last verified**: 2026-11-19 (UNVERIFIED: current development host is Fedora Linux x86_64; Windows host commands and GUI installers were not executed directly in this environment).

---

## Core Definitions
- **Terminal**: A command-line window (such as Windows PowerShell or Ubuntu in Windows Terminal) where you type text commands rather than clicking graphical menus.
- **Container**: An isolated, lightweight software environment run by Docker Desktop that executes Linux services (Nginx, PHP, MySQL) on Windows without conflicts.
- **Environment variable**: A named configuration key stored by your shell (such as `.env` variables) that tells software which ports or passwords to use.

---

## 1. Hardware & System Requirements
- OS: Windows 10 (Version 2004 or higher, Build 19041+) or Windows 11.
- Virtualization: Hardware Virtualization (VT-x / AMD-V) enabled in host UEFI/BIOS.
- Minimum Specs: 8 GB Host RAM, 4 CPU cores, 20 GB free disk on system drive `C:`.

---

## 2. Required Software Installations

### 1. Windows Subsystem for Linux (WSL2)
Open Windows PowerShell as Administrator and run:
```powershell
wsl --install -d Ubuntu
```
Restart your computer when prompted. Upon restart, launch Ubuntu from the Start menu and set up your UNIX username and password.

### 2. Docker Desktop for Windows
1. Download Docker Desktop from [docker.com](https://www.docker.com/products/docker-desktop/).
2. Run installer and ensure **Use WSL 2 instead of Hyper-V (recommended)** is checked.
3. Open Docker Desktop Settings:
   - Go to **General** -> Verify **Use the WSL 2 based engine** is checked.
   - Go to **Resources** -> **WSL Integration** -> Toggle on integration for **Ubuntu**.
   - Click **Apply & restart**.

### 3. Git for Windows (CRLF Line-Ending Guard)
When checking out repositories on Windows, Git may inadvertently convert UNIX LF line endings to Windows CRLF, which breaks shell scripts (`\r: command not found`). Configure global Git safety:
```powershell
git config --global core.autocrlf false
```
Inside your Ubuntu terminal, configure:
```bash
git config --global core.autocrlf input
```
