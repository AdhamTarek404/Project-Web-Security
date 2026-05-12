<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Example variants for one pizza:
        //   name='Small',  price_modifier=0       (default, free)
        //   name='Medium', price_modifier=2000    (+20 EGP)
        //   name='Large',  price_modifier=4000    (+40 EGP)
        Schema::create('menu_item_variants', function (Blueprint $table) {
            $table->id();

            $table->foreignId('menu_item_id')
                ->constrained('menu_items')
                ->cascadeOnDelete();

            $table->string('name'); // "Small", "Medium", "Large"

            // SIGNED int (Laravel's integer()) so a variant could *discount*
            // the base price too if needed. Final price = base_price + price_modifier.
            $table->integer('price_modifier')->default(0);

            $table->boolean('is_default')->default(false);

            $table->timestamps();

            $table->unique(['menu_item_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_variants');
    }
};
