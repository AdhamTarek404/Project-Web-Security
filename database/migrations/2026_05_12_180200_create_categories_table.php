<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('categories', function (Blueprint $table) {
            $table->id();

            $table->foreignId('restaurant_id')
                ->constrained('restaurants')
                ->cascadeOnDelete();

            $table->string('name'); // e.g. "Appetizers", "Main Course"

            // sort_order lets the restaurant owner reorder categories
            // on their menu page without renaming them.
            $table->unsignedInteger('sort_order')->default(0);

            $table->timestamps();

            // Two restaurants can both have "Appetizers", but the SAME
            // restaurant can't have two categories with the same name.
            $table->unique(['restaurant_id', 'name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('categories');
    }
};
