# Sanabel IQ — Demo Presentation Guide

**Audience:** Product demos, investor walkthroughs, teacher onboarding  
**Runtime:** ~5 minutes  
**Prep:** Seed demo data first (see below)

---

## 0. One-time setup

```bash
./vendor/bin/sail artisan migrate:fresh
./vendor/bin/sail artisan db:seed --class=DemoPresentationSeeder
# or: ./vendor/bin/sail artisan db:seed   # includes DemoPresentationSeeder
```

`DemoPresentationSeeder` loads Grade 1 curriculum + interactive lessons, creates demo accounts, and fills `lesson_analytics` for أحمد across all four subjects.

**Default password for all demo accounts:** `password`

| Role | Email | Name | Notes |
|------|-------|------|-------|
| Parent | `parent@sanabel.test` | ولي أمر أحمد | Owns student **أحمد العلي** (canonical analytics) |
| Student persona | `student@sanabel.test` | أحمد العلي | Parent-role login for student portal (child: أحمد العلي) |
| Teacher / Admin | `teacher@sanabel.test` | معلم سنابل | Filament admin + legacy mastery analytics |

> Sanabel uses **parent authentication** for the student experience: after login, set the active child, then open student routes.

---

## 1. Access URLs

| Surface | URL | Login |
|---------|-----|-------|
| Login | `/login` | Any demo account |
| Parent Filament home | `/parent` | `parent@sanabel.test` |
| Parent mastery dashboard | `/parent/mastery-analytics/{studentId}` | `parent@sanabel.test` |
| Parent mastery JSON | `/parent/mastery-analytics/{studentId}/data` | same |
| Student dashboard | `/student/dashboard` | `student@sanabel.test` or parent |
| Letter Raa lesson | `/student/interactive-lesson/ar-g1-letter-raa` | student/parent + active child |
| Math Number 3 | `/student/interactive-lesson/ar-g1-math-number-3` | same |
| Surah Al-Fatiha | `/student/interactive-lesson/ar-g1-islamic-surah-fatiha` | same |
| Palestine Flag | `/student/interactive-lesson/ar-g1-civics-palestine-flag` | same |
| Filament admin | `/admin` | `teacher@sanabel.test` |
| Interactive lessons CRUD | `/admin/interactive-lessons` | teacher |
| Teacher mastery UI | `/admin-legacy/mastery-analytics` | teacher |
| Teacher mastery JSON | `/admin-legacy/mastery-analytics/data` | teacher |

Find أحمد’s id after seed:

```bash
./vendor/bin/sail artisan tinker --execute="echo App\Models\Student::where('name','أحمد العلي')->whereHas('user',fn(\$q)=>\$q->where('email','parent@sanabel.test'))->value('id');"
```

---

## 2. Five-minute walkthrough script

### Minute 0–1 — Student journey & 6 stations 🎒

1. Login as **`student@sanabel.test`** / `password`.
2. Open **`/student/dashboard`** — show Grade 1 map / launch cards.
3. Open **`/student/interactive-lesson/ar-g1-letter-raa`**.
4. Narrate the pipeline while advancing stations:
   1. **variant_matrix** — hear رَ / رُ / رِ  
   2. **sequence_pop** — build رَمَل  
   3. **structure_cards** — letter positions  
   4. **trace_canvas** — stroke the raa  
   5. **scratch_discover** — reveal scene items  
   6. **guided_demo** — Sonbol models the answer → quiz CTA  

**Talking point:** *One engine, four subjects — Arabic, Math, Islamic Studies, National Education — same six station types.*

Optional 20s: jump to `/student/interactive-lesson/ar-g1-math-number-3` to show subject reuse.

---

### Minute 1–2.5 — Sonbol & AI intervention 🐣

1. Stay on an interactive lesson; trigger a wrong pronunciation / wrong sequence / failed trace (or describe seeded `micro_hint` events).
2. Show Sonbol’s encouraging state and **micro-hint** card after repeated errors (Adaptive Mastery Engine threshold).
3. Mention AI helpers:
   - `POST /student/ai/pronunciation` — pronunciation score  
   - `POST /student/ai/stroke` — path direction / accuracy  

**Talking point:** *Hints are concept-aware (`diacritic_confusion`, `bubble_sequence`, `incomplete_trace`) and logged to `lesson_analytics` for parents and teachers.*

---

### Minute 2.5–4 — Parent & teacher mastery analytics 📊

**Parent**

1. Logout → login **`parent@sanabel.test`**.
2. Open `/parent` (Filament parent panel) briefly.
3. Open `/parent/mastery-analytics/{ahmadId}` (or hit `/data` in a browser tab).
4. Point at cards:
   - Completion rate & average mastery  
   - Time by lesson / station  
   - Voice scores & attempts  
   - Tracing accuracy / path precision  
   - Quiz & discovery first-attempt success  
   - Sonbol intervention counts by concept  

**Teacher**

1. Login **`teacher@sanabel.test`**.
2. Open `/admin-legacy/mastery-analytics`.
3. Show classroom rollup (`students_tracked`, shared mastery, total hints).

**Talking point:** *Demo seed includes all four lessons completed with voice, trace, quiz, station, lesson, and micro-hint events — dashboards are never empty.*

---

### Minute 4–5 — Filament admin engine configuration 🎛️

1. Still as teacher → `/admin/interactive-lessons`.
2. Open **حرف الراء** (or any published lesson).
3. Show Stations relation manager — change a tab glyph or Sonbol prompt (don’t save if live audience shares the DB).
4. Mention JSONB `config` per `station_type` and docs: [`INTERACTIVE_LESSON_ENGINE.md`](INTERACTIVE_LESSON_ENGINE.md).

**Talking point:** *Curriculum teams expand subjects via seeders or Filament without rewriting the student runner.*

---

## 3. Demo data snapshot (what was seeded)

| Lesson key | Subject | Seeded events (per lesson) |
|------------|---------|----------------------------|
| `ar-g1-letter-raa` | Arabic | voice ×2, trace ×2, discovery, quiz, station_complete ×6, micro_hint, lesson_complete |
| `ar-g1-math-number-3` | Math | same pattern |
| `ar-g1-islamic-surah-fatiha` | Islamic | same pattern |
| `ar-g1-civics-palestine-flag` | National | same pattern (quiz first-attempt marked unsuccessful for contrast) |

Canonical analytics student: **أحمد العلي** under `parent@sanabel.test`.  
A mirrored copy exists for the `student@sanabel.test` child so student-persona login also shows progress.

---

## 4. Troubleshooting

| Issue | Fix |
|-------|-----|
| Empty mastery cards | Re-run `db:seed --class=DemoPresentationSeeder` |
| 403 on mastery routes | Use `parent@` for child’s parent id; teacher must use admin email |
| 404 interactive lesson | Ensure seeder finished (Grade 1 interactive lessons published) |
| Wrong child in session | Switch active child in parent panel / student switcher |
| Login fails | Password is literally `password` |

---

## 5. Related docs

- [`INTERACTIVE_LESSON_ENGINE.md`](INTERACTIVE_LESSON_ENGINE.md) — station JSONB schemas & analytics dictionary  
- [`architecture/TICKET-031-interactive-lesson-engine-schema-audit.md`](architecture/TICKET-031-interactive-lesson-engine-schema-audit.md) — original schema proposal  

---

*Seed class: `Database\Seeders\DemoPresentationSeeder`*
