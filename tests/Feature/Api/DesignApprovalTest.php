<?php

namespace Tests\Feature\Api;

use App\Models\Design;
use App\Models\SystemEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class DesignApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_cannot_view_the_designs_queue(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->getJson('/api/designs')->assertForbidden();
    }

    public function test_admins_can_view_the_designs_queue(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Design::create(['title' => 'Pending Design', 'status' => 'pending_approval']);

        $response = $this->actingAs($admin)->getJson('/api/designs');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_customers_cannot_approve_a_design(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $design = Design::create(['title' => 'Pending Design', 'status' => 'pending_approval']);

        $this->actingAs($customer)->postJson("/api/designs/{$design->id}/approve")->assertForbidden();
        $this->assertDatabaseHas('designs', ['id' => $design->id, 'status' => 'pending_approval']);
    }

    public function test_an_admin_can_approve_a_design_and_it_is_logged(): void
    {
        $admin = User::factory()->create(['role' => 'admin', 'name' => 'Approving Admin']);
        $design = Design::create(['title' => 'Pending Design', 'status' => 'pending_approval']);

        $response = $this->actingAs($admin)->postJson("/api/designs/{$design->id}/approve");

        $response->assertOk()->assertJsonPath('data.status', 'approved');
        $this->assertDatabaseHas('designs', [
            'id' => $design->id,
            'status' => 'approved',
            'approved_by' => $admin->id,
        ]);
        $this->assertDatabaseHas('system_events', [
            'event_type' => 'design.approved',
            'actor_name' => 'Approving Admin',
        ]);
    }

    public function test_an_admin_can_reject_a_design_with_a_reason(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $design = Design::create(['title' => 'Pending Design', 'status' => 'pending_approval']);

        $response = $this->actingAs($admin)->postJson("/api/designs/{$design->id}/reject", [
            'rejection_reason' => 'Too busy for the brand.',
        ]);

        $response->assertOk()->assertJsonPath('data.status', 'rejected');
        $this->assertDatabaseHas('designs', [
            'id' => $design->id,
            'status' => 'rejected',
            'rejection_reason' => 'Too busy for the brand.',
        ]);
    }
}
