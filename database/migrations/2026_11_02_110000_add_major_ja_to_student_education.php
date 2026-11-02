<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_education', function (Blueprint $table) {
            // Japanese for the CV, translated from major when the profile is saved.
            $table->string('major_ja', 160)->nullable()->after('major');
        });
    }

    public function down(): void
    {
        Schema::table('student_education', fn (Blueprint $table) => $table->dropColumn('major_ja'));
    }
};
