<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    protected $model = Ticket::class;

    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(),
            'description' => fake()->paragraph(),
            'status' => 'Open',
            'priority' => fake()->randomElement(['Low', 'Medium', 'High']),
            'user_id' => User::factory(),
            'assigned_to' => null,
        ];
    }
}
