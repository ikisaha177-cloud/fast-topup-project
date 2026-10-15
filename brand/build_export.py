# -*- coding: utf-8 -*-
"""FastTopup Store - export lockup builder.

Derives the three wordmark-bearing export lockups (brand/export/) from the
master kit horizontal lockup (brand/kit/fasttopup-horizontal.svg): same
geometry, ink fill swapped for the target colour, plus a plain <title>.

Symbol-only exports carry no wordmark, so they are left untouched.
"""
import os

ROOT = os.path.dirname(os.path.abspath(__file__))
KIT = os.path.join(ROOT, "kit")
EXPORT = os.path.join(ROOT, "export")
os.makedirs(EXPORT, exist_ok=True)

INK = "#0B0A10"
BRAND = "#FFD60A"
WHITE = "#FFFFFF"
BLACK = "#000000"

TITLE = "FastTopup Store"


def read(path):
    with open(path, "r", encoding="utf-8") as f:
        return f.read()


def write(path, text):
    with open(path, "w", encoding="utf-8") as f:
        f.write(text)


def colorize(svg, src_fill, dst_fill):
    out = svg.replace(src_fill, dst_fill)
    out = out.replace("<svg ", '<svg fill="%s" ' % dst_fill, 1)
    i = out.index(">") + 1
    return out[:i] + "<title>%s</title>" % TITLE + out[i:]


def main():
    horizontal = read(os.path.join(KIT, "fasttopup-horizontal.svg"))
    for name, fill in [
        ("fasttopup-horizontal-black", BLACK),
        ("fasttopup-horizontal-mono-ffd60a", BRAND),
        ("fasttopup-horizontal-white", WHITE),
    ]:
        write(os.path.join(EXPORT, name + ".svg"), colorize(horizontal, INK, fill))
        print("wrote export/%s.svg" % name)


if __name__ == "__main__":
    main()
