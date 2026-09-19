# سنابل IQ — Sanabel IQ

[![CI](https://github.com/yousefbzaqout/sanabel-IQ/actions/workflows/ci.yml/badge.svg)](https://github.com/yousefbzaqout/sanabel-IQ/actions/workflows/ci.yml)
[![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php&logoColor=white)](https://www.php.net/)
[![Laravel](https://img.shields.io/badge/Laravel-13-FF2D20?logo=laravel&logoColor=white)](https://laravel.com)
[![PostgreSQL](https://img.shields.io/badge/PostgreSQL-18%20%2B%20pgvector-4169E1?logo=postgresql&logoColor=white)](https://www.postgresql.org/)
[![License](https://img.shields.io/badge/license-Proprietary-lightgrey)](LICENSE)

**منصة تعليم تكيّفية عربية للأطفال** تربط الطالب وولي الأمر والمعلم والمدرسة في تجربة واحدة — دروس تفاعلية، اختبارات غامرة، تحليلات إتقان، وتعدد مستأجرين (multi-tenant).

> Adaptive Arabic learning for Grade 1+ with interactive lessons, gamified quizzes, mastery analytics, and B2B tenancy.

---

## Table of contents

- [How the system works](#how-the-system-works)
- [Product surfaces](#product-surfaces)
- [Tech stack](#tech-stack)
- [Quick start](#quick-start)
- [Demo accounts](#demo-accounts)
- [Testing](#testing)
- [Repository workflow](#repository-workflow)
- [Documentation](#documentation)
- [Security](#security)
- [License](#license)

---

## How the system works

Sanabel IQ is organized around **four actors** and **one shared curriculum engine**.

```text
                    ┌─────────────────────────────────────┐
                    │         Tenant (school / org)       │
                    └─────────────────┬───────────────────┘
          ┌─────────────┬─────────────┼─────────────┬─────────────┐
          ▼             ▼             ▼             ▼             ▼
       Student       Parent        Teacher        Admin        Public
      (child PIN)   (Filament)    (portal)     (Filament)    (landing)
          │             │             │             │
          └──────┬──────┴──────┬──────┴──────┬──────┘
                 ▼             ▼             ▼
        Interactive Lesson Engine · Quizzes · XP / Badges
                 │
                 ▼
        lesson_analytics · mastery concepts · weekly digests
```

### Learning loop

1. **Curriculum** — subjects, materials, and questions live in PostgreSQL (with optional PDF → vector pipeline via `pgvector`).
2. **Interactive lesson** — six stations (`variant_matrix` → `guided_demo`) teach a concept, then hand off to a quiz.
3. **Quiz + celebration** — scoring awards XP, streaks, and badges; the child sees an immersive celebration screen.
4. **Telemetry** — station events feed `lesson_analytics` for parent and teacher mastery dashboards.
5. **Adult loop** — parents monitor progress and goals; teachers assign lessons; admins manage tenants and content.

### Auth model (important)

| Actor | How they sign in |
|-------|------------------|
| Parent / teacher / admin | Email + password (`/login`) |
| Child | PIN login (`SNBLxx` + PIN) scoped to the parent household |
| Student portal | Requires an **active child** context after adult or PIN auth |

Multi-tenancy is enforced via tenant middleware and Filament tenant context so schools stay isolated.

---

## Product surfaces

| Surface | Path | Purpose |
|---------|------|---------|
| Marketing landing | `/` | Brand, demo request, product story |
| Child PIN login | `/login` (children tab) | Fast, safe child entry |
| Student app | `/student/*` | Dashboard, map, lessons, quizzes, badges, leaderboard |
| Parent panel | `/parent` | Filament: children, goals, materials, mastery |
| Teacher portal | `/teacher/*` | Assignments and classroom views |
| Admin panel | `/admin` | Tenants, curriculum, interactive lessons, demo requests |
| Health | `/health` | Ops readiness probe |

Marketing UI references: [`docs/marketing/feature-mockups/`](docs/marketing/feature-mockups/).

---

## Tech stack

| Layer | Choice |
|-------|--------|
| Backend | PHP 8.5, Laravel 13, strict types, PSR-12 |
| UI | Blade, Livewire, Alpine.js, Vite, Tailwind |
| Admin / parent | Filament v4 (multi-panel) |
| Database | PostgreSQL 18 + `pgvector` |
| Cache / queue | Redis |
| Realtime | Laravel Echo / Reverb (web push optional) |
| AI | OpenRouter (generation + embeddings) via Prism |
| Local runtime | Laravel Sail (Docker) |

Platform capabilities are catalogued in [`docs/SKILLS.md`](docs/SKILLS.md).

---

## Quick start

### Prerequisites

- Docker + Docker Compose
- Git
- Make sure ports `80`, `5432`, `6379`, and Vite are free (or adjust Sail)

### Install

```bash
git clone https://github.com/yousefbzaqout/sanabel-IQ.git
cd sanabel-IQ

cp .env.example .env
./vendor/bin/sail up -d          # after first composer install inside Sail if needed
./vendor/bin/sail composer install
./vendor/bin/sail artisan key:generate
./vendor/bin/sail npm install
./vendor/bin/sail npm run build
./vendor/bin/sail artisan migrate --seed
```

If this is a fresh clone without `vendor/` yet:

```bash
docker run --rm -v "$(pwd)":/var/www/html -w /var/www/html laravelsail/php85-composer:latest composer install
./vendor/bin/sail up -d
```

### Day-to-day

```bash
./vendor/bin/sail up -d
./vendor/bin/sail npm run dev
./vendor/bin/sail artisan queue:work
```

App URL (Sail default): [http://localhost](http://localhost)

---

## Demo accounts

Seed with the presentation seeder (or full `db:seed`):

```bash
./vendor/bin/sail artisan migrate:fresh --seed
# or
./vendor/bin/sail artisan db:seed --class=DemoPresentationSeeder
```

**Default password:** `password`

| Role | Email | Notes |
|------|-------|--------|
| Parent | `parent@sanabel.test` | Owns demo children; Filament `/parent` |
| Teacher / admin | `teacher@sanabel.test` | Filament `/admin` |
| Child PIN | code from parent panel (e.g. `SNBL01`) | PIN set on the child record |

Full walkthrough: [`docs/DEMO_PRESENTATION_GUIDE.md`](docs/DEMO_PRESENTATION_GUIDE.md).

---

## Testing

TDD is mandatory in this repository. Prefer Pest/PHPUnit feature tests before implementation.

```bash
./vendor/bin/sail artisan test
# or a filter
./vendor/bin/sail artisan test --filter=InteractiveLesson
```

CI runs the same suite on pull requests to `develop` and `main` (PostgreSQL 18 + pgvector). See [`.github/workflows/ci.yml`](.github/workflows/ci.yml).

---

## Repository workflow

We follow **trunk-based feature branches** into `develop`, then promote to `main`.

```text
main          ← production-ready, tagged releases
  ▲
develop       ← integration branch
  ▲
feat/*  fix/*  docs/*  test/*  chore/*
```

1. Branch from `develop`: `feat/short-description`
2. Commit with [Conventional Commits](https://www.conventionalcommits.org/): `feat:`, `fix:`, `test:`, `docs:`, `chore:`, `refactor:`
3. Open a PR → `develop` (CI must pass)
4. Promote `develop` → `main` when ready to release
5. Tag a [GitHub Release](https://github.com/yousefbzaqout/sanabel-IQ/releases) from `main`

Details: [`CONTRIBUTING.md`](CONTRIBUTING.md).

---

## Documentation

| Doc | Audience |
|-----|----------|
| [`docs/SKILLS.md`](docs/SKILLS.md) | Product & engineering capability map |
| [`docs/HOW_IT_WORKS.md`](docs/HOW_IT_WORKS.md) | Deeper system walkthrough |
| [`docs/INTERACTIVE_LESSON_ENGINE.md`](docs/INTERACTIVE_LESSON_ENGINE.md) | Six-station lesson engine |
| [`docs/DEMO_PRESENTATION_GUIDE.md`](docs/DEMO_PRESENTATION_GUIDE.md) | Live demo script |
| [`CHANGELOG.md`](CHANGELOG.md) | Release history |
| [`SECURITY.md`](SECURITY.md) | Vulnerability reporting |

---

## Security

- Never commit `.env`, API keys, or child PII dumps
- Report vulnerabilities privately — see [`SECURITY.md`](SECURITY.md)

---

## License

Proprietary — © 2026 Sanabel IQ. See [`LICENSE`](LICENSE).

---

<p align="center">
  <strong>سنابل IQ</strong> — نزرع المهارة، نحصد الثقة.
</p>
