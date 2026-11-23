#!/usr/bin/env bash
# ==============================================================================
# EIMS LaTeX Tooling Pre-flight Guard
# Verifies presence of xelatex and latexmk before compilation.
# Emits copy-paste OS installation commands if dependencies are missing.
# ==============================================================================
set -euo pipefail

BOLD='\033[1m'
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
CYAN='\033[0;36m'
NC='\033[0m'

MISSING=()

if ! command -v xelatex >/dev/null 2>&1; then
    MISSING+=("xelatex (XeTeX engine)")
fi

if ! command -v latexmk >/dev/null 2>&1; then
    MISSING+=("latexmk (automated compilation driver)")
fi

if [[ ${#MISSING[@]} -eq 0 ]]; then
    exit 0
fi

echo ""
echo -e "${RED}================================================================================${NC}"
echo -e "${RED}${BOLD}[ERROR] MISSING LATEX COMPILATION PREREQUISITES${NC}"
echo -e "${RED}================================================================================${NC}"
echo -e "${YELLOW}Cannot compile academic monograph 'paper/main.tex'. Missing tools:${NC}"
for item in "${MISSING[@]}"; do
    echo -e "  - ${BOLD}${item}${NC}"
done
echo ""
echo -e "${BOLD}Install with your system package manager:${NC}"

if command -v dnf >/dev/null 2>&1; then
    echo -e "  ${CYAN}Fedora/RHEL/AlmaLinux:${NC}"
    echo -e "    ${BOLD}sudo dnf install texlive-xetex latexmk texlive-collection-latexextra biber${NC}"
elif command -v apt-get >/dev/null 2>&1; then
    echo -e "  ${CYAN}Debian/Ubuntu/Pop!_OS:${NC}"
    echo -e "    ${BOLD}sudo apt update && sudo apt install -y latexmk texlive-xetex biber${NC}"
elif command -v pacman >/dev/null 2>&1; then
    echo -e "  ${CYAN}Arch Linux / Manjaro:${NC}"
    echo -e "    ${BOLD}sudo pacman -S texlive-meta texlive-xetex biber${NC}"
elif [[ "$OSTYPE" == "darwin"* ]]; then
    echo -e "  ${CYAN}macOS (Homebrew):${NC}"
    echo -e "    ${BOLD}brew install --cask mactex${NC} (full suite)"
    echo -e "    or: ${BOLD}brew install basictex && eval \"\$(/usr/libexec/path_helper)\" && sudo tlmgr install latexmk xetex biber${NC}"
elif command -v nix-env >/dev/null 2>&1 || command -v nix >/dev/null 2>&1; then
    echo -e "  ${CYAN}Nix / NixOS:${NC}"
    echo -e "    ${BOLD}nix-env -iA nixpkgs.texlive.combined.scheme-full nixpkgs.biber${NC}"
else
    echo -e "  ${CYAN}Generic Linux/Unix:${NC}"
    echo -e "    Install TeX Live from https://tug.org/texlive/ or your system software center."
fi

echo -e "${RED}================================================================================${NC}"
echo ""
exit 1
