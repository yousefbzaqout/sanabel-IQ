<?php

declare(strict_types=1);

namespace App\Providers;

use App\Broadcasting\AuthorizingLogBroadcaster;
use App\Broadcasting\AuthorizingNullBroadcaster;
use App\Contracts\AIServiceInterface;
use App\Models\Activity;
use App\Models\DemoRequest;
use App\Models\InteractiveLesson;
use App\Models\InteractiveLessonStation;
use App\Models\LearningMaterial;
use App\Models\LessonAnalytic;
use App\Models\MasteryConcept;
use App\Models\ParentLearningGoal;
use App\Models\ParentMaterial;
use App\Models\Question;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Tenant;
use App\Models\User;
use App\Observers\InteractiveLessonObserver;
use App\Observers\InteractiveLessonStationObserver;
use App\Observers\MasteryConceptObserver;
use App\Policies\ActivityPolicy;
use App\Policies\AdminModelPolicy;
use App\Policies\DemoRequestPolicy;
use App\Policies\ParentLearningGoalPolicy;
use App\Policies\ParentMaterialPolicy;
use App\Policies\StudentPolicy;
use App\Services\AI\PrismEmbeddingService;
use App\Support\RateLimiting\AiEndpointRateLimiters;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Psr\Log\LoggerInterface;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AIServiceInterface::class, PrismEmbeddingService::class);
    }

    public function boot(): void
    {
        Broadcast::extend('null', fn (): AuthorizingNullBroadcaster => new AuthorizingNullBroadcaster);

        Broadcast::extend('log', fn ($app): AuthorizingLogBroadcaster => new AuthorizingLogBroadcaster(
            $app->make(LoggerInterface::class),
        ));

        Broadcast::purge('null');
        Broadcast::purge('log');

        require base_path('routes/channels.php');

        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(ParentMaterial::class, ParentMaterialPolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);
        Gate::policy(ParentLearningGoal::class, ParentLearningGoalPolicy::class);
        Gate::policy(Subject::class, AdminModelPolicy::class);
        Gate::policy(LearningMaterial::class, AdminModelPolicy::class);
        Gate::policy(Question::class, AdminModelPolicy::class);
        Gate::policy(InteractiveLesson::class, AdminModelPolicy::class);
        Gate::policy(InteractiveLessonStation::class, AdminModelPolicy::class);
        Gate::policy(User::class, AdminModelPolicy::class);
        Gate::policy(LessonAnalytic::class, AdminModelPolicy::class);
        Gate::policy(Tenant::class, AdminModelPolicy::class);
        Gate::policy(DemoRequest::class, DemoRequestPolicy::class);

        Gate::define('view-mastery', function (User $user, Student $student): bool {
            return app(StudentPolicy::class)->viewMastery($user, $student);
        });

        InteractiveLesson::observe(InteractiveLessonObserver::class);
        InteractiveLessonStation::observe(InteractiveLessonStationObserver::class);
        MasteryConcept::observe(MasteryConceptObserver::class);

        AiEndpointRateLimiters::register();

        View::composer(['layouts.navigation', 'layouts.parent', 'components.notification-bell'], function ($view): void {
            $user = auth()->user();

            if ($user === null) {
                return;
            }

            $view->with('unreadNotificationsCount', $user->unreadNotifications()->count());
            $view->with('latestNotifications', $user->unreadNotifications()->latest()->limit(5)->get());
        });
    }
}
