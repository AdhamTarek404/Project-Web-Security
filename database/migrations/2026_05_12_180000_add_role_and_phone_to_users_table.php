<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // role drives which dashboards/APIs the user can access.
            // We use a plain string instead of an enum for SQLite portability
            // and easy expansion (e.g. 'support' later) without ALTER TYPE.
            $table->string('role', 20)->default('customer')->after('email');
            $table->string('phone', 30)->nullable()->after('role');

            $table->index('role');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropIndex(['role']);
            $table->dropColumn(['role', 'phone']);
        });
    }
};
