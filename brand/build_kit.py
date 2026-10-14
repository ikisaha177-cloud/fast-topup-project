# -*- coding: utf-8 -*-
"""
FastTop-up Store - brand kit builder (Phase 7, direction B "Boost Bars").

Everything is pure vector outline (no <text>, no filters, no rasters):
  symbol, two-tone hero, small-size cut, wordmark extracted from a real OFL
  typeface, horizontal + stacked lockups, wordmark-only, on-dark colourway.

Geometry language: clean angles only (0/45/60/90 deg), 20-unit strokes,
3-decimal precision. Wordmark outlines come from PlusJakartaSans-ExtraBold
(OFL) via extract_glyphs.ps1 -> glyphs/*.json -> build_wordmark.wm().
"""
import math
import os
import sys

ROOT = os.path.dirname(os.path.abspath(__file__))
KIT = os.path.join(ROOT, "kit")
os.makedirs(KIT, exist_ok=True)
sys.path.insert(0, ROOT)

INK = "#0B0A10"     # obsidian (matches the web product --bg)
BRAND = "#FFD60A"   # neon yellow (matches the web product --brand)
PAPER = "#FFFFFF"


def write(name, body, vb, sub=""):
    # integer canvas: fractional viewBoxes don't snap to a grid
    _, _, w, h = vb.split()
    vb = "0 0 %d %d" % (math.ceil(float(w)), math.ceil(float(h)))
    svg = ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" width="%s" height="%s" '
           'role="img" aria-labelledby="t"><title id="t">FastTop-up Store %s</title>%s</svg>') % (
        vb, vb.split()[2], vb.split()[3], sub, body)
    with open(os.path.join(KIT, name + ".svg"), "w", encoding="utf-8") as f:
        f.write(svg)


def poly(pts, fill):
    d = "M " + " L ".join("%.3f %.3f" % (x, y) for x, y in pts) + " Z"
    return '<path fill="%s" d="%s"/>' % (fill, d)


def finalize(name, content, w, h, m, sub):
    body = '<g transform="translate(%.3f,%.3f)">%s</g>' % (m, m, content)
    write(name, body, "0 0 %.3f %.3f" % (w + 2 * m, h + 2 * m), sub)


# ----------------------------------------------------------------- symbol
# Bars on a common baseline; the tallest peaks into an up arrow with a 60 deg head.
ARROW_TIP_Y = 88.0 - 20.0 * 1.7320508075688772  # 60 deg from horizontal -> 53.359
BARS = [
    [(44, 164), (84, 164), (84, 212), (44, 212)],
    [(108, 116), (148, 116), (148, 212), (108, 212)],
    [(172, 212), (212, 212), (212, 88), (192, ARROW_TIP_Y), (172, 88)],
]
SYM_X0, SYM_Y0 = 44.0, ARROW_TIP_Y
SYM_W, SYM_H = 212.0 - 44.0, 212.0 - ARROW_TIP_Y   # 168 x 158.641


def symbol_paths(fill):
    return "".join(poly(b, fill) for b in BARS)


def symbol_group(fill, scale, tx, ty):
    return ('<g transform="translate(%.3f,%.3f) scale(%.3f)">'
            '<g transform="translate(%.3f,%.3f)">%s</g></g>') % (
        tx, ty, scale, -SYM_X0, -SYM_Y0, symbol_paths(fill))


# small-size cut: two bars, wider gaps so it survives 16 px
SMALL_TIP = 96.0 - 26.0 * 1.7320508075688772     # 60 deg head
SMALL = [
    [(52, 152), (104, 152), (104, 208), (52, 208)],
    [(152, 208), (204, 208), (204, 96), (178, SMALL_TIP), (152, 96)],
]
write("fasttopup-symbol", symbol_paths(INK), "0 0 256 256", "symbol")
write("fasttopup-symbol-small", "".join(poly(b, INK) for b in SMALL), "0 0 256 256", "small-size symbol")
write("fasttopup-symbol-two-tone",
      poly(BARS[0], PAPER) + poly(BARS[1], PAPER) + poly(BARS[2], BRAND),
      "0 0 256 256", "symbol, two-tone (for dark surfaces)")

# ------------------------------------------------------- wordmark (real outlines)
# One <path> per glyph, fill-rule="nonzero" (TrueType contours overlap by
# design; evenodd cancels the overlap into white seams). tracking is in font
# units at em 1000 / cap 730: 36 = 4.9% of cap, 86 = 11.8% of cap (subline).
from build_wordmark import wm, FINAL_FONT, FINAL_TRACK, SUB_TRACK  # noqa: E402

FONT = FINAL_FONT


def wordmark(text, cap_h, fill, tracking=FINAL_TRACK):
    """Return (svg group string, width), baseline on y=0."""
    return wm(FONT, text, cap_h, tracking, fill)


# ------------------------------------------------------- lockups
SYM_SCALE = round(150.0 / SYM_H, 3)          # 0.946
SYM_W_S = SYM_W * SYM_SCALE                  # 158.928
SYM_H_S = SYM_H * SYM_SCALE                  # 150.075


def horizontal(fill, sub_fill):
    cap1, cap2 = 62.0, 26.0
    w1, wm1 = wordmark("FASTTOP-UP", cap1, fill, FINAL_TRACK)
    w2, wm2 = wordmark("STORE", cap2, sub_fill, SUB_TRACK)
    bx = SYM_W_S + 40.0
    block_h = cap1 + 22.0 + cap2
    H = max(SYM_H_S, block_h)
    top = (H - block_h) / 2.0
    content = symbol_group(fill, SYM_SCALE, 0.0, (H - SYM_H_S) / 2.0)
    content += '<g transform="translate(%.3f,%.3f)">%s</g>' % (bx, top + cap1, w1)
    content += '<g transform="translate(%.3f,%.3f)">%s</g>' % (bx, top + cap1 + 22.0 + cap2, w2)
    return content, bx + max(wm1, wm2), H


def stacked(fill, sub_fill):
    cap1, cap2 = 52.0, 24.0
    w1, wm1 = wordmark("FASTTOP-UP", cap1, fill, FINAL_TRACK)
    w2, wm2 = wordmark("STORE", cap2, sub_fill, SUB_TRACK)
    W = max(SYM_W_S, wm1, wm2)
    content = symbol_group(fill, SYM_SCALE, (W - SYM_W_S) / 2.0, 0.0)
    top1 = SYM_H_S + 58.0
    content += '<g transform="translate(%.3f,%.3f)">%s</g>' % ((W - wm1) / 2.0, top1 + cap1, w1)
    top2 = top1 + cap1 + 30.0
    content += '<g transform="translate(%.3f,%.3f)">%s</g>' % ((W - wm2) / 2.0, top2 + cap2, w2)
    return content, W, top2 + cap2


ch, wh, hh = horizontal(INK, INK)
finalize("fasttopup-horizontal", ch, wh, hh, 14.0, "horizontal lockup")

cs, ws, hs = stacked(INK, INK)
finalize("fasttopup-stacked", cs, ws, hs, 16.0, "stacked lockup")

cg, wg, hg = horizontal(BRAND, BRAND)
finalize("fasttopup-horizontal-dark", cg, wg, hg, 14.0, "horizontal lockup (on dark)")

gw, ww = wordmark("FASTTOP-UP", 100.0, INK, FINAL_TRACK)
finalize("fasttopup-wordmark", '<g transform="translate(0,100)">%s</g>' % gw, ww, 100.0, 10.0, "wordmark")

print("kit written ->", KIT)
for f in sorted(os.listdir(KIT)):
    print("  ", f)
