<?php

declare(strict_types=1);

namespace App\Filament\Parent\Widgets;

use App\Support\ActiveChildResolver;
use Filament\Widgets\Widget;

class StitchChildrenOverviewWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    protected static ?int $sort = -5;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.parent.widgets.stitch-children-overview';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $user = auth()->user();
        $familyCode = null;
        if ($user !== null) {
            $familyCode = app(\App\Services\Student\ChildLoginCredentialService::class)->ensureFamilyCode($user);
        }

        $children = $user?->students()->with('streak')->orderBy('name')->get() ?? collect();
        $active = app(ActiveChildResolver::class)->resolve($user);

        return [
            'children' => $children,
            'active' => $active,
            'totalXp' => (int) $children->sum('total_xp'),
            'schoolName' => $active?->tenant?->name ?? $children->first()?->tenant?->name ?? 'مدرسة مرتبطة',
            'familyCode' => $familyCode,
        ];
    }
}
