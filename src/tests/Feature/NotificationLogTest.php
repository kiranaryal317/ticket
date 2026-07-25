<?php

namespace Tests\Feature;

use App\Models\Ticket;
use App\Models\User;
use App\Notifications\TicketAssigned;
use App\Notifications\TicketCreated;
use App\Notifications\TicketStatusChanged;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class NotificationLogTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $staff;
    protected User $admin;
    protected string $logPath;

    protected function setUp(): void
    {
        parent::setUp();

        Role::create(['name' => 'User']);
        Role::create(['name' => 'Staff']);
        Role::create(['name' => 'Admin']);

        $this->user = User::factory()->create(['email' => 'creator@example.com']);
        $this->user->assignRole('User');

        $this->staff = User::factory()->create(['email' => 'staff@example.com']);
        $this->staff->assignRole('Staff');

        $this->admin = User::factory()->create(['email' => 'admin@example.com']);
        $this->admin->assignRole('Admin');

        $this->logPath = storage_path('logs/laravel.log');

        // Reset laravel.log before each test
        File::put($this->logPath, '');
    }

    public function test_ticket_created_notification_is_queued_and_logged(): void
    {
        config(['mail.default' => 'log']);

        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/tickets', [
                'subject' => 'Queued Mail Test Ticket',
                'description' => 'Testing queued email log driver',
                'priority' => 'High',
            ]);

        $response->assertStatus(201);

        $logContent = File::get($this->logPath);

        $this->assertStringContainsString('Subject: New Ticket: Queued Mail Test Ticket', $logContent);
        $this->assertStringContainsString('To: admin@example.com', $logContent);
    }

    public function test_ticket_status_changed_notification_is_queued_and_logged(): void
    {
        config(['mail.default' => 'log']);

        $ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'status' => 'Open',
        ]);

        $response = $this->actingAs($this->staff, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}/status", [
                'status' => 'Resolved',
            ]);

        $response->assertStatus(200);

        $logContent = File::get($this->logPath);

        $this->assertStringContainsString('Subject: Ticket Status Updated: ' . $ticket->subject, $logContent);
        $this->assertStringContainsString('To: creator@example.com', $logContent);
    }

    public function test_ticket_assigned_notification_is_queued_and_logged(): void
    {
        config(['mail.default' => 'log']);

        $ticket = Ticket::factory()->create();

        $response = $this->actingAs($this->admin, 'sanctum')
            ->patchJson("/api/tickets/{$ticket->id}/assign", [
                'assigned_to' => $this->staff->id,
            ]);

        $response->assertStatus(200);

        $logContent = File::get($this->logPath);

        $this->assertStringContainsString('Subject: Ticket Assigned to You: ' . $ticket->subject, $logContent);
        $this->assertStringContainsString('To: staff@example.com', $logContent);
    }
}
