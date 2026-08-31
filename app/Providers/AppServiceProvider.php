<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AIServiceInterface;
use App\Models\Activity;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Policies\ActivityPolicy;
use App\Policies\ParentMaterialPolicy;
use App\Policies\StudentPolicy;
use App\Services\AI\PrismEmbeddingService;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->bind(AIServiceInterface::class, PrismEmbeddingService::class);
    }

    public function boot(): void
    {
        Gate::policy(Student::class, StudentPolicy::class);
        Gate::policy(ParentMaterial::class, ParentMaterialPolicy::class);
        Gate::policy(Activity::class, ActivityPolicy::class);

        View::composer(['layouts.navigation', 'components.notification-bell'], function ($view): void {
            $user = auth()->user();

            if ($user === null) {
                return;
            }

            $view->with('unreadNotificationsCount', $user->unreadNotifications()->count());
            $view->with('latestNotifications', $user->unreadNotifications()->latest()->limit(5)->get());
        });
    }
}
