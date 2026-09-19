---
name: Sanabel-IQ RTL EdTech Design System
colors:
  surface: '#f8f9ff'
  surface-dim: '#ccdbf3'
  surface-bright: '#f8f9ff'
  surface-container-lowest: '#ffffff'
  surface-container-low: '#eff4ff'
  surface-container: '#e6eeff'
  surface-container-high: '#dce9ff'
  surface-container-highest: '#d5e3fc'
  on-surface: '#0d1c2e'
  on-surface-variant: '#554336'
  inverse-surface: '#233144'
  inverse-on-surface: '#eaf1ff'
  outline: '#887364'
  outline-variant: '#dbc2b0'
  surface-tint: '#904d00'
  primary: '#8d4b00'
  on-primary: '#ffffff'
  primary-container: '#b15f00'
  on-primary-container: '#fffbff'
  inverse-primary: '#ffb77d'
  secondary: '#006a61'
  on-secondary: '#ffffff'
  secondary-container: '#86f2e4'
  on-secondary-container: '#006f66'
  tertiary: '#545c72'
  on-tertiary: '#ffffff'
  tertiary-container: '#6c748b'
  on-tertiary-container: '#fefcff'
  error: '#ba1a1a'
  on-error: '#ffffff'
  error-container: '#ffdad6'
  on-error-container: '#93000a'
  primary-fixed: '#ffdcc3'
  primary-fixed-dim: '#ffb77d'
  on-primary-fixed: '#2f1500'
  on-primary-fixed-variant: '#6e3900'
  secondary-fixed: '#89f5e7'
  secondary-fixed-dim: '#6bd8cb'
  on-secondary-fixed: '#00201d'
  on-secondary-fixed-variant: '#005049'
  tertiary-fixed: '#dae2fd'
  tertiary-fixed-dim: '#bec6e0'
  on-tertiary-fixed: '#131b2e'
  on-tertiary-fixed-variant: '#3f465c'
  background: '#f8f9ff'
  on-background: '#0d1c2e'
  surface-variant: '#d5e3fc'
typography:
  display-hero:
    fontFamily: Plus Jakarta Sans
    fontSize: 48px
    fontWeight: '800'
    lineHeight: 64px
  display-hero-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 32px
    fontWeight: '800'
    lineHeight: 44px
  headline-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 36px
    fontWeight: '700'
    lineHeight: 48px
  headline-lg-mobile:
    fontFamily: Plus Jakarta Sans
    fontSize: 26px
    fontWeight: '700'
    lineHeight: 36px
  headline-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 24px
    fontWeight: '700'
    lineHeight: 34px
  headline-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 20px
    fontWeight: '600'
    lineHeight: 30px
  body-lg:
    fontFamily: Be Vietnam Pro
    fontSize: 18px
    fontWeight: '400'
    lineHeight: 30px
  body-md:
    fontFamily: Be Vietnam Pro
    fontSize: 16px
    fontWeight: '400'
    lineHeight: 26px
  body-sm:
    fontFamily: Be Vietnam Pro
    fontSize: 14px
    fontWeight: '400'
    lineHeight: 22px
  label-lg:
    fontFamily: Plus Jakarta Sans
    fontSize: 14px
    fontWeight: '600'
    lineHeight: 20px
  label-md:
    fontFamily: Plus Jakarta Sans
    fontSize: 12px
    fontWeight: '600'
    lineHeight: 18px
  label-sm:
    fontFamily: Plus Jakarta Sans
    fontSize: 11px
    fontWeight: '700'
    lineHeight: 16px
rounded:
  sm: 0.5rem
  DEFAULT: 1rem
  md: 1.5rem
  lg: 2rem
  xl: 3rem
  full: 9999px
spacing:
  gutter: 1.5rem
  gutter-sm: 1rem
  gutter-lg: 2rem
  margin: 1.5rem
  margin-mobile: 1rem
  margin-desktop: 3rem
  space-xs: 0.375rem
  space-sm: 0.75rem
  space-md: 1.25rem
  space-lg: 2rem
  space-xl: 3rem
---

## Brand & Style

The design system bridges institutional rigor with warm, welcoming optimism. Built specifically for an Arabic-first educational ecosystem, it addresses four distinct user groups across K–12 SaaS: educational administrators, teachers, parents, and young learners.

The personality balances two essential qualities:
- **Academic Credibility & Trust:** Grounded, precise data tables, institutional gradebooks, curriculum roadmaps, and administrative governance.
- **Warmth, Growth, and Encouragement:** Inspired by ripe wheat stalks (*Sanabel*), the visual tone emphasizes flourishing potential, safety, and delight without descending into chaotic infantilism.

The design movement mixes **Soft Institutional Modernism** with **Warm Tactile Depth**. Interfaces leverage generous curved boundaries (rounded-3xl containers), ambient luminous warm shadows, directional RTL flow considerations, and dual-tone visual feedback that provides clarity for enterprise school boards while remaining joyful and friendly for students and parents.

## Colors

The palette pairs sun-drenched harvest amber with intellectual scholastic teal, anchored by deep slate typography over warm, non-fatiguing paper surfaces.

- **Primary Wheat & Amber (`#D97706` / `#F59E0B`):** Represents growth, celebration, achievements, active learning milestones, and primary calls to action.
- **Secondary Edu-Teal & Emerald (`#0D9488` / `#0F766E`):** Represents curriculum accuracy, intelligence, verified grades, pedagogical progress, and analytical data widgets.
- **Neutral Dark Slate (`#0F172A` / `#475569`):** High-legibility Arabic text rendering. Never use pure `#000000`; deep slate ensures optimal optical contrast against light backgrounds while avoiding eye fatigue.
- **Backgrounds & Canvas:**
  - Base canvas: Warm cream (`#FFFDF9`) and soft alabaster (`#F8FAFC`).
  - Card surfaces: Pure White (`#FFFFFF`) framed by light warm amber-tinted borders (`#FEF3C7` / `#F1F5F9`).
- **Functional Semantics:**
  - Success / Mastery: `#10B981` (Emerald)
  - Pending / Review: `#F59E0B` (Amber)
  - Alert / Needs Support: `#EF4444` (Coral Red)
  - Informational / System: `#0284C7` (Sky Blue)

## Typography

The typography stack ensures balanced metric compatibility between Western alphanumeric notations and Arabic text shaping.

- **Headings & Key Metrics:** Set in `Plus Jakarta Sans` (paired with native geometric Arabic typefaces like *Readex Pro* / *Cairo* in production). The font delivers rounded terminals, friendly counters, and crisp structure for lesson titles, student KPI metrics, and administrative dashboards.
- **Body & Continuous Text:** Set in `Be Vietnam Pro` (paired with clear Arabic text faces like *IBM Plex Sans Arabic* or *Cairo*). Generous x-height, wide apertures, and deliberate vertical spacing account for Arabic diacritics (Tashkeel) and descending glyphs without clipping.
- **RTL Baseline Rules:**
  - All text containers must preserve native RTL alignment (`text-align: right`) with contextual overrides for internationalized figures and metrics.
  - Arabic typography requires 20–25% higher line-height ratios than pure Latin counterparts to prevent descender collisions and maintain legible mark positioning.

## Layout & Spacing

The layout is built on a responsive 12-column fluid grid system engineered for bidirectional and native RTL rendering (`dir="rtl"`).

- **Desktop (1280px+):** 12 columns with 32px (`2rem`) gutters and 48px (`3rem`) screen margins. Side navigation sits anchored on the right hand side; the primary content view expands leftward.
- **Tablet (768px – 1024px):** 8 columns with 24px (`1.5rem`) gutters and 24px outer margins. Collapsible rail navigation.
- **Mobile (< 768px):** 4 columns with 16px (`1rem`) gutters and 16px canvas margins. Bottom tab bar navigation for student and parent workflows.

### RTL Directionality & Spacing Conventions
- **Logical Flow Properties:** Avoid physical `left`/`right` token assignments. Rely exclusively on inline start (`margin-inline-start`, `padding-inline-start`) and inline end declarations.
- **Reading Order:** Hero sections, primary metrics, student avatar groupings, and table primary identifiers originate at the top-right and progress toward the bottom-left.
- **Internal Component Spacing:**
  - Micro spaces (`space-xs` = 6px, `space-sm` = 12px) format badges, avatar offsets, icon-to-text distances, and pill counters.
  - Moderate spaces (`space-md` = 20px, `space-lg` = 32px) handle card padding, fieldsets, and dashboard tile intervals.
  - Macro spacing (`space-xl` = 48px) defines clear curriculum module sectioning.

## Elevation & Depth

Visual hierarchy uses **Ambient Warm Glows & Low-Contrast Protective Borders** rather than aggressive dark drop shadows. Surfaces appear elevated above warm cream backdrops like physical educational cards.

- **Level 0 (Canvas Base):** Flat `#FFFDF9` or `#F8FAFC`. Zero elevation.
- **Level 1 (Standard Surface / Module Card):** Pure white background (`#FFFFFF`) with a 1px border of `#F1F5F9` (or `#FEF3C7` for highlighted student tasks). Shadow: `0px 4px 20px -2px rgba(217, 119, 6, 0.05), 0px 2px 6px -1px rgba(15, 23, 42, 0.03)`.
- **Level 2 (Interactive Hover & Popovers):** Elevated card state upon hover or active focus. Shadow shifts to a golden-tinted ambient halo: `0px 12px 28px -4px rgba(217, 119, 6, 0.12), 0px 4px 10px -2px rgba(15, 23, 42, 0.04)`. Border shifts slightly toward primary amber.
- **Level 3 (Modals, Overlays, and Drawer Panels):** Floats prominently over a frosted warm overlay (`rgba(15, 23, 42, 0.4)` backdrop blur of `8px`). Shadow: `0px 24px 48px -8px rgba(15, 23, 42, 0.16)`.
- **Level 4 (Toasts & Floating Action Badges):** Pill-shaped alerts floating with `0px 10px 24px -3px rgba(13, 148, 136, 0.18)` using teal tints for learning achievements or amber for reminders.

## Shapes

The design system adopts a **Pill & Soft Pebble (Level 3)** roundedness aesthetic. Soft shapes remove intimidation from complex academic metrics, encourage tactile exploration for younger students, and present an approachable interface for teachers and parents.

- **Buttons & Tags:** Full continuous pill curves (`rounded-full` / 9999px).
- **Cards, Modules, and Modals:** Generous curves utilizing `rounded-3xl` (24px to 32px / `2rem` to `3rem`).
- **Input Fields & Filter Selects:** `rounded-2xl` (16px / `1rem`), matching the pill language while retaining clear edge definition for structured data entry.
- **Progress Trackers & Badges:** Fully circular (`rounded-full`) avatar nodes and metric capsules.

## Components

### 1. Buttons
- **Primary Action (Amber):** Solid `#D97706` background, crisp white label, fully pill-shaped (`rounded-full`). Hover state shifts to `#B45309` with a gentle upward translation (`transform: translateY(-1px)`) and amber glow.
- **Secondary Action (Edu-Teal):** Solid `#0D9488` with white text for secondary workflows (e.g., "Download Syllabus", "Verify Attendance").
- **Soft/Tonal Variant:** `#FEF3C7` container with `#B45309` label text for low-friction actions (e.g., "Save Draft", "Bookmark Lesson").
- **Directional Icons:** Icon offsets must use logical margins (`margin-inline-end: 0.5rem`). Forward indicator arrows point to the left in RTL contexts.

### 2. Cards & Learning Modules
- Built on `#FFFFFF` with `rounded-3xl` geometry.
- Enclosed with a 1px border (`#FEF3C7` for learning tasks, `#E2E8F0` for administrative tables).
- Headers feature student avatars or subject badges on the right (start), status indicator pills on the left (end), and title text stacked directly beneath.
- Hover states slightly elevate the card with a warm amber rim.

### 3. Input Fields & Form Controls
- **Text Inputs:** Height 48px, `rounded-2xl`, background `#FFFFFF`, border 1.5px solid `#E2E8F0`. Focus state: border color `#D97706` with a soft ring of `4px rgba(217, 119, 6, 0.15)`. Text alignment defaults to right (`rtl`).
- **Icons within Inputs:** Leading icons sit on the right-hand side; clear buttons or trailing indicators sit on the left-hand side.
- **Checkboxes & Radios:** `rounded-lg` for checkboxes (6px) and `rounded-full` for radios. Selected state fills with `#0D9488` (Teal) with a crisp white glyph checkmark.

### 4. Badges & Skill Pills
- Pill-shaped (`rounded-full`), padding `0.25rem 0.875rem`.
- **Achievement/Gold:** Background `#FEF3C7`, text `#B45309`, border `1px solid #FDE68A`.
- **Verified/Curriculum:** Background `#CCFBF1`, text `#0F766E`, border `1px solid #99F6E4`.
- **Grade/Level Tag:** Dark slate `#0F172A` with `#F8FAFC` text for formal school grading levels.

### 5. Lists & Data Rows
- Striped student rosters use alternating transparent and warm cream (`#FFFDF9`) backgrounds.
- Row padding: `1rem 1.25rem` with `rounded-2xl` hover outlines.
- Right-to-left layout: Student photo/identifier -> Student Name -> Attendance Status pill -> Performance grade -> Action triggers on far left.

### 6. Specialized EdTech Components
- **Gamified Progress Bar:** 12px height container with full pill caps (`rounded-full`). Background `#FEF3C7`, inner active fill transitioning from `#F59E0B` to `#0D9488` as completion approaches 100%.
- **Parent-Teacher Notification Banner:** Warm card featuring a teal accent bar on the right border (`border-inline-start: 4px solid #0D9488`), communicating safety, transparency, and collaboration.