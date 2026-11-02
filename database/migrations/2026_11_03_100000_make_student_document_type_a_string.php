<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * student_documents.type was an enum of the first document keys (ktp,
 * jlpt_certificate, …), but the keys now come from the document_types
 * table (idcard, jlpt_jft, ssw, …), so uploads of the newer types were
 * refused by the database. Any key from document_types is allowed now.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('student_documents', function (Blueprint $table) {
            $table->string('type', 64)->default('other')->change();
        });
    }

    public function down(): void
    {
        // Not reversible: rows may hold keys the old enum does not know.
    }
};
