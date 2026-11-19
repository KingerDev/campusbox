<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            // Správca platformy (schvaľuje podniky). Nastavuje sa len príkazom campusbox:make-admin.
            $table->boolean('is_admin')->default(false);
        });

        Schema::table('businesses', function (Blueprint $table) {
            // status: pending → approved | rejected
            $table->timestamp('rejected_at')->nullable();
            $table->string('rejection_reason', 300)->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['rejected_at', 'rejection_reason']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('is_admin');
        });
    }
};
