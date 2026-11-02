<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->boolean('eats_pork')->default(false)->after('blood_type');
        });
        Schema::table('student_work_experiences', function (Blueprint $table) {
            // Japanese for the CV, translated from position when the profile is saved.
            $table->string('position_ja', 160)->nullable()->after('position');
        });
    }

    public function down(): void
    {
        Schema::table('student_profiles', fn (Blueprint $table) => $table->dropColumn('eats_pork'));
        Schema::table('student_work_experiences', fn (Blueprint $table) => $table->dropColumn('position_ja'));
    }
};
