<?php

declare(strict_types=1);

namespace App\Filament\Parent\Pages;

use Filament\Actions\Action;
use Filament\Pages\Dashboard as BaseDashboard;

class ParentDashboard extends BaseDashboard
{
    protected static ?string $navigationLabel = 'لوحة التحكم';

    protected function getHeaderActions(): array
    {
        $student = auth()->user()?->students()->find(session('active_student_id'));

        if ($student === null) {
            return [];
        }

        return [
            Action::make('exportStudentPdf')
                ->label('تصدير تقرير PDF')
                ->url(route('parent.students.export', [
                    'student' => $student,
                    'format' => 'pdf',
                ]))
                ->openUrlInNewTab(),
            Action::make('exportStudentCsv')
                ->label('تصدير CSV')
                ->url(route('parent.students.export', [
                    'student' => $student,
                    'format' => 'csv',
                ]))
                ->openUrlInNewTab(),
        ];
    }
}
