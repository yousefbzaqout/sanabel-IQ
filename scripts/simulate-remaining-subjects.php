<?php

declare(strict_types=1);

use App\Livewire\Student\InteractiveLessonDemo;
use App\Livewire\Student\QuizRunner;
use App\Models\LearningMaterial;
use App\Models\Student;
use App\Models\Subject;
use App\Services\Student\LearningMapService;
use Illuminate\Http\Request;
use Livewire\Livewire;

require __DIR__.'/../vendor/autoload.php';
$app = require __DIR__.'/../bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$student = Student::query()->where('name', 'زياد')->firstOrFail();
auth()->login($student->loginUser);
session(['active_student_id' => $student->id]);

$mapSvc = app(LearningMapService::class);
$report = ['subjects' => [], 'perf' => [], 'issues' => []];

$timeGet = static function (string $path): array {
    $t = microtime(true);
    $res = app()->handle(Request::create($path, 'GET'));

    return [
        'status' => $res->getStatusCode(),
        'ms' => round((microtime(true) - $t) * 1000, 1),
        'bytes' => strlen($res->getContent()),
    ];
};

$verifyOptions = static function ($question): ?string {
    $orders = $question->options->sortBy([
        ['order_column', 'asc'],
        ['id', 'asc'],
    ])->pluck('order_column')->all();

    if ($orders === []) {
        return 'no_options';
    }

    if (count($orders) !== count(array_unique($orders))) {
        return 'dup_option_order';
    }

    $correct = $question->options->where('is_correct', true)->count();

    return $correct === 1 ? null : 'correct_count='.$correct;
};

foreach (['MATH', 'ISLAM', 'SOCIAL'] as $code) {
    $subject = Subject::query()->where('code', $code)->where('grade_level', 1)->firstOrFail();
    $mats = $subject->learningMaterials()
        ->published()
        ->where('title', 'not like', 'تجريبي%')
        ->orderBy('order_column')
        ->orderBy('id')
        ->with(['questions.options', 'interactiveLesson'])
        ->get();

    $subjReport = [
        'code' => $code,
        'name' => $subject->name,
        'materials' => [],
        'map_before' => null,
        'map_after' => null,
    ];

    $nodesBefore = collect($mapSvc->buildNodes($student->fresh()))->keyBy('title');
    $subjReport['map_before'] = $nodesBefore[$subject->name]['state'] ?? 'missing';

    foreach ($mats as $mat) {
        $entry = [
            'id' => $mat->id,
            'title' => $mat->title,
            'order' => $mat->order_column,
            'q_count' => $mat->questions->count(),
            'q_orders' => $mat->questions->sortBy([
                ['order_column', 'asc'],
                ['id', 'asc'],
            ])->pluck('order_column')->values()->all(),
            'launch' => $mat->studentLaunchUrl(),
        ];

        $qOrders = $entry['q_orders'];
        if (count($qOrders) !== count(array_unique($qOrders))) {
            $entry['issue'] = 'dup_question_order';
            $report['issues'][] = $mat->title.': dup_question_order';
        }
        if ($qOrders === []) {
            $entry['issue'] = 'no_questions';
            $report['issues'][] = $mat->title.': no_questions';
        }

        foreach ($mat->questions as $q) {
            $optIssue = $verifyOptions($q);
            if ($optIssue !== null) {
                $entry['issue'] = 'q'.$q->id.':'.$optIssue;
                $report['issues'][] = $mat->title.' q'.$q->id.':'.$optIssue;
            }
        }

        $path = parse_url($entry['launch'], PHP_URL_PATH) ?: '/';
        $perf = $timeGet($path);
        $entry['launch_perf'] = $perf;
        $report['perf'][] = array_merge(['path' => $path], $perf);

        if ($perf['status'] !== 200) {
            $entry['issue'] = 'launch_status_'.$perf['status'];
            $report['issues'][] = $mat->title.': launch '.$perf['status'];
        }
        if ($perf['ms'] > 500) {
            $report['issues'][] = $mat->title.': slow_launch '.$perf['ms'].'ms';
        }

        $lesson = $mat->interactiveLesson;
        if ($lesson !== null && $lesson->status === 'published') {
            $entry['lesson_key'] = $lesson->lesson_key;
            $entry['stations'] = $lesson->stations()->count();
            try {
                $lw = Livewire::test(InteractiveLessonDemo::class, ['lessonKey' => $lesson->lesson_key]);
                for ($s = 1; $s <= 6; $s++) {
                    $lw->call('goToStation', $s);
                    if ((int) $lw->get('currentStation') !== $s) {
                        $report['issues'][] = $lesson->lesson_key.': station_jump_failed_'.$s;
                    }
                }
                $entry['stations_ok'] = true;
            } catch (Throwable $e) {
                $entry['stations_ok'] = false;
                $report['issues'][] = $lesson->lesson_key.': '.$e->getMessage();
            }
        }

        if ($mat->questions->isNotEmpty()) {
            $sorted = $mat->questions->sortBy([
                ['order_column', 'asc'],
                ['id', 'asc'],
            ])->values();

            try {
                $c = Livewire::test(QuizRunner::class, [
                    'learningMaterialId' => $mat->id,
                    'studentId' => $student->id,
                ]);
                $seq = [];
                foreach ($sorted as $idx => $q) {
                    $cur = $c->instance()->currentQuestion;
                    $seq[] = $cur?->id;
                    if ($cur?->id !== $q->id) {
                        $report['issues'][] = $mat->title.': quiz_order_mismatch at '.$idx;
                        break;
                    }
                    $correct = $q->options->firstWhere('is_correct', true);
                    $c->call('selectAnswer', $correct->id);
                    $c->call('advanceAfterFeedback');
                }
                $c->assertRedirect(route('student.quiz.completion', $mat));
                $entry['quiz_ok'] = true;
                $entry['quiz_seq'] = $seq;

                session([
                    'quiz_celebration' => [
                        'learning_material_id' => $mat->id,
                        'xp_earned' => 10,
                        'percentage' => 100,
                        'streak_days' => 1,
                        'badge_ids' => [],
                    ],
                ]);
                $cel = $timeGet(route('student.quiz.completion', $mat, false));
                $entry['celebration_perf'] = $cel;
                $report['perf'][] = array_merge(['path' => '/completion/'.$mat->id], $cel);
                if ($cel['status'] !== 200) {
                    $report['issues'][] = $mat->title.': celebration '.$cel['status'];
                }
            } catch (Throwable $e) {
                $entry['quiz_ok'] = false;
                $report['issues'][] = $mat->title.': quiz '.$e->getMessage();
            }
        }

        $subjReport['materials'][] = $entry;
    }

    $nodesAfter = collect($mapSvc->buildNodes($student->fresh()))->keyBy('title');
    $subjReport['map_after'] = $nodesAfter[$subject->name]['state'] ?? 'missing';
    $subjReport['xp'] = $student->fresh()->total_xp;
    $report['subjects'][] = $subjReport;
}

$report['final_map'] = $mapSvc->buildNodes($student->fresh());
$report['final_xp'] = $student->fresh()->total_xp;
$perfCollection = collect($report['perf']);
$report['perf_summary'] = [
    'avg_ms' => round((float) $perfCollection->avg('ms'), 1),
    'max_ms' => $perfCollection->max('ms'),
    'pages' => $perfCollection->count(),
];

echo json_encode($report, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT).PHP_EOL;
