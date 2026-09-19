<?php

declare(strict_types=1);

namespace App\Services\Ai;

final class LetterStrokeDirectionAnalyzer
{
    /**
     * Score a finger-trace trajectory using optional station guide metadata.
     *
     * @param  list<array{x: float|int, y: float|int}>  $points
     * @param  array{
     *     direction?: string,
     *     tolerance_px?: int,
     *     checkpoints?: list<array<string, mixed>>,
     *     path_d?: string,
     *     feedback_retry?: string,
     *     feedback_match?: string
     * }  $guide
     * @return array{result: string, score: int, direction_ok: bool, feedback: string}
     */
    public function score(array $points, array $guide = []): array
    {
        $direction = (string) ($guide['direction'] ?? 'top_to_bottom_arc');
        $tolerance = max(8, (int) ($guide['tolerance_px'] ?? 18));
        $retryFeedback = (string) ($guide['feedback_retry'] ?? $this->defaultRetryFeedback($direction));
        $matchFeedback = (string) ($guide['feedback_match'] ?? 'رائع! اتجاه الرسم صحيح');

        if (count($points) < 4) {
            return [
                'result' => 'retry',
                'score' => 0,
                'direction_ok' => false,
                'feedback' => 'ارسم خطاً أطول قليلاً',
            ];
        }

        $xs = [];
        $ys = [];
        foreach ($points as $point) {
            if (! isset($point['x'], $point['y'])) {
                continue;
            }
            $xs[] = (float) $point['x'];
            $ys[] = (float) $point['y'];
        }

        if (count($xs) < 4) {
            return [
                'result' => 'retry',
                'score' => 0,
                'direction_ok' => false,
                'feedback' => 'لم أتعرف على المسار',
            ];
        }

        $startY = $ys[0];
        $midY = $ys[(int) floor(count($ys) / 2)];
        $endY = $ys[count($ys) - 1];
        $maxY = max($ys);
        $minY = min($ys);
        $verticalSpan = $maxY - $minY;
        $horizontalSpan = max($xs) - min($xs);

        [$directionOk, $spanOk] = $this->evaluateDirection(
            $direction,
            $startY,
            $midY,
            $endY,
            $verticalSpan,
            $horizontalSpan,
            $tolerance,
        );

        $checkpointBonus = $this->checkpointBonus($ys, $guide['checkpoints'] ?? [], $tolerance);

        $score = 0;
        if ($directionOk) {
            $score += 55;
        }
        if ($spanOk) {
            $score += 25;
        }
        if ($endY >= $startY) {
            $score += 15;
        }
        if (count($xs) >= 6) {
            $score += 5;
        }
        $score += $checkpointBonus;

        $score = min(100, $score);
        $result = ($directionOk && $score >= 70) ? 'match' : 'retry';

        return [
            'result' => $result,
            'score' => $score,
            'direction_ok' => $directionOk,
            'feedback' => $result === 'match' ? $matchFeedback : $retryFeedback,
        ];
    }

    /**
     * @return array{0: bool, 1: bool}
     */
    private function evaluateDirection(
        string $direction,
        float $startY,
        float $midY,
        float $endY,
        float $verticalSpan,
        float $horizontalSpan,
        int $tolerance,
    ): array {
        $dropThreshold = max(8, (int) round($tolerance * 0.45));
        $depthThreshold = max(20, (int) round($tolerance * 1.1));

        return match ($direction) {
            'loop' => [
                $verticalSpan >= $dropThreshold
                    && $horizontalSpan >= $dropThreshold
                    && abs($endY - $startY) <= ($tolerance * 2),
                $verticalSpan >= 25 && $horizontalSpan >= 25,
            ],
            'top_to_bottom_double_curve', 'top_to_bottom_arc', 'top_to_bottom' => [
                ($midY > $startY + $dropThreshold) && ($verticalSpan >= $depthThreshold),
                $verticalSpan >= max(30, $tolerance) && $horizontalSpan >= max(15, (int) round($tolerance * 0.8)),
            ],
            default => [
                ($midY > $startY + 8) && ($verticalSpan >= 20),
                $verticalSpan >= 35 && $horizontalSpan >= 20,
            ],
        };
    }

    /**
     * @param  list<float>  $ys
     * @param  list<array<string, mixed>>  $checkpoints
     */
    private function checkpointBonus(array $ys, array $checkpoints, int $tolerance): int
    {
        if ($checkpoints === [] || $ys === []) {
            return 0;
        }

        $minY = min($ys);
        $maxY = max($ys);
        $span = max(1.0, $maxY - $minY);
        $hits = 0;

        foreach ($checkpoints as $checkpoint) {
            $t = (float) ($checkpoint['t'] ?? -1);
            if ($t < 0.0 || $t > 1.0) {
                continue;
            }

            $expectedY = $minY + ($span * $t);
            foreach ($ys as $y) {
                if (abs($y - $expectedY) <= $tolerance) {
                    $hits++;
                    break;
                }
            }
        }

        return min(10, $hits * 3);
    }

    private function defaultRetryFeedback(string $direction): string
    {
        return match ($direction) {
            'top_to_bottom_double_curve' => 'ابدأ من الأعلى: قوس ثم قوس لكتابة العدد 3',
            'loop' => 'ارسم حلقة كاملة بهدوء',
            default => 'ابدأ من الأعلى وانزل بقوس المسار',
        };
    }
}
