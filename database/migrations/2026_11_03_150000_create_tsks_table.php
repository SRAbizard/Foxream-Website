<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * TSK (登録支援機関, registered support organisations): each company
 * works with one, so the chain is TSK → company → job posting.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::create('tsks', function (Blueprint $table) {
            $table->id();
            $table->string('name', 160);
            $table->string('name_ja', 160)->nullable();
            $table->string('registration_number', 40)->nullable();   // 登録番号, e.g. 19登-000123
            $table->string('contact_person', 120)->nullable();
            $table->string('phone', 40)->nullable();
            $table->string('email', 160)->nullable();
            $table->string('city', 80)->nullable();
            $table->text('address')->nullable();
            $table->text('notes')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->foreignId('tsk_id')->nullable()->after('id')->index();
        });

        // The foreign key only where the database adds one in place. On
        // SQLite Laravel rebuilds the table for it, and inside the
        // migration's transaction dropping the old companies table cascades
        // into job_vacancies and applications, deleting them all. Tsk
        // clears tsk_id itself when it is deleted.
        if (Schema::getConnection()->getDriverName() !== 'sqlite') {
            Schema::table('companies', function (Blueprint $table) {
                $table->foreign('tsk_id')->references('id')->on('tsks')->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            if (Schema::getConnection()->getDriverName() !== 'sqlite') {
                $table->dropForeign(['tsk_id']);
            }
            $table->dropColumn('tsk_id');
        });
        Schema::dropIfExists('tsks');
    }
};
