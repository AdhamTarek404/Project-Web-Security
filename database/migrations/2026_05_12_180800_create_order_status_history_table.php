<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // This is the "event sourcing-inspired order history" from the description.
        // RULES:
        //  - APPEND ONLY. Never UPDATE or DELETE rows here.
        //  - One row per state change, including the initial 'placed' state.
        //  - Tells you WHO changed it (actor) and WHEN.
        //  - You can replay the whole life of an order from this table alone.
        Schema::create('order_status_history', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // null on the very first row (creation event)
            $table->string('from_status', 20)->nullable();
            $table->string('to_status', 20);

            // WHO triggered this change?
            //   actor_type: 'system' | 'customer' | 'restaurant' | 'rider' | 'admin'
            //   actor_id  : the user id, or null for 'system' (queued jobs etc)
            $table->string('actor_type', 20);
            $table->unsignedBigInteger('actor_id')->nullable();

            // Free-text reason — e.g. "Cancelled: restaurant closed"
            $table->text('reason')->nullable();

            // Wall-clock time the event happened.
            $table->timestamp('occurred_at')->useCurrent();

            // No updated_at — these rows are immutable.
            $table->timestamp('created_at')->useCurrent();

            $table->index(['order_id', 'occurred_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_status_history');
    }
};
