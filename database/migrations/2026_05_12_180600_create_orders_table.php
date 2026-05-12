<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();

            $table->foreignId('customer_id')
                ->constrained('users')
                ->restrictOnDelete();

            $table->foreignId('restaurant_id')
                ->constrained('restaurants')
                ->restrictOnDelete();

            // Nullable: no rider is attached until dispatch finds one.
            $table->foreignId('rider_id')
                ->nullable()
                ->constrained('riders')
                ->nullOnDelete();

            // === Finite State Machine current state ===
            // Allowed values (enforced in PHP, not the DB, for portability):
            // placed → confirmed → preparing → on_the_way → delivered
            //   any state → cancelled (with reason)
            $table->string('status', 20)->default('placed');

            // === Money breakdown — all integer cents ===
            // subtotal           = sum of order_items.line_total
            // delivery_fee       = base delivery price
            // surge_multiplier   = e.g. 1.50 during rain / rush hour
            // platform_fee       = subtotal * commission_rate (kept by us)
            // restaurant_payout  = subtotal - platform_fee
            // rider_payout       = delivery_fee * surge_multiplier
            // total              = subtotal + (delivery_fee * surge_multiplier)
            $table->unsignedInteger('subtotal');
            $table->unsignedInteger('delivery_fee');
            $table->decimal('surge_multiplier', 4, 2)->default(1.00);
            $table->unsignedInteger('platform_fee');
            $table->unsignedInteger('restaurant_payout');
            $table->unsignedInteger('rider_payout');
            $table->unsignedInteger('total');

            // === Delivery destination ===
            $table->string('delivery_address');
            $table->decimal('delivery_latitude', 10, 7);
            $table->decimal('delivery_longitude', 10, 7);

            // === State-change timestamps ===
            // Why duplicate these when we have order_status_history?
            // For fast queries ("show me delivered orders today")
            // without joining the history table every time.
            $table->timestamp('placed_at')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->timestamp('preparing_at')->nullable();
            $table->timestamp('on_the_way_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->string('cancellation_reason')->nullable();

            // Reference to the Stripe PaymentIntent so we can refund/look it up.
            $table->string('payment_intent_id')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index(['restaurant_id', 'status']);
            $table->index(['rider_id', 'status']);
            $table->index(['customer_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
