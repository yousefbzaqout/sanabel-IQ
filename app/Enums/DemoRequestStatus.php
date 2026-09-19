<?php

declare(strict_types=1);

namespace App\Enums;

enum DemoRequestStatus: string
{
    case New = 'new';
    case Contacted = 'contacted';
    case DemoScheduled = 'demo_scheduled';
    case Converted = 'converted';
    case Closed = 'closed';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد',
            self::Contacted => 'تم التواصل',
            self::DemoScheduled => 'عرض مجدول',
            self::Converted => 'تم التحويل',
            self::Closed => 'مغلق',
        };
    }

    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->label();
        }

        return $options;
    }
}
