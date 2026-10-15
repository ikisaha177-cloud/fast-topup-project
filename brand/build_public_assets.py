# -*- coding: utf-8 -*-
"""Rebuild the public brand assets that still carried the old identity:

  public/images/logo-horizontal.svg   (was: shield + bolt + gradient)
  brand/og-cover.svg                  (source for public/images/og-cover.png)

Both are composed from the master kit so the wordmark/symbol stay in sync.
The OG cover is rasterised separately with Chrome (brand/render.ps1).
"""
import os
import re

ROOT = os.path.dirname(os.path.abspath(__file__))
PROJECT = os.path.dirname(ROOT)
KIT = os.path.join(ROOT, "kit")
PUBLIC = os.path.join(PROJECT, "public", "images")

INK = "#0B0A10"
BRAND = "#FFD60A"
PAPER = "#FFFFFF"


def read(p):
    with open(p, "r", encoding="utf-8") as f:
        return f.read()


def write(p, s):
    with open(p, "w", encoding="utf-8") as f:
        f.write(s)


def inner(svg):
    """Body markup of an SVG, minus the outer tag and any <title>."""
    i = svg.index(">") + 1
    j = svg.rindex("</svg>")
    body = svg[i:j]
    return re.sub(r"<title[^>]*>.*?</title>", "", body, flags=re.S)


def vb(svg):
    m = re.search(r'viewBox="([\d.\- ]+)"', svg)
    x, y, w, h = [float(v) for v in m.group(1).split()]
    return w, h


# ------------------------------------------------------------------ sources
lockup_dark = read(os.path.join(KIT, "fasttopup-horizontal-dark.svg"))
lockup_ink = read(os.path.join(KIT, "fasttopup-horizontal.svg"))
symbol_two_tone = read(os.path.join(KIT, "fasttopup-symbol-two-tone.svg"))
symbol_small = read(os.path.join(KIT, "fasttopup-symbol-small.svg"))

LW, LH = vb(lockup_dark)          # 748 x 179
SW, SH = vb(symbol_two_tone)      # 256 x 256

# ------------------------------------------------ public/images/logo-horizontal.svg
# Dark rounded pill, brand-yellow lockup centred inside.
PILL_W, PILL_H = 430.0, 104.0
pad = 16.0
s = min((PILL_W - 2 * pad) / LW, (PILL_H - 2 * pad) / LH)
lw, lh = LW * s, LH * s
tx, ty = (PILL_W - lw) / 2.0, (PILL_H - lh) / 2.0
logo_horizontal = (
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 430 104" width="430" height="104" '
    'role="img" aria-label="FastTopup Store \u2014 Digital Top-Up &amp; Marketplace">'
    '<title>FastTopup Store \u2014 Digital Top-Up &amp; Marketplace</title>'
    '<rect x="0.5" y="0.5" width="429" height="103" rx="22" fill="%s" stroke="rgba(255,214,10,0.28)"/>'
    '<g transform="translate(%.2f,%.2f) scale(%.4f)">%s</g>'
    '</svg>' % (INK, tx, ty, s, inner(lockup_dark))
)
write(os.path.join(PUBLIC, "logo-horizontal.svg"), logo_horizontal)
print("wrote public/images/logo-horizontal.svg")

# ------------------------------------------------------------ brand/og-cover.svg
W, H = 1200.0, 630.0
ls = 0.42                                   # lockup scale for the header
lw2, lh2 = LW * ls, LH * ls
ss = 1.15                                   # big symbol scale on the right
sw2, sh2 = SW * ss, SH * ss

FONT = "'Sora','Plus Jakarta Sans',system-ui,-apple-system,'Segoe UI',Roboto,Arial,sans-serif"

# Embed the real brand fonts so the rasterised cover does not fall back.
STYLE = (
    "<style>"
    "@font-face{font-family:'Sora';font-weight:700;src:url('fonts/Sora-Bold.ttf');}"
    "@font-face{font-family:'Sora';font-weight:600;src:url('fonts/Sora-SemiBold.ttf');}"
    "</style>"
)


def esc(t):
    return t.replace("&", "&amp;").replace("<", "&lt;").replace(">", "&gt;")


badges = ["QRIS", "DANA", "GoPay", "OVO", "Transfer Bank"]
bx = 66.0
badge_svg = []
for b in badges:
    w = 26 + len(b) * 8.6
    badge_svg.append(
        '<rect x="%.1f" y="470" width="%.1f" height="42" rx="21" fill="rgba(255,255,255,0.06)" '
        'stroke="rgba(255,255,255,0.16)"/>'
        '<text x="%.1f" y="497" font-family="%s" font-size="17" font-weight="600" fill="#E8E8EA">%s</text>'
        % (bx, w, bx + w / 2.0, FONT, esc(b))
    )
    bx += w + 14

og = (
    '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 1200 630" width="1200" height="630" '
    'font-family="%s">'
    '%s'
    '<defs>'
    '<radialGradient id="glow" cx="0.78" cy="0.42" r="0.55">'
    '<stop offset="0" stop-color="#FFD60A" stop-opacity="0.20"/>'
    '<stop offset="1" stop-color="#FFD60A" stop-opacity="0"/>'
    '</radialGradient>'
    '<pattern id="grid" width="48" height="48" patternUnits="userSpaceOnUse">'
    '<path d="M 48 0 L 0 0 0 48" fill="none" stroke="rgba(255,255,255,0.035)" stroke-width="1"/>'
    '</pattern>'
    '</defs>'
    '<rect width="1200" height="630" fill="%s"/>'
    '<rect width="1200" height="630" fill="url(#grid)"/>'
    '<rect width="1200" height="630" fill="url(#glow)"/>'
    # header lockup (brand yellow on ink)
    '<g transform="translate(60,58) scale(%.4f)">%s</g>'
    # headline
    '<text x="66" y="288" font-size="64" font-weight="700" fill="#FFFFFF">Top Up Game &amp; Belanja</text>'
    '<text x="66" y="362" font-size="64" font-weight="700" fill="#FFD60A">Item Tanpa Nunggu</text>'
    # subline
    '<text x="66" y="420" font-size="22" font-weight="600" fill="#A9A9B2">Pembayaran QRIS, e-wallet, dan transfer bank \u2014 otomatis 24 jam.</text>'
    # badges
    '%s'
    # trust row
    '<text x="66" y="566" font-size="20" font-weight="600" fill="#8F8F98">Aman \u2022 Cepat \u2022 Transparan \u2022 Terlacak</text>'
    # big symbol, two-tone
    '<g transform="translate(%.1f,%.1f) scale(%.3f)">%s</g>'
    '</svg>'
) % (FONT, STYLE, INK, ls, inner(lockup_dark), "".join(badge_svg),
     1200 - sw2 - 52, (630 - sh2) / 2.0, ss, inner(symbol_two_tone))

write(os.path.join(ROOT, "og-cover.svg"), og)
print("wrote brand/og-cover.svg")
