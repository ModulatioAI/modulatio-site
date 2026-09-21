#!/usr/bin/env python3
"""Rebuild the offline documentation bundle from the site's own doc sources.

The bundle was assembled by hand once and then went stale by five releases while the
site it mirrors moved on. A bundle derived from the sources cannot drift from them: the
page list comes from the directory tree and the version comes from the engine's package
literal, so a release that forgets this step produces a stale-version bundle that fails
its own check rather than a plausible-looking one nobody notices.

Usage:  python3 scripts/build_offline_docs.py [--check]

``--check`` rebuilds into a temporary directory and compares, exiting non-zero when the
shipped bundle no longer matches the sources — a release gate that needs no memory.
"""
from __future__ import annotations

import argparse
import gzip
import hashlib
import io
import json
import re
import sys
import tarfile
import tempfile
from pathlib import Path

SITE = Path(__file__).resolve().parent.parent
DOCS = SITE / "src" / "content" / "docs"
OUT = SITE / "public" / "docs" / "offline"
ENGINE_VERSION = Path.home() / "modulatio" / "src" / "modulatio" / "__init__.py"

# Overview first, then each section in reading order, then the two standalone pages.
# Release-notes pages and the landing page are deliberately absent: the bundle is the
# reference material, not the changelog.
SECTIONS = ("getting-started", "concepts", "architecture", "operations", "reference")
STANDALONE_HEAD = ("overview.mdx",)
STANDALONE_TAIL = ("methodology.mdx", "troubleshooting.mdx")

FRONTMATTER = re.compile(r"\A---\n(.*?)\n---\n", re.S)
IMPORT_LINE = re.compile(r"^import .*?;\s*$\n?", re.M)
ASIDE_OPEN = re.compile(r"^<Aside(?:\s+type=\"[a-z]+\")?(?:\s+title=\"([^\"]*)\")?\s*>\s*$",
                        re.M)


def page_order() -> list[Path]:
    pages = [DOCS / n for n in STANDALONE_HEAD]
    for section in SECTIONS:
        pages += sorted((DOCS / section).glob("*.mdx"))
    pages += [DOCS / n for n in STANDALONE_TAIL]
    missing = [p for p in pages if not p.is_file()]
    if missing:
        raise SystemExit(f"missing doc sources: {missing}")
    return pages


def title_of(text: str, path: Path) -> str:
    m = FRONTMATTER.match(text)
    if not m:
        raise SystemExit(f"{path} has no frontmatter")
    for line in m.group(1).splitlines():
        if line.startswith("title:"):
            return line[len("title:"):].strip().strip("'\"")
    raise SystemExit(f"{path} has no title")


def to_markdown(text: str, title: str) -> str:
    """Strip the MDX layer, leaving portable markdown.

    An Aside becomes a blockquote because a reader opening these files in a plain editor
    has no component to render, and a dropped Aside would silently lose the caveats that
    are the reason several of these pages exist.
    """
    body = FRONTMATTER.sub("", text)
    body = IMPORT_LINE.sub("", body)

    out, i = [], 0
    for m in ASIDE_OPEN.finditer(body):
        out.append(body[i:m.start()])
        close = body.index("</Aside>", m.end())
        inner = body[m.end():close].strip("\n")
        quoted = [f"> **{m.group(1)}**", ">"] if m.group(1) else []
        quoted += [f"> {ln}" if ln.strip() else ">" for ln in inner.splitlines()]
        out.append("\n".join(quoted))
        i = close + len("</Aside>")
    out.append(body[i:])
    body = "".join(out)

    if "<Aside" in body or "</Aside>" in body:
        raise SystemExit("an Aside survived conversion — check for a nested or inline one")
    return f"# {title}\n\n{body.lstrip()}"


def engine_version() -> str:
    m = re.search(r'__version__ = "([^"]+)"', ENGINE_VERSION.read_text())
    if not m:
        raise SystemExit(f"no __version__ in {ENGINE_VERSION}")
    return m.group(1)


def build(dest: Path) -> dict:
    dest.mkdir(parents=True, exist_ok=True)
    pages, files = [], []
    for n, src in enumerate(page_order(), start=1):
        text = src.read_text()
        title = title_of(text, src)
        slug = f"{n:02d}-{src.stem}"
        (dest / f"{slug}.md").write_text(to_markdown(text, title))
        pages.append({"slug": slug, "title": title})
        files.append(f"{slug}.md")

    # The reader takes the docs version from a manifest inside the archive, so the
    # manifest goes in as a member; the checksum is added to the copy served beside it.
    manifest = {"version": engine_version(), "bundle": "docs-bundle.tar.gz", "pages": pages}
    (dest / "manifest.json").write_text(json.dumps(manifest, indent=2) + "\n")
    files.append("manifest.json")

    # A fixed mtime and sorted member order keep the archive byte-identical for identical
    # sources, which is what makes --check a usable gate.
    raw = io.BytesIO()
    # gzip stamps the current time into its own header, so the archive is built through
    # an explicit GzipFile with a fixed mtime rather than tarfile's "w:gz" shorthand.
    gz = gzip.GzipFile(filename="", fileobj=raw, mode="wb", compresslevel=9, mtime=0)
    with gz, tarfile.open(fileobj=gz, mode="w") as tar:
        for name in files:
            info = tar.gettarinfo(dest / name, arcname=name)
            info.mtime = 0
            info.uid = info.gid = 0
            info.uname = info.gname = ""
            with open(dest / name, "rb") as fh:
                tar.addfile(info, fh)
    data = raw.getvalue()
    (dest / "docs-bundle.tar.gz").write_bytes(data)
    for name in files:
        (dest / name).unlink()

    manifest["bundle_sha256"] = hashlib.sha256(data).hexdigest()
    (dest / "manifest.json").write_text(json.dumps(manifest, indent=2) + "\n")
    return manifest


def main() -> int:
    ap = argparse.ArgumentParser()
    ap.add_argument("--check", action="store_true",
                    help="verify the shipped bundle matches the sources")
    args = ap.parse_args()

    if args.check:
        with tempfile.TemporaryDirectory() as tmp:
            fresh = build(Path(tmp))
            shipped = json.loads((OUT / "manifest.json").read_text())
        same = (fresh["version"] == shipped.get("version")
                and fresh["bundle_sha256"] == shipped.get("bundle_sha256"))
        print("offline docs bundle:", "current" if same else "STALE")
        if not same:
            print(f"  sources -> {fresh['version']} {fresh['bundle_sha256'][:12]}")
            print(f"  shipped -> {shipped.get('version')} "
                  f"{str(shipped.get('bundle_sha256'))[:12]}")
        return 0 if same else 1

    m = build(OUT)
    print(f"{OUT}: {len(m['pages'])} pages, version {m['version']}, "
          f"sha256 {m['bundle_sha256'][:12]}")
    return 0


if __name__ == "__main__":
    sys.exit(main())
