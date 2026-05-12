<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Polymorphic ratings — the description requires rating BOTH
        // restaurants AND individual riders. Instead of two near-identical
        // tables, we use rateable_type + rateable_id to point at either.
        //
        // Example rows for one delivered order:
        //   order=123, rateable_type='App\Models\Restaurant', rateable_id=7, stars=5
        //   order=123, rateable_type='App\Models\Rider',      rateable_id=4, stars=4
        Schema::create('ratings', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            $table->foreignId('customer_id')
                ->constrained('users')
                ->cascadeOnDelete();

            // morphs() creates rateable_type (string) + rateable_id (bigint)
            // and adds an index on the pair.
            $table->morphs('rateable');

            $table->unsignedTinyInteger('stars'); // 1..5 — enforced in PHP
            $table->text('comment')->nullable();

            $table->timestamps();

            // One rating per (order, rateable) pair so customers can't spam.
            $table->unique(['order_id', 'rateable_type', 'rateable_id'], 'ratings_order_rateable_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ratings');
    }
};
