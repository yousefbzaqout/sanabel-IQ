<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Weekly Progress Summary') }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Figtree',Tahoma,Arial,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(79,70,229,0.12);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#4f46e5,#7c3aed);padding:32px 24px;color:#ffffff;">
                            <h1 style="margin:0;font-size:28px;">{{ __('Weekly Progress Summary') }}</h1>
                            <p style="margin:12px 0 0;font-size:16px;opacity:0.95;">
                                {{ __('Hello :name, here is your children\'s learning progress this week.', ['name' => $parent->name]) }}
                            </p>
                            <p style="margin:8px 0 0;font-size:14px;opacity:0.85;">
                                {{ $summary['period_start'] }} — {{ $summary['period_end'] }}
                            </p>
                        </td>
                    </tr>
                    @foreach ($summary['children'] as $child)
                        <tr>
                            <td style="padding:24px;border-bottom:1px solid #e5e7eb;">
                                <h2 style="margin:0 0 8px;font-size:20px;color:#312e81;">
                                    {{ $child['name'] }} — {{ __('Grade') }} {{ $child['grade_level'] }}
                                </h2>
                                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="margin-top:16px;">
                                    <tr>
                                        <td width="33%" style="padding:12px;background:#eef2ff;border-radius:12px;text-align:center;">
                                            <div style="font-size:24px;font-weight:700;color:#4338ca;">{{ $child['activities_completed'] }}</div>
                                            <div style="font-size:12px;color:#4b5563;">{{ __('Activities') }}</div>
                                        </td>
                                        <td width="8"></td>
                                        <td width="33%" style="padding:12px;background:#fef3c7;border-radius:12px;text-align:center;">
                                            <div style="font-size:24px;font-weight:700;color:#b45309;">{{ $child['xp_earned'] }}</div>
                                            <div style="font-size:12px;color:#4b5563;">XP</div>
                                        </td>
                                        <td width="8"></td>
                                        <td width="33%" style="padding:12px;background:#ecfdf5;border-radius:12px;text-align:center;">
                                            <div style="font-size:24px;font-weight:700;color:#047857;">{{ count($child['badges_unlocked']) }}</div>
                                            <div style="font-size:12px;color:#4b5563;">{{ __('Badges') }}</div>
                                        </td>
                                    </tr>
                                </table>

                                @if (! empty($child['badges_unlocked']))
                                    <p style="margin:16px 0 8px;font-size:14px;font-weight:600;color:#374151;">{{ __('Badges unlocked this week') }}</p>
                                    <ul style="margin:0;padding-right:20px;color:#4b5563;font-size:14px;">
                                        @foreach ($child['badges_unlocked'] as $badge)
                                            <li>{{ $badge['name'] }}</li>
                                        @endforeach
                                    </ul>
                                @endif

                                @if (! empty($child['weak_topics']))
                                    <p style="margin:16px 0 8px;font-size:14px;font-weight:600;color:#b91c1c;">{{ __('Weak areas requiring attention') }}</p>
                                    <ul style="margin:0;padding-right:20px;color:#991b1b;font-size:14px;">
                                        @foreach ($child['weak_topics'] as $topic)
                                            <li>{{ $topic['subject'] }} — {{ $topic['accuracy_percent'] }}%</li>
                                        @endforeach
                                    </ul>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="padding:24px;text-align:center;">
                            <a href="{{ $analyticsUrl }}" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:9999px;font-weight:600;">
                                {{ __('View Full Analytics Dashboard') }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
