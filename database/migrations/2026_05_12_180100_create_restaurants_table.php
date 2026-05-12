<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('restaurants', function (Blueprint $table) {
            $table->id();

            // The restaurant owner — a user with role='restaurant_owner'.
            // ON DELETE CASCADE: deleting the owner removes their restaurant.
            $table->foreignId('owner_id')
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('name');
            $table->string('slug')->unique();
            $table->string('address');

            // GPS coordinates. decimal(10,7) gives ~1.1 cm precision worldwide.
            // Used by the Haversine algorithm to find the nearest rider.
            $table->decimal('latitude', 10, 7);
            $table->decimal('longitude', 10, 7);

            // The cut the platform takes from each order, e.g. 15.00 = 15%.
            // Required for the "Stripe Connect split payments" requirement.
            $table->decimal('commission_rate', 5, 2)->default(15.00);

            // Quick on/off switch for the restaurant to stop receiving orders.
            $table->boolean('is_open')->default(true);

            $table->timestamps();

            // Composite index helps "find restaurants near (lat, lon)" queries.
            $table->index(['latitude', 'longitude']);
            $table->index('is_open');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('restaurants');
    }
};
