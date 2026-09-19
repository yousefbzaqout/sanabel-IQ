<?php

declare(strict_types=1);

namespace App\Support\Charts;

/**
 * Builds SVG stroke/area paths for 0–100% trend series.
 */
final class SvgTrendPath
{
    /**
     * @param  list<int|null>  $values  Percent points (null = gap / no sample that day)
     * @return array{stroke: string, area: string}
     */
    public static function fromPercents(
        array $values,
        float $width = 680.0,
        float $height = 220.0,
        float $padX = 40.0,
        float $padTop = 20.0,
        float $padBottom = 20.0,
    ): array {
        $plottable = array_values(array_filter(
            $values,
            static fn (int|null $value): bool => $value !== null,
        ));

        if ($plottable === []) {
            return ['stroke' => '', 'area' => ''];
        }

        $count = count($values);
        $innerWidth = max(1.0, $width - (2 * $padX));
        $innerHeight = max(1.0, $height - $padTop - $padBottom);
        $baselineY = $height - $padBottom;

        $points = [];
        foreach ($values as $index => $value) {
            if ($value === null) {
                continue;
            }

            $x = $padX + ($count === 1 ? $innerWidth / 2 : ($index / ($count - 1)) * $innerWidth);
            $clamped = max(0, min(100, $value));
            $y = $padTop + ((100 - $clamped) / 100) * $innerHeight;
            $points[] = ['x' => round($x, 2), 'y' => round($y, 2)];
        }

        if ($points === []) {
            return ['stroke' => '', 'area' => ''];
        }

        $stroke = '';
        foreach ($points as $index => $point) {
            $stroke .= ($index === 0 ? 'M ' : ' L ').$point['x'].','.$point['y'];
        }

        $first = $points[0];
        $last = $points[array_key_last($points)];
        $area = $stroke.' L '.$last['x'].','.$baselineY.' L '.$first['x'].','.$baselineY.' Z';

        return [
            'stroke' => $stroke,
            'area' => $area,
        ];
    }
}
