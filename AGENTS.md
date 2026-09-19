# Agent guide — Sanabel IQ

Instructions for AI coding agents working in this repository.

## Non-negotiables

- PHP **8.5**, `declare(strict_types=1);`, PSR-12
- **TDD**: write Pest/PHPUnit tests before feature code
- Database assumption: **PostgreSQL 18 + pgvector**
- Feature branches from **`develop`** (never land features straight on `main`)
- Conventional Commits in English
- If a package version or Laravel API is unknown, **stop and ask** — do not guess

## Preferred commands

```bash
./vendor/bin/sail artisan test
./vendor/bin/sail artisan migrate
./vendor/bin/sail npm run build
```

## Where to look

| Need | Location |
|------|----------|
| Product capabilities | `docs/SKILLS.md` |
| Runtime architecture | `docs/HOW_IT_WORKS.md` |
| Lesson engine contracts | `docs/INTERACTIVE_LESSON_ENGINE.md` |
| Demo script | `docs/DEMO_PRESENTATION_GUIDE.md` |
| Stability rules | `.cursorrules` |

## Do not

- Commit `.env`, API keys, or child PII
- Commit `vendor/`, `node_modules/`, or `docs/marketing/feature-mockups/_raw-*`
- Invent exploits, weaken auth, or bypass tenant isolation in “temporary” code
