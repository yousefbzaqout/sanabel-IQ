<?php

declare(strict_types=1);

namespace App\Filament\Parent\Widgets;

use App\Services\Analytics\SubjectAnalyticsService;
use App\Support\ActiveChildResolver;
use Filament\Widgets\ChartWidget;

class SubjectMasteryChartWidget extends ChartWidget
{
    protected static bool $isDiscovered = false;

    protected static bool $isLazy = false;

    protected static ?int $sort = 2;

    protected ?string $heading = 'إتقان المواد';

    /**
     * @var int | string | array<string, int | string | null>
     */
    protected int|string|array $columnSpan = [
        'default' => 1,
        'md' => 2,
        'lg' => 3,
    ];

    protected ?string $maxHeight = '280px';

    protected function getType(): string
    {
        return 'bar';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getOptions(): array
    {
        return [
            'responsive' => true,
            'maintainAspectRatio' => false,
            'plugins' => [
                'legend' => [
                    'display' => true,
                    'labels' => [
                        'font' => [
                            'family' => 'Tajawal',
                            'size' => 14,
                        ],
                    ],
                ],
            ],
            'scales' => [
                'x' => [
                    'ticks' => [
                        'autoSkip' => true,
                        'maxRotation' => 45,
                        'minRotation' => 0,
                        'font' => [
                            'family' => 'Tajawal',
                            'size' => 12,
                        ],
                    ],
                ],
                'y' => [
                    'beginAtZero' => true,
                    'max' => 100,
                    'ticks' => [
                        'font' => [
                            'family' => 'Tajawal',
                            'size' => 12,
                        ],
                    ],
                ],
            ],
        ];
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
