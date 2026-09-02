<?php

declare(strict_types=1);

namespace App\Filament\Parent\Widgets;

use App\Services\Analytics\SubjectAnalyticsService;
use App\Support\ActiveChildResolver;
use Filament\Widgets\ChartWidget;

class SubjectMasteryChartWidget extends ChartWidget
{
    protected static ?int $sort = 2;

    protected ?string $heading = 'إتقان المواد';

    protected function getType(): string
    {
        return 'bar';
    }

    protected function getData(): array
    {
        $student = app(ActiveChildResolver::class)->resolve(auth()->user());

        if ($student === null) {
            return [
                'datasets' => [
                    ['label' => 'الدقة %', 'data' => []],
                ],
                'labels' => [],
            ];
        }

        $analysis = app(SubjectAnalyticsService::class)->analyze($student);
        $breakdown = collect($analysis['subject_breakdown']);

        return [
            'datasets' => [
                [
                    'label' => 'الدقة %',
                    'data' => $breakdown->pluck('accuracy_percent')->all(),
                    'backgroundColor' => '#7c3aed',
                ],
            ],
            'labels' => $breakdown->pluck('subject')->all(),
        ];
    }
}
