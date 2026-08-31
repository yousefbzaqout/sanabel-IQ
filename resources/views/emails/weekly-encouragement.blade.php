<!DOCTYPE html>
<html lang="ar" dir="rtl">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ __('Encourage Your Child to Start Learning') }}</title>
</head>
<body style="margin:0;padding:0;background:#f3f4f6;font-family:'Figtree',Tahoma,Arial,sans-serif;color:#111827;">
    <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="background:#f3f4f6;padding:24px 0;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:640px;background:#ffffff;border-radius:16px;overflow:hidden;box-shadow:0 10px 30px rgba(79,70,229,0.12);">
                    <tr>
                        <td style="background:linear-gradient(135deg,#0ea5e9,#6366f1);padding:32px 24px;color:#ffffff;">
                            <h1 style="margin:0;font-size:28px;">{{ __('حفّز طفلك لبدء التعلم') }}</h1>
                            <p style="margin:12px 0 0;font-size:16px;opacity:0.95;">
                                {{ __('Hello :name, your children have not completed any activities this week yet.', ['name' => $parent->name]) }}
                            </p>
                        </td>
                    </tr>
                    @foreach ($summary['children'] as $child)
                        <tr>
                            <td style="padding:20px 24px;border-bottom:1px solid #e5e7eb;">
                                <p style="margin:0;font-size:18px;font-weight:600;color:#312e81;">
                                    {{ $child['name'] }} — {{ __('Grade') }} {{ $child['grade_level'] }}
                                </p>
                                <p style="margin:8px 0 0;font-size:14px;color:#6b7280;">
                                    {{ __('No activities completed this week. Start with a short quiz together!') }}
                                </p>
                            </td>
                        </tr>
                    @endforeach
                    <tr>
                        <td style="padding:24px;text-align:center;">
                            <a href="{{ $analyticsUrl }}" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;padding:14px 28px;border-radius:9999px;font-weight:600;">
                                {{ __('Explore Activities') }}
                            </a>
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
