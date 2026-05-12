<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

// Stripe Connect requirement: each restaurant and each rider needs its
// own Stripe-connected-account id so we can transfer their cut of every
// order payout. Onboarding happens out-of-band via Stripe Connect's hosted
// onboarding link (acct_*). Until they're onboarded the column stays NULL
// and the StripeConnectGateway defers their transfer.
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->after('commission_rate');
        });

        Schema::table('riders', function (Blueprint $table) {
            $table->string('stripe_account_id')->nullable()->after('is_available');
        });
    }

    public function down(): void
    {
        Schema::table('restaurants', function (Blueprint $table) {
            $table->dropColumn('stripe_account_id');
        });

        Schema::table('riders', function (Blueprint $table) {
            $table->dropColumn('stripe_account_id');
        });
    }
};
