<?php

declare(strict_types=1);

use App\Http\Controllers\ActiveChildController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityGenerationController;
use App\Http\Controllers\Admin\AdminMaterialController;
use App\Http\Controllers\Admin\AdminQuestionController;
use App\Http\Controllers\Admin\AdminSubjectController;
use App\Http\Controllers\Admin\MasteryAnalyticsController as AdminMasteryAnalyticsController;
use App\Http\Controllers\Parent\MasteryAnalyticsController as ParentMasteryAnalyticsController;
use App\Http\Controllers\ChildActivityController;
use App\Http\Controllers\ChildOnboardingController;
use App\Http\Controllers\DemoRequestController;
use App\Http\Controllers\HealthCheckController;
use App\Http\Controllers\LegalPageController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Parent\ParentAnalyticsController as ParentProgressAnalyticsController;
use App\Http\Controllers\ParentAnalyticsController;
use App\Http\Controllers\ParentComparativeAnalyticsController;
use App\Http\Controllers\ParentCurriculumController;
use App\Http\Controllers\ParentMaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PublicInteractiveLessonPreviewController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\Teacher\TeacherAuthController;
use App\Http\Controllers\Teacher\TeacherClassroomController;
use App\Http\Controllers\Teacher\TeacherLessonAssignmentController;
use App\Http\Controllers\Student\LessonAnalyticsController;
use App\Http\Controllers\Student\StudentAiEvaluationController;
use App\Http\Controllers\Student\StudentBadgeController;
use App\Http\Controllers\Student\StudentDashboardController;
use App\Http\Controllers\Student\StudentInteractiveLessonController;
use App\Http\Controllers\Student\StudentLeaderboardController;
use App\Http\Controllers\Student\StudentLessonDemoController;
use App\Http\Controllers\Student\StudentQuizController;
use App\Http\Controllers\Student\StudentTextToSpeechController;
use App\Http\Controllers\Student\SwitchToAdultsPortalController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentProgressController;
use App\Http\Controllers\StudentReportExportController;
use App\Support\AuthRedirectResolver;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::post('/demo-requests', [DemoRequestController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('demo-requests.store');

Route::get('/preview/interactive-lesson/{lessonKey}', [PublicInteractiveLessonPreviewController::class, 'show'])
    ->where('lessonKey', '[A-Za-z0-9._-]+')
    ->name('preview.interactive-lesson');

Route::get('/health', HealthCheckController::class)->name('health');

Route::get('/legal/privacy', [LegalPageController::class, 'privacy'])->name('legal.privacy');
Route::get('/legal/terms', [LegalPageController::class, 'terms'])->name('legal.terms');
Route::get('/legal/compliance', [LegalPageController::class, 'compliance'])->name('legal.compliance');

Route::middleware(['auth', 'tenant'])->group(function (): void {
    Route::get('/dashboard', function () {
        $user = auth()->user();
        abort_unless($user !== null, 403);

        return redirect(app(AuthRedirectResolver::class)->homeUrl($user));
    })->name('dashboard');

    Route::get('/onboarding/child', [ChildOnboardingController::class, 'show'])
        ->name('onboarding.child');
    Route::post('/onboarding/child', [ChildOnboardingController::class, 'store'])
        ->name('onboarding.child.store');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllAsRead'])->name('notifications.read-all');

    Route::post('/parent/push-subscriptions', [PushSubscriptionController::class, 'store'])
        ->name('parent.push-subscriptions.store');
    Route::delete('/parent/push-subscriptions', [PushSubscriptionController::class, 'destroy'])
        ->name('parent.push-subscriptions.destroy');

    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');

    Route::get('/parent/analytics/{student}', [ParentProgressAnalyticsController::class, 'show'])
        ->name('parent.analytics.show');
    Route::get('/parent/analytics/{student}/export/pdf', [ParentProgressAnalyticsController::class, 'exportPdf'])
        ->name('parent.analytics.export.pdf');
    Route::get('/parent/analytics/{student}/export/excel', [ParentProgressAnalyticsController::class, 'exportExcel'])
        ->name('parent.analytics.export.excel');

    Route::get('/parent/mastery-analytics/{student}', [ParentMasteryAnalyticsController::class, 'show'])
        ->middleware('can:view-mastery,student')
        ->name('parent.mastery-analytics.show');
    Route::get('/parent/mastery-analytics/{student}/data', [ParentMasteryAnalyticsController::class, 'data'])
        ->middleware('can:view-mastery,student')
        ->name('parent.mastery-analytics.data');
});

Route::middleware(['auth', 'active.child', 'tenant'])->group(function (): void {
    Route::get('/student', function () {
        return redirect()->route('student.dashboard');
    })->name('student.home');

    Route::get('/parent/analytics', [ParentAnalyticsController::class, 'index'])
        ->name('parent.analytics');
    Route::post('/parent/analytics/generate-recommendations', [ParentAnalyticsController::class, 'generateRecommendations'])
        ->name('parent.analytics.generate-recommendations');

    Route::get('/parent/comparative-analytics', [ParentComparativeAnalyticsController::class, 'index'])
        ->name('parent.comparative-analytics');
    Route::get('/parent/curriculum/subjects/{subject}/materials', [ParentCurriculumController::class, 'materials'])
        ->name('parent.curriculum.materials');
    Route::get('/parent/students/{student}/export', [StudentReportExportController::class, 'export'])
        ->name('parent.students.export');

    Route::get('/materials/{parentMaterial}', [ParentMaterialController::class, 'show'])
        ->name('materials.show');
    Route::post('/materials', [ParentMaterialController::class, 'store'])
        ->name('materials.store');
    Route::delete('/materials/{parentMaterial}', [ParentMaterialController::class, 'destroy'])
        ->name('materials.destroy');
    Route::post('/materials/{parentMaterial}/generate-activity', [ActivityGenerationController::class, 'store'])
        ->name('materials.generate-activity');
    Route::get('/activities/{activity}', [ActivityController::class, 'show'])
        ->name('activities.show');

    Route::prefix('student')->name('student.')->middleware('student.locale')->group(function (): void {
        Route::get('/dashboard', [StudentDashboardController::class, 'index'])
            ->name('dashboard');
        Route::get('/adults-portal', SwitchToAdultsPortalController::class)
            ->name('adults-portal');
        Route::get('/activities', [ChildActivityController::class, 'index'])
            ->name('activities.index');
        Route::get('/activities/{activity}/play', [ChildActivityController::class, 'show'])
            ->name('activities.play');
        Route::post('/activities/{activity}/submit', [ChildActivityController::class, 'submit'])
            ->name('activities.submit');
        Route::get('/progress', [StudentProgressController::class, 'index'])
            ->name('progress');
        Route::get('/leaderboard', [StudentLeaderboardController::class, 'index'])
            ->name('leaderboard');
        Route::get('/badges', [StudentBadgeController::class, 'index'])
            ->name('badges');
        Route::get('/tts', StudentTextToSpeechController::class)
            ->name('tts');
        Route::post('/ai/pronunciation', [StudentAiEvaluationController::class, 'pronunciation'])
            ->middleware(['active.student', 'throttle:ai-voice-eval'])
            ->name('ai.pronunciation');
        Route::post('/ai/stroke', [StudentAiEvaluationController::class, 'stroke'])
            ->middleware(['active.student', 'throttle:ai-trace-eval'])
            ->name('ai.stroke');
        Route::post('/ai/micro-hint', [StudentAiEvaluationController::class, 'microHint'])
            ->middleware(['active.student', 'throttle:ai-micro-hint'])
            ->name('ai.micro-hint');
        Route::get('/lesson-demo/letter-raa', [StudentLessonDemoController::class, 'letterRaa'])
            ->name('lesson-demo.letter-raa');
        Route::get('/interactive-lesson/{lessonKey}', [StudentInteractiveLessonController::class, 'show'])
            ->where('lessonKey', '[A-Za-z0-9._-]+')
            ->name('interactive-lesson.show');
        Route::get('/lessons/{interactiveLesson}', [StudentInteractiveLessonController::class, 'showById'])
            ->whereNumber('interactiveLesson')
            ->name('lessons.show');
        Route::post('/interactive-lesson/{lessonKey}/analytics', [LessonAnalyticsController::class, 'store'])
            ->middleware('active.student')
            ->where('lessonKey', '[A-Za-z0-9._-]+')
            ->name('interactive-lesson.analytics.store');
        Route::get('/materials/{learningMaterial}/quiz', [StudentQuizController::class, 'show'])
            ->name('materials.quiz');
        Route::post('/materials/{learningMaterial}/quiz/submit', [StudentQuizController::class, 'submit'])
            ->name('materials.quiz.submit');
        Route::get('/quiz/{learningMaterial}/completion', [StudentQuizController::class, 'completion'])
            ->name('quiz.completion');
    });

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::patch('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::post('/students/{student}/select', [ActiveChildController::class, 'select'])
        ->name('students.select');
});

Route::middleware('guest')->prefix('teacher')->name('teacher.')->group(function (): void {
    Route::get('/login', [TeacherAuthController::class, 'create'])->name('login');
    Route::post('/login', [TeacherAuthController::class, 'store'])->name('login.store');
});

Route::middleware(['auth', 'teacher', 'tenant'])->prefix('teacher')->name('teacher.')->group(function (): void {
    Route::get('/', [TeacherClassroomController::class, 'dashboard'])->name('dashboard');
    Route::get('/students/{student}/progress', [TeacherClassroomController::class, 'studentProgress'])
        ->name('students.progress');
    Route::get('/lessons', [TeacherLessonAssignmentController::class, 'index'])->name('lessons.index');
    Route::post('/lessons/assign', [TeacherLessonAssignmentController::class, 'assign'])->name('lessons.assign');
    Route::post('/lessons/unassign', [TeacherLessonAssignmentController::class, 'unassign'])->name('lessons.unassign');
    Route::post('/logout', [TeacherAuthController::class, 'destroy'])->name('logout');
});

Route::middleware(['auth', 'admin', 'tenant'])->prefix('admin-legacy')->name('admin.')->group(function (): void {
    Route::get('/mastery-analytics', [AdminMasteryAnalyticsController::class, 'show'])
        ->name('mastery-analytics.show');
    Route::get('/mastery-analytics/data', [AdminMasteryAnalyticsController::class, 'data'])
        ->name('mastery-analytics.data');

    Route::get('/subjects', [AdminSubjectController::class, 'index'])->name('subjects.index');
    Route::post('/subjects', [AdminSubjectController::class, 'store'])->name('subjects.store');
    Route::get('/subjects/{subject}', [AdminSubjectController::class, 'show'])->name('subjects.show');
    Route::put('/subjects/{subject}', [AdminSubjectController::class, 'update'])->name('subjects.update');
    Route::delete('/subjects/{subject}', [AdminSubjectController::class, 'destroy'])->name('subjects.destroy');

    Route::post('/subjects/{subject}/materials', [AdminMaterialController::class, 'store'])
        ->name('subjects.materials.store');
    Route::put('/materials/{learningMaterial}', [AdminMaterialController::class, 'update'])
        ->name('materials.update');
    Route::delete('/materials/{learningMaterial}', [AdminMaterialController::class, 'destroy'])
        ->name('materials.destroy');
    Route::post('/materials/reorder', [AdminMaterialController::class, 'reorder'])
        ->name('materials.reorder');

    Route::get('/materials/{learningMaterial}/questions', [AdminQuestionController::class, 'index'])
        ->name('materials.questions.index');
    Route::post('/materials/{learningMaterial}/questions', [AdminQuestionController::class, 'store'])
        ->name('materials.questions.store');
    Route::post('/materials/{learningMaterial}/questions/reorder', [AdminQuestionController::class, 'reorder'])
        ->name('materials.questions.reorder');
    Route::put('/questions/{question}', [AdminQuestionController::class, 'update'])
        ->name('questions.update');
    Route::delete('/questions/{question}', [AdminQuestionController::class, 'destroy'])
        ->name('questions.destroy');
});

require __DIR__.'/auth.php';
