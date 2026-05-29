#!/usr/bin/env python3
"""Durable FTPS uploader for the Modulatio docs site.

Mirrors the built ``dist/`` tree to the modulatio.ai web root over FTPS.
Credentials come from the environment — nothing secret lives in this file.

    MODULATIO_FTP_HOST   default ftp.modulatio.ai
    MODULATIO_FTP_USER   default cknox@modulatio.ai
    MODULATIO_FTP_PASS   required (no default)
    MODULATIO_FTP_BASE   remote base dir; default "" (the cknox@ user is
                         chrooted to public_html, so the chroot root IS
                         the web root — do NOT prepend public_html or you
                         get the orphan-files-in-a-subdir bug we hit once)

Usage:
    MODULATIO_FTP_PASS=... python3 scripts/deploy_ftp.py            # deploy
    MODULATIO_FTP_PASS=... python3 scripts/deploy_ftp.py --dry-run  # list only
"""
from __future__ import annotations

import ftplib
import os
import sys
from pathlib import Path

HERE = Path(__file__).resolve().parent.parent
DIST = HERE / "dist"


def env(name: str, default: str | None = None) -> str:
    val = os.environ.get(name, default)
    if val is None:
        sys.exit(f"error: {name} is required (export it before running)")
    return val


def ensure_remote_dir(ftp: ftplib.FTP_TLS, remote_dir: str) -> None:
    """mkdir -p for the remote path, segment by segment, idempotent."""
    parts = [p for p in remote_dir.split("/") if p]
    path = ""
    for part in parts:
        path = f"{path}/{part}" if path else part
        try:
            ftp.mkd(path)
        except ftplib.error_perm as e:
            if not str(e).startswith("550"):  # 550 = already exists
                raise


def main() -> None:
    dry = "--dry-run" in sys.argv
    if not DIST.is_dir():
        sys.exit(f"error: {DIST} not found — run `npm run build` first")

    host = env("MODULATIO_FTP_HOST", "ftp.modulatio.ai")
    user = env("MODULATIO_FTP_USER", "cknox@modulatio.ai")
    base = os.environ.get("MODULATIO_FTP_BASE", "").strip("/")

    files = sorted(p for p in DIST.rglob("*") if p.is_file())
    print(f"{'DRY-RUN: ' if dry else ''}deploying {len(files)} files "
          f"from {DIST} → {host}/{base or '(chroot root)'} as {user}")
    if dry:
        for f in files:
            print(f"  would upload {f.relative_to(DIST)}")
        return

    pw = env("MODULATIO_FTP_PASS")
    ftp = ftplib.FTP_TLS(host, timeout=60)
    ftp.login(user, pw)
    ftp.prot_p()  # secure the data channel, not just the control channel

    made: set[str] = set()
    for f in files:
        rel = f.relative_to(DIST).as_posix()
        remote = f"{base}/{rel}" if base else rel
        rdir = "/".join(remote.split("/")[:-1])
        if rdir and rdir not in made:
            ensure_remote_dir(ftp, rdir)
            made.add(rdir)
        with f.open("rb") as fh:
            ftp.storbinary(f"STOR {remote}", fh)
        print(f"  ↑ {rel}")
    ftp.quit()
    print(f"done — {len(files)} files uploaded.")


if __name__ == "__main__":
    main()
