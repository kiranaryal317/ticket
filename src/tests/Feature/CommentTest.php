<?php

namespace Tests\Feature;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class CommentTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected User $staff;
    protected User $admin;
    protected User $otherUser;
    protected Ticket $ticket;

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

        $this->otherUser = User::factory()->create();
        $this->otherUser->assignRole('User');

        // Ticket created by $this->user and assigned to $this->staff
        $this->ticket = Ticket::factory()->create([
            'user_id' => $this->user->id,
            'assigned_to' => $this->staff->id,
        ]);
    }

    public function test_ticket_creator_can_view_comments(): void
    {
        Comment::factory()->count(2)->create(['ticket_id' => $this->ticket->id]);

        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/tickets/{$this->ticket->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_assigned_staff_can_view_comments(): void
    {
        Comment::factory()->count(2)->create(['ticket_id' => $this->ticket->id]);

        $response = $this->actingAs($this->staff, 'sanctum')
            ->getJson("/api/tickets/{$this->ticket->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_admin_can_view_comments(): void
    {
        Comment::factory()->count(2)->create(['ticket_id' => $this->ticket->id]);

        $response = $this->actingAs($this->admin, 'sanctum')
            ->getJson("/api/tickets/{$this->ticket->id}/comments");

        $response->assertStatus(200)
            ->assertJsonCount(2, 'data');
    }

    public function test_unauthorized_user_cannot_view_comments(): void
    {
        Comment::factory()->create(['ticket_id' => $this->ticket->id]);

        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->getJson("/api/tickets/{$this->ticket->id}/comments");

        $response->assertStatus(403);
    }

    public function test_ticket_creator_can_post_comment(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/comments", [
                'body' => 'Adding additional details to the ticket',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'Adding additional details to the ticket')
            ->assertJsonPath('data.author.id', $this->user->id);

        $this->assertDatabaseHas('comments', [
            'ticket_id' => $this->ticket->id,
            'user_id' => $this->user->id,
            'body' => 'Adding additional details to the ticket',
        ]);
    }

    public function test_assigned_staff_can_post_comment(): void
    {
        $response = $this->actingAs($this->staff, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/comments", [
                'body' => 'Working on this issue currently.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'Working on this issue currently.')
            ->assertJsonPath('data.author.id', $this->staff->id);
    }

    public function test_admin_can_post_comment(): void
    {
        $response = $this->actingAs($this->admin, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/comments", [
                'body' => 'Admin note regarding this ticket.',
            ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.body', 'Admin note regarding this ticket.')
            ->assertJsonPath('data.author.id', $this->admin->id);
    }

    public function test_unauthorized_user_cannot_post_comment(): void
    {
        $response = $this->actingAs($this->otherUser, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/comments", [
                'body' => 'Unauthorized comment attempt.',
            ]);

        $response->assertStatus(403);
    }

    public function test_comment_requires_body(): void
    {
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson("/api/tickets/{$this->ticket->id}/comments", [
                'body' => '',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['body']);
    }
}
