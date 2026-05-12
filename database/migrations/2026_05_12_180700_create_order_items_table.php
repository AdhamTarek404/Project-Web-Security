<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->cascadeOnDelete();

            // We DON'T cascade-delete on menu_item: an order item must keep
            // pointing to a real menu_item record for history. Use restrictOnDelete
            // so the restaurant can't accidentally orphan order history.
            $table->foreignId('menu_item_id')
                ->constrained('menu_items')
                ->restrictOnDelete();

            $table->foreignId('variant_id')
                ->nullable()
                ->constrained('menu_item_variants')
                ->nullOnDelete();

            $table->unsignedInteger('quantity');

            // CRITICAL: we snapshot the price AT TIME OF ORDER.
            // If the restaurant raises prices tomorrow, this order is unaffected.
            // This is also why we don't recompute totals on the fly.
            $table->unsignedInteger('unit_price'); // cents, includes variant modifier
            $table->unsignedInteger('line_total'); // unit_price * quantity

            $table->text('special_instructions')->nullable();

            $table->timestamps();

            $table->index('order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
