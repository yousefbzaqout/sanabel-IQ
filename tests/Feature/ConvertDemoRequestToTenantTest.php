<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DemoRequestStatus;
use App\Enums\UserRole;
use App\Filament\Resources\DemoRequests\Pages\ListDemoRequests;
use App\Filament\Resources\DemoRequests\Pages\ViewDemoRequest;
use App\Models\DemoRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\TenantDemoWelcomeNotification;
use App\Services\DemoRequestTenantConversionService;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Livewire\Livewire;
use Tests\TestCase;

class ConvertDemoRequestToTenantTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    private function createLead(array $overrides = []): DemoRequest
    {
        return DemoRequest::query()->create(array_merge([
            'school_name' => 'مدارس الرياض الأهلية',
            'contact_name' => 'أحمد العتيبي',
            'job_title' => 'مدير مدرسة',
            'phone' => '0512345678',
            'email' => 'principal@riyadh-schools.test',
            'seat_range' => '101-300',
            'notes' => 'الخطة المختارة: Growth School',
            'status' => DemoRequestStatus::New,
        ], $overrides));
    }

    public function test_conversion_provisions_tenant_domain_admin_and_seat_limit_from_lead(): void
    {
        Notification::fake();

        $lead = $this->createLead();
        $superAdmin = User::factory()->admin()->create(['email' => 'super@sanabel.test']);

        $tenant = app(DemoRequestTenantConversionService::class)->convert($lead, [
            'name' => 'مدارس الرياض الأهلية',
            'slug' => 'riyadh-schools',
            'domain' => 'riyadh-schools.sanabel.test',
            'admin_name' => 'أحمد العتيبي',
            'admin_email' => 'principal@riyadh-schools.test',
            'seat_limit' => 300,
            'password' => 'TempPass123!',
        ]);

        $this->assertInstanceOf(Tenant::class, $tenant);
        $this->assertSame('مدارس الرياض الأهلية', $tenant->name);
        $this->assertSame('riyadh-schools', $tenant->slug);
        $this->assertSame('riyadh-schools.sanabel.test', $tenant->domain);
        $this->assertSame(300, $tenant->seat_limit);
        $this->assertSame(Tenant::STATUS_ACTIVE, $tenant->status);

        $admin = User::query()
            ->withoutTenantScope()
            ->where('email', 'principal@riyadh-schools.test')
            ->first();

        $this->assertNotNull($admin);
        $this->assertSame(UserRole::TenantAdmin, $admin->role);
        $this->assertSame($tenant->id, $admin->tenant_id);
        $this->assertSame('أحمد العتيبي', $admin->name);
        $this->assertTrue(Hash::check('TempPass123!', $admin->password));

        $lead->refresh();
        $this->assertSame(DemoRequestStatus::Converted, $lead->status);
        $this->assertSame($tenant->id, $lead->tenant_id);

        Notification::assertSentTo($admin, TenantDemoWelcomeNotification::class);

        unset($superAdmin);
    }

    public function test_duplicate_conversion_is_prevented(): void
    {
        Notification::fake();

        $lead = $this->createLead();

        app(DemoRequestTenantConversionService::class)->convert($lead, [
            'name' => 'مدارس الرياض الأهلية',
            'slug' => 'riyadh-schools',
            'domain' => 'riyadh-schools.sanabel.test',
            'admin_name' => 'أحمد العتيبي',
            'admin_email' => 'principal@riyadh-schools.test',
            'seat_limit' => 300,
            'password' => 'TempPass123!',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(DemoRequestTenantConversionService::class)->convert($lead->fresh(), [
            'name' => 'مدارس الرياض الأهلية',
            'slug' => 'riyadh-schools-2',
            'domain' => 'riyadh-schools-2.sanabel.test',
            'admin_name' => 'أحمد العتيبي',
            'admin_email' => 'principal2@riyadh-schools.test',
            'seat_limit' => 300,
            'password' => 'TempPass123!',
        ]);

        $this->assertSame(1, Tenant::query()->where('slug', 'like', 'riyadh-schools%')->count());
    }

    public function test_filament_convert_action_provisions_tenant_for_super_admin(): void
    {
        Notification::fake();

        $lead = $this->createLead(['seat_range' => '50-100']);
        $superAdmin = User::factory()->admin()->create(['email' => 'ops@sanabel.test']);

        Livewire::actingAs($superAdmin)
            ->test(ListDemoRequests::class)
            ->callTableAction('convertToTenant', $lead, data: [
                'name' => 'مدارس الرياض الأهلية',
                'slug' => 'riyadh-ahliya',
                'domain' => 'riyadh-ahliya.sanabel.test',
                'admin_name' => 'أحمد العتيبي',
                'admin_email' => 'principal@riyadh-schools.test',
                'seat_limit' => 100,
            ])
            ->assertHasNoTableActionErrors();

        $lead->refresh();
        $this->assertSame(DemoRequestStatus::Converted, $lead->status);
        $this->assertNotNull($lead->tenant_id);

        $tenant = Tenant::query()->findOrFail($lead->tenant_id);
        $this->assertSame('riyadh-ahliya.sanabel.test', $tenant->domain);
        $this->assertSame(100, $tenant->seat_limit);

        $admin = User::query()
            ->withoutTenantScope()
            ->where('email', 'principal@riyadh-schools.test')
            ->firstOrFail();

        Notification::assertSentTo($admin, TenantDemoWelcomeNotification::class);
    }

    public function test_convert_action_hidden_for_already_converted_leads_on_view_page(): void
    {
        $tenant = Tenant::query()->create([
            'name' => 'مدرسة محولة',
            'slug' => 'converted-school',
            'domain' => 'converted.sanabel.test',
            'status' => Tenant::STATUS_ACTIVE,
            'seat_limit' => 50,
        ]);

        $lead = $this->createLead([
            'status' => DemoRequestStatus::Converted,
            'tenant_id' => $tenant->id,
        ]);

        $superAdmin = User::factory()->admin()->create(['email' => 'view@sanabel.test']);

        Livewire::actingAs($superAdmin)
            ->test(ViewDemoRequest::class, ['record' => $lead->getRouteKey()])
            ->assertActionHidden('convertToTenant');
    }
}
