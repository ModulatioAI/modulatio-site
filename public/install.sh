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

DEB_NAME="modulatio_latest_amd64.deb"
DEB_URL="https://github.com/ModulatioAI/modulatio/releases/latest/download/${DEB_NAME}"
SUMS_URL="https://github.com/ModulatioAI/modulatio/releases/latest/download/SHA256SUMS.deb"

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

# Verify the download against the release's published checksum manifest before
# installing anything as root. The manifest lists the package by name; pull
# its expected hash and compare. A mismatch (or a missing manifest entry)
# aborts before apt runs.
echo "Verifying checksum…"
if ! curl -fL -o "$TMP/SHA256SUMS.deb" "$SUMS_URL"; then
    echo "Could not fetch the checksum manifest ($SUMS_URL) — aborting." >&2
    exit 1
fi
EXPECTED="$(awk -v n="$DEB_NAME" '$2 ~ n {print $1; exit}' "$TMP/SHA256SUMS.deb")"
if [ -z "$EXPECTED" ]; then
    echo "No checksum for $DEB_NAME in the manifest — aborting." >&2
    exit 1
fi
ACTUAL="$(sha256sum "$TMP/modulatio.deb" | awk '{print $1}')"
if [ "$EXPECTED" != "$ACTUAL" ]; then
    echo "Checksum mismatch — refusing to install." >&2
    echo "  expected: $EXPECTED" >&2
    echo "  actual:   $ACTUAL" >&2
    exit 1
fi

echo "Installing…"
$SUDO apt-get install -y "$TMP/modulatio.deb"

echo
modulatio --version
echo "Done. Start with:  modulatio setup    (then: modulatio-tui, or modulatio-api for the browser)"
