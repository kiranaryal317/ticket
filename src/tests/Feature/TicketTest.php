<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class TicketTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $staff;
    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'User']);
        Role::create(['name' => 'Staff']);
        Role::create(['name' => 'Admin']);

        $this->user = User::factory()->create();
        $this->user->assignRole('User');

        $this->staff = User::factory()->create();
        $this->staff->assignRole('Staff');

        $this->admin = User::factory()->create();
        $this->admin->assignRole('Admin');
    }

    public function test_user_can_create_ticket(): void
    {
        \Illuminate\Support\Facades\Notification::fake();

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tickets', [
                'subject' => 'Issue with login',
                'description' => 'Cannot reset password via email',
                'priority' => 'High',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.subject', 'Issue with login')
            ->assertJsonPath('data.status', 'Open')
            ->assertJsonPath('data.priority', 'High');

        $this->assertDatabaseHas('tickets', [
            'subject' => 'Issue with login',
            'user_id' => $this->user->id,
        ]);

        \Illuminate\Support\Facades\Notification::assertSentTo(
            $this->admin,
            \App\Notifications\TicketCreated::class
        );
    }

    public function test_user_can_only_see_own_tickets(): void
    {
        Ticket::factory()->create(['user_id' => $this->user->id]);
        $otherUser = User::factory()->create();
        Ticket::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_staff_can_only_see_assigned_tickets(): void
    {
        Ticket::factory()->create(['assigned_to' => $this->staff->id]);
        Ticket::factory()->create(['assigned_to' => null]);

        $response = $this->actingAs($this->staff, 'sanctum')
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data');
    }

    public function test_admin_can_see_all_tickets(): void
    {
        Ticket::factory()->count(3)->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/tickets');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_user_can_view_own_ticket(): void
    {
        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $ticket->id);
    }

    public function test_user_cannot_view_others_ticket(): void
    {
        $otherUser = User::factory()->create();
        $ticket = Ticket::factory()->create(['user_id' => $otherUser->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tickets/{$ticket->id}");

        $response->assertStatus(403);
    }

    public function test_staff_and_admin_can_update_ticket_status(): void
    {
        $ticket = Ticket::factory()->create();

        // Staff update
        $response = $this->actingAs($this->staff, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'In Progress',
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'In Progress');

        // Admin update
        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'Resolved',
            ]);
        $response->assertStatus(200)
            ->assertJsonPath('data.status', 'Resolved');
    }

    public function test_regular_user_cannot_update_ticket_status(): void
    {
        $ticket = Ticket::factory()->create(['user_id' => $this->user->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'Resolved',
            ]);

        $response->assertStatus(403);
    }

    public function test_admin_can_assign_ticket_to_staff(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to' => $this->staff->id,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.assignee.id', $this->staff->id);

        $this->assertDatabaseHas('tickets', [
            'id' => $ticket->id,
            'assigned_to' => $this->staff->id,
        ]);
    }

    public function test_cannot_assign_ticket_to_non_staff_user(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to' => $this->user->id,
            ]);

        $response->assertStatus(422);
    }

    public function test_non_admin_cannot_assign_ticket(): void
    {
        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($this->staff, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to' => $this->staff->id,
            ]);

        $response->assertStatus(403);
    }

    public function test_can_filter_tickets_by_status_and_priority(): void
    {
        Ticket::factory()->create([
            'status' => 'Open',
            'priority' => 'High',
        ]);
        Ticket::factory()->create([
            'status' => 'Resolved',
            'priority' => 'Low',
        ]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson('/api/tickets?status=Open&priority=High');

        $response->assertStatus(200)
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.status', 'Open')
            ->assertJsonPath('data.0.priority', 'High');
    }
}
