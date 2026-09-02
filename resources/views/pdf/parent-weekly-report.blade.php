<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
    <title>{{ __('Sanabel IQ Progress Report') }} — {{ $student->name }}</title>
    <style>
        @font-face {
            font-family: 'Amiri';
            font-style: normal;
            font-weight: 400;
            src: url('{{ storage_path('fonts/Amiri-Regular.ttf') }}') format('truetype');
        }

        body {
            font-family: 'Amiri', 'DejaVu Sans', sans-serif;
            color: #111827;
            margin: 24px;
            direction: rtl;
            text-align: right;
        }

        .brand {
            color: #4338ca;
            font-size: 28px;
            margin-bottom: 4px;
        }

        .subtitle {
            color: #6b7280;
            margin-bottom: 20px;
        }

        .summary {
            width: 100%;
            margin: 16px 0;
        }

        .summary td {
            width: 25%;
            background: #eef2ff;
            border: 1px solid #c7d2fe;
            border-radius: 8px;
            padding: 12px;
            text-align: center;
        }

        .summary strong {
            display: block;
            font-size: 22px;
            color: #4338ca;
        }

        table.data {
            width: 100%;
            border-collapse: collapse;
            margin-top: 12px;
        }

        table.data th,
        table.data td {
            border: 1px solid #e5e7eb;
            padding: 8px;
            font-size: 13px;
        }

        table.data th {
            background: #f9fafb;
        }

        h2 {
            color: #312e81;
            margin-top: 24px;
        }
    </style>
</head>
<body>
    <div class="brand">Sanabel IQ</div>
    <div class="subtitle">
        {{ __('Weekly Progress Report') }} — {{ $student->name }} ({{ __('Grade') }} {{ $student->grade_level }})
    </div>
    <p>{{ __('Period') }}: {{ $summary->startDate->toDateString() }} — {{ $summary->endDate->toDateString() }}</p>

    <table class="summary">
        <tr>
            <td><strong>{{ $summary->xpEarnedInPeriod }}</strong>{{ __('XP Earned') }}</td>
            <td><strong>{{ $summary->quizAccuracyPercent }}%</strong>{{ __('Quiz Accuracy') }}</td>
            <td><strong>{{ $summary->completedGoalsCount }}</strong>{{ __('Completed Goals') }}</td>
            <td><strong>{{ $summary->currentStreak }}</strong>{{ __('Active Streak') }}</td>
        </tr>
    </table>

    <h2>{{ __('Badge Trophies') }}</h2>
    <ul>
        @forelse ($summary->badgesUnlocked as $badge)
            <li>{{ $badge['name'] }}</li>
        @empty
            <li>{{ __('No badges unlocked in this period.') }}</li>
        @endforelse
    </ul>

    <h2>{{ __('Parent Goal Checklist') }}</h2>
    <ul>
        @forelse ($summary->parentGoals as $goal)
            <li>{{ $goal['title'] }} — {{ __($goal['status']) }}</li>
        @empty
            <li>{{ __('No goals configured yet.') }}</li>
        @endforelse
    </ul>

    <h2>{{ __('Quiz History') }}</h2>
    <table class="data">
        <thead>
            <tr>
                <th>{{ __('Quiz') }}</th>
                <th>{{ __('Completed At') }}</th>
                <th>{{ __('Score %') }}</th>
                <th>{{ __('XP') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($summary->quizHistory as $attempt)
                <tr>
                    <td>{{ $attempt['title'] }}</td>
                    <td>{{ $attempt['completed_at'] }}</td>
                    <td>{{ $attempt['score_percentage'] }}%</td>
                    <td>{{ $attempt['xp_earned'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="4">{{ __('No quiz attempts in this period.') }}</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
