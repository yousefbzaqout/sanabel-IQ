# Interactive Lesson Engine

**Project:** Sanabel IQ  
**Audience:** Engineers expanding Grade 1+ interactive lessons across subjects  
**Stack:** Laravel (PHP 8.5), PostgreSQL 18 (`jsonb` station configs), Filament admin, Livewire student runner  
**Related:** [TICKET-031 schema audit](architecture/TICKET-031-interactive-lesson-engine-schema-audit.md) (historical proposal)

---

## 1. Overview

Every interactive lesson is a **6-station pipeline** linked to a published `learning_materials` row (quiz CTA after Station 6).

```
LearningMaterial (quiz bank)
        ▲
        │ learning_material_id
InteractiveLesson (lesson_key, subject_code, status)
        │
        └── InteractiveLessonStation × 6  (station_number 1–6, station_type, config jsonb)
```

| Layer | Responsibility |
|-------|----------------|
| `interactive_lessons` | Identity, publish status, subject/grade, intro audio |
| `interactive_lesson_stations` | Ordered stations; **JSONB `config`** is the payload contract |
| `InteractiveLessonCatalog` | Runtime loader (DB first; PHP fallback for legacy Raa) |
| Student UI | `/student/interactive-lesson/{lessonKey}` → Livewire demo |
| `lesson_analytics` | Telemetry for mastery dashboards |

**Canonical `lesson_key` pattern:** `ar-g1-{subject-slug}-{topic}`  
Examples: `ar-g1-letter-raa`, `ar-g1-math-number-3`, `ar-g1-islamic-surah-fatiha`, `ar-g1-civics-palestine-flag`.

---

## 2. Six-station architecture

| # | `station_type` | Pedagogy | Typical subject use |
|---|----------------|----------|---------------------|
| 1 | `variant_matrix` | Hear / see variants (tabs + cards + voice targets) | Diacritics, number faces, flag colors, basmala words |
| 2 | `sequence_pop` | Ordered pop / sequence | Syllables, count 1→2→3, verse fragments |
| 3 | `structure_cards` | Flip cards explaining structure / meaning | Letter position, quantity forms, flag parts, meanings |
| 4 | `trace_canvas` | Finger / pen path along SVG | Letter stroke, triangle, digit shape |
| 5 | `scratch_discover` | Scratch overlay → hotspots | Vocab scene, symbols of homeland, mosque items |
| 6 | `guided_demo` | Sonbol models answer → quiz CTA | Links `quiz_learning_material_id` |

Station row fields (all types):

| Column | Notes |
|--------|--------|
| `station_number` | `1`–`6` (unique per lesson) |
| `station_type` | One of the six types above |
| `title` | UI label (Arabic) |
| `instructions` | Learner-facing copy |
| `sonbol_prompt` | Mascot line |
| `config` | **JSONB** — schema per type (below) |
| `assets` | Optional JSONB flags (e.g. `{ "neural_audio": true }`) |
| `is_skippable` | Default `false` |
| `order_column` | Display order (usually equals `station_number`) |

---

## 3. JSONB `config` schemas

Schemas below match what importers / Filament write and what `InteractiveLessonCatalog` exposes to the student UI.

### 3.1 Station 1 — `variant_matrix`

```json
{
  "tabs": [
    {
      "glyph": "رَ",
      "label": "فتحة",
      "audio_script": "راء فتحة",
      "audio_path": "audio/grade1/lessons/raa/tab-….mp3",
      "cards": [
        {
          "word": "رَمل",
          "emoji": "🏖️",
          "highlight": "رَ",
          "audio_script": "رمل",
          "audio_path": "audio/grade1/lessons/raa/card-….mp3",
          "image_path": null
        }
      ]
    }
  ],
  "voice_targets": ["رَ", "رُ", "رِ"]
}
```

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `tabs[]` | array | yes | One tab per variant |
| `tabs[].glyph` | string | yes | Primary glyph / symbol |
| `tabs[].label` | string | yes | Tab label |
| `tabs[].audio_script` | string | no | TTS / generation script |
| `tabs[].audio_path` | string | no | Public disk MP3 path |
| `tabs[].cards[]` | array | yes | Vocab / example cards |
| `voice_targets` | string[] | yes | Allowed pronunciation targets for AI scorer |

---

### 3.2 Station 2 — `sequence_pop`

```json
{
  "target_word": "رَمَل",
  "syllables": [
    { "glyph": "رَ", "order": 1, "audio_path": "audio/grade1/lessons/raa/seq-1.mp3" },
    { "glyph": "مَ", "order": 2, "audio_path": "audio/grade1/lessons/raa/seq-2.mp3" },
    { "glyph": "لْ", "order": 3, "audio_path": "audio/grade1/lessons/raa/seq-3.mp3" }
  ],
  "distractors": [],
  "completion_audio_script": "أحسنت! كوّنت كلمة رَمَل",
  "completion_audio_path": "audio/grade1/lessons/raa/seq-complete.mp3"
}
```

| Key | Type | Required | Description |
|-----|------|----------|-------------|
| `target_word` | string | yes | Concatenated / display target |
| `syllables[]` | array | yes | Ordered poppable units |
| `syllables[].order` | int | yes | 1-based sequence order |
| `distractors` | array | no | Extra wrong glyphs (optional) |
| `completion_audio_*` | string | no | Celebration audio |

---

### 3.3 Station 3 — `structure_cards`

```json
{
  "mode": "letter_position",
  "cards": [
    {
      "id": "begin",
      "label": "في البداية",
      "display_word": "رَمل",
      "parts": [
        { "text": "رَ", "highlight": true },
        { "text": "مل", "highlight": false }
      ],
      "audio_script": "الراء في أول الكلمة",
      "audio_path": "audio/grade1/lessons/raa/structure-begin.mp3"
    }
  ]
}
```

**Known `mode` values:**

| Mode | Used by |
|------|---------|
| `letter_position` | Arabic letter lessons |
| `quantity_representation` | Math (e.g. number 3) |
| `meaning_cards` | Islamic Studies (Fatiha meanings) |
| `flag_parts` | National Education (Palestine flag) |

Filament currently lists `letter_position` and `quantity_representation` in the select; other modes are valid in JSONB and seeders.

---

### 3.4 Station 4 — `trace_canvas`

```json
{
  "view_box": "0 0 140 140",
  "paths": [
    {
      "id": "stroke-1",
      "d": "M 110 40 L 40 40 …",
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
  "complete_audio_script": "أحسنت!",
  "complete_audio_path": "audio/grade1/lessons/raa/trace-complete.mp3"
}
```

| Key | Type | Description |
|-----|------|-------------|
| `paths[].d` | string | SVG path `d` attribute |
| `paths[].direction` | string | Hint for `LetterStrokeDirectionAnalyzer` (e.g. `top_to_bottom_arc`, `loop`) |
| `tolerance_px` | int | Stroke scoring tolerance |
| `checkpoints` | array | Optional progress markers `t ∈ [0,1]` |

---

### 3.5 Station 5 — `scratch_discover`

```json
{
  "background_image": "images/lessons/raa/bg.webp",
  "overlay_image": "images/lessons/raa/sand-overlay.webp",
  "overlay_mode": "sand",
  "reveal_threshold": 0.55,
  "story_audio_script": "مرحباً! امسح الرمل…",
  "story_audio_path": "audio/grade1/lessons/raa/story.mp3",
  "hotspots": [
    {
      "id": "sand",
      "label": "رمل",
      "emoji": "🏖️",
      "correct": true,
      "audio_script": "رمل",
      "audio_path": "audio/grade1/lessons/raa/hotspot-sand.mp3",
      "x": null,
      "y": null,
      "r": null
    }
  ]
}
```

| Key | Description |
|-----|-------------|
| `reveal_threshold` | Fraction of overlay scratched before reveal (0–1) |
| `hotspots[].correct` | Whether the item is a target find |
| `x` / `y` / `r` | Optional layout hints (nullable in seeders) |

---

### 3.6 Station 6 — `guided_demo`

```json
{
  "question_text": "أي كلمة فيها راء بفتحة؟",
  "parts": [
    { "text": "رَ", "highlight": true },
    { "text": "مل", "highlight": false }
  ],
  "explain_script": "انظر معي: راء بفتحة في أول رَمل…",
  "explain_audio_path": "audio/grade1/lessons/raa/guided-demo.mp3",
  "linked_question_id": null,
  "cta_label": "جاهز للاختبار يا بطل! 🎯",
  "quiz_learning_material_id": 42
}
```

| Key | Description |
|-----|-------------|
| `quiz_learning_material_id` | FK to the quiz `learning_materials` row |
| `parts` | Highlighted demo answer segments |
| `cta_label` | Button copy before quiz launch |

---

## 4. Adding a new interactive lesson

### 4.1 Checklist

1. Ensure a **published** `LearningMaterial` exists (title must match the seeder’s quiz title).
2. Choose a unique `lesson_key`.
3. Provide six stations with the types above.
4. Wire the seeder into `Grade1Semester1Seeder` (or subject-specific seeder).
5. Generate audio if needed (`curriculum:generate-station-audio --lesson=…`).
6. Smoke-test: `/student/interactive-lesson/{lesson_key}` and mastery analytics.

### 4.2 Via Seeders (preferred for curriculum packs)

**Pattern used by Islamic / National Education:**

1. **Definition class** — e.g. `App\Support\Lessons\SurahFatihaLessonDefinition`  
   - Constants: `LESSON_KEY`, `QUIZ_MATERIAL_TITLE`  
   - `definition(): array` with copy, tabs, sequence, structure, tracing, discovery, demo.

2. **Importer** — reuse `CurriculumInteractiveLessonImporter` (or a subject-specific importer like `LetterRaaInteractiveLessonImporter`).  
   - Resolves material by title.  
   - `updateOrCreate` on `lesson_key`.  
   - Upserts stations 1–6.

3. **Seeder** — thin wrapper:

```php
class IslamicStudiesInteractiveLessonSeeder extends Seeder
{
    public function run(): void
    {
        app(CurriculumInteractiveLessonImporter::class)->import(
            SurahFatihaLessonDefinition::definition(),
            SurahFatihaLessonDefinition::class,
        );
    }
}
```

4. **Curriculum data** — add the quiz lesson to `database/data/grade1_sem1/curriculum.php` under the correct subject (`AR`, `MATH`, `ISLAM`, `SOCIAL`).

5. **Register** in `Grade1Semester1Seeder`:

```php
$this->call(LetterRaaInteractiveLessonSeeder::class);
$this->call(NumberThreeInteractiveLessonSeeder::class);
$this->call(IslamicStudiesInteractiveLessonSeeder::class);
$this->call(NationalEducationInteractiveLessonSeeder::class);
```

### 4.3 Via Filament UI (admin)

1. Sign in as **admin** → Filament panel `/admin`.
2. Open **الدروس التفاعلية** (`InteractiveLessonResource`).
3. Create lesson: link `learning_material_id`, set `lesson_key`, `subject_code`, `status` (`draft` | `published`), intro audio.
4. On the lesson edit page, use the **Stations** relation manager.
5. For each station 1–6, pick `station_type`; the form shows type-specific `config.*` fields (`StationForm`).
6. Publish (`status = published`) before student launch.

Authorization: only admins (`AdminModelPolicy` on `InteractiveLesson` / `InteractiveLessonStation`). Parents receive **403** on `/admin/interactive-lessons`.

### 4.4 Runtime resolution

```
GET /student/interactive-lesson/{lessonKey}
  → InteractiveLessonCatalog::get(lessonKey)
       1. DB row status=published + 6 stations
       2. Else PHP fallback (legacy letter-raa only)
  → Livewire InteractiveLessonDemo
```

Launch URLs also appear on the student dashboard / learning map when a material has a linked interactive lesson (`LearningMaterial::studentLaunchUrl()`).

---

## 5. `lesson_analytics` event dictionary

### 5.1 Table columns

| Column | Type | Notes |
|--------|------|--------|
| `student_id` | FK | Required |
| `lesson_key` | string(64) | e.g. `ar-g1-letter-raa` |
| `interactive_lesson_id` | FK nullable | Filled by `LessonAnalyticsRecorder` when known |
| `learning_material_id` | FK nullable | From linked interactive lesson |
| `station` | tinyint nullable | `1`–`6` or null for lesson-level events |
| `concept_key` | string(64) | Skill / concept code |
| `event_type` | string(32) | See dictionary below |
| `error_count` | uint | Snapshot / attempt counter |
| `payload` | JSON | Event-specific fields |
| `created_at` | timestamp | Used for time-series analytics |

**Write paths:**

- `POST /student/interactive-lesson/{lessonKey}/analytics` → `LessonAnalyticsRecorder` (requires `auth` + `active.student`)
- `AdaptiveMasteryEngine::recordError()` → `error` + optional `micro_hint`

### 5.2 Allowed `event_type` values

| `event_type` | Station focus | Typical `payload` keys | Aggregated by |
|--------------|---------------|------------------------|---------------|
| `voice_attempt` | 1 | `pronunciation_score` (0–100) | Voice avg + attempt count |
| `trace_attempt` | 4 | `stroke_accuracy`, `path_precision` | Tracing averages |
| `discovery_attempt` | 5 | `first_attempt` (bool), `success` (bool) | First-attempt success rate |
| `quiz_attempt` | 6 | `first_attempt`, `success` | Same pool as discovery |
| `station_complete` | 1–6 | `time_spent` (seconds), `mastery_score` (0–100) | Time-by-station; fallback mastery |
| `lesson_complete` | — | `time_spent`, `mastery_score` | Completion rate; preferred mastery & lesson time |
| `error` | any | Context (`expected`, `actual`, …) | Adaptive engine |
| `micro_hint` | any | `hint`, `mastery_concept_id`, `trigger` | AI / Sonbol intervention counts |

### 5.3 Example rows

**Voice attempt**

```json
{
  "event_type": "voice_attempt",
  "station": 1,
  "concept_key": "diacritic_confusion",
  "payload": { "pronunciation_score": 92 }
}
```

**Trace attempt**

```json
{
  "event_type": "trace_attempt",
  "station": 4,
  "concept_key": "incomplete_trace",
  "payload": { "stroke_accuracy": 88, "path_precision": 81 }
}
```

**Station complete**

```json
{
  "event_type": "station_complete",
  "station": 2,
  "concept_key": "station_2",
  "payload": { "time_spent": 35, "mastery_score": 82 }
}
```

**Lesson complete**

```json
{
  "event_type": "lesson_complete",
  "station": null,
  "concept_key": "lesson",
  "payload": { "time_spent": 240, "mastery_score": 90 }
}
```

**Sonbol micro-hint** (from adaptive engine)

```json
{
  "event_type": "micro_hint",
  "station": 1,
  "concept_key": "diacritic_confusion",
  "error_count": 3,
  "payload": {
    "hint": "تلميح سنبل: فرّق جيداً بين رَ و رُ…",
    "mastery_concept_id": 1,
    "trigger": { "expected": "رَ", "actual": "رُ" }
  }
}
```

### 5.4 Mastery dashboards

| Audience | Endpoint |
|----------|----------|
| Parent (own child only) | `GET /parent/mastery-analytics/{student}/data` — Gate `view-mastery` |
| Teacher / admin | `GET /admin-legacy/mastery-analytics/data` — `admin` middleware |

Aggregation lives in `App\Services\Analytics\MasteryAnalyticsService` (completion %, avg mastery, time by lesson/station, voice/trace/quiz-discovery, micro-hint counts by `concept_key`).

---

## 6. Supporting services & routes (quick map)

| Concern | Entry point |
|---------|-------------|
| Catalog | `App\Support\Lessons\InteractiveLessonCatalog` |
| Shared seeder import | `App\Support\Lessons\CurriculumInteractiveLessonImporter` |
| Adaptive hints | `App\Services\AdaptiveMasteryEngine` + `mastery_concepts` |
| Pronunciation AI | `POST /student/ai/pronunciation` |
| Stroke AI | `POST /student/ai/stroke` |
| Analytics write | `POST /student/interactive-lesson/{lessonKey}/analytics` |
| Station audio CLI | `php artisan curriculum:generate-station-audio` |
| Filament CRUD | `/admin/interactive-lessons` |

---

## 7. Expansion tips

- **Reuse station types** — do not invent a seventh type unless the runner UI is updated.
- **Keep `structure_cards.mode` descriptive** — UI may ignore unknown modes but analytics/docs should list them.
- **Match quiz titles** — importers resolve materials by `QUIZ_MATERIAL_TITLE`.
- **Idempotent seeders** — always `updateOrCreate` on `lesson_key` / `(interactive_lesson_id, station_number)`.
- **Authorize early** — parent mastery uses `can:view-mastery,student`; AI/analytics require `active.student`.
- **Test** — prefer Feature tests that seed → open lesson → post analytics → assert mastery JSON (see `EndToEndInteractiveLessonFlowTest`).

---

*Last updated for the Interactive Lesson Engine track (TICKET-031 → TICKET-044).*
