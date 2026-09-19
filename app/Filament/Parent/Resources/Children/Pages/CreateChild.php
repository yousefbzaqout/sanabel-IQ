<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Children\Pages;

use App\Filament\Parent\Resources\Children\ChildResource;
use App\Models\Tenant;
use App\Services\Student\ChildLoginCredentialService;
use App\Services\Tenancy\TenantSeatLimitService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class CreateChild extends CreateRecord
{
    protected static string $resource = ChildResource::class;

    private ?string $pendingPin = null;

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $user = auth()->user();
        if ($user?->tenant_id !== null) {
            $tenant = Tenant::query()->find($user->tenant_id);
            if ($tenant !== null) {
                app(TenantSeatLimitService::class)->ensureCanAddStudent($tenant);
            }
        }

        if ($user !== null) {
            app(ChildLoginCredentialService::class)->ensureFamilyCode($user);
        }

        $this->pendingPin = $this->normalizePendingPin($data['login_pin'] ?? null);
        unset($data['login_pin']);

        $data['user_id'] = $user?->id;

        return $data;
    }

    protected function afterCreate(): void
    {
        if (session('active_student_id') === null) {
            session(['active_student_id' => $this->record->id]);
        }

        if ($this->pendingPin === null) {
            return;
        }

        try {
            app(ChildLoginCredentialService::class)->setPin($this->record->fresh(), $this->pendingPin);
            $familyCode = auth()->user()?->fresh()?->family_code ?? '—';

            Notification::make()
                ->title('تم تفعيل دخول الطالب')
                ->body('رمز PIN: '.$this->pendingPin.' — احفظه الآن. رمز العائلة: '.$familyCode)
                ->success()
                ->persistent()
                ->send();
        } catch (InvalidArgumentException $exception) {
            throw ValidationException::withMessages([
                'login_pin' => $exception->getMessage(),
            ]);
        } finally {
            $this->pendingPin = null;
        }
    }

    private function normalizePendingPin(mixed $pin): ?string
    {
        if (is_int($pin) || is_float($pin)) {
            $pin = (string) $pin;
        }

        if (! is_string($pin)) {
            return null;
        }

        $pin = trim($pin);

        return $pin !== '' ? $pin : null;
    }
}
