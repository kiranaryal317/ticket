<?php

namespace Database\Seeders;

use App\Models\Comment;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            RoleSeeder::class,
            AdminSeeder::class,
        ]);

        // Seed Staff user
        $staff = User::firstOrCreate(
            ['email' => 'staff@example.com'],
            [
                'name' => 'Staff Member',
                'password' => Hash::make('password123'),
            ]
        );
        $staff->assignRole('Staff');

        // Seed regular User
        $user = User::firstOrCreate(
            ['email' => 'user@example.com'],
            [
                'name' => 'John Client',
                'password' => Hash::make('password123'),
            ]
        );
        $user->assignRole('User');

        // Seed sample tickets
        $ticket1 = Ticket::create([
            'subject' => 'Cannot reset password via email link',
            'description' => 'When I click on the password reset link sent to my inbox, it says token expired.',
            'status' => 'Open',
            'priority' => 'High',
            'user_id' => $user->id,
            'assigned_to' => $staff->id,
        ]);

        $ticket2 = Ticket::create([
            'subject' => 'Dashboard widgets taking too long to load',
            'description' => 'The metrics on the main dashboard take around 10 seconds to load in Chrome.',
            'status' => 'In Progress',
            'priority' => 'Medium',
            'user_id' => $user->id,
            'assigned_to' => $staff->id,
        ]);

        $ticket3 = Ticket::create([
            'subject' => 'Billing invoice generation error',
            'description' => 'Exporting PDF invoice fails with 500 error.',
            'status' => 'Resolved',
            'priority' => 'Low',
            'user_id' => $user->id,
            'assigned_to' => null,
        ]);

        Comment::create([
            'ticket_id' => $ticket1->id,
            'user_id' => $staff->id,
            'body' => 'Investigating the token expiration issue in our mail service.',
        ]);
    }
}

