<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\ActivityStatus;
use App\Enums\MaterialStatus;
use App\Enums\MaterialType;
use App\Enums\UserRole;
use App\Models\Activity;
use App\Models\ActivityAttempt;
use App\Models\LearningMaterial;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Models\StudentQuizAttempt;
use App\Models\StudentStreak;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Fake-but-realistic marketing data for the Filament parent dashboard.
 *
 * Usage:
 *   php artisan db:seed --class=ParentDashboardMarketingSeeder
 */
class ParentDashboardMarketingSeeder extends Seeder
{
    public const PARENT_EMAIL = 'parent@sanabel.test';

    public const PARENT_PASSWORD = 'password';

    public const SHOWCASE_CHILD_NAME = 'أحمد العلي';

    public const FAMILY_CODE = 'SNBL01';

    /**
     * Skill bars shown on the Stitch parent dashboard (material titles).
     *
     * @var list<array{title: string, accuracy: int, attempts: int}>
     */
    private const SKILL_PROFILES = [
        ['title' => 'اللغة العربية', 'accuracy' => 94, 'attempts' => 8],
        ['title' => 'الرياضيات', 'accuracy' => 88, 'attempts' => 7],
        ['title' => 'العلوم', 'accuracy' => 81, 'attempts' => 6],
        ['title' => 'النطق', 'accuracy' => 96, 'attempts' => 9],
    ];

    public function run(): void
    {
        $this->call(DualRoleLoginSeeder::class);

        $tenant = Tenant::query()->firstOrCreate(
            ['slug' => 'sanabel-model-school'],
            [
                'name' => 'مدرسة سنابل النموذجية',
                'domain' => 'demo.sanabel.test',
            ],
        );

        $parent = User::query()->updateOrCreate(
            ['email' => self::PARENT_EMAIL],
            [
                'name' => 'ولي أمر سنابل',
                'password' => Hash::make(self::PARENT_PASSWORD),
                'family_code' => self::FAMILY_CODE,
                'email_verified_at' => now(),
            ],
        );
        $parent->assignRole(UserRole::Parent);
        $parent->forceFill(['family_code' => self::FAMILY_CODE])->save();

        $showcase = Student::query()->updateOrCreate(
            [
                'user_id' => $parent->id,
                'name' => self::SHOWCASE_CHILD_NAME,
            ],
            [
                'grade_level' => 1,
                'school_term' => 1,
                'total_xp' => 2840,
                'coins' => 320,
                'lives' => 3,
                'tenant_id' => $tenant->id,
            ],
        );

        $this->seedStreak($showcase, current: 14, max: 21);
        $this->seedSkillActivityAttempts($parent, $showcase);
        $this->seedQuizHistory($showcase, distinctMaterials: 42, weeklyXpTarget: 680);
        $this->seedSiblingLeaderboardPeers($parent, $tenant, $showcase);
    }

    private function seedStreak(Student $student, int $current, int $max): void
    {
        StudentStreak::query()->updateOrCreate(
            ['student_id' => $student->id],
            [
                'current_streak' => $current,
                'max_streak' => $max,
                'last_activity_date' => now()->toDateString(),
            ],
        );
    }

    private function seedSkillActivityAttempts(User $parent, Student $student): void
    {
        ActivityAttempt::query()
            ->where('student_id', $student->id)
            ->delete();

        foreach (self::SKILL_PROFILES as $profile) {
            $material = ParentMaterial::query()->updateOrCreate(
                [
                    'user_id' => $parent->id,
                    'student_id' => $student->id,
                    'title' => $profile['title'],
                ],
                [
                    'file_path' => 'marketing/'.str()->slug($profile['title']).'.pdf',
                    'type' => MaterialType::Worksheet,
                    'status' => MaterialStatus::Completed,
                ],
            );

            $activity = Activity::query()->updateOrCreate(
                [
                    'student_id' => $student->id,
                    'parent_material_id' => $material->id,
                    'title' => 'نشاط '.$profile['title'],
                ],
                [
                    'payload' => ['marketing' => true],
                    'xp_reward' => 40,
                    'status' => ActivityStatus::Published,
                ],
            );

            for ($i = 0; $i < $profile['attempts']; $i++) {
                $total = 10;
                $score = (int) round($total * ($profile['accuracy'] / 100));
                // Slight variance across attempts
                $jitter = ($i % 3) - 1;
                $score = max(6, min($total, $score + $jitter));

                // Always inside "this week" even when Carbon week starts on Saturday (ar locale)
                ActivityAttempt::query()->create([
                    'activity_id' => $activity->id,
                    'student_id' => $student->id,
                    'score' => $score,
                    'total_questions' => $total,
                    'xp_earned' => 25 + ($i * 2),
                    'answers_json' => [],
                    'completed_at' => now()->subHours($i)->subMinutes($i * 3),
                ]);
            }

            // Extra older attempts for the 7-day mastery trend (may fall outside weekly board)
            for ($d = 1; $d <= 6; $d++) {
                $total = 10;
                $score = max(6, (int) round($total * (($profile['accuracy'] - 4 + ($d % 3)) / 100)));
                ActivityAttempt::query()->create([
                    'activity_id' => $activity->id,
                    'student_id' => $student->id,
                    'score' => $score,
                    'total_questions' => $total,
                    'xp_earned' => 8,
                    'answers_json' => [],
                    'completed_at' => now()->subDays($d)->setTime(11, 15 + $d),
                ]);
            }
        }
    }

    private function seedQuizHistory(Student $student, int $distinctMaterials, int $weeklyXpTarget): void
    {
        StudentQuizAttempt::query()
            ->where('student_id', $student->id)
            ->delete();

        $materials = LearningMaterial::query()
            ->published()
            ->whereHas('subject', static fn ($q) => $q->where('grade_level', 1))
            ->orderBy('id')
            ->limit($distinctMaterials)
            ->get();

        if ($materials->isEmpty()) {
            $materials = LearningMaterial::factory()
                ->published()
                ->count($distinctMaterials)
                ->create();
        }

        $perAttemptXp = max(10, (int) floor($weeklyXpTarget / max(1, $materials->count())));
        $earned = 0;

        foreach ($materials->values() as $index => $material) {
            $total = 8;
            $correct = 6 + ($index % 3); // 6–8 → ~75–100%
            $correct = min($total, $correct);
            $xp = $perAttemptXp + ($index % 5);
            $earned += $xp;

            // Prefer "now" timestamps so weekly leaderboard (startOfWeek) always includes them
            $completedAt = now()->subMinutes(($index * 11) + 5);

            StudentQuizAttempt::query()->create([
                'student_id' => $student->id,
                'learning_material_id' => $material->id,
                'total_questions' => $total,
                'correct_answers' => $correct,
                'score_percentage' => round(($correct / $total) * 100, 2),
                'xp_earned' => $xp,
                'completed_at' => $completedAt,
            ]);
        }

        // Top-up so weekly XP lands near the marketing target
        if ($earned < $weeklyXpTarget && $materials->isNotEmpty()) {
            $extra = $weeklyXpTarget - $earned;
            StudentQuizAttempt::query()->create([
                'student_id' => $student->id,
                'learning_material_id' => $materials->last()->id,
                'total_questions' => 5,
                'correct_answers' => 5,
                'score_percentage' => 100,
                'xp_earned' => $extra,
                'completed_at' => now()->subMinutes(2),
            ]);
        }

        // Sparse older quiz points for the 7-day trend curve
        foreach ($materials->take(7)->values() as $day => $material) {
            StudentQuizAttempt::query()->create([
                'student_id' => $student->id,
                'learning_material_id' => $material->id,
                'total_questions' => 4,
                'correct_answers' => 3,
                'score_percentage' => 75,
                'xp_earned' => 5,
                'completed_at' => now()->subDays($day + 1)->setTime(16, 20),
            ]);
        }
    }

    private function seedSiblingLeaderboardPeers(User $parent, Tenant $tenant, Student $showcase): void
    {
        $ziyad = Student::query()->updateOrCreate(
            ['user_id' => $parent->id, 'name' => 'زياد'],
            [
                'grade_level' => 1,
                'school_term' => 1,
                'total_xp' => 1960,
                'coins' => 140,
                'lives' => 3,
                'tenant_id' => $tenant->id,
            ],
        );
        $sara = Student::query()->updateOrCreate(
            ['user_id' => $parent->id, 'name' => 'سارة'],
            [
                'grade_level' => 1,
                'school_term' => 1,
                'total_xp' => 1520,
                'coins' => 95,
                'lives' => 3,
                'tenant_id' => $tenant->id,
            ],
        );

        $this->seedStreak($ziyad, current: 9, max: 12);
        $this->seedStreak($sara, current: 5, max: 8);

        // Peers below showcase on weekly board (Arabic demo names)
        $peerSpecs = [
            ['name' => 'ليان الفهد', 'xp' => 520],
            ['name' => 'كريم الدوسري', 'xp' => 410],
            ['name' => 'نورة الشمري', 'xp' => 360],
        ];

        $material = LearningMaterial::query()->published()->orderBy('id')->first()
            ?? LearningMaterial::factory()->published()->create();

        foreach (
            [
                ['student' => $ziyad, 'weekly' => 480],
                ['student' => $sara, 'weekly' => 390],
            ] as $row
        ) {
            $sibling = $row['student'];
            $weekly = $row['weekly'];

            StudentQuizAttempt::query()
                ->where('student_id', $sibling->id)
                ->where('completed_at', '>=', now()->subDays(7))
                ->delete();

            StudentQuizAttempt::query()->create([
                'student_id' => $sibling->id,
                'learning_material_id' => $material->id,
                'total_questions' => 6,
                'correct_answers' => 5,
                'score_percentage' => 83.33,
                'xp_earned' => $weekly,
                'completed_at' => now()->subMinutes(30 + $weekly % 17),
            ]);
        }

        foreach ($peerSpecs as $spec) {
            $peers = Student::query()
                ->where('name', $spec['name'])
                ->where('grade_level', 1)
                ->orderBy('id')
                ->get();

            $peer = $peers->first();

            // Collapse duplicates from earlier marketing seed runs
            foreach ($peers->skip(1) as $duplicate) {
                StudentQuizAttempt::query()->where('student_id', $duplicate->id)->delete();
                $duplicate->forceFill(['total_xp' => 0])->save();
            }

            if ($peer === null) {
                $peerParent = User::factory()->create([
                    'name' => 'ولي أمر '.$spec['name'],
                ]);
                $peerParent->assignRole(UserRole::Parent);

                $peer = Student::query()->create([
                    'user_id' => $peerParent->id,
                    'name' => $spec['name'],
                    'grade_level' => 1,
                    'school_term' => 1,
                    'total_xp' => $spec['xp'] + 800,
                    'coins' => 50,
                    'lives' => 3,
                    'tenant_id' => $tenant->id,
                ]);
            } else {
                $peer->forceFill([
                    'total_xp' => $spec['xp'] + 800,
                    'tenant_id' => $tenant->id,
                    'grade_level' => 1,
                ])->save();
            }

            StudentQuizAttempt::query()->where('student_id', $peer->id)->delete();

            StudentQuizAttempt::query()->create([
                'student_id' => $peer->id,
                'learning_material_id' => $material->id,
                'total_questions' => 5,
                'correct_answers' => 4,
                'score_percentage' => 80,
                'xp_earned' => $spec['xp'],
                'completed_at' => now()->subMinutes(40 + $spec['xp'] % 23),
            ]);
        }

        // Ensure showcase remains the default active child (lowest id among household
        // is fine; ActiveChildResolver falls back to orderBy id).
        unset($showcase);
    }
}
