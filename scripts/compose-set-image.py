"""Compose a set (bundle) photo from its components' product photos.

Usage:
  python3 scripts/compose-set-image.py <photos_dir> <out.jpg> '<spec json>'

spec: [{"file": "opera-15.png", "h": 0.62, "n": 2}, ...]
  file  product photo in photos_dir (white or transparent background)
  h     height relative to the tallest item (stands ~1.0, speakers ~0.4-0.6)
  n     how many units to show side by side

Output: 1600x1200 JPEG on white with soft floor shadows. Upload it as the
set's main photo (Admin, or MCP tool upload-product-photo). Used 2026-11-21
for the DJ, speeches and ceremony sets; needs Pillow.
"""
import sys, json
from PIL import Image, ImageFilter, ImageChops, ImageDraw

SRC, OUT = sys.argv[1], sys.argv[2]
spec = json.loads(sys.argv[3])  # [{"file":..., "h": relative height, "n": units}]
W, H, PAD = 1600, 1200, 90
UNIT = 1000  # working height reference

def cutout(path):
    im = Image.open(path).convert('RGBA')
    r, g, b, a = im.split()
    nonwhite = Image.eval(ImageChops.darker(ImageChops.darker(r, g), b), lambda v: 255 if v < 240 else 0)
    mask = ImageChops.darker(a, nonwhite).filter(ImageFilter.MaxFilter(3)).filter(ImageFilter.GaussianBlur(1))
    im.putalpha(mask)
    return im.crop(mask.point(lambda v: 255 if v > 24 else 0).getbbox())

# 1. Lay everything out on a transparent strip, bottoms on one floor line.
placed, x = [], 0
for i, s in enumerate(spec):
    im = cutout(f"{SRC}/{s['file']}")
    th = int(UNIT * s['h'])
    im = im.resize((max(1, round(im.width * th / im.height)), th), Image.LANCZOS)
    step = int(im.width * 0.92)
    for k in range(s.get('n', 1)):
        placed.append((im, x + k * step))
    x += im.width + step * (s.get('n', 1) - 1) + (int(UNIT * 0.07) if i < len(spec) - 1 else 0)
strip_w, strip_h = x, max(im.height for im, _ in placed) + 60
floor = strip_h - 40
strip = Image.new('RGBA', (strip_w, strip_h), (0, 0, 0, 0))

# soft elliptical floor shadows, then the products on top
shadow = Image.new('L', (strip_w, strip_h), 0)
d = ImageDraw.Draw(shadow)
for im, px in placed:
    d.ellipse((px + im.width * 0.08, floor - 14, px + im.width * 0.92, floor + 14), fill=90)
shadow = shadow.filter(ImageFilter.GaussianBlur(14))
strip = Image.composite(Image.new('RGBA', strip.size, (0, 0, 0, 255)), strip, shadow)
for im, px in placed:
    strip.alpha_composite(im, (px, floor - im.height))

# 2. Fit the strip into the canvas and centre it.
scale = min((W - 2 * PAD) / strip_w, (H - 2 * PAD) / strip_h)
strip = strip.resize((round(strip_w * scale), round(strip_h * scale)), Image.LANCZOS)
canvas = Image.new('RGBA', (W, H), (255, 255, 255, 255))
canvas.alpha_composite(strip, ((W - strip.width) // 2, (H - strip.height) // 2 + 20))
canvas.convert('RGB').save(OUT, quality=88)
print(OUT)
