# -*- coding: utf-8 -*-
"""
Final three concepts for FastTop-up Store (Phase 4/5: build in SVG, black first).

Three different mark types:
  A  letterform    - "Fast-F": geometric F whose arms end in 45 deg cuts (speed)
  B  abstract      - "Boost Bars": three rising bars, tallest peaked into a top-up
  C  containment   - "Hex Top-Up": hex plate with an up-arrow knocked out (token)

All angles are clean (0/45/60/90 deg). No shear, no <text>, no filters.
Canvas 256x256 for symbols.
"""
import os, math

ROOT = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(ROOT, "concepts")
os.makedirs(OUT, exist_ok=True)
BLACK = "#111111"


def write(name, body, vb="0 0 256 256"):
    svg = ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" width="256" height="256" '
           'role="img" aria-labelledby="t"><title id="t">%s</title>%s</svg>') % (vb, name, body)
    with open(os.path.join(OUT, name + ".svg"), "w", encoding="utf-8") as f:
        f.write(svg)


def P(pts, fill=BLACK):
    d = "M " + " L ".join("%.2f %.2f" % (x, y) for x, y in pts) + " Z"
    return '<path fill="%s" d="%s"/>' % (fill, d)


def evenodd(subpaths, fill=BLACK):
    d = " ".join("M " + " L ".join("%.2f %.2f" % (x, y) for x, y in sp) + " Z" for sp in subpaths)
    return '<path fill="%s" fill-rule="evenodd" d="%s"/>' % (fill, d)


def up_arrow(cx, cy, w, h, sw):
    """Up arrow with a 45 deg head; w = head width, sw = stem width."""
    hh = w / 2.0
    return [(cx, cy - h / 2.0), (cx + w / 2.0, cy - h / 2.0 + hh),
            (cx + sw / 2.0, cy - h / 2.0 + hh), (cx + sw / 2.0, cy + h / 2.0),
            (cx - sw / 2.0, cy + h / 2.0), (cx - sw / 2.0, cy - h / 2.0 + hh),
            (cx - w / 2.0, cy - h / 2.0 + hh)]


def flat_hex(cx, cy, r):
    """Pointy-left/right hexagon: vertices every 60 deg, flat top and bottom."""
    return [(cx + r * math.cos(math.radians(a)), cy + r * math.sin(math.radians(a)))
            for a in (0, 60, 120, 180, 240, 300)]


# ---------------------------------------------------------------- A: Fast-F
# 40-unit stem, 40-unit arms, 40-unit counters. Arm tips cut at 45 deg so the
# letter reads as forward motion. Reads as a clean "F" at 16 px.
F_FAST = [
    (52, 32), (204, 32), (160, 76), (96, 76), (96, 120),
    (172, 120), (128, 164), (96, 164), (96, 224), (52, 224),
]
write("final-a-fast-f", P(F_FAST))

# ---------------------------------------------------------- B: Boost Bars
# Three bars on a common baseline (y=212); the tallest peaks into an up arrow.
BOOST_BARS = [
    [(44, 164), (84, 164), (84, 212), (44, 212)],
    [(108, 116), (148, 116), (148, 212), (108, 212)],
    [(172, 212), (212, 212), (212, 88), (192, 88 - 20 * 1.7320508075688772), (172, 88)],
]
write("final-b-boost-bars", "".join(P(b) for b in BOOST_BARS))

# ---------------------------------------------------------- C: Hex Top-Up
# Hex plate (token) with a top-up arrow knocked out as real negative space.
write("final-c-hex-topup",
      evenodd([flat_hex(128, 128, 104), up_arrow(128, 128, 84, 116, 34)]))

print("final concepts written ->", OUT)
for f in sorted(os.listdir(OUT)):
    if f.startswith("final-"):
        print("  ", f)
