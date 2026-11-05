<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Asked on the student's profile (Health tab), for the candidate list sent
 * to companies: tattoo, debts, colour blindness. Null until answered.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->boolean('has_tattoo')->nullable()->after('smoker');
            $table->boolean('has_debt')->nullable()->after('has_tattoo');
            $table->boolean('colour_blind')->nullable()->after('has_debt');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropColumn(['has_tattoo', 'has_debt', 'colour_blind']);
        });
    }
};
