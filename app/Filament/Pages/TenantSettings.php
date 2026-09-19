<?php

declare(strict_types=1);

namespace App\Filament\Pages;

use App\Models\Tenant;
use App\Support\Tenancy\FilamentTenantSynchronizer;
use App\Support\Tenancy\TenantContext;
use BackedEnum;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

/**
 * @property-read Schema $form
 */
class TenantSettings extends Page implements HasForms
{
    use InteractsWithForms;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'إعدادات المدرسة';

    protected static ?string $title = 'إعدادات وهوية المدرسة';

    protected static ?int $navigationSort = 90;

    protected string $view = 'filament.pages.tenant-settings';

    /**
     * @var array<string, mixed>|null
     */
    public ?array $data = [];

    public ?Tenant $tenant = null;

    public function mount(): void
    {
        FilamentTenantSynchronizer::syncFromAuth();

        $this->tenant = TenantContext::tenant()
            ?? (auth()->user()?->tenant_id ? Tenant::query()->find(auth()->user()->tenant_id) : null)
            ?? Tenant::query()->first();

        abort_unless($this->tenant !== null, 404, 'No active tenant found.');

        $this->form->fill([
            'name' => $this->tenant->name,
            'slug' => $this->tenant->slug,
            'domain' => $this->tenant->domain,
            'logo_path' => $this->tenant->logo_path,
            'primary_color' => $this->tenant->primary_color,
            'contact_email' => $this->tenant->contact_email,
            'contact_phone' => $this->tenant->contact_phone,
            'seat_limit' => $this->tenant->seat_limit,
            'subscription_plan' => $this->tenant->subscription_plan,
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('معلومات المدرسة والهوية البصرية')
                    ->description('إدارة هوية المدرسة، الشعار، الألوان ونطاق الدخول')
                    ->schema([
                        TextInput::make('name')
                            ->label('اسم المدرسة')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('slug')
                            ->label('المعرف الفرعي (Subdomain slug)')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('domain')
                            ->label('النطاق المخصص (Custom Domain)')
                            ->maxLength(255),
                        TextInput::make('logo_path')
                            ->label('رابط الشعار (Logo URL)')
                            ->maxLength(500),
                        TextInput::make('primary_color')
                            ->label('اللون الأساسي (Hex Code)')
                            ->maxLength(32),
                    ])->columns(2),

                Section::make('معلومات التواصل والاشتراك')
                    ->description('بيانات التواصل الرسمية وسعة المقاعد')
                    ->schema([
                        TextInput::make('contact_email')
                            ->label('البريد الإلكتروني للتواصل')
                            ->email()
                            ->maxLength(255),
                        TextInput::make('contact_phone')
                            ->label('هاتف التواصل')
                            ->maxLength(64),
                        TextInput::make('seat_limit')
                            ->label('سعة مقاعد الطلاب المخصصة')
                            ->numeric()
                            ->disabled(fn (): bool => ! (auth()->user()?->isAdmin() ?? false)),
                        TextInput::make('subscription_plan')
                            ->label('خطة الاشتراك')
                            ->disabled(fn (): bool => ! (auth()->user()?->isAdmin() ?? false)),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        if ($this->tenant === null) {
            $this->tenant = TenantContext::tenant()
                ?? (auth()->user()?->tenant_id ? Tenant::query()->find(auth()->user()->tenant_id) : null)
                ?? Tenant::query()->first();
        }

        abort_unless($this->tenant !== null, 404);

        $state = $this->form->getState();

        $updateData = [
            'name' => $state['name'],
            'slug' => $state['slug'],
            'domain' => $state['domain'] ?? null,
            'logo_path' => $state['logo_path'] ?? null,
            'primary_color' => $state['primary_color'] ?? null,
            'contact_email' => $state['contact_email'] ?? null,
            'contact_phone' => $state['contact_phone'] ?? null,
        ];

        if (auth()->user()?->isAdmin() ?? false) {
            if (isset($state['seat_limit'])) {
                $updateData['seat_limit'] = (int) $state['seat_limit'];
            }
            if (isset($state['subscription_plan'])) {
                $updateData['subscription_plan'] = (string) $state['subscription_plan'];
            }
        }

        $this->tenant->update($updateData);

        Notification::make()
            ->title('تم حفظ إعدادات المدرسة بنجاح')
            ->success()
            ->send();
    }

    public static function canAccess(): bool
    {
        $user = auth()->user();

        return $user !== null && $user->canAccessAdminPanel();
    }
}
