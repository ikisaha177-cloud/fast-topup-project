# -*- coding: utf-8 -*-
"""Round 2 candidates for FastTopup Store. Black on white, pure geometry."""
import os

ROOT = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(ROOT, "concepts")
os.makedirs(OUT, exist_ok=True)
BLACK = "#111111"


def write(name, body, vb="0 0 256 256"):
    svg = ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" width="256" height="256" '
           'role="img" aria-labelledby="t"><title id="t">%s</title>%s</svg>') % (vb, name, body)
    open(os.path.join(OUT, name + ".svg"), "w", encoding="utf-8").write(svg)


def P(pts, fill=BLACK, extra=""):
    d = "M " + " L ".join("%.2f %.2f" % (x, y) for x, y in pts) + " Z"
    return '<path fill="%s" %sd="%s"/>' % (fill, extra, d)


def evenodd(subpaths, fill=BLACK):
    d = " ".join("M " + " L ".join("%.2f %.2f" % (x, y) for x, y in sp) + " Z" for sp in subpaths)
    return '<path fill="%s" fill-rule="evenodd" d="%s"/>' % (fill, d)


def shear(pts, k, y0=128.0):
    return [(x - (y - y0) * k, y) for x, y in pts]


def chevron(cx, y_base, half_w, height, thick):
    """^ band of constant vertical thickness."""
    return [(cx - half_w, y_base), (cx, y_base - height), (cx + half_w, y_base),
            (cx + half_w, y_base + thick), (cx, y_base - height + thick),
            (cx - half_w, y_base + thick)]


def up_arrow(cx, cy, w, h, sw):
    """Up-pointing arrow, 45 deg head."""
    hh = w / 2.0
    return [(cx, cy - h / 2.0), (cx + w / 2.0, cy - h / 2.0 + hh),
            (cx + sw / 2.0, cy - h / 2.0 + hh), (cx + sw / 2.0, cy + h / 2.0),
            (cx - sw / 2.0, cy + h / 2.0), (cx - sw / 2.0, cy - h / 2.0 + hh),
            (cx - w / 2.0, cy - h / 2.0 + hh)]


def flat_hex(cx, cy, r):
    import math
    return [(cx + r * math.cos(math.radians(a)), cy + r * math.sin(math.radians(a)))
            for a in (0, 60, 120, 180, 240, 300)]


# ---- B2: boost bars, tallest carries a clean wide arrowhead ----
bars = [
    [(48, 168), (88, 168), (88, 208), (48, 208)],
    [(104, 124), (144, 124), (144, 208), (104, 208)],
]
head = [(168, 96), (196, 56), (224, 96)]          # wide arrowhead
stem = [(172, 208), (220, 208), (220, 96), (172, 96)]
write("concept-b2-boost", "".join(P(shear(p, 0.10)) for p in bars + [head, stem]))

# ---- D: double up-chevron (fast + top-up) ----
lead = chevron(128, 120, 72, 60, 28)
trail = chevron(128, 214, 72, 60, 28)
write("concept-d-double-chevron", "".join(P(shear(p, 0.12)) for p in (lead, trail)))

# ---- E: futuristic tag with an up-arrow knocked out ----
tag = [(36, 128), (100, 60), (222, 60), (222, 196), (100, 196)]
arrow = up_arrow(150, 128, 84, 108, 34)
write("concept-e-tag-up", evenodd([tag, arrow]))

# ---- F: flat-top hex (tech plate) with an up-chevron knocked out ----
hexa = flat_hex(128, 128, 106)
write("concept-f-hex-up", evenodd([hexa, chevron(128, 168, 62, 54, 26)]))

# ---- G: flat-top hex with a plus knocked out ----
plus = [(122, 62), (154, 62), (154, 102), (194, 102), (194, 134),
        (154, 134), (154, 174), (122, 174), (122, 134), (82, 134),
        (82, 102), (122, 102)]
write("concept-g-hex-plus", evenodd([hexa, plus]))

print("round 2 written")
for f in sorted(os.listdir(OUT)):
    print("  ", f)
