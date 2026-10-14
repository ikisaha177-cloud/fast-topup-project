# -*- coding: utf-8 -*-
"""
Logo concept generator - FastTop-up Store.
Phase 4 (build in SVG, black on white). Pure geometry, no <text>, no filters.

Canvas 256x256, symbol-only (lockups come later, only after the direction is picked).
"""
import os

ROOT = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(ROOT, "concepts")
os.makedirs(OUT, exist_ok=True)

BLACK = "#111111"


def write(name, body, vb="0 0 256 256"):
    svg = (
        '<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" width="256" height="256" '
        'role="img" aria-labelledby="t"><title id="t">%s</title>%s</svg>'
    ) % (vb, name, body)
    with open(os.path.join(OUT, name + ".svg"), "w", encoding="utf-8") as f:
        f.write(svg)


def poly(pts, fill=BLACK, extra=""):
    d = "M " + " L ".join("%.2f %.2f" % (x, y) for x, y in pts) + " Z"
    return '<path fill="%s" %sd="%s"/>' % (fill, extra, d)


def path(d, fill=BLACK, extra=""):
    return '<path fill="%s" %sd="%s"/>' % (fill, extra, d)


def shear(pts, k, y0=128.0):
    """Lean forward: x moves right toward the top (negative k leans the other way)."""
    return [(x - (y - y0) * k, y) for x, y in pts]


# ----------------------------------------------------------------------
# CONCEPT A - letterform "Fast-F": geometric F, arms cut at 45 deg so the
# letter itself reads as forward motion.
# ----------------------------------------------------------------------
F_UPRIGHT = [
    (64, 48), (192, 48), (152, 88), (104, 88), (104, 120),
    (160, 120), (120, 160), (104, 160), (104, 208), (64, 208),
]
write("concept-a-fast-f", poly(F_UPRIGHT))
write("concept-a2-fast-f-lean", poly(shear(F_UPRIGHT, 0.18)))

# ----------------------------------------------------------------------
# CONCEPT B - abstract "boost meter": three rising bars, the tallest one
# peaked into a top-up arrow. Leans forward for speed.
# ----------------------------------------------------------------------
BARS = [
    [(52, 168), (92, 168), (92, 208), (52, 208)],
    [(108, 124), (148, 124), (148, 208), (108, 208)],
    [(164, 208), (204, 208), (204, 96), (184, 60), (164, 96)],
]
write("concept-b-boost-meter", "".join(poly(shear(b, 0.10)) for b in BARS))

# ----------------------------------------------------------------------
# CONCEPT C - combination "top-up tag": futuristic price tag with a bold
# plus cut out in negative space (store + add value).
# ----------------------------------------------------------------------
TAG = [(40, 128), (96, 64), (216, 64), (216, 192), (96, 192)]
PLUS = [
    (136, 76), (168, 76), (168, 112), (204, 112), (204, 144),
    (168, 144), (168, 180), (136, 180), (136, 144), (100, 144),
    (100, 112), (136, 112),
]
d = "M " + " L ".join("%.2f %.2f" % p for p in TAG) + " Z " \
    + "M " + " L ".join("%.2f %.2f" % p for p in PLUS) + " Z"
write("concept-c-tag-plus", '<path fill="%s" fill-rule="evenodd" d="%s"/>' % (BLACK, d))

print("written ->", OUT)
for f in sorted(os.listdir(OUT)):
    print("  ", f)
