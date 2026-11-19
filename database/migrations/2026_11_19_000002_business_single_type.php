<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Podnik má jeden typ (namiesto zoznamu, v ktorom boli zmiešané typy podniku a druhy kuchyne).
 * Kategórie podniku sa už neukladajú – počítajú sa z kategórií jedál v menu.
 */
return new class extends Migration
{
    private const TYPES = ['Bistro', 'Reštaurácia', 'Pekáreň', 'Kaviareň', 'Cukráreň', 'Pizzeria', 'Potraviny'];

    public function up(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->string('type', 40)->default('Bistro')->after('dic');
        });

        // Hlavným typom zostane prvý zo zaškrtnutých, ktorý je skutočne typ podniku.
        foreach (DB::table('businesses')->get(['id', 'types']) as $row) {
            $types = json_decode($row->types ?? '[]', true) ?: [];
            $type = collect($types)->first(fn ($t) => in_array($t, self::TYPES, true)) ?? 'Reštaurácia';
            DB::table('businesses')->where('id', $row->id)->update(['type' => $type]);
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn(['types', 'categories']);
        });
    }

    public function down(): void
    {
        Schema::table('businesses', function (Blueprint $table) {
            $table->json('types')->default('[]');
            $table->json('categories')->default('[]');
        });

        foreach (DB::table('businesses')->get(['id', 'type']) as $row) {
            DB::table('businesses')->where('id', $row->id)->update(['types' => json_encode([$row->type])]);
        }

        Schema::table('businesses', function (Blueprint $table) {
            $table->dropColumn('type');
        });
    }
};
