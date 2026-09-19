<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\DemoRequestStatus;
use App\Filament\Resources\DemoRequests\Pages\EditDemoRequest;
use App\Filament\Resources\DemoRequests\Pages\ListDemoRequests;
use App\Models\DemoRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Notifications\DemoRequestSubmittedNotification;
use App\Support\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\RateLimiter;
use Livewire\Livewire;
use Tests\TestCase;

class DemoRequestTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        TenantContext::clear();

        parent::tearDown();
    }

    /**
     * @return array<string, mixed>
     */
    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'school_name' => 'مدارس الرياض الأهلية',
            'contact_name' => 'أحمد العتيبي',
            'job_title' => 'مدير مدرسة',
            'phone' => '0512345678',
            'email' => 'principal@riyadh-schools.test',
            'seat_range' => '101-300',
            'notes' => 'نرغب بعرض خلال أسبوعين',
        ], $overrides);
    }

    public function test_valid_submission_creates_demo_request_and_notifies_super_admins(): void
    {
        Notification::fake();

        $superAdmin = User::factory()->admin()->create(['email' => 'super@sanabel.test']);
        $tenantAdmin = User::factory()->tenantAdmin()->create([
            'email' => 'admin@school.test',
            'tenant_id' => Tenant::query()->create([
                'name' => 'مدرسة تجريبية',
                'slug' => 'demo-school',
                'domain' => 'demo-school.sanabel.test',
                'status' => Tenant::STATUS_ACTIVE,
            ])->id,
        ]);

        $response = $this->postJson(route('demo-requests.store'), $this->validPayload());

        $response->assertCreated()
            ->assertJsonPath('message', 'تم استلام طلب العرض التجريبي بنجاح. سيتواصل معكم فريق سنابل IQ قريباً.');

        $this->assertDatabaseHas('demo_requests', [
            'school_name' => 'مدارس الرياض الأهلية',
            'contact_name' => 'أحمد العتيبي',
            'job_title' => 'مدير مدرسة',
            'phone' => '0512345678',
            'email' => 'principal@riyadh-schools.test',
            'seat_range' => '101-300',
            'status' => DemoRequestStatus::New->value,
        ]);

        Notification::assertSentTo($superAdmin, DemoRequestSubmittedNotification::class);
        Notification::assertNotSentTo($tenantAdmin, DemoRequestSubmittedNotification::class);
    }

    public function test_validation_rejects_invalid_email_and_phone(): void
    {
        $response = $this->postJson(route('demo-requests.store'), $this->validPayload([
            'email' => 'not-an-email',
            'phone' => 'abc',
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['email', 'phone']);

        $this->assertDatabaseCount('demo_requests', 0);
    }

    public function test_validation_rejects_invalid_seat_range(): void
    {
        $response = $this->postJson(route('demo-requests.store'), $this->validPayload([
            'seat_range' => '999',
        ]));

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['seat_range']);
    }

    public function test_rate_limiting_blocks_more_than_five_requests_per_minute(): void
    {
        RateLimiter::clear('demo-requests');

        for ($i = 0; $i < 5; $i++) {
            $this->postJson(route('demo-requests.store'), $this->validPayload([
                'email' => "lead{$i}@school.test",
                'phone' => '051234567'.$i,
            ]))->assertCreated();
        }

        $this->postJson(route('demo-requests.store'), $this->validPayload([
            'email' => 'spam@school.test',
            'phone' => '0599999999',
        ]))->assertStatus(429);

        $this->assertDatabaseCount('demo_requests', 5);
    }

    public function test_filament_resource_is_accessible_only_by_super_admin(): void
    {
        $request = DemoRequest::query()->create($this->validPayload());

        $tenant = Tenant::query()->create([
            'name' => 'مدرسة ب',
            'slug' => 'school-b',
            'domain' => 'school-b.sanabel.test',
            'status' => Tenant::STATUS_ACTIVE,
        ]);

        $tenantAdmin = User::factory()->tenantAdmin()->create([
            'email' => 'tenant-admin@school.test',
            'tenant_id' => $tenant->id,
        ]);
        $superAdmin = User::factory()->admin()->create(['email' => 'super-admin@sanabel.test']);

        Livewire::actingAs($tenantAdmin)
            ->test(ListDemoRequests::class)
            ->assertForbidden();

        Livewire::actingAs($tenantAdmin)
            ->test(EditDemoRequest::class, ['record' => $request->getRouteKey()])
            ->assertForbidden();

        Livewire::actingAs($superAdmin)
            ->test(ListDemoRequests::class)
            ->assertSuccessful()
            ->assertCanSeeTableRecords([$request]);
    }

    public function test_super_admin_can_update_status_and_admin_notes(): void
    {
        $request = DemoRequest::query()->create($this->validPayload());
        $superAdmin = User::factory()->admin()->create(['email' => 'ops@sanabel.test']);

        Livewire::actingAs($superAdmin)
            ->test(EditDemoRequest::class, ['record' => $request->getRouteKey()])
            ->assertSuccessful()
            ->fillForm([
                'status' => DemoRequestStatus::DemoScheduled->value,
                'admin_notes' => 'تم تحديد العرض يوم الأحد',
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->assertDatabaseHas('demo_requests', [
            'id' => $request->id,
            'status' => DemoRequestStatus::DemoScheduled->value,
            'admin_notes' => 'تم تحديد العرض يوم الأحد',
        ]);
    }
}
