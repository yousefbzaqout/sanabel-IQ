#!/usr/bin/env python3
"""Capture real product screenshots at natural UI size.

Default rule (product preference):
- Image size = interface size. Tall page → tall image (full_page).
- Edge-to-edge browser chrome (frame = image border).
- Optional --sections only when you explicitly want one crop per section.
"""

from __future__ import annotations

import argparse
import json
import subprocess
import sys
from pathlib import Path

from playwright.sync_api import sync_playwright

ROOT = Path(__file__).resolve().parents[1]
DEFAULT_CHROME = Path.home() / ".cache/ms-playwright/chromium-1243/chrome-linux64/chrome"
COMPOSE = ROOT / "scripts/compose_screely_mockup.py"
OUT_DIR = ROOT / "docs/marketing/feature-mockups"

# Landing page sections discovered from welcome.blade.php
LANDING_SECTIONS: list[dict[str, str]] = [
    {
        "slug": "01a-landing-hero",
        "label": "Hero",
        # First <section> only (parent .relative is unclosed in Blade and wraps all)
        "selector": "main section",
        "mode": "hero",
    },
    {
        "slug": "01b-landing-features",
        "label": "Features",
        "selector": "#features",
        "mode": "element",
    },
    {
        "slug": "01c-landing-journey",
        "label": "Learning Journey",
        "selector": "#learning-journey",
        "mode": "element",
    },
    {
        "slug": "01d-landing-schools",
        "label": "Schools Solutions",
        "selector": "#schools",
        "mode": "element",
    },
    {
        "slug": "01e-landing-roles",
        "label": "Roles",
        "selector": "#roles",
        "mode": "element",
    },
    {
        "slug": "01f-landing-pricing",
        "label": "Pricing",
        "selector": "#pricing",
        "mode": "element",
    },
    {
        "slug": "01g-landing-demo",
        "label": "Demo Request",
        "selector": "#demo-request-section",
        "mode": "element",
    },
]


def compose(raw: Path, output: Path, *, title: str, url_label: str) -> None:
    subprocess.check_call(
        [
            sys.executable,
            str(COMPOSE),
            str(raw),
            str(output),
            "--title",
            title,
            "--url",
            url_label,
        ]
    )


def capture_landing_sections(
    *,
    base_url: str = "http://localhost/",
    chrome: Path = DEFAULT_CHROME,
    width: int = 1440,
    height: int = 900,
    dpr: float = 2.0,
    title: str = "سنابل IQ",
    url_label: str = "sanabel.iq",
) -> list[Path]:
    if not chrome.is_file():
        raise SystemExit(f"Chromium not found at {chrome}")

    OUT_DIR.mkdir(parents=True, exist_ok=True)
    outputs: list[Path] = []

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True, executable_path=str(chrome))
        context = browser.new_context(
            viewport={"width": width, "height": height},
            device_scale_factor=dpr,
            locale="ar",
        )
        page = context.new_page()
        page.goto(base_url, wait_until="networkidle", timeout=90_000)
        page.evaluate(
            """() => {
                document.querySelectorAll(
                    '[data-cookie], .cookie-banner, #cookie, .intercom-lightweight-app'
                ).forEach((el) => el.remove());
            }"""
        )
        page.wait_for_timeout(600)

        for spec in LANDING_SECTIONS:
            slug = spec["slug"]
            raw = OUT_DIR / f"_raw-{slug}.png"
            out = OUT_DIR / f"{slug}.png"

            if slug == "01a-landing-hero":
                # Scroll top; temporarily grow viewport so nav + full first
                # <section> (incl. split visual cards) fit without mid-crop.
                page.evaluate("() => window.scrollTo(0, 0)")
                page.wait_for_timeout(400)
                box = page.locator("main section").first.bounding_box()
                if not box:
                    raise RuntimeError("hero section not found")
                clip_h = int(box["y"] + box["height"] + 16)
                page.set_viewport_size({"width": width, "height": max(height, clip_h)})
                page.wait_for_timeout(300)
                page.evaluate("() => window.scrollTo(0, 0)")
                page.wait_for_timeout(200)
                page.screenshot(
                    path=str(raw),
                    type="png",
                    clip={
                        "x": 0,
                        "y": 0,
                        "width": float(width),
                        "height": float(clip_h),
                    },
                )
                page.set_viewport_size({"width": width, "height": height})
            else:
                loc = page.locator(spec["selector"]).first
                loc.scroll_into_view_if_needed()
                page.wait_for_timeout(500)
                loc.screenshot(path=str(raw), type="png")

            compose(raw, out, title=title, url_label=url_label)
            outputs.append(out)
            print(f"OK {slug} → {out.name}")

        # Do NOT overwrite 01-landing-page.png here — that file is the
        # full-page natural-height capture (see --landing-full / default).
        browser.close()

    manifest = OUT_DIR / "_landing-sections.json"
    manifest.write_text(
        json.dumps(
            [{"slug": s["slug"], "label": s["label"]} for s in LANDING_SECTIONS],
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )
    return outputs


def capture_landing_full(
    *,
    base_url: str = "http://localhost/",
    chrome: Path = DEFAULT_CHROME,
    width: int = 1440,
    height: int = 900,
    dpr: float = 2.0,
    title: str = "سنابل IQ",
    url_label: str = "sanabel.iq",
) -> Path:
    """One image = entire landing UI at its natural scroll height."""
    out = OUT_DIR / "01-landing-page.png"
    return capture_url(
        base_url,
        out,
        chrome=chrome,
        full_page=True,
        title=title,
        url_label=url_label,
        width=width,
        height=height,
        dpr=dpr,
    )


def capture_url(
    url: str,
    output: Path,
    *,
    chrome: Path = DEFAULT_CHROME,
    full_page: bool = True,
    selector: str | None = None,
    title: str = "سنابل IQ",
    url_label: str = "sanabel.iq",
    width: int = 1440,
    height: int = 900,
    dpr: float = 2.0,
) -> Path:
    if not chrome.is_file():
        raise SystemExit(f"Chromium not found at {chrome}")

    raw = output.with_name(f"_raw-{output.stem}.png")
    output.parent.mkdir(parents=True, exist_ok=True)

    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True, executable_path=str(chrome))
        context = browser.new_context(
            viewport={"width": width, "height": height},
            device_scale_factor=dpr,
            locale="ar",
        )
        page = context.new_page()
        page.goto(url, wait_until="networkidle", timeout=90_000)
        page.wait_for_timeout(600)

        if selector:
            loc = page.locator(selector).first
            loc.scroll_into_view_if_needed()
            page.wait_for_timeout(400)
            loc.screenshot(path=str(raw), type="png")
        else:
            page.evaluate("() => window.scrollTo(0, 0)")
            page.wait_for_timeout(300)
            page.screenshot(path=str(raw), type="png", full_page=full_page)

        browser.close()

    compose(raw, output, title=title, url_label=url_label)
    return output


def main() -> None:
    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--landing-full",
        action="store_true",
        help="Capture entire landing at natural scroll height → 01-landing-page.png",
    )
    parser.add_argument(
        "--landing-sections",
        action="store_true",
        help="Optional: also emit one mockup per landing section (01a–01g)",
    )
    parser.add_argument("url", nargs="?", default=None)
    parser.add_argument("output", nargs="?", type=Path, default=None)
    parser.add_argument("--selector", default=None)
    parser.add_argument(
        "--viewport-only",
        action="store_true",
        help="Capture viewport only (default is full_page = natural UI height)",
    )
    parser.add_argument("--title", default="سنابل IQ")
    parser.add_argument("--url-label", default="sanabel.iq")
    parser.add_argument("--chrome", type=Path, default=DEFAULT_CHROME)
    args = parser.parse_args()

    if args.landing_full:
        capture_landing_full(
            chrome=args.chrome,
            title=args.title,
            url_label=args.url_label,
        )
        return

    if args.landing_sections:
        capture_landing_sections(
            chrome=args.chrome,
            title=args.title,
            url_label=args.url_label,
        )
        return

    if not args.url or not args.output:
        parser.error("url and output required unless --landing-full / --landing-sections")

    capture_url(
        args.url,
        args.output,
        chrome=args.chrome,
        full_page=not args.viewport_only,
        selector=args.selector,
        title=args.title,
        url_label=args.url_label,
    )


if __name__ == "__main__":
    main()
