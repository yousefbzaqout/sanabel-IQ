# Sanabel IQ — Skills & capabilities

A concise map of what the platform can do today. Use this for product demos, investor briefs, and engineering onboarding.

## Product skills

| Skill | Description | Primary surfaces |
|-------|-------------|------------------|
| **Interactive lesson engine** | Six pedagogical stations with JSONB configs, reusable across subjects | `/student/interactive-lesson/{key}` |
| **Immersive quizzes** | Livewire runner, scoring, XP, streak, celebration + confetti | `/student/quiz/{id}`, `/completion` |
| **Child-safe auth** | PIN login codes (`SNBLxx`) without exposing parent passwords | Login → children tab |
| **Parent cockpit** | Filament panel: children, goals, materials, leaderboard, mastery | `/parent` |
| **Teacher portal** | Assignments and classroom-oriented flows | `/teacher/*` |
| **Admin curriculum ops** | Subjects, materials, questions, interactive lessons, tenants | `/admin` |
| **Adaptive mastery** | Concept-aware micro-hints and analytics events | Lesson runner + mastery dashboards |
| **Gamification** | XP, badges, learning map, leaderboard | Student dashboard |
| **Sonbol mascot + audio** | Encouragement states, fanfare, optional TTS narration | Student shells |
| **Multi-tenancy (B2B)** | School/org isolation for users, students, and content | Tenant middleware + Filament |
| **Demo request → tenant** | Landing CTA converts inbound demos into tenants | `/` + admin Demo Requests |
| **Weekly digests** | Parent email summaries and encouragement | Queue + mail templates |
| **Web push / PWA hooks** | Optional parent push subscriptions (VAPID) | Parent panel |
| **Health & observability** | `/health` probe and load-test scripts | Ops |
| **Marketing system shots** | Edge-to-edge UI mockups for go-to-market | `docs/marketing/feature-mockups/` |

## Engineering skills (repo conventions)

| Skill | Practice |
|-------|----------|
| **TDD** | Write Pest/PHPUnit tests before features |
| **Strict PHP** | PHP 8.5, `declare(strict_types=1);`, PSR-12 |
| **Conventional Commits** | `feat`, `fix`, `test`, `docs`, `chore`, `refactor` |
| **Branching** | Feature branches from `develop`; release from `main` |
| **Sail-first local** | Dockerized Postgres 18 + pgvector + Redis |
| **No secret commits** | `.env` gitignored; use `.env.example` only |
| **CI gate** | GitHub Actions tests on PRs to `develop` / `main` |

## Subject coverage (Grade 1 prototype)

| Subject | Example lesson key |
|---------|--------------------|
| Arabic | `ar-g1-letter-raa` |
| Math | `ar-g1-math-number-3` |
| Islamic Studies | `ar-g1-islamic-surah-fatiha` |
| National Education | `ar-g1-civics-palestine-flag` |

## Agent / AI development skills

When using Cursor or similar agents in this repo:

1. Respect [`.cursorrules`](../.cursorrules) (PHP 8.5, TDD, Postgres 18 + pgvector, branch from `develop`).
2. Prefer Sail commands (`./vendor/bin/sail …`) over host PHP when services are required.
3. Do not invent package versions or Laravel APIs — verify in the codebase or ask.
4. Keep PRs focused; document user-facing behavior in the PR body.

Related deep-dives: [HOW_IT_WORKS.md](HOW_IT_WORKS.md), [INTERACTIVE_LESSON_ENGINE.md](INTERACTIVE_LESSON_ENGINE.md).
