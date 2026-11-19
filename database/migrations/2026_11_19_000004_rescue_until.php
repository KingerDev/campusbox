<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Záchrana jedla už nemá okno výdaja od–do: objednáva sa od zverejnenia a vyzdvihnúť sa dá do času „do“.
 * Prázdna hodnota znamená „do dnešnej záverečnej“ (riadi sa otváracími hodinami).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('rescue_until', 5)->nullable()->after('rescue_slot');
        });
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('rescue_slot');
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('rescue_slot', 20)->default('19:30–20:00');
        });
        DB::table('businesses')->whereNotNull('rescue_until')->update(['rescue_slot' => DB::raw("'19:30–' || rescue_until")]);
        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('rescue_until');
        });
    }
};
