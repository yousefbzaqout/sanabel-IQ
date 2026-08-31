<?php

declare(strict_types=1);

namespace App\Providers;

use App\Contracts\AIServiceInterface;
use App\Models\ParentMaterial;
use App\Models\Student;
use App\Policies\ParentMaterialPolicy;
use App\Policies\StudentPolicy;
use App\Services\AI\PrismEmbeddingService;
use Illuminate\Support\Facades\Gate;
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
    }
}
