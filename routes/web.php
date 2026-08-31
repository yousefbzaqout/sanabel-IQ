<?php

declare(strict_types=1);

use App\Http\Controllers\ActiveChildController;
use App\Http\Controllers\ActivityController;
use App\Http\Controllers\ActivityGenerationController;
use App\Http\Controllers\ChildActivityController;
use App\Http\Controllers\ChildOnboardingController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ParentAnalyticsController;
use App\Http\Controllers\ParentMaterialController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentProgressController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::middleware('auth')->group(function (): void {
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

    Route::get('/students/create', [StudentController::class, 'create'])->name('students.create');
    Route::post('/students', [StudentController::class, 'store'])->name('students.store');
});

Route::middleware(['auth', 'active.child'])->group(function (): void {
    Route::get('/dashboard', [ParentMaterialController::class, 'index'])->name('dashboard');

    Route::get('/parent/analytics', [ParentAnalyticsController::class, 'index'])
        ->name('parent.analytics');
    Route::post('/parent/analytics/generate-recommendations', [ParentAnalyticsController::class, 'generateRecommendations'])
        ->name('parent.analytics.generate-recommendations');

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

    Route::prefix('student')->name('student.')->group(function (): void {
        Route::get('/activities', [ChildActivityController::class, 'index'])
            ->name('activities.index');
        Route::get('/activities/{activity}/play', [ChildActivityController::class, 'show'])
            ->name('activities.play');
        Route::post('/activities/{activity}/submit', [ChildActivityController::class, 'submit'])
            ->name('activities.submit');
        Route::get('/progress', [StudentProgressController::class, 'index'])
            ->name('progress');
        Route::get('/leaderboard', [StudentProgressController::class, 'leaderboard'])
            ->name('leaderboard');
    });

    Route::get('/students', [StudentController::class, 'index'])->name('students.index');
    Route::get('/students/{student}', [StudentController::class, 'show'])->name('students.show');
    Route::patch('/students/{student}', [StudentController::class, 'update'])->name('students.update');
    Route::delete('/students/{student}', [StudentController::class, 'destroy'])->name('students.destroy');
    Route::post('/students/{student}/select', [ActiveChildController::class, 'select'])
        ->name('students.select');
});

require __DIR__.'/auth.php';
