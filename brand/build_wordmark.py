# -*- coding: utf-8 -*-
"""Compose a wordmark from true glyph outlines (extracted to glyphs/*.json).

Coordinates in the glyph JSON are already Y-DOWN with the baseline at y=0
(cap top is negative), so they drop straight into SVG. Cap height is
normalised to `CAP` units.
"""
import json
import os
import re

ROOT = os.path.dirname(os.path.abspath(__file__))
GJ = os.path.join(ROOT, "glyphs")

INK = "#0B0A10"
BRAND = "#FFD60A"
PAPER = "#FFFFFF"


def load(name):
    with open(os.path.join(GJ, name + ".json"), "r", encoding="utf-8") as f:
        return json.load(f)


def subpaths(glyph):
    return glyph["d"].split("|")


def path_bounds(d):
    """Rough bounds from the coordinate pairs in a path string."""
    xs, ys = [], []
    toks = d.replace("M", " ").replace("L", " ").replace("C", " ").replace("Q", " ").replace("Z", " ").split()
    nums = [float(t) for t in toks]
    for i in range(0, len(nums) - 1, 2):
        xs.append(nums[i]); ys.append(nums[i + 1])
    return min(xs), min(ys), max(xs), max(ys)


_TOKEN = re.compile(r"[A-Za-z]|-?\d+(?:\.\d+)?")


def scale_d(d, s):
    """Bake the cap scale into the coordinates.

    The outline has only M/L/C/Z commands (no arcs), so every number is a
    coordinate and scales uniformly. Emitting final units lets us round to
    2 decimals and drop the <transform scale> attribute, which otherwise
    carries 5+ decimals and breaks the precision check.
    """
    out = []
    for m in _TOKEN.finditer(d):
        t = m.group(0)
        if t.isalpha():
            out.append(t)              # command letter, keep as-is
            continue
        v = "%.2f" % (float(t) * s)
        out.append(v.rstrip("0").rstrip(".") or "0")
    return " ".join(out)


def cap_units(glyphs):
    """Cap height in font units: top of a flat-cap letter (H/F/E/T)."""
    top = 0.0
    for ch in "HEFT":
        if ch in glyphs:
            _, y0, _, _ = path_bounds(subpaths(glyphs[ch])[0])
            top = min(top, y0)
    return -top  # positive


def wordmark(glyphs, text, cap, tracking=0.0, space=0.34):
    cu = cap_units(glyphs)
    s = cap / cu
    x = 0.0
    parts = []
    for ch in text:
        if ch == " ":
            x += space * cap
            continue
        g = glyphs[ch]
        # one <path> per glyph so all contours share a fill-rule. TrueType stems and
        # bowls overlap by design: evenodd cancels the overlap into white seams,
        # nonzero unions it and still cuts counters via outer/inner winding.
        d = scale_d(" ".join(subpaths(g)), s)
        parts.append('<path transform="translate(%.3f,0)" d="%s"/>' % (x, d))
        x += (g["advance"] + tracking) * s
    width = x - tracking * s
    return "".join(parts), width


def svg(name, body, vb, fill=INK):
    w, h = vb.split()[2], vb.split()[3]
    # nonzero: honours TrueType winding (outer CW / counter CCW) -> real counters,
    # and unions overlapping subpaths instead of cancelling them.
    out = ('<svg xmlns="http://www.w3.org/2000/svg" viewBox="%s" width="%s" height="%s" fill="%s" '
           'fill-rule="nonzero">%s</svg>') % (vb, w, h, fill, body)
    with open(os.path.join(ROOT, name), "w", encoding="utf-8") as f:
        f.write(out)


FONTS = ["Sora-SemiBold", "Sora-Bold", "PlusJakartaSans-Bold", "PlusJakartaSans-ExtraBold"]

# --------------------------------------------------------------- public API
FINAL_FONT = "PlusJakartaSans-ExtraBold"   # squarer bowls, denser, holds up small
FINAL_TRACK = 36.0                          # font units = 4.9% of cap
SUB_TRACK = 86.0                            # font units = 11.8% of cap (subline)

_G = {}


def get_glyphs(font):
    if font not in _G:
        _G[font] = load(font)["glyphs"]
    return _G[font]


def wm(font, text, cap, tracking=0.0, fill=INK, space=0.34):
    """Group string + width. Baseline at y=0, cap top at y=-cap.

    fill-rule="nonzero" is set on the group: TrueType stems/bowls overlap by
    design, evenodd would cancel the overlap into white seams, and counters
    survive because outer/inner contours are wound opposite ways.
    """
    body, width = wordmark(get_glyphs(font), text, cap, tracking, space)
    return '<g fill="%s" fill-rule="nonzero">%s</g>' % (fill, body), width


# ------------------------------------------------------------------ previews
def previews():
    """Dev-only comparison sheets. tracking is in FONT UNITS (cap = 730),
    so a logo-grade 3-6% of cap = 22..44."""
    rows, y = [], 44.0
    maxw = 0.0
    for fn in ["Sora-Bold", "PlusJakartaSans-ExtraBold"]:
        gl = get_glyphs(fn)
        for tr in (24.0, 36.0, 48.0):
            wm1, w1 = wordmark(gl, "FASTTOPUP", 110.0, tracking=tr)
            wm2, w2 = wordmark(gl, "STORE", 30.0, tracking=tr * 2.4)
            maxw = max(maxw, w1, w2)
            rows.append('<rect x="%.1f" y="%.1f" width="%.1f" height="190" fill="%s"/>'
                        % (maxw + 60.0, y, w1 + 60.0, INK))
            rows.append('<g fill="%s" transform="translate(0,%.1f)">%s</g>' % (INK, y + 110.0, wm1))
            rows.append('<g fill="%s" transform="translate(0,%.1f)">%s</g>' % (INK, y + 148.0, wm2))
            rows.append('<g fill="#FFFFFF" transform="translate(%.1f,%.1f)">%s</g>'
                        % (maxw + 60.0, y + 110.0, wm1))
            rows.append('<g fill="#FFFFFF" transform="translate(%.1f,%.1f)">%s</g>'
                        % (maxw + 60.0, y + 148.0, wm2))
            rows.append('<text x="0" y="%.1f" font-family="monospace" font-size="19" '
                        'fill="%s">%s  t=%.0f</text>' % (y - 12.0, INK, fn, tr))
            y += 250.0
    svg("renders/_faceoff.svg", "".join(rows), "0 0 %.1f %.1f" % (maxw * 2 + 160.0, y))
    print("faceoff %.1f x %.1f  (maxw=%.1f)" % (maxw * 2 + 160.0, y, maxw))


if __name__ == "__main__":
    previews()
