#!/usr/bin/env python3
"""Compose an edge-to-edge browser-chrome mockup from a real screenshot.

Frame rule (per product request):
- The browser chrome IS the image border — no studio padding, no floating card.
- Soft inner shadow only under the chrome bar; corners lightly rounded.

Industry pattern for tall UIs (Screenhance / SaaS feature pages):
- One image per section / capability — never force a long page into one crop.
"""

from __future__ import annotations

import argparse
from pathlib import Path

from PIL import Image, ImageDraw, ImageFont


def rounded_mask(size: tuple[int, int], radius: int) -> Image.Image:
    mask = Image.new("L", size, 0)
    draw = ImageDraw.Draw(mask)
    draw.rounded_rectangle((0, 0, size[0] - 1, size[1] - 1), radius=radius, fill=255)
    return mask


def compose_browser_mockup(
    screenshot: Image.Image,
    *,
    title: str = "سنابل IQ",
    url: str = "sanabel.iq",
    chrome_h: int = 44,
    radius: int = 12,
) -> Image.Image:
    """Edge-to-edge: output size == chrome + screenshot (no outer padding)."""
    shot = screenshot.convert("RGBA")
    win_w, win_h = shot.size[0], shot.size[1] + chrome_h

    window = Image.new("RGBA", (win_w, win_h), (255, 255, 255, 255))
    wdraw = ImageDraw.Draw(window)

    # Chrome bar (top edge of the image)
    wdraw.rectangle((0, 0, win_w, chrome_h), fill=(248, 248, 249, 255))
    wdraw.line((0, chrome_h - 1, win_w, chrome_h - 1), fill=(228, 228, 231, 255))

    for i, color in enumerate([(255, 95, 86), (255, 189, 46), (39, 201, 63)]):
        cx = 22 + i * 20
        cy = chrome_h // 2
        wdraw.ellipse((cx - 6, cy - 6, cx + 6, cy + 6), fill=color)

    url_left, url_right = 100, win_w - 16
    url_top, url_bottom = 8, chrome_h - 8
    wdraw.rounded_rectangle(
        (url_left, url_top, url_right, url_bottom),
        radius=8,
        fill=(238, 238, 240, 255),
    )

    try:
        font_sm = ImageFont.truetype(
            "/usr/share/fonts/truetype/dejavu/DejaVuSans.ttf",
            13,
        )
    except OSError:
        font_sm = ImageFont.load_default()

    label = f"{title}  ·  {url}"
    bbox = wdraw.textbbox((0, 0), label, font=font_sm)
    th = bbox[3] - bbox[1]
    wdraw.text(
        (url_left + 12, (chrome_h - th) // 2),
        label,
        fill=(82, 82, 92, 255),
        font=font_sm,
    )

    window.paste(shot, (0, chrome_h))

    mask = rounded_mask((win_w, win_h), radius)
    framed = Image.new("RGBA", (win_w, win_h), (0, 0, 0, 0))
    framed.paste(window, (0, 0), mask)

    # Flatten onto white so PNG has no transparent margin
    out = Image.new("RGB", (win_w, win_h), (255, 255, 255))
    out.paste(framed, (0, 0), framed)
    return out


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument("input", type=Path)
    parser.add_argument("output", type=Path)
    parser.add_argument("--title", default="سنابل IQ")
    parser.add_argument("--url", default="sanabel.iq")
    args = parser.parse_args()

    shot = Image.open(args.input)
    mock = compose_browser_mockup(shot, title=args.title, url=args.url)
    args.output.parent.mkdir(parents=True, exist_ok=True)
    mock.save(args.output, format="PNG", optimize=True)
    print(f"saved {args.output} ({mock.size[0]}x{mock.size[1]})")


if __name__ == "__main__":
    main()
