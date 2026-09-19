<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\InteractiveLesson;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Models\User;
use App\Services\Analytics\MasteryAnalyticsService;
use App\Services\Lessons\LessonAnalyticsRecorder;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DemoPresentationSeeder extends Seeder
{
    public const PARENT_EMAIL = 'parent@sanabel.test';

    public const STUDENT_EMAIL = 'student@sanabel.test';

    public const TEACHER_EMAIL = 'teacher@sanabel.test';

    public const PASSWORD = 'password';

    public const STUDENT_DISPLAY_NAME = 'أحمد العلي';

    /**
     * @var list<string>
     */
    public const LESSON_KEYS = [
        'ar-g1-letter-raa',
        'ar-g1-math-number-3',
        'ar-g1-islamic-surah-fatiha',
        'ar-g1-civics-palestine-flag',
    ];

    public function run(): void
    {
        $this->call(Grade1Semester1Seeder::class);

        $parent = $this->seedParent();
        $this->seedStudentPersona();
        $this->seedTeacher();

        $ahmad = Student::query()->updateOrCreate(
            [
                'user_id' => $parent->id,
                'name' => self::STUDENT_DISPLAY_NAME,
            ],
            [
                'grade_level' => 1,
                'school_term' => 1,
                'total_xp' => 420,
                'coins' => 85,
                'lives' => 3,
            ],
        );

        $this->seedAnalyticsForStudent($ahmad);
    }

    private function seedParent(): User
    {
        $parent = User::query()->updateOrCreate(
            ['email' => self::PARENT_EMAIL],
            [
                'name' => 'ولي أمر أحمد',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ],
        );
        $parent->assignRole(UserRole::Parent);

        return $parent->fresh() ?? $parent;
    }

    private function seedStudentPersona(): User
    {
        $user = User::query()->updateOrCreate(
            ['email' => self::STUDENT_EMAIL],
            [
                'name' => self::STUDENT_DISPLAY_NAME,
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ],
        );
        $user->assignRole(UserRole::Parent);

        // Same household persona for student-portal login (active child = أحمد).
        Student::query()->updateOrCreate(
            [
                'user_id' => $user->id,
                'name' => self::STUDENT_DISPLAY_NAME,
            ],
            [
                'grade_level' => 1,
                'school_term' => 1,
                'total_xp' => 420,
                'coins' => 85,
                'lives' => 3,
            ],
        );

        return $user->fresh() ?? $user;
    }

    private function seedTeacher(): User
    {
        $teacher = User::query()->updateOrCreate(
            ['email' => self::TEACHER_EMAIL],
            [
                'name' => 'معلم سنابل',
                'password' => Hash::make(self::PASSWORD),
                'email_verified_at' => now(),
            ],
        );
        $teacher->assignRole(UserRole::Admin);

        return $teacher->fresh() ?? $teacher;
    }

    private function seedAnalyticsForStudent(Student $student): void
    {
        LessonAnalytic::query()->where('student_id', $student->id)->delete();

        $recorder = app(LessonAnalyticsRecorder::class);
        $lessons = InteractiveLesson::query()
            ->whereIn('lesson_key', self::LESSON_KEYS)
            ->get()
            ->keyBy('lesson_key');

        $profiles = [
            'ar-g1-letter-raa' => [
                'mastery' => 92,
                'voice' => [88, 94],
                'trace' => [[82, 79], [90, 86]],
                'hint_concept' => 'diacritic_confusion',
                'quiz_success' => true,
            ],
            'ar-g1-math-number-3' => [
                'mastery' => 86,
                'voice' => [80, 91],
                'trace' => [[78, 74], [88, 83]],
                'hint_concept' => 'bubble_sequence',
                'quiz_success' => true,
            ],
            'ar-g1-islamic-surah-fatiha' => [
                'mastery' => 90,
                'voice' => [85, 93],
                'trace' => [[84, 80], [91, 87]],
                'hint_concept' => 'incomplete_trace',
                'quiz_success' => true,
            ],
            'ar-g1-civics-palestine-flag' => [
                'mastery' => 88,
                'voice' => [79, 89],
                'trace' => [[76, 72], [87, 84]],
                'hint_concept' => 'diacritic_confusion',
                'quiz_success' => false,
            ],
        ];

        foreach ($profiles as $lessonKey => $profile) {
            $interactive = $lessons->get($lessonKey);
            $stationTimes = [1 => 42, 2 => 36, 3 => 33, 4 => 48, 5 => 40, 6 => 38];
            $totalTime = array_sum($stationTimes);

            foreach ($profile['voice'] as $index => $score) {
                $recorder->record(
                    student: $student,
                    lessonKey: $lessonKey,
                    eventType: MasteryAnalyticsService::EVENT_VOICE_ATTEMPT,
                    conceptKey: 'voice',
                    station: 1,
                    payload: ['pronunciation_score' => $score],
                    errorCount: $index + 1,
                );
            }

            foreach ($profile['trace'] as $index => [$accuracy, $precision]) {
                $recorder->record(
                    student: $student,
                    lessonKey: $lessonKey,
                    eventType: MasteryAnalyticsService::EVENT_TRACE_ATTEMPT,
                    conceptKey: 'incomplete_trace',
                    station: 4,
                    payload: [
                        'stroke_accuracy' => $accuracy,
                        'path_precision' => $precision,
                    ],
                    errorCount: $index + 1,
                );
            }

            $recorder->record(
                student: $student,
                lessonKey: $lessonKey,
                eventType: MasteryAnalyticsService::EVENT_DISCOVERY_ATTEMPT,
                conceptKey: 'discovery',
                station: 5,
                payload: ['first_attempt' => true, 'success' => true],
            );

            $recorder->record(
                student: $student,
                lessonKey: $lessonKey,
                eventType: MasteryAnalyticsService::EVENT_QUIZ_ATTEMPT,
                conceptKey: 'quiz',
                station: 6,
                payload: [
                    'first_attempt' => true,
                    'success' => (bool) $profile['quiz_success'],
                ],
                errorCount: $profile['quiz_success'] ? 0 : 1,
            );

            foreach ($stationTimes as $station => $seconds) {
                $recorder->record(
                    student: $student,
                    lessonKey: $lessonKey,
                    eventType: MasteryAnalyticsService::EVENT_STATION_COMPLETE,
                    conceptKey: 'station_'.$station,
                    station: $station,
                    payload: [
                        'time_spent' => $seconds,
                        'mastery_score' => max(70, (int) $profile['mastery'] - (6 - $station)),
                    ],
                );
            }

            LessonAnalytic::query()->create([
                'student_id' => $student->id,
                'lesson_key' => $lessonKey,
                'interactive_lesson_id' => $interactive?->id,
                'learning_material_id' => $interactive?->learning_material_id,
                'station' => 1,
                'concept_key' => (string) $profile['hint_concept'],
                'event_type' => MasteryAnalyticsService::EVENT_MICRO_HINT,
                'error_count' => 3,
                'payload' => [
                    'hint' => 'تلميح سنبل التجريبي: حاول مرة أخرى يا بطل!',
                    'mastery_concept_id' => null,
                    'demo' => true,
                ],
            ]);

            $recorder->record(
                student: $student,
                lessonKey: $lessonKey,
                eventType: MasteryAnalyticsService::EVENT_LESSON_COMPLETE,
                conceptKey: 'lesson',
                station: null,
                payload: [
                    'time_spent' => $totalTime,
                    'mastery_score' => (int) $profile['mastery'],
                ],
            );
        }

        // Mirror a lighter analytics snapshot onto the student-persona child for student@ login demos.
        $personaChild = Student::query()
            ->where('user_id', User::query()->where('email', self::STUDENT_EMAIL)->value('id'))
            ->where('name', self::STUDENT_DISPLAY_NAME)
            ->first();

        if ($personaChild !== null && $personaChild->id !== $student->id) {
            LessonAnalytic::query()->where('student_id', $personaChild->id)->delete();

            $source = LessonAnalytic::query()->where('student_id', $student->id)->get();
            foreach ($source as $row) {
                LessonAnalytic::query()->create([
                    'student_id' => $personaChild->id,
                    'lesson_key' => $row->lesson_key,
                    'interactive_lesson_id' => $row->interactive_lesson_id,
                    'learning_material_id' => $row->learning_material_id,
                    'station' => $row->station,
                    'concept_key' => $row->concept_key,
                    'event_type' => $row->event_type,
                    'error_count' => $row->error_count,
                    'payload' => $row->payload,
                ]);
            }
        }
    }
}
