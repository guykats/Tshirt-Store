<?php

namespace Tests\Feature\Api;

use App\Models\SystemEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SystemEventControllerTest extends TestCase
{
    use RefreshDatabase;

    public function test_customers_cannot_view_system_events(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->getJson('/api/system-events')->assertForbidden();
    }

    public function test_guests_cannot_view_system_events(): void
    {
        $this->getJson('/api/system-events')->assertUnauthorized();
    }

    public function test_admins_can_view_system_events(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SystemEvent::log('order.paid', 'Order T-1 paid.', 'Alice', 'user');

        $response = $this->actingAs($admin)->getJson('/api/system-events');

        $response->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_events_can_be_filtered_by_event_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SystemEvent::log('order.paid', 'Order T-1 paid.', 'Alice', 'user');
        SystemEvent::log('design.approved', 'Design "Chai" approved.', 'Bob', 'user');

        $this->actingAs($admin)->getJson('/api/system-events?event_type=order.paid')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.event_type', 'order.paid');
    }

    public function test_events_can_be_filtered_by_actor_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SystemEvent::log('order.paid', 'Order T-1 paid.', 'PayPal', 'system');
        SystemEvent::log('design.approved', 'Design "Chai" approved.', 'Bob', 'user');

        $this->actingAs($admin)->getJson('/api/system-events?actor_type=system')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.actor_type', 'system');
    }

    public function test_events_can_be_filtered_by_combined_event_type_and_actor_type(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SystemEvent::log('order.paid', 'Order T-1 paid via webhook.', 'PayPal', 'system');
        SystemEvent::log('order.paid', 'Order T-2 paid.', 'Carol', 'user');
        SystemEvent::log('design.approved', 'Design "Chai" approved.', 'Bob', 'user');

        $this->actingAs($admin)->getJson('/api/system-events?event_type=order.paid&actor_type=user')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Order T-2 paid.');
    }

    public function test_events_can_be_filtered_by_date_range(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $old = SystemEvent::log('order.paid', 'Old order paid.', 'Alice', 'user');
        $old->created_at = now()->subDays(10);
        $old->save();

        $recent = SystemEvent::log('order.paid', 'Recent order paid.', 'Alice', 'user');
        $recent->created_at = now()->subDay();
        $recent->save();

        $response = $this->actingAs($admin)->getJson('/api/system-events?date_from='.now()->subDays(2)->toDateString());

        $response->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Recent order paid.');
    }

    public function test_events_can_be_searched_by_description(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        SystemEvent::log('order.paid', 'Order ABC-123 paid.', 'Alice', 'user');
        SystemEvent::log('design.approved', 'Design "Chai" approved.', 'Bob', 'user');

        $this->actingAs($admin)->getJson('/api/system-events?search=ABC-123')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.description', 'Order ABC-123 paid.');
    }

    public function test_events_paginate_beyond_page_one(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        for ($i = 1; $i <= 35; $i++) {
            SystemEvent::log('order.paid', "Order {$i} paid.", 'Alice', 'user');
        }

        $firstPage = $this->actingAs($admin)->getJson('/api/system-events');
        $firstPage->assertOk()
            ->assertJsonCount(30, 'data')
            ->assertJsonPath('meta.current_page', 1)
            ->assertJsonPath('meta.last_page', 2)
            ->assertJsonPath('meta.total', 35);

        $secondPage = $this->actingAs($admin)->getJson('/api/system-events?page=2');
        $secondPage->assertOk()
            ->assertJsonCount(5, 'data')
            ->assertJsonPath('meta.current_page', 2);
    }
}
