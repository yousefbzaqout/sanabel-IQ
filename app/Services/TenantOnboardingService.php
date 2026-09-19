<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\UserRole;
use App\Models\InteractiveLesson;
use App\Models\LessonAnalytic;
use App\Models\Student;
use App\Models\Tenant;
use App\Models\User;
use App\Services\Analytics\MasteryAnalyticsService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;

final class TenantOnboardingService
{
    public const DEFAULT_PASSWORD = 'password';

    /**
     * Provision a tenant with admin/staff/demo data and B2B commercial fields.
     *
     * @param  array{
     *     name: string,
     *     slug: string,
     *     domain?: string|null,
     *     admin_email?: string,
     *     admin_name?: string,
     *     teacher_email?: string,
     *     teacher_name?: string,
     *     student_email?: string,
     *     student_name?: string,
     *     student_display_name?: string,
     *     password?: string,
     *     lesson_keys?: list<string>|null,
     *     seat_limit?: int,
     *     subscription_plan?: string,
     *     contact_email?: string|null,
     *     contact_phone?: string|null
     * }  $tenantData
     */
    public function provision(array $tenantData): Tenant
    {
        $tenant = $this->onboard($tenantData);

        $updates = array_filter([
            'seat_limit' => isset($tenantData['seat_limit']) ? (int) $tenantData['seat_limit'] : null,
            'subscription_plan' => isset($tenantData['subscription_plan'])
                ? (string) $tenantData['subscription_plan']
                : null,
            'contact_email' => array_key_exists('contact_email', $tenantData)
                ? $tenantData['contact_email']
                : null,
            'contact_phone' => array_key_exists('contact_phone', $tenantData)
                ? $tenantData['contact_phone']
                : null,
        ], static fn (mixed $value): bool => $value !== null);

        if ($updates !== []) {
            $tenant->forceFill($updates)->save();
        }

        return $tenant->fresh() ?? $tenant;
    }

    /**
     * Provision a tenant with admin, teacher, student household, lessons, and sample analytics.
     *
     * @param  array{
     *     name: string,
     *     slug: string,
     *     domain?: string|null,
     *     admin_email?: string,
     *     admin_name?: string,
     *     teacher_email?: string,
     *     teacher_name?: string,
     *     student_email?: string,
     *     student_name?: string,
     *     student_display_name?: string,
     *     password?: string,
     *     lesson_keys?: list<string>|null
     * }  $tenantData
     */
    public function onboard(array $tenantData): Tenant
    {
        $name = trim((string) ($tenantData['name'] ?? ''));
        $slug = trim((string) ($tenantData['slug'] ?? ''));

        if ($name === '' || $slug === '') {
            throw new InvalidArgumentException('Tenant onboarding requires name and slug.');
        }

        $domain = isset($tenantData['domain']) ? trim((string) $tenantData['domain']) : null;
        $domain = $domain === '' ? null : $domain;
        $password = (string) ($tenantData['password'] ?? self::DEFAULT_PASSWORD);

        $adminEmail = (string) ($tenantData['admin_email'] ?? 'admin@'.$slug.'.test');
        $teacherEmail = (string) ($tenantData['teacher_email'] ?? 'teacher@'.$slug.'.test');
        $studentEmail = (string) ($tenantData['student_email'] ?? 'parent@'.$slug.'.test');

        return DB::transaction(function () use (
            $tenantData,
            $name,
            $slug,
            $domain,
            $password,
            $adminEmail,
            $teacherEmail,
            $studentEmail,
        ): Tenant {
            $tenant = Tenant::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $name,
                    'domain' => $domain,
                    'status' => Tenant::STATUS_ACTIVE,
                ],
            );

            TenantContext::setTenant($tenant);

            try {
                $this->upsertUser(
                    email: $adminEmail,
                    name: (string) ($tenantData['admin_name'] ?? 'مدير '.$name),
                    password: $password,
                    role: UserRole::TenantAdmin,
                    tenantId: $tenant->id,
                );

                $this->upsertUser(
                    email: $teacherEmail,
                    name: (string) ($tenantData['teacher_name'] ?? 'معلم '.$name),
                    password: $password,
                    role: UserRole::Teacher,
                    tenantId: $tenant->id,
                );

                $parent = $this->upsertUser(
                    email: $studentEmail,
                    name: (string) ($tenantData['student_name'] ?? 'ولي أمر '.$name),
                    password: $password,
                    role: UserRole::Parent,
                    tenantId: $tenant->id,
                );

                $student = Student::query()->updateOrCreate(
                    [
                        'user_id' => $parent->id,
                        'name' => (string) ($tenantData['student_display_name'] ?? 'طالب '.$name),
                    ],
                    [
                        'grade_level' => 1,
                        'school_term' => 1,
                        'total_xp' => 120,
                        'coins' => 40,
                        'lives' => 3,
                    ],
                );

                $lessons = $this->attachPublishedLessons(
                    $tenant,
                    $tenantData['lesson_keys'] ?? null,
                );

                $this->seedMockAnalytics($tenant, $student, $lessons);
            } finally {
                TenantContext::clear();
            }

            return $tenant->fresh() ?? $tenant;
        });
    }

    private function upsertUser(
        string $email,
        string $name,
        string $password,
        UserRole $role,
        int $tenantId,
    ): User {
        $user = User::query()->withoutTenantScope()->updateOrCreate(
            ['email' => $email],
            [
                'name' => $name,
                'password' => Hash::make($password),
                'email_verified_at' => now(),
                'tenant_id' => $tenantId,
            ],
        );

        $user->assignRole($role);

        return $user->fresh() ?? $user;
    }

    /**
     * @param  list<string>|null  $lessonKeys
     * @return list<InteractiveLesson>
     */
    private function attachPublishedLessons(Tenant $tenant, ?array $lessonKeys): array
    {
        $query = InteractiveLesson::query()
            ->withoutTenantScope()
            ->where('status', 'published')
            ->where(function ($builder) use ($tenant): void {
                $builder->whereNull('tenant_id')
                    ->orWhere('tenant_id', $tenant->id);
            });

        if ($lessonKeys !== null && $lessonKeys !== []) {
            $query->whereIn('lesson_key', $lessonKeys);
        }

        $lessons = $query->get();

        foreach ($lessons as $lesson) {
            if ($lesson->tenant_id !== $tenant->id) {
                $lesson->forceFill(['tenant_id' => $tenant->id])->save();
            }
        }

        return $lessons->all();
    }

    /**
     * @param  list<InteractiveLesson>  $lessons
     */
    private function seedMockAnalytics(Tenant $tenant, Student $student, array $lessons): void
    {
        LessonAnalytic::query()
            ->withoutTenantScope()
            ->where('tenant_id', $tenant->id)
            ->where('student_id', $student->id)
            ->delete();

        if ($lessons === []) {
            return;
        }

        foreach ($lessons as $lesson) {
            LessonAnalytic::query()->create([
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'lesson_key' => $lesson->lesson_key,
                'interactive_lesson_id' => $lesson->id,
                'learning_material_id' => $lesson->learning_material_id,
                'station' => 1,
                'concept_key' => 'voice',
                'event_type' => MasteryAnalyticsService::EVENT_VOICE_ATTEMPT,
                'error_count' => 1,
                'payload' => ['pronunciation_score' => 88, 'demo' => true],
            ]);

            LessonAnalytic::query()->create([
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'lesson_key' => $lesson->lesson_key,
                'interactive_lesson_id' => $lesson->id,
                'learning_material_id' => $lesson->learning_material_id,
                'station' => 4,
                'concept_key' => 'incomplete_trace',
                'event_type' => MasteryAnalyticsService::EVENT_TRACE_ATTEMPT,
                'error_count' => 1,
                'payload' => [
                    'stroke_accuracy' => 82,
                    'path_precision' => 79,
                    'demo' => true,
                ],
            ]);

            LessonAnalytic::query()->create([
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'lesson_key' => $lesson->lesson_key,
                'interactive_lesson_id' => $lesson->id,
                'learning_material_id' => $lesson->learning_material_id,
                'station' => 2,
                'concept_key' => 'station_2',
                'event_type' => MasteryAnalyticsService::EVENT_STATION_COMPLETE,
                'error_count' => 0,
                'payload' => [
                    'time_spent' => 36,
                    'mastery_score' => 85,
                    'demo' => true,
                ],
            ]);

            LessonAnalytic::query()->create([
                'tenant_id' => $tenant->id,
                'student_id' => $student->id,
                'lesson_key' => $lesson->lesson_key,
                'interactive_lesson_id' => $lesson->id,
                'learning_material_id' => $lesson->learning_material_id,
                'station' => null,
                'concept_key' => 'lesson',
                'event_type' => MasteryAnalyticsService::EVENT_LESSON_COMPLETE,
                'error_count' => 0,
                'payload' => [
                    'time_spent' => 210,
                    'mastery_score' => 90,
                    'demo' => true,
                ],
            ]);
        }
    }
}
