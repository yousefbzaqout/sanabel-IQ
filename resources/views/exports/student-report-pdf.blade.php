<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Student Progress Report') }} — {{ $student->name }}</title>
    <style>
        body { font-family: Tahoma, Arial, sans-serif; color: #111827; margin: 24px; }
        h1, h2 { color: #312e81; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; }
        th, td { border: 1px solid #e5e7eb; padding: 8px; text-align: right; font-size: 14px; }
        th { background: #eef2ff; }
        .summary { display: grid; grid-template-columns: repeat(3, 1fr); gap: 12px; margin: 16px 0; }
        .card { background: #f9fafb; border: 1px solid #e5e7eb; border-radius: 12px; padding: 12px; text-align: center; }
        .card strong { display: block; font-size: 24px; color: #4338ca; }
    </style>
</head>
<body>
    <h1>{{ __('Academic Progress Report') }}</h1>
    <p>{{ $student->name }} — {{ __('Grade') }} {{ $student->grade_level }}</p>

    <div class="summary">
        <div class="card"><strong>{{ $student->total_xp }}</strong> XP</div>
        <div class="card"><strong>{{ $analysis['overall_accuracy_percent'] }}%</strong> {{ __('Accuracy') }}</div>
        <div class="card"><strong>{{ $analysis['total_questions_attempted'] }}</strong> {{ __('Questions') }}</div>
    </div>

    <h2>{{ __('Activity Attempts') }}</h2>
    <table>
        <thead>
            <tr>
                <th>{{ __('Completed At') }}</th>
                <th>{{ __('Score') }}</th>
                <th>{{ __('Total') }}</th>
                <th>XP</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($attempts as $attempt)
                <tr>
                    <td>{{ $attempt->completed_at?->toDateTimeString() }}</td>
                    <td>{{ $attempt->score }}</td>
                    <td>{{ $attempt->total_questions }}</td>
                    <td>{{ $attempt->xp_earned }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <h2>{{ __('Badges') }}</h2>
    <ul>
        @forelse ($badges as $badge)
            <li>{{ $badge['name'] }} @if($badge['unlocked_at']) — {{ $badge['unlocked_at'] }} @endif</li>
        @empty
            <li>{{ __('No badges earned yet.') }}</li>
        @endforelse
    </ul>
</body>
</html>
