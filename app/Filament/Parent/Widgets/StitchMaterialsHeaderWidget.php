<?php

declare(strict_types=1);

namespace App\Filament\Parent\Widgets;

use App\Support\ActiveChildResolver;
use Filament\Widgets\Widget;

class StitchMaterialsHeaderWidget extends Widget
{
    protected static bool $isLazy = false;

    protected static bool $isDiscovered = false;

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = 'full';

    protected string $view = 'filament.parent.widgets.stitch-materials-header';

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $student = app(ActiveChildResolver::class)->resolve(auth()->user());
        $count = auth()->user()?->parentMaterials()->count() ?? 0;

        return [
            'student' => $student,
            'materialsCount' => $count,
        ];
    }
}
