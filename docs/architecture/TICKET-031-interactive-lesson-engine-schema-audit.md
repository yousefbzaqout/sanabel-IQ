# TICKET-031: Interactive Lesson Engine — Database Schema Audit & Architecture Proposal

**Project:** Sanabel IQ  
**Scope:** Grade 1 curriculum-wide Interactive Lesson Engine (Arabic, Math, Islamic Studies, National Education)  
**Baseline:** Letter Raa prototype (`/student/lesson-demo/letter-raa`) + quiz runner  
**Database assumption:** PostgreSQL 18 + `pgvector`  
**Date:** 2026-09-04  

---

## 1. Executive summary

Today’s Postgres schema is **quiz-shaped**, not **station-shaped**.

| Layer | Status |
|-------|--------|
| Subjects → Learning materials → Questions → Options | Present and multi-subject |
| Per-entity `audio_path` (MP3) for quiz TTS | Present |
| Interaction telemetry (`lesson_analytics`) | Present (string `lesson_key`, not FK) |
| Persisted 6-station interactive payload | **Missing** (hardcoded in PHP) |
| Units / lesson modules as first-class rows | **Missing** (folded into `description` text) |
| Subject-agnostic station catalog | **Missing** (`letter-raa` only) |

**Recommendation:** keep the existing quiz hierarchy for Station 6 CTA + assessment, and add a **first-class interactive lesson graph** (`interactive_lessons` + `interactive_lesson_stations`) with **JSONB** station payloads, linked to `learning_materials`.

---

## 2. Current schema inventory

### 2.1 Core curriculum tables

#### `subjects`
- `id`, `name`, `slug`, `code`, `grade_level`, `icon`, `description`
- Unique: `(code, grade_level)`
- Seeded Grade 1 codes: `AR`, `MATH`, `ISLAM`, `SOCIAL`

#### `learning_materials`
| Column | Notes |
|--------|--------|
| `subject_id` | FK |
| `title`, `description` | Unit metadata concatenated into `description` text |
| `xp_reward`, `order_column`, `is_published` | Ordering / publish gate |
| `audio_path` | Nullable MP3 path for material title narration |

**No** `stations`, `lesson_key`, `interactive_payload`, or `unit_id`.

#### `questions` / `question_options`
| Table | Key columns |
|-------|-------------|
| `questions` | `type` ∈ `{mcq, true_false, fill_blank}`, `prompt`, `explanation`, `points`, `order_column`, `audio_path` |
| `question_options` | `option_text`, `is_correct`, `order_column`, `audio_path` |

Suitable for Station 6 sample Q + quiz runner. **Unsuitable** as the store for diacritic tabs, SVG traces, bubble sequences, or scratch scenes.

#### Quiz attempts
- `student_quiz_attempts` / `student_quiz_answers` — post-lesson assessment only.

### 2.2 Prototype telemetry

#### `lesson_analytics`
| Column | Type | Limitation |
|--------|------|------------|
| `student_id` | FK | OK |
| `lesson_key` | `string(64)`, default `letter-raa` | Free string; **no FK** to material |
| `station` | `unsignedTinyInteger` | OK for 1–6 |
| `concept_key` | string | Hardcoded concepts in PHP |
| `event_type` | string | `error` / `micro_hint` today |
| `error_count` | uint | Session + row snapshot |
| `payload` | **`json`** (not jsonb) | Opaque context blob |

### 2.3 JSON usage inconsistency

| Table | Column | Blueprint |
|-------|--------|-----------|
| `activities` | `payload` | **jsonb** |
| `lesson_analytics` | `payload` | json |
| `activity_attempts` | `answers_json` | json |
| Curriculum interactive stations | — | **none** |

For large, queryable station documents, prefer **`jsonb`** with GIN indexes where needed.

### 2.4 What the seeder actually persists

`Grade1Semester1Seeder` + `database/data/grade1_sem1/curriculum.php`:

```
Subject → units[] (NOT rows)
  └─ lessons[] → LearningMaterial rows
       └─ questions[] → Question + QuestionOption rows
```

Arabic letter lessons are **2 quiz items** via helpers (`sanabel_g1_letter_lesson`). They do **not** seed diacritic tabs, bubble syllables, SVG paths, discovery hotspots, or guided demos.

### 2.5 Hardcoded prototype payload (not DB)

`App\Support\Lessons\LetterRaaLessonDefinition` owns the entire 6-station demo:

1. `diacritic_tabs` — glyphs + vocab cards + TTS **text** (not `audio_path`)
2. (Demo) bubble sequence — hardcoded `['رَ','مَ','لْ']` in Blade/JS
3. `positions` — begin/middle/end highlight parts
4. `tracing` — SVG `path` string + completion audio text
5. `discovery` — items with correct/incorrect flags
6. `demo` — sample question parts + explain script + CTA copy

`InteractiveLessonCatalog` only resolves `letter-raa`.  
`AdaptiveMasteryEngine` defaults `lesson_key` to `letter-raa` and hardcodes hint copy for ر.

---

## 3. Station-by-station gaps vs required payload

| Station | Required assets | Current support |
|---------|-----------------|-----------------|
| **1 — Diacritics & audio** | Phonetic variants, vocab cards, neural MP3 paths | TTS text in PHP; quiz `audio_path` unrelated to tabs |
| **2 — Bubble pop** | Ordered syllable array, target word, SFX keys | Hardcoded in demo Blade/JS only |
| **3 — Positional awareness** | Position variants / highlight segments / cards | PHP `positions` array only |
| **4 — Canvas tracing** | SVG path(s), stroke order, checkpoints | Single SVG path string in PHP |
| **5 — Scratch & reveal** | Background/foreground images, hotspot coords, story audio | Emoji scene + canvas overlay; no media table |
| **6 — Little Teacher** | Guided Q, Sonbol scripts, correct demo, quiz CTA | PHP `demo` + title-matched `LearningMaterial` |

---

## 4. Proposed schema (curriculum-wide)

### 4.1 Design principles

1. **Don’t overload** `questions` with station mechanics — keep quiz for assessment.
2. Prefer **normalized station rows** + **JSONB config** over one mega-column (easier Filament admin + per-station versioning).
3. Use **`jsonb`** for station configs; keep frequently filtered fields as columns.
4. Link interactive lessons to **`learning_materials`** (1:1 or 1:0..1) so CTA → quiz stays a FK, not a title string.
5. Make station types **subject-agnostic enums**; Arabic diacritics are one config shape among many (Math can reuse “variant tabs”, National Education can reuse “positions/hotspots”).

### 4.2 New tables (recommended)

#### A. `curriculum_units` (optional but valuable)

```sql
curriculum_units (
  id, subject_id, title, outcomes jsonb, order_column, timestamps
)
```

Stops stuffing units into `learning_materials.description`.

#### B. `interactive_lessons`

```sql
interactive_lessons (
  id bigserial PK,
  learning_material_id bigint UNIQUE NOT NULL REFERENCES learning_materials(id) ON DELETE CASCADE,
  lesson_key varchar(64) UNIQUE NOT NULL,          -- e.g. 'ar-g1-letter-raa'
  title varchar(255) NOT NULL,
  subtitle varchar(255) NULL,
  subject_code varchar(16) NOT NULL,               -- AR|MATH|ISLAM|SOCIAL (denormalized for fast filters)
  grade_level smallint NOT NULL DEFAULT 1,
  station_count smallint NOT NULL DEFAULT 6,
  status varchar(32) NOT NULL DEFAULT 'draft',     -- draft|published
  intro_audio_path varchar(255) NULL,
  meta jsonb NULL,                                 -- tags, difficulty, estimated_minutes
  timestamps
)
```

#### C. `interactive_lesson_stations`

```sql
interactive_lesson_stations (
  id bigserial PK,
  interactive_lesson_id bigint NOT NULL REFERENCES interactive_lessons(id) ON DELETE CASCADE,
  station_number smallint NOT NULL,                -- 1..6
  station_type varchar(32) NOT NULL,               -- see enum below
  title varchar(255) NOT NULL,
  instructions text NULL,
  sonbol_prompt text NULL,                         -- dynamic intro copy
  config jsonb NOT NULL DEFAULT '{}',              -- station-specific payload
  assets jsonb NULL,                               -- image/audio path registry
  is_skippable boolean NOT NULL DEFAULT false,
  order_column integer NOT NULL DEFAULT 0,
  timestamps,
  UNIQUE (interactive_lesson_id, station_number)
)
```

**`station_type` enum (application-level):**

| Value | Typical subject use |
|-------|---------------------|
| `variant_matrix` | Station 1 — diacritics / number forms / vocabulary variants |
| `sequence_pop` | Station 2 — bubble / syllable / step sequence |
| `structure_cards` | Station 3 — position / place-value / structure cards |
| `trace_canvas` | Station 4 — SVG / stroke order |
| `scratch_discover` | Station 5 — overlay + hotspots |
| `guided_demo` | Station 6 — little teacher + quiz CTA |

#### D. Soften analytics coupling

```sql
-- alter lesson_analytics
ALTER TABLE lesson_analytics
  ADD COLUMN interactive_lesson_id bigint NULL REFERENCES interactive_lessons(id) ON DELETE SET NULL,
  ADD COLUMN learning_material_id bigint NULL REFERENCES learning_materials(id) ON DELETE SET NULL;

-- keep lesson_key for backward compatibility during migration
```

Migrate existing `letter-raa` rows by resolving `interactive_lessons.lesson_key`.

#### E. Optional media table (if Filament needs gallery UX)

```sql
interactive_lesson_assets (
  id, interactive_lesson_id, station_id NULL,
  kind varchar(32),          -- image|audio|svg|overlay
  disk varchar(32) DEFAULT 'public',
  path varchar(255) NOT NULL,
  meta jsonb NULL,
  timestamps
)
```

Can defer if `stations.assets` jsonb is enough for v1.

### 4.3 Proposed JSONB shapes (per station)

#### Station 1 — `variant_matrix`

```json
{
  "tabs": [
    {
      "glyph": "رَ",
      "label": "فتحة",
      "audio_path": "audio/grade1/lessons/raa/fatha.mp3",
      "cards": [
        {
          "word": "رَسّام",
          "emoji": "🎨",
          "image_path": null,
          "highlight": "رَ",
          "audio_path": "audio/grade1/lessons/raa/rassam.mp3"
        }
      ]
    }
  ],
  "voice_targets": ["رَ", "رُ", "رِ"]
}
```

#### Station 2 — `sequence_pop`

```json
{
  "target_word": "رَمَل",
  "syllables": [
    { "glyph": "رَ", "order": 1, "audio_path": "..." },
    { "glyph": "مَ", "order": 2, "audio_path": "..." },
    { "glyph": "لْ", "order": 3, "audio_path": "..." }
  ],
  "distractors": [],
  "completion_audio_path": "..."
}
```

#### Station 3 — `structure_cards`

```json
{
  "mode": "letter_position",
  "cards": [
    {
      "id": "begin",
      "label": "في البداية",
      "display_word": "رَايَة",
      "parts": [
        { "text": "رَ", "highlight": true },
        { "text": "ايَة", "highlight": false }
      ],
      "audio_path": "..."
    }
  ]
}
```

Math reuse example: `mode: "place_value"` with tens/ones cards.

#### Station 4 — `trace_canvas`

```json
{
  "view_box": "0 0 140 140",
  "paths": [
    {
      "id": "stroke-1",
      "d": "M 70 28 C 92 28 ...",
      "order": 1,
      "direction": "top_to_bottom_arc"
    }
  ],
  "checkpoints": [
    { "t": 0.0, "label": "start" },
    { "t": 0.5, "label": "mid" },
    { "t": 1.0, "label": "end" }
  ],
  "tolerance_px": 18,
  "complete_audio_path": "..."
}
```

#### Station 5 — `scratch_discover`

```json
{
  "background_image": "images/lessons/raa/orchard-bg.webp",
  "overlay_image": "images/lessons/raa/sand-overlay.webp",
  "reveal_threshold": 0.55,
  "hotspots": [
    {
      "id": "rumman",
      "label": "رُمّان",
      "x": 0.42, "y": 0.55, "r": 0.08,
      "correct": true,
      "audio_path": "..."
    }
  ],
  "story_audio_path": "..."
}
```

#### Station 6 — `guided_demo`

```json
{
  "question_text": "أين حرف الراء في كلمة (مَرْكَب)؟",
  "parts": [
    { "text": "مَ", "highlight": false },
    { "text": "رْ", "highlight": true },
    { "text": "كَب", "highlight": false }
  ],
  "explain_audio_path": "...",
  "explain_script": "انظر معي: ...",
  "linked_question_id": null,
  "cta_label": "جاهز للاختبار يا بطل! 🎯",
  "quiz_learning_material_id": 123
}
```

Prefer `quiz_learning_material_id` over title matching.

### 4.4 Adaptive mastery content (DB-driven hints)

Add:

```sql
mastery_concepts (
  id, code UNIQUE,           -- diacritic_confusion, bubble_sequence, ...
  subject_code NULL,         -- NULL = global
  label, hint_template text,
  threshold smallint DEFAULT 2,
  meta jsonb
)

-- optional per-lesson overrides
interactive_lesson_concepts (
  interactive_lesson_id, mastery_concept_id,
  threshold_override NULL, hint_override NULL
)
```

Replace hardcoded Arabic strings in `AdaptiveMasteryEngine`.

---

## 5. Updated Eloquent relationships (target)

```
Subject
  hasMany CurriculumUnit
  hasMany LearningMaterial

CurriculumUnit
  belongsTo Subject
  hasMany LearningMaterial

LearningMaterial
  belongsTo Subject
  belongsTo CurriculumUnit (nullable during migration)
  hasMany Question
  hasOne InteractiveLesson
  hasMany StudentQuizAttempt

InteractiveLesson
  belongsTo LearningMaterial
  hasMany InteractiveLessonStation
  hasMany LessonAnalytic
  belongsToMany MasteryConcept (via interactive_lesson_concepts)

InteractiveLessonStation
  belongsTo InteractiveLesson

LessonAnalytic
  belongsTo Student
  belongsTo InteractiveLesson (nullable)
  belongsTo LearningMaterial (nullable)

Question / QuestionOption
  unchanged for quiz assessment; Station 6 may optionally belongTo Question
```

---

## 6. Migration plan (phased)

### Phase 0 — Non-breaking prep
1. Create `interactive_lessons` + `interactive_lesson_stations`.
2. Backfill one row for Letter Raa from `LetterRaaLessonDefinition`.
3. Point `InteractiveLessonCatalog` / demo runner at DB with PHP fallback.
4. Add FKs on `lesson_analytics` (nullable).

### Phase 1 — Content ops
1. Filament resources for interactive lessons + station JSON forms (per `station_type`).
2. Artisan `curriculum:import-interactive {lesson_key}` from PHP definitions.
3. Switch audio fields in station config from TTS text → `audio_path` + `curriculum:generate-audio`.

### Phase 2 — Multi-subject templates
1. Seed MATH / ISLAM / SOCIAL interactive lessons using same station types.
2. Move mastery hints to `mastery_concepts`.
3. Drop PHP-only catalog match for production paths.

### Phase 3 — Cleanup
1. Convert `lesson_analytics.payload` to **jsonb**.
2. Remove title-string quiz linking.
3. Optionally extract `curriculum_units` from description text.

---

## 7. Alternatives considered

| Option | Pros | Cons |
|--------|------|------|
| **A. JSONB column on `learning_materials` only** | Fastest | Hard to query/version stations; Filament UX painful |
| **B. Stations table + JSONB config (recommended)** | Clear admin model; per-station publish; analytics FK | Extra tables |
| **C. Fully normalized rows per card/hotspot** | Max queryability | Over-normalized for creative payloads; slow to author |

**Choose B** for Grade 1 multi-subject rollout.

---

## 8. Risks & constraints

- **Do not guess** Filament form schemas or package versions — validate when implementing Filament resources.
- Neural MP3 generation already assumes `public` disk paths; station assets should reuse the same convention (`audio/grade1/...`, `images/lessons/...`).
- Prototype Livewire currently embeds station UI; runner should become **config-driven** (`station_type` → Blade/Alpine partial map).
- Session error counters in `AdaptiveMasteryEngine` should eventually key by `interactive_lesson_id`, not only `lesson_key`.

---

## 9. Concrete next tickets (suggested)

1. **TICKET-032:** Migrations for `interactive_lessons` + `interactive_lesson_stations` + analytics FKs (TDD).  
2. **TICKET-033:** Seeder/importer from `LetterRaaLessonDefinition` → DB.  
3. **TICKET-034:** Refactor `InteractiveLessonCatalog` to load from DB.  
4. **TICKET-035:** Filament CRUD for stations (type-specific JSON forms).  
5. **TICKET-036:** MATH pilot interactive lesson using shared station types.

---

## 10. Appendix — Current vs proposed (quick matrix)

| Concern | Current | Proposed |
|---------|---------|----------|
| Lesson identity | PHP `letter-raa` / title string | `interactive_lessons.lesson_key` + FK to material |
| Station content | PHP arrays | `interactive_lesson_stations.config` jsonb |
| Neural audio | Quiz `audio_path` + live TTS text | Station `audio_path` fields in config/assets |
| Analytics | `lesson_key` string | + `interactive_lesson_id` / `learning_material_id` |
| Multi-subject | Quiz only | Same station types, different configs |
| Adaptive hints | Hardcoded Arabic | `mastery_concepts` table |

---

*End of report — TICKET-031*
