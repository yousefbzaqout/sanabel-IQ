<?php

declare(strict_types=1);

namespace App\Filament\Resources\DemoRequests\Actions;

use App\Models\DemoRequest;
use App\Services\DemoRequestTenantConversionService;
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Auth;
use Throwable;

final class ConvertDemoRequestToTenantAction
{
    public static function make(): Action
    {
        return Action::make('convertToTenant')
            ->label('تحويل إلى مدرسة تجريبية')
            ->icon(Heroicon::OutlinedBuildingOffice2)
            ->color('success')
            ->visible(function (?DemoRequest $record): bool {
                $user = Auth::user();

                return ($user?->isAdmin() ?? false)
                    && $record instanceof DemoRequest
                    && $record->isConvertible();
            })
            ->modalHeading('تحويل الطلب إلى مدرسة تجريبية')
            ->modalDescription('سيتم إنشاء مستأجر جديد مع حساب مدير مدرسة وإرسال بيانات الدخول المؤقتة.')
            ->modalSubmitActionLabel('إنشاء البيئة التجريبية')
            ->fillForm(function (DemoRequest $record): array {
                return [
                    'name' => $record->school_name,
                    'slug' => $record->suggestedSlug(),
                    'domain' => $record->suggestedDomain(),
                    'admin_name' => $record->contact_name,
                    'admin_email' => $record->email,
                    'seat_limit' => $record->recommendedSeatLimit(),
                ];
            })
            ->form([
                TextInput::make('name')
                    ->label('اسم المدرسة')
                    ->required()
                    ->maxLength(255),
                TextInput::make('slug')
                    ->label('المعرّف الفرعي (slug)')
                    ->required()
                    ->maxLength(64)
                    ->alphaDash(),
                TextInput::make('domain')
                    ->label('النطاق / النطاق الفرعي')
                    ->required()
                    ->maxLength(255),
                TextInput::make('admin_name')
                    ->label('اسم مدير المدرسة')
                    ->required()
                    ->maxLength(255),
                TextInput::make('admin_email')
                    ->label('بريد مدير المدرسة')
                    ->email()
                    ->required()
                    ->maxLength(255),
                TextInput::make('seat_limit')
                    ->label('حد المقاعد الموصى به')
                    ->numeric()
                    ->required()
                    ->minValue(1)
                    ->maxValue(100000),
            ])
            ->action(function (DemoRequest $record, array $data, DemoRequestTenantConversionService $converter): void {
                try {
                    $tenant = $converter->convert($record, [
                        'name' => (string) $data['name'],
                        'slug' => (string) $data['slug'],
                        'domain' => (string) $data['domain'],
                        'admin_name' => (string) $data['admin_name'],
                        'admin_email' => (string) $data['admin_email'],
                        'seat_limit' => (int) $data['seat_limit'],
                    ]);
                } catch (Throwable $exception) {
                    Notification::make()
                        ->title('تعذر تحويل الطلب')
                        ->body($exception->getMessage())
                        ->danger()
                        ->send();

                    return;
                }

                Notification::make()
                    ->title('تم إنشاء المدرسة التجريبية')
                    ->body('تم ربط الطلب بالمدرسة: '.$tenant->name)
                    ->success()
                    ->send();
            });
    }
}
