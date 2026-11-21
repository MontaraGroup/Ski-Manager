#!/usr/bin/env python3
"""
Cut the trail map images into 256 px WebP tiles for Leaflet, so the browser loads only the part of the
mountain on screen instead of decoding one 100+ megapixel image.

The map uses Leaflet's SimpleCRS with bounds [[0, 0], [HEIGHT, WIDTH]] in map units (ResortMap::RESORT_MAPS,
600 x 340), and the image is stretched to fill them. At zoom z one map unit is 2**z pixels, the image's left
edge is at x = 0 and its top edge at y = -HEIGHT * 2**z (latitude grows upward). Tiles are cut on Leaflet's
own 256 px grid, so {y} is negative, and tiles on the edges are partly transparent.

Output: public/img/tiles/v2/<name>/<z>/<x>_<y>.webp and meta.json (written last; the site uses the tiles only
once meta.json exists). A run is skipped when meta.json already matches the source file and this script.

Usage: scripts/make_map_tiles.py <public dir> [image name ...]   (default names: ParkCity ParkCity-Big)
"""

import hashlib
import json
import os
import sys
import tempfile

from PIL import Image

Image.MAX_IMAGE_PIXELS = None  # the sources are our own maps (up to 238 MP), not untrusted uploads

SCRIPT_VERSION = 1
TILE = 256
WIDTH, HEIGHT = 600, 340  # map units, as in ResortMap::RESORT_MAPS
MAX_ZOOM = 4  # the map's maxZoom
QUALITY = 80


def source_stamp(path: str) -> str:
    st = os.stat(path)
    return f"{st.st_size}-{int(st.st_mtime)}-v{SCRIPT_VERSION}"


def load(path: str, need_w: int) -> Image.Image:
    img = Image.open(path)
    # JPEG can decode at 1/2, 1/4 or 1/8 scale: take the smallest that comes within 2% of the largest zoom
    # level (the rest is a slight upscale nobody sees). Keeps the 18900 px admin map at ~360 MB, not 714 MB.
    if img.format == "JPEG":
        want = int(need_w * 0.98)
        img.draft("RGB", (want, int(want * img.height / img.width)))
    img.load()
    return img if img.mode == "RGB" else img.convert("RGB")


def cut(public: str, name: str) -> None:
    src = os.path.join(public, "img", name + ".jpg")
    if not os.path.isfile(src):
        print(f"{name}: no {src}, skipped")
        return
    out = os.path.join(public, "img", "tiles", "v2", name)
    meta_path = os.path.join(out, "meta.json")
    stamp = source_stamp(src)
    try:
        with open(meta_path) as f:
            if json.load(f).get("stamp") == stamp:
                print(f"{name}: tiles are current")
                return
    except (OSError, ValueError):
        pass

    # Tiles go to a fresh folder that replaces the old one only when complete
    os.makedirs(os.path.dirname(out), exist_ok=True)
    work = tempfile.mkdtemp(prefix=f".{name}.", dir=os.path.dirname(out))
    full_w = WIDTH * 2**MAX_ZOOM
    full_h = HEIGHT * 2**MAX_ZOOM
    # Stretch to the largest level in bands of rows (resize's box= reads only that part of the source), so no
    # full-size intermediate is allocated; then drop the source. Peak memory is the source plus the result.
    source = load(src, full_w)
    sw, sh = source.size
    base = Image.new("RGB", (full_w, full_h))
    band = 512
    for oy in range(0, full_h, band):
        bh = min(band, full_h - oy)
        part = source.resize((full_w, bh), Image.LANCZOS, box=(0, oy * sh / full_h, sw, (oy + bh) * sh / full_h))
        base.paste(part, (0, oy))
        part.close()
    source.close()
    del source
    count = 0
    for z in range(0, MAX_ZOOM + 1):
        w, h = WIDTH * 2**z, HEIGHT * 2**z
        level = base if z == MAX_ZOOM else base.resize((w, h), Image.LANCZOS)
        os.makedirs(os.path.join(work, str(z)))
        top = -h  # image top edge in Leaflet pixel space
        for ty in range(top // TILE, 0):
            for tx in range(0, -(-w // TILE)):
                x0, y0 = tx * TILE, ty * TILE - top  # tile corner inside the stretched image
                tile = Image.new("RGBA", (TILE, TILE), (0, 0, 0, 0))
                crop = level.crop((max(0, x0), max(0, y0), min(w, x0 + TILE), min(h, y0 + TILE)))
                tile.paste(crop, (max(0, -x0), max(0, -y0)))
                if tile.getextrema()[3][0] == 255:
                    tile = tile.convert("RGB")
                tile.save(os.path.join(work, str(z), f"{tx}_{ty}.webp"), "WEBP", quality=QUALITY, method=4)
                count += 1
        level.close()

    version = hashlib.sha1(stamp.encode()).hexdigest()[:10]
    with open(os.path.join(work, "meta.json"), "w") as f:
        json.dump({"stamp": stamp, "version": version, "maxZoom": MAX_ZOOM, "tileSize": TILE, "tiles": count}, f)
    old = out + ".old"
    if os.path.isdir(out):
        os.rename(out, old)
    os.rename(work, out)
    os.chmod(out, 0o755)
    if os.path.isdir(old):
        import shutil
        shutil.rmtree(old, ignore_errors=True)
    print(f"{name}: {count} tiles, version {version}")


def main() -> int:
    if len(sys.argv) < 2:
        print(__doc__)
        return 1
    public = sys.argv[1]
    for name in sys.argv[2:] or ["ParkCity", "ParkCity-Big"]:
        cut(public, name)
    return 0


if __name__ == "__main__":
    sys.exit(main())
