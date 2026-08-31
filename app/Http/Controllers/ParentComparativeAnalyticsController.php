<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Analytics\ComparativeAnalyticsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentComparativeAnalyticsController extends Controller
{
    public function index(Request $request, ComparativeAnalyticsService $comparativeAnalytics): View
    {
        $parent = $request->user();
        $children = $comparativeAnalytics->compareChildren($parent);

        return view('parent.comparative-analytics.index', [
            'children' => $children,
            'chartLabels' => collect($children)->pluck('student.name')->all(),
            'accuracyValues' => collect($children)->pluck('overall_accuracy_percent')->all(),
            'xpValues' => collect($children)->pluck('total_xp')->all(),
        ]);
    }
}
