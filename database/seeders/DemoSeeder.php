<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\MenuItem;
use App\Models\MenuItemVariant;
use App\Models\Restaurant;
use App\Models\Rider;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

// One-shot demo seeder. Creates a complete demo state:
//   - 1 admin
//   - 1 restaurant owner with 1 restaurant + 2 categories + 6 menu items + variants
//   - 3 riders scattered around Cairo
//   - 2 customers
//
// All passwords: "password"
class DemoSeeder extends Seeder
{
    public function run(): void
    {
        // === Admin ===
        User::create([
            'name' => 'Admin',
            'email' => 'admin@demo.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN,
        ]);

        // === Restaurant owner + restaurant ===
        $owner = User::create([
            'name' => 'Sara (Owner)',
            'email' => 'owner@demo.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_RESTAURANT_OWNER,
        ]);

        $restaurant = Restaurant::create([
            'owner_id' => $owner->id,
            'name' => 'Demo Bistro',
            'slug' => 'demo-bistro',
            'address' => '12 Tahrir Square, Cairo',
            'latitude' => 30.0444,
            'longitude' => 31.2357,
            'commission_rate' => 15.00,
            'is_open' => true,
        ]);

        $mains = Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Mains', 'sort_order' => 1,
        ]);
        $drinks = Category::create([
            'restaurant_id' => $restaurant->id,
            'name' => 'Drinks', 'sort_order' => 2,
        ]);

        // Margherita with size variants
        $pizza = MenuItem::create([
            'category_id' => $mains->id,
            'name' => 'Margherita Pizza',
            'description' => 'Tomato, mozzarella, basil',
            'base_price' => 12000, // 120 EGP
            'is_available' => true,
        ]);
        MenuItemVariant::create(['menu_item_id' => $pizza->id, 'name' => 'Small',  'price_modifier' => 0,    'is_default' => true]);
        MenuItemVariant::create(['menu_item_id' => $pizza->id, 'name' => 'Medium', 'price_modifier' => 2500, 'is_default' => false]);
        MenuItemVariant::create(['menu_item_id' => $pizza->id, 'name' => 'Large',  'price_modifier' => 5000, 'is_default' => false]);

        MenuItem::create([
            'category_id' => $mains->id,
            'name' => 'Cheeseburger',
            'description' => 'Beef patty, cheddar, lettuce, tomato',
            'base_price' => 9500,
            'is_available' => true,
        ]);
        MenuItem::create([
            'category_id' => $mains->id,
            'name' => 'Chicken Shawarma',
            'description' => 'Marinated chicken in pita with garlic sauce',
            'base_price' => 7500,
            'is_available' => true,
        ]);
        MenuItem::create([
            'category_id' => $mains->id,
            'name' => 'Caesar Salad',
            'description' => 'Romaine, parmesan, croutons, caesar dressing',
            'base_price' => 6500,
            'is_available' => true,
        ]);
        MenuItem::create([
            'category_id' => $drinks->id,
            'name' => 'Fresh Lemonade',
            'description' => 'House-made, lightly sweet',
            'base_price' => 3000,
            'is_available' => true,
        ]);
        MenuItem::create([
            'category_id' => $drinks->id,
            'name' => 'Iced Coffee',
            'description' => 'Cold brew with milk',
            'base_price' => 3500,
            'is_available' => false, // demo of the availability toggle
        ]);

        // === Riders ===
        // Three available, on-duty riders at varying distances from the restaurant.
        $riderConfigs = [
            ['name' => 'Omar Rider',   'email' => 'rider1@demo.test', 'lat' => 30.0450, 'lng' => 31.2360], // very near
            ['name' => 'Mona Rider',   'email' => 'rider2@demo.test', 'lat' => 30.0500, 'lng' => 31.2400], // medium
            ['name' => 'Khaled Rider', 'email' => 'rider3@demo.test', 'lat' => 30.0800, 'lng' => 31.3000], // far
        ];
        foreach ($riderConfigs as $cfg) {
            $u = User::create([
                'name' => $cfg['name'],
                'email' => $cfg['email'],
                'password' => Hash::make('password'),
                'role' => User::ROLE_RIDER,
                'phone' => '0100'.rand(1000000, 9999999),
            ]);
            Rider::create([
                'user_id' => $u->id,
                'vehicle_type' => 'bike',
                'license_plate' => 'EG-'.rand(1000, 9999),
                'current_latitude' => $cfg['lat'],
                'current_longitude' => $cfg['lng'],
                'last_location_at' => now(),
                'is_on_duty' => true,
                'is_available' => true,
            ]);
        }

        // === Customers ===
        User::create([
            'name' => 'Ahmed Customer',
            'email' => 'customer1@demo.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_CUSTOMER,
        ]);
        User::create([
            'name' => 'Layla Customer',
            'email' => 'customer2@demo.test',
            'password' => Hash::make('password'),
            'role' => User::ROLE_CUSTOMER,
        ]);

        $this->command->info('=== Demo data seeded ===');
        $this->command->info('All passwords: password');
        $this->command->info('admin@demo.test       (admin)');
        $this->command->info('owner@demo.test       (restaurant owner of "Demo Bistro")');
        $this->command->info('rider1@demo.test ... rider3@demo.test');
        $this->command->info('customer1@demo.test, customer2@demo.test');
    }
}
