#!/usr/bin/env python3
"""Batch-capture all Sanabel IQ feature mockups at natural UI height.

Rule: image size = interface size (full_page), edge-to-edge browser chrome.
"""

from __future__ import annotations

import json
import subprocess
import sys
import time
from dataclasses import dataclass
from pathlib import Path

from playwright.sync_api import Browser, BrowserContext, Page, sync_playwright

ROOT = Path(__file__).resolve().parents[1]
OUT = ROOT / "docs/marketing/feature-mockups"
COMPOSE = ROOT / "scripts/compose_screely_mockup.py"
CHROME = Path.home() / ".cache/ms-playwright/chromium-1243/chrome-linux64/chrome"
BASE = "http://localhost"

PARENT_EMAIL = "parent@sanabel.test"
PARENT_PASSWORD = "password"
FAMILY_CODE = "SNBL01"
CHILD_PIN = "1234"
CHILD_NAME = "زياد"

TEACHER_EMAIL = "teacher@alamal.sanabel.test"
TEACHER_PASSWORD = "password"

ADMIN_EMAIL = "admin@alamal.sanabel.test"
ADMIN_PASSWORD = "password"

# Platform-ish admin (no tenant) for tenants / demo-requests
PLATFORM_EMAIL = "teacher@sanabel.test"
PLATFORM_PASSWORD = "password"


@dataclass(frozen=True)
class Shot:
    num: str
    file: str
    url: str
    auth: str  # guest | parent | child | teacher | admin | platform
    title: str = "سنابل IQ"
    url_label: str = "sanabel.iq"
    settle_ms: int = 800


def resolve_ids() -> dict[str, str]:
    """Ask Laravel for dynamic IDs used in authenticated URLs."""
    php = r"""
use App\Models\User;
use App\Models\Student;
use App\Models\LearningMaterial;
use App\Models\Subject;

$out = [];
$p = User::where('email', 'parent@sanabel.test')->first();
$child = Student::where('user_id', $p->id)->where('name', 'زياد')->first()
    ?? Student::where('user_id', $p->id)->orderBy('id')->first();
$out['student_id'] = (string) ($child?->id ?? '');
$mat = LearningMaterial::query()->whereHas('questions')->orderBy('id')->first();
$out['material_id'] = (string) ($mat?->id ?? '1');
$sub = Subject::query()->orderBy('id')->first();
$out['subject_id'] = (string) ($sub?->id ?? '1');
$t = User::where('email', 'teacher@alamal.sanabel.test')->first();
$tStu = null;
if ($t && $t->tenant_id) {
  $tStu = Student::where('tenant_id', $t->tenant_id)->orderBy('id')->first();
}
if (! $tStu) {
  $tStu = Student::where('user_id', User::where('email', 'parent@alamal.sanabel.test')->value('id'))->orderBy('id')->first();
}
$out['teacher_student_id'] = (string) ($tStu?->id ?? $out['student_id']);
echo json_encode($out, JSON_UNESCAPED_UNICODE);
"""
    cmd = [
        str(ROOT / "vendor/bin/sail"),
        "artisan",
        "tinker",
        f"--execute={php}",
    ]
    result = subprocess.run(cmd, cwd=ROOT, capture_output=True, text=True, check=False)
    text = (result.stdout or "").strip().splitlines()
    # last non-empty line should be JSON
    for line in reversed(text):
        line = line.strip()
        if line.startswith("{"):
            return json.loads(line)
    raise RuntimeError(f"Could not resolve IDs:\n{result.stdout}\n{result.stderr}")


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
        ],
        cwd=ROOT,
    )


def new_context(browser: Browser) -> BrowserContext:
    return browser.new_context(
        viewport={"width": 1440, "height": 900},
        device_scale_factor=2,
        locale="ar",
        ignore_https_errors=True,
    )


def wait_ready(page: Page, ms: int = 800) -> None:
    try:
        page.wait_for_load_state("networkidle", timeout=20_000)
    except Exception:
        page.wait_for_load_state("domcontentloaded", timeout=10_000)
    page.wait_for_timeout(ms)


def login_parent(page: Page) -> None:
    page.goto(f"{BASE}/login", wait_until="domcontentloaded")
    wait_ready(page, 500)
    page.get_by_role("tab", name="أولياء الأمور").click()
    page.wait_for_timeout(200)
    page.locator('input[name="email"]').first.fill(PARENT_EMAIL)
    page.locator('input[name="password"]').first.fill(PARENT_PASSWORD)
    page.locator('form').filter(has=page.locator('input[name="email"]')).locator(
        'button[type="submit"]'
    ).first.click()
    wait_ready(page, 1200)


def login_admin(page: Page, email: str, password: str) -> None:
    page.goto(f"{BASE}/admin/login", wait_until="domcontentloaded", timeout=60_000)
    wait_ready(page, 800)
    page.locator('input[type="email"], input[name="email"]').first.fill(email)
    page.locator('input[type="password"], input[name="password"]').first.fill(password)
    page.locator('button[type="submit"]').first.click()
    wait_ready(page, 2000)
    if "/admin/login" in page.url:
        raise RuntimeError(f"admin login failed for {email}: {page.url}")


def login_teacher(page: Page) -> None:
    page.goto(f"{BASE}/teacher/login", wait_until="domcontentloaded")
    wait_ready(page, 500)
    page.locator('input[name="email"]').fill(TEACHER_EMAIL)
    page.locator('input[name="password"]').fill(TEACHER_PASSWORD)
    page.locator('button[type="submit"]').first.click()
    wait_ready(page, 1200)


def login_child(page: Page) -> None:
    """Authenticate child via lookup + POST /login/child (same as the UI)."""
    from urllib.parse import unquote

    page.goto(f"{BASE}/login", wait_until="domcontentloaded")
    wait_ready(page, 500)

    csrf = page.evaluate(
        """() => document.querySelector('meta[name=csrf-token]')?.content"""
    )
    if not csrf:
        raise RuntimeError("missing CSRF token on /login")

    lookup = page.request.post(
        f"{BASE}/login/child/lookup",
        headers={
            "X-CSRF-TOKEN": csrf,
            "Accept": "application/json",
            "X-Requested-With": "XMLHttpRequest",
        },
        data={"family_code": FAMILY_CODE},
    )
    if not lookup.ok:
        raise RuntimeError(f"child lookup HTTP {lookup.status}: {lookup.text()[:300]}")
    payload = lookup.json()
    children = payload.get("children") or []
    if not children:
        raise RuntimeError(f"no children for {FAMILY_CODE}: {payload}")

    child = next((c for c in children if c.get("name") == CHILD_NAME), children[0])
    print(f"child lookup OK → {child}")

    # Fresh page for session cookie + CSRF after lookup
    page.goto(f"{BASE}/login", wait_until="domcontentloaded")
    wait_ready(page, 300)
    csrf2 = page.evaluate(
        """() => document.querySelector('meta[name=csrf-token]')?.content"""
    ) or csrf

    resp = page.request.post(
        f"{BASE}/login/child",
        form={
            "_token": csrf2,
            "family_code": str(payload.get("family_code", FAMILY_CODE)),
            "student_id": str(child["id"]),
            "pin": CHILD_PIN,
        },
        max_redirects=5,
    )
    print(f"child POST status={resp.status} url={resp.url}")
    if resp.status >= 400:
        raise RuntimeError(f"child login HTTP {resp.status}: {resp.text()[:400]}")

    page.goto(f"{BASE}/student/dashboard", wait_until="domcontentloaded")
    wait_ready(page, 1500)
    if "/login" in page.url:
        raise RuntimeError(f"child session missing, landed on {page.url}")
    print(f"child login OK → {page.url}")



def capture_shot(page: Page, shot: Shot) -> Path:
    page.goto(f"{BASE}{shot.url}", wait_until="domcontentloaded", timeout=60_000)
    wait_ready(page, shot.settle_ms)
    page.evaluate("() => window.scrollTo(0,0)")
    page.wait_for_timeout(200)
    raw = OUT / f"_raw-{shot.file}"
    out = OUT / shot.file
    page.screenshot(path=str(raw), type="png", full_page=True)
    compose(raw, out, title=shot.title, url_label=shot.url_label)
    return out


def build_shots(ids: dict[str, str]) -> list[Shot]:
    sid = ids["student_id"]
    mid = ids["material_id"]
    sub = ids["subject_id"]
    tsid = ids["teacher_student_id"]

    return [
        Shot("01", "01-landing-page.png", "/", "guest"),
        Shot("02", "02-family-login.png", "/login", "guest"),
        Shot("03", "03-teacher-login.png", "/teacher/login", "guest"),
        Shot("04", "04-admin-login.png", "/admin/login", "guest"),
        Shot("05", "05-parent-register.png", "/register", "guest"),
        Shot("06", "06-child-onboarding.png", "/onboarding/child", "parent"),
        Shot("07", "07-parent-dashboard.png", "/parent", "parent"),
        Shot("08", "08-children-pin.png", "/parent/children", "parent"),
        Shot("09", "09-parent-materials.png", "/parent/materials", "parent"),
        Shot("10", "10-parent-goals.png", "/parent/goals", "parent"),
        Shot(
            "11",
            "11-parent-progress-analytics.png",
            f"/parent/analytics/{sid}",
            "parent",
        ),
        Shot(
            "12",
            "12-parent-mastery-analytics.png",
            f"/parent/mastery-analytics/{sid}",
            "parent",
        ),
        Shot(
            "13",
            "13-comparative-analytics.png",
            "/parent/comparative-analytics",
            "parent",
        ),
        Shot("14", "14-student-dashboard.png", "/student/dashboard", "child"),
        Shot(
            "15",
            "15-interactive-lesson.png",
            "/preview/interactive-lesson/ar-g1-letter-raa",
            "guest",
            settle_ms=1200,
        ),
        Shot(
            "16",
            "16-quiz-runner.png",
            f"/student/materials/{mid}/quiz",
            "child",
        ),
        Shot(
            "17",
            "17-quiz-completion.png",
            f"/student/quiz/{mid}/completion",
            "child",
        ),
        Shot("18", "18-daily-activities.png", "/student/activities", "child"),
        Shot("19", "19-progress-path.png", "/student/progress", "child"),
        Shot("20", "20-badges-cabinet.png", "/student/badges", "child"),
        Shot("21", "21-leaderboard.png", "/student/leaderboard", "child"),
        Shot("22", "22-teacher-classroom.png", "/teacher", "teacher"),
        Shot("23", "23-teacher-lesson-assignment.png", "/teacher/lessons", "teacher"),
        Shot(
            "24",
            "24-teacher-student-progress.png",
            f"/teacher/students/{tsid}/progress",
            "teacher",
        ),
        Shot("25", "25-admin-dashboard.png", "/admin", "admin"),
        Shot("26", "26-admin-users.png", "/admin/users", "admin"),
        Shot("27", "27-admin-students.png", "/admin/students", "admin"),
        Shot("28", "28-admin-subjects.png", "/admin/subjects", "admin"),
        Shot(
            "29",
            "29-admin-learning-materials.png",
            "/admin/learning-materials",
            "admin",
        ),
        Shot("30", "30-admin-questions.png", "/admin/questions", "admin"),
        Shot(
            "31",
            "31-admin-interactive-lessons.png",
            "/admin/interactive-lessons",
            "admin",
        ),
        Shot(
            "32",
            "32-admin-lesson-analytics.png",
            "/admin/lesson-analytics",
            "admin",
        ),
        Shot("33", "33-admin-schools-tenants.png", "/admin/tenants", "platform"),
        Shot("34", "34-tenant-settings.png", "/admin/tenant-settings", "admin"),
        Shot("35", "35-demo-requests.png", "/admin/demo-requests", "platform"),
    ]


def ensure_auth(page: Page, auth: str, current: str | None) -> str:
    if auth == "guest":
        # Fresh guest context preferred by caller
        return "guest"
    if current == auth:
        return auth
    if auth == "parent":
        login_parent(page)
        return "parent"
    if auth == "admin":
        login_admin(page, ADMIN_EMAIL, ADMIN_PASSWORD)
        return "admin"
    if auth == "platform":
        login_admin(page, PLATFORM_EMAIL, PLATFORM_PASSWORD)
        return "platform"
    if auth == "teacher":
        login_teacher(page)
        return "teacher"
    if auth == "child":
        login_child(page)
        return "child"
    raise ValueError(auth)


def main() -> None:
    import argparse

    parser = argparse.ArgumentParser()
    parser.add_argument(
        "--only",
        nargs="*",
        default=None,
        help="Optional file stems or numbers to capture (e.g. 14 33 35-demo-requests)",
    )
    args = parser.parse_args()

    if not CHROME.is_file():
        raise SystemExit(f"Chromium missing: {CHROME}")

    OUT.mkdir(parents=True, exist_ok=True)
    print("Resolving dynamic IDs…")
    ids = resolve_ids()
    print("IDs:", ids)
    shots = build_shots(ids)
    if args.only:
        keys = {o.lower().removesuffix(".png") for o in args.only}
        shots = [
            s
            for s in shots
            if s.num in keys
            or s.file.removesuffix(".png").lower() in keys
            or any(k in s.file for k in keys)
        ]
        print("Filtered:", [s.file for s in shots])

    results: list[tuple[str, str]] = []
    with sync_playwright() as p:
        browser = p.chromium.launch(headless=True, executable_path=str(CHROME))

        # Group by auth to reuse sessions
        order = ["guest", "parent", "child", "teacher", "admin", "platform"]
        for auth in order:
            group = [s for s in shots if s.auth == auth]
            if not group:
                continue
            context = new_context(browser)
            page = context.new_page()
            if auth != "guest":
                try:
                    ensure_auth(page, auth, None)
                    print(f"AUTH OK {auth}")
                except Exception as e:
                    print(f"AUTH FAIL {auth}: {e}")
                    for s in group:
                        results.append((s.file, f"AUTH_FAIL: {e}"))
                    context.close()
                    continue

            for s in group:
                try:
                    out = capture_shot(page, s)
                    # Detect login redirect (auth failed silently)
                    if auth != "guest" and any(
                        x in page.url
                        for x in ("/login", "/admin/login", "/teacher/login")
                    ):
                        raise RuntimeError(f"redirected to login: {page.url}")
                    size = out.stat().st_size
                    print(f"OK {s.num} {s.file} ({size} bytes) url={page.url}")
                    results.append((s.file, "ok"))
                except Exception as e:
                    print(f"FAIL {s.num} {s.file}: {e}")
                    results.append((s.file, f"FAIL: {e}"))
                time.sleep(0.2)

            context.close()

        browser.close()

    ok = sum(1 for _, st in results if st == "ok")
    print(f"\nDone: {ok}/{len(results)} ok")
    manifest = OUT / "_batch-results.json"
    prev = {}
    if manifest.is_file():
        try:
            prev = json.loads(manifest.read_text(encoding="utf-8"))
        except json.JSONDecodeError:
            prev = {}
    merged = {r[0]: r[1] for r in prev.get("results", [])}
    for f, st in results:
        merged[f] = st
    manifest.write_text(
        json.dumps(
            {
                "ids": ids,
                "results": [[k, v] for k, v in sorted(merged.items())],
            },
            ensure_ascii=False,
            indent=2,
        )
        + "\n",
        encoding="utf-8",
    )


if __name__ == "__main__":
    main()
