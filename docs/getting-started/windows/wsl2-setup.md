# Windows WSL2 Setup: Performance Tuning & Resource Capping

- **Root Hub**: [Getting Started Runbook](../../GETTING-STARTED.md)
- **Handbook Index**: [Getting Started Handbook](../README.md)
- **Prerequisites**: [Windows Prerequisites](prerequisites.md)
- **Installation**: [Windows Installation](installation.md)
- **Troubleshooting**: [Windows Troubleshooting](troubleshooting.md)

---

## Document Purpose & Audience
- **What this is for**: Explains how to cap WSL2 memory and CPU allocation via `.wslconfig` to prevent Windows laptop stuttering.
- **Who reads this**: Windows students and developers running Docker on 8 GB or 16 GB laptops.
- **Last verified**: 2026-11-19 (UNVERIFIED: current host is Linux x86_64; Windows `.wslconfig` host tuning was not executed directly in this environment).

---

## Core Definitions
- **Terminal**: The command window (such as PowerShell or WSL2 Ubuntu) used to issue system commands.
- **Container**: The encapsulated Docker software instance running inside the WSL2 virtual machine.
- **Environment variable**: A system setting that passes configuration values to running processes.

---

## 1. Preventing Host Freezes via `.wslconfig`
By default, WSL2 can consume up to 50% to 80% of total host RAM, leading to severe Windows stuttering on 8 GB or 16 GB laptops when compiling containers or running stress seeders.

Create a global configuration file in your Windows user profile folder:
Path: `C:\Users\<YourWindowsUsername>\.wslconfig`

Open Notepad or VS Code and create the file with the following contents:
```ini
[wsl2]
# Cap RAM allocation to 4GB to keep Windows host responsive
memory=4GB

# Assign 2 logical cores to WSL2
processors=2

# Cap swap space to prevent SSD exhaustion
swap=2GB

# Automatically release cached memory back to Windows
autoMemoryReclaim=gradual
```

Save the file and shut down WSL2 from PowerShell to apply settings:
```powershell
wsl --shutdown
```
Restart Ubuntu. Verify memory capping inside Ubuntu:
```bash
free -h
```
Total memory should now report approximately `3.8Gi` to `4.0Gi`.

---

## 2. Directory Placement Invariant (Performance Guard)
**CRITICAL**: Always clone and store this repository inside the Linux virtual filesystem (`/home/<username>/projects/...` or `~`), NEVER inside the Windows mount (`/mnt/c/...`).
- Windows mount (`/mnt/c/`): Cross-filesystem 9P driver introduces 10x-50x I/O latency bottlenecks, breaking container file-watching and causing MySQL timeouts.
- Native Linux ext4 (`~/`): Maximum native SSD throughput.
