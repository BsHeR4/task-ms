<?php

namespace Database\Factories;

use App\Enums\TaskStatus;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Task>
 * 
 * Task Factory definition.
 * Defines the default attributes for creating a Task model instance.
 */
class TaskFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            // Generate a random sentence for the task title
            'title' => fake()->sentence(),

            // Generate a random paragraph for the task description
            'description' => fake()->paragraph(),

            // Randomly select a status from the TaskStatus enum
            'status' => fake()->randomElement(TaskStatus::all()),

            // Note: 'user_id' is intentionally omitted here. 
            // It will be automatically populated when using the `hasTasks()` relationship 
            // method in the seeder, ensuring correct foreign key assignment.
        ];
    }
}
