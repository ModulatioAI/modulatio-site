#!/bin/sh
# Modulatio installer — Debian/Ubuntu (.deb, self-contained).
#
#   curl -fsSL https://modulatio.ai/install.sh | sh
#
# Downloads the latest Modulatio .deb from GitHub Releases and installs it
# with apt. The package bundles its own Python interpreter under
# /opt/modulatio — no Python required on the host, only glibc.
# On non-Debian systems, install from PyPI instead:  pip install "modulatio[web]"

set -eu

DEB_URL="https://github.com/ModulatioAI/modulatio/releases/latest/download/modulatio_latest_amd64.deb"

if ! command -v apt-get >/dev/null 2>&1 || ! command -v dpkg >/dev/null 2>&1; then
    echo "This installer targets Debian/Ubuntu (apt/dpkg not found)." >&2
    echo "Install from PyPI instead:  pip install \"modulatio[web]\"" >&2
    exit 1
fi

ARCH="$(dpkg --print-architecture)"
if [ "$ARCH" != "amd64" ]; then
    echo "The packaged build is amd64 (this system: $ARCH)." >&2
    echo "Install from PyPI instead:  pip install \"modulatio[web]\"" >&2
    exit 1
fi

SUDO=""
if [ "$(id -u)" -ne 0 ]; then
    if command -v sudo >/dev/null 2>&1; then SUDO="sudo"; else
        echo "Run as root, or install sudo." >&2; exit 1
    fi
fi

TMP="$(mktemp -d)"
trap 'rm -rf "$TMP"' EXIT

echo "Downloading the latest Modulatio package…"
curl -fL --progress-bar -o "$TMP/modulatio.deb" "$DEB_URL"

echo "Installing…"
$SUDO apt-get install -y "$TMP/modulatio.deb"

echo
modulatio --version
echo "Done. Start with:  modulatio setup    (then: modulatio-tui, or modulatio-api for the browser)"
