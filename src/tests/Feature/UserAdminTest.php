<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class UserAdminTest extends TestCase
{
    use RefreshDatabase;

    protected Role $adminRole;
    protected Role $userRole;

    protected function setUp(): void
    {
        parent::setUp();
        $this->userRole = Role::create(['name' => 'User']);
        $this->adminRole = Role::create(['name' => 'Admin']);
    }

    public function test_admin_can_list_users(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        User::factory()->count(3)->create();

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/users');

        $response->assertStatus(200)
            ->assertJsonCount(4, 'data');
    }

    public function test_non_admin_cannot_list_users(): void
    {
        $regularUser = User::factory()->create();
        $regularUser->assignRole('User');

        $response = $this->actingAs($regularUser, 'sanctum')
            ->getJson('/api/users');

        $response->assertStatus(403);
    }

    public function test_admin_can_assign_role(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $targetUser = User::factory()->create();
        $targetUser->assignRole('User');

        $response = $this->actingAs($admin, 'sanctum')
            ->patchJson("/api/users/{$targetUser->id}/role", [
                'role' => 'Admin',
            ]);

        $response->assertStatus(200);
        $this->assertTrue($targetUser->fresh()->hasRole('Admin'));
    }

    public function test_admin_can_fetch_roles_list(): void
    {
        $admin = User::factory()->create();
        $admin->assignRole('Admin');

        $response = $this->actingAs($admin, 'sanctum')
            ->getJson('/api/roles');

        $response->assertStatus(200);
        $this->assertEqualsCanonicalizing(['User', 'Admin'], $response->json());
    }
}
