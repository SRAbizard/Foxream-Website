<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Each visa says which of its required documents are needed to apply
 * (visa_categories.apply_documents); the rest wait until the student
 * passes the company interview. This replaces the per-type "after the
 * contract" switch. Internship students hand in their student ID card
 * (KTM), a new document type. A document type can also carry a form for
 * students to download and fill in (the MCU form).
 */
return new class extends Migration {
    /** Visa slug => documents needed to apply. */
    private const NEEDED_TO_APPLY = [
        'tokutei-ginou' => ['photo', 'jlpt_jft', 'ssw'],
        'engineering'   => ['photo', 'jlpt_jft', 'ijazah', 'transcript'],
        'internship'    => ['photo', 'ktm', 'transcript'],
    ];

    public function up(): void
    {
        Schema::table('visa_categories', function (Blueprint $table) {
            $table->json('apply_documents')->nullable()->after('required_documents');
        });
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn('after_contract');
        });
        Schema::table('document_types', function (Blueprint $table) {
            $table->string('template_path')->nullable()->after('is_active');
            $table->string('template_name')->nullable()->after('template_path');
        });

        if (! DB::table('document_types')->where('key', 'ktm')->exists()) {
            DB::table('document_types')->where('sort_order', '>', 3)->increment('sort_order');
            DB::table('document_types')->insert([
                'key'        => 'ktm',
                'label'      => json_encode(['id' => 'KTM (Kartu Tanda Mahasiswa)', 'en' => 'Student ID card (KTM)', 'ja' => '学生証（KTM）']),
                'icon'       => 'heroicon-o-identification',
                'sort_order' => 4,
                'is_active'  => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        foreach (self::NEEDED_TO_APPLY as $slug => $keys) {
            $visa = DB::table('visa_categories')->where('slug', $slug)->first();
            if (! $visa) {
                continue;
            }
            // What is needed to apply is required too.
            $required = array_values(array_unique([...(json_decode($visa->required_documents ?? '[]', true) ?: []), ...$keys]));
            $optional = array_values(array_diff(json_decode($visa->optional_documents ?? '[]', true) ?: [], $required));
            DB::table('visa_categories')->where('id', $visa->id)->update([
                'apply_documents'    => json_encode($keys),
                'required_documents' => json_encode($required),
                'optional_documents' => json_encode($optional),
            ]);
        }
    }

    public function down(): void
    {
        Schema::table('document_types', function (Blueprint $table) {
            $table->dropColumn(['template_path', 'template_name']);
            $table->boolean('after_contract')->default(false);
        });
        Schema::table('visa_categories', function (Blueprint $table) {
            $table->dropColumn('apply_documents');
        });
    }
};
