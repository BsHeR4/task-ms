<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * @extends \Illuminate\Database\Seeder
 *
 * UserSeeder class responsible for populating the users table, 
 * focusing on creating specific test accounts with associated tasks.
 */
class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     * 
     * @return void
     */
    public function run(): void
    {
        // 1. Define an array of specific user accounts required for testing or demonstration.
        $users = [
            [
                'name' => 'bsher',
                'email' => 'bsher@gmail.com',
                'password' => 'passWord@12',
            ],
            [
                'name' => 'mohammed',
                'email' => 'mohammed@gmail.com',
                'password' => 'passWord@12',
            ],
        ];

        // 2. Iterate through the predefined users and create them.
        foreach ($users as $user) {

            // 3. Use the User Factory to create the model instance, overriding default attributes.
            User::factory()

                // Used the hasTasks relationship method to attach a random number of tasks
                // The rand(3, 7) ensures each user gets between 3 and 7 random tasks 
                // using the TaskFactory definition.
                ->hasTasks(rand(3, 7))
                ->create([
                    'name' => $user['name'],
                    'email' => $user['email'],
                    'password' => Hash::make($user['password']),
                    'email_verified_at' => now(),
                ]);
        }

        // Optional: The users above for fast testing, email and passowrd are known ^_^
        // So if you also need additional generic users, uncomment the line below:
        // User::factory(5)->hasTasks(5)->create();
    }
}
