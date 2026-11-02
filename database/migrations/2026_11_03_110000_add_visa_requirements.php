<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

/**
 * What a student hands in with a visa target request: the partner campus
 * for Internship, and the certificates for Tokutei Ginou / Gijinkoku
 * (kept in visa_request, with the uploads in student_documents). Also
 * the "manage students" permission for admins who handle students.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('partner_campuses', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('city', 80)->nullable();
            $table->boolean('is_active')->default(true);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::table('student_profiles', function (Blueprint $table) {
            $table->foreignId('internship_campus_id')->nullable()->after('visa_target_notes')
                ->constrained('partner_campuses')->nullOnDelete();
            $table->string('internship_campus_other', 160)->nullable()->after('internship_campus_id');
            $table->json('visa_request')->nullable()->after('internship_campus_other');
        });

        Permission::firstOrCreate(['name' => 'manage students', 'guard_name' => 'web']);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Schema::table('student_profiles', function (Blueprint $table) {
            $table->dropConstrainedForeignId('internship_campus_id');
            $table->dropColumn(['internship_campus_other', 'visa_request']);
        });
        Schema::dropIfExists('partner_campuses');
        Permission::where('name', 'manage students')->delete();
    }
};
