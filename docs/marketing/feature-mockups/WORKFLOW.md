# Professional Feature Mockup Workflow — سنابل IQ

## Locked rules

1. **Image size = interface size** — `full_page` capture (tall UI → tall PNG).
2. **Edge-to-edge chrome** — browser frame is the image border (no studio padding).
3. **Real product only** — Playwright @ 1440× wide, DPR 2.
4. Never use Cursor CDP + `deviceScaleFactor` (tiles the buffer).

## Batch (all catalog screens)

```bash
python3 scripts/batch_capture_mockups.py

# Retry subset:
python3 scripts/batch_capture_mockups.py --only 14 17 25
```

## Single screen

```bash
python3 scripts/capture_feature_mockup.py --landing-full

python3 scripts/capture_feature_mockup.py \
  http://localhost/login \
  docs/marketing/feature-mockups/02-family-login.png
```

## Compose only

```bash
python3 scripts/compose_screely_mockup.py raw.png out.png --title "سنابل IQ" --url "sanabel.iq"
```
