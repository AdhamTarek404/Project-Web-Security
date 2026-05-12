<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // A "rider" is a user (role='rider') with extra delivery-specific
        // fields. We use a separate table so the 'users' table stays clean
        // and customers/owners don't carry irrelevant columns.
        Schema::create('riders', function (Blueprint $table) {
            $table->id();

            // 1:1 with users — each rider record belongs to exactly one user.
            $table->foreignId('user_id')
                ->unique()
                ->constrained('users')
                ->cascadeOnDelete();

            $table->string('vehicle_type', 20); // bike | scooter | car
            $table->string('license_plate', 20)->nullable();

            // Last known GPS — updated by the rider's mobile app every few sec.
            // Nullable because a rider may exist without ever having reported yet.
            $table->decimal('current_latitude', 10, 7)->nullable();
            $table->decimal('current_longitude', 10, 7)->nullable();
            $table->timestamp('last_location_at')->nullable();

            // is_on_duty  = rider has clocked in (logged into the rider app)
            // is_available = is_on_duty AND not currently delivering an order
            // The dispatch algorithm picks riders where both are true.
            $table->boolean('is_on_duty')->default(false);
            $table->boolean('is_available')->default(false);

            $table->timestamps();

            // The dispatch query filters by these two flags first,
            // then sorts by distance — so a composite index is the right shape.
            $table->index(['is_available', 'is_on_duty']);
            $table->index(['current_latitude', 'current_longitude']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('riders');
    }
};
