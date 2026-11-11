<?php

namespace Database\Seeders;

use App\Enums\Role;
use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        User::factory()->admin()->create([
            'name' => 'Admin User',
            'email' => 'admin@example.com',
        ]);

        User::factory()->resourceManager()->create([
            'name' => 'Resource Manager',
            'email' => 'manager@example.com',
        ]);

        User::factory()->create([
            'name' => 'Employee One',
            'email' => 'employee@example.com',
        ]);

        $catalog = [
            'Projectors' => [['Epson EB-X06 Projector', 5, 'Nairobi HQ']],
            'Laptops' => [
                ['Dell Latitude 5440', 10, 'Nairobi HQ'],
                ['MacBook Air', 3, 'Mombasa Office'],
            ],
            'Meeting Rooms' => [['Boardroom A', 1, 'Nairobi HQ']],
        ];

        foreach ($catalog as $categoryName => $items) {
            $category = Category::create(['name' => $categoryName]);

            foreach ($items as [$name, $quantity, $location]) {
                $category->resources()->create([
                    'name' => $name,
                    'total_quantity' => $quantity,
                    'location' => $location,
                ]);
            }
        }
    }
}
