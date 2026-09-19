<?php

declare(strict_types=1);

namespace App\Filament\Parent\Resources\Children\Pages;

use App\Filament\Parent\Resources\Children\ChildResource;
use App\Services\Student\ChildLoginCredentialService;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Validation\ValidationException;
use InvalidArgumentException;

class EditChild extends EditRecord
{
    protected static string $resource = ChildResource::class;

    private ?string $pendingPin = null;

    protected function mutateFormDataBeforeFill(array $data): array
    {
        $user = auth()->user();
        if ($user !== null) {
            app(ChildLoginCredentialService::class)->ensureFamilyCode($user);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    protected function mutateFormDataBeforeSave(array $data): array
    {
        $this->pendingPin = $this->normalizePendingPin($data['login_pin'] ?? null);
        unset($data['login_pin']);

        return $data;
    }

    protected function afterSave(): void
    {
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
