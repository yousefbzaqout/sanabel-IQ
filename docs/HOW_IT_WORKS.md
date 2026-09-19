# How Sanabel IQ works

This document explains the runtime architecture for engineers and technical partners.

## 1. High-level domains

| Domain | Responsibility |
|--------|----------------|
| **Identity & tenancy** | Users, roles (`parent`, `teacher`, `admin`), tenants, child PIN credentials |
| **Curriculum** | Subjects → learning materials → questions / options |
| **Interactive lessons** | Six-station engine + Sonbol mascot + optional neural TTS |
| **Gameplay** | Quizzes, XP, streaks, badges, leaderboard |
| **Analytics** | `lesson_analytics`, mastery concepts, parent/teacher dashboards |
| **Ops** | Queues, Redis cache, health checks, weekly digests, web push |

## 2. Request paths

### Child session

1. Child authenticates with login code + PIN.
2. Middleware sets `active_student_id` in the session.
3. Student routes (`/student/*`) render Arabic RTL shells with bottom navigation.
4. Completing a quiz stores a one-shot `quiz_celebration` payload, then redirects to `/student/quiz/{material}/completion`.

### Parent session

1. Parent signs in with email/password.
2. Filament panel at `/parent` lists children, goals, and materials.
3. Mastery analytics read aggregated `lesson_analytics` for the selected child.

### Teacher / admin

1. Teacher or admin uses Filament `/admin` (and teacher portal routes).
2. Interactive lessons, tenants, demo requests, and curriculum resources are managed here.
3. Tenant middleware scopes queries to the active organization.

## 3. Interactive lesson pipeline

```text
InteractiveLesson (lesson_key, subject, status=published)
        │
        ├── Station 1  variant_matrix
        ├── Station 2  sequence_pop
        ├── Station 3  structure_cards
        ├── Station 4  trace_canvas
        ├── Station 5  scratch_discover
        └── Station 6  guided_demo ──► LearningMaterial (quiz)
```

- Station configs are **JSONB** contracts (see `docs/INTERACTIVE_LESSON_ENGINE.md`).
- Adaptive Mastery Engine can inject micro-hints after repeated errors.
- Events land in `lesson_analytics` for later mastery scoring.

## 4. Data stores

| Store | Use |
|-------|-----|
| PostgreSQL 18 | Primary OLTP + `jsonb` configs |
| `pgvector` | Embeddings for PDF / material retrieval |
| Redis | Cache, queues, leaderboards |
| Object storage (local/S3) | Uploaded PDFs, generated audio |

## 5. Background work

- Queue workers process AI generation, digests, and analytics jobs.
- Weekly parent encouragement / summary mails are scheduled Artisan commands.
- Web push uses VAPID keys when configured.

## 6. Local vs production

| Concern | Local (Sail) | Production |
|---------|--------------|------------|
| HTTP | `http://localhost` | HTTPS behind reverse proxy |
| DB / Redis | Compose services | Managed Postgres + Redis |
| Assets | `npm run dev` or `build` | Built assets in deploy image |
| Secrets | `.env` (never committed) | Vault / platform secrets |

## 7. Extension points

- **New lesson** — seed or Filament-create an `InteractiveLesson` + six stations + linked material.
- **New subject analytics** — extend mastery concept codes and dashboard widgets.
- **New tenant** — create tenant, attach users/students, assign lessons.
