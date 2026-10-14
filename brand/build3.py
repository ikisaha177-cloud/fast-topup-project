# -*- coding: utf-8 -*-
"""Round 3 - cleaner angles (no shear), stronger ideas. Black on white."""
import os, math

ROOT = os.path.dirname(os.path.abspath(__file__))
OUT = os.path.join(ROOT, "concepts")
os.makedirs(OUT, exist_ok=True)
BLACK = "#111111"


def write(name, body, vb="0 0 256 256"):
    svg = ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" width="256" height="256" '
           'role="img" aria-labelledby="t"><title id="t">%s</title>%s</svg>') % (vb, name, body)
    open(os.path.join(OUT, name + ".svg"), "w", encoding="utf-8").write(svg)


def P(pts, fill=BLACK):
    d = "M " + " L ".join("%.2f %.2f" % (x, y) for x, y in pts) + " Z"
    return '<path fill="%s" d="%s"/>' % (fill, d)


def evenodd(subpaths, fill=BLACK):
    d = " ".join("M " + " L ".join("%.2f %.2f" % (x, y) for x, y in sp) + " Z" for sp in subpaths)
    return '<path fill="%s" fill-rule="evenodd" d="%s"/>' % (fill, d)


def chevron(cx, y_base, half_w, height, thick):
    return [(cx - half_w, y_base), (cx, y_base - height), (cx + half_w, y_base),
            (cx + half_w, y_base + thick), (cx, y_base - height + thick),
            (cx - half_w, y_base + thick)]


def up_arrow(cx, cy, w, h, sw):
    hh = w / 2.0
    return [(cx, cy - h / 2.0), (cx + w / 2.0, cy - h / 2.0 + hh),
            (cx + sw / 2.0, cy - h / 2.0 + hh), (cx + sw / 2.0, cy + h / 2.0),
            (cx - sw / 2.0, cy + h / 2.0), (cx - sw / 2.0, cy - h / 2.0 + hh),
            (cx - w / 2.0, cy - h / 2.0 + hh)]


def flat_hex(cx, cy, r):
    return [(cx + r * math.cos(math.radians(a)), cy + r * math.sin(math.radians(a)))
            for a in (0, 60, 120, 180, 240, 300)]


# ---- H: hex plate + up arrow (tech + top-up), all clean angles ----
write("concept-h-hex-arrow", evenodd([flat_hex(128, 128, 104), up_arrow(128, 128, 84, 116, 34)]))

# ---- I: tapered double chevron (fast-forward that ascends) ----
top = chevron(128, 132, 74, 62, 26)
bot = chevron(128, 216, 56, 48, 22)
write("concept-i-chevron-ascent", P(top) + P(bot))

# ---- J: token/coin disc + up arrow knocked out (in-game currency) ----
write("concept-j-token-arrow", evenodd([flat_hex(128, 128, 104), up_arrow(128, 128, 84, 116, 34)]) if False else
      evenodd([[ (128 + 106 * math.cos(math.radians(a)), 128 + 106 * math.sin(math.radians(a))) for a in range(0, 360, 45) ], up_arrow(128, 128, 84, 116, 34)]))

# ---- K: bold up-arrow with two speed cuts (fast + top-up) ----
arrow = up_arrow(140, 128, 88, 128, 38)
cut1 = [(72, 108), (104, 108), (104, 128), (72, 128)]
cut2 = [(56, 148), (96, 148), (96, 168), (56, 168)]
write("concept-k-arrow-speed", evenodd([arrow, cut1, cut2]))

# ---- L: refined sporty F (exact 45 deg terminals, upright) ----
F = [(64, 48), (196, 48), (156, 88), (104, 88), (104, 120),
     (164, 120), (124, 160), (104, 160), (104, 208), (64, 208)]
write("concept-l-f-sport", P(F))

# ---- M: clean boost bars + arrow (no shear) ----
bars = [
    [(44, 164), (84, 164), (84, 208), (44, 208)],
    [(108, 118), (148, 118), (148, 208), (108, 208)],
    [(172, 208), (212, 208), (212, 92), (192, 52), (172, 92)],
]
write("concept-m-boost-clean", "".join(P(b) for b in bars))

print("round 3 written")
for f in sorted(os.listdir(OUT)):
    print("  ", f)
