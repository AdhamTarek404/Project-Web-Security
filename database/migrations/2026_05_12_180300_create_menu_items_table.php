<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('category_id')
                ->constrained('categories')
                ->cascadeOnDelete();

            $table->string('name');
            $table->text('description')->nullable();

            // Money is stored as integer CENTS (or piastres) — never floats,
            // because float math breaks at the cash register (0.1 + 0.2 != 0.3).
            // Example: 5.99 EGP → 599
            $table->unsignedInteger('base_price');

            $table->string('image_path')->nullable();

            // "Availability toggle" from the description: lets the restaurant
            // hide a dish that's out of stock without deleting it.
            $table->boolean('is_available')->default(true);

            $table->timestamps();

            $table->index(['category_id', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_items');
    }
};
