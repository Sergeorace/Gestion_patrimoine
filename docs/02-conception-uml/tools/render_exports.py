#!/usr/bin/env python3
"""Rend les diagrammes Mermaid (.mmd) en SVG et PNG via kroki.io."""
import base64
import sys
import time
import zlib
from pathlib import Path

import requests

ROOT = Path(__file__).resolve().parent.parent
SOURCES = ROOT / "sources"
EXPORTS = ROOT / "exports"
FORMATS = ["svg", "png"]


def encode(diagram: str) -> str:
    return base64.urlsafe_b64encode(zlib.compress(diagram.encode("utf-8"), 9)).decode().rstrip("=")


def render(code: str, fmt: str) -> bytes | None:
    url = f"https://kroki.io/mermaid/{fmt}/{encode(code)}"
    r = requests.get(url, timeout=60)
    if r.status_code != 200 or len(r.content) < 100:
        return None
    return r.content


def main() -> int:
    only = sys.argv[1] if len(sys.argv) > 1 else None
    files = sorted(SOURCES.rglob("*.mmd"))
    ok, ko = 0, []
    for f in files:
        rel = f.relative_to(SOURCES).with_suffix("")
        if only and only not in str(rel):
            continue
        code = f.read_text(encoding="utf-8")
        for fmt in FORMATS:
            out = EXPORTS / f"{rel}.{fmt}"
            out.parent.mkdir(parents=True, exist_ok=True)
            data = None
            for attempt in range(3):
                try:
                    data = render(code, fmt)
                except requests.RequestException:
                    data = None
                if data:
                    break
                time.sleep(2)
            if data:
                out.write_bytes(data)
                ok += 1
            else:
                ko.append(str(rel) + "." + fmt)
                print(f"ECHEC {rel}.{fmt}", flush=True)
        print(f"OK {rel}", flush=True)
    print(f"\n{ok} exports generes, {len(ko)} echecs")
    return 0 if not ko else 1


if __name__ == "__main__":
    sys.exit(main())
