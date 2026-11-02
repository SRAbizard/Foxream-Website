<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * After passing: the employment contract (issued by the team, signed by
 * the student), the COE, the visa and the departure (date, flight, ticket)
 * live on the application. Documents can wait for the contract (the
 * passport), and fields can require their own documents.
 */
return new class extends Migration {
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('contract_path')->nullable()->after('admin_notes');
            $table->string('signed_contract_path')->nullable()->after('contract_path');
            $table->timestamp('signed_contract_at')->nullable()->after('signed_contract_path');
            $table->date('coe_issued_at')->nullable()->after('signed_contract_at');
            $table->string('coe_path')->nullable()->after('coe_issued_at');
            $table->date('visa_issued_at')->nullable()->after('coe_path');
            $table->string('visa_path')->nullable()->after('visa_issued_at');
            $table->dateTime('departure_at')->nullable()->after('visa_path');
            $table->text('flight_info')->nullable()->after('departure_at');
            $table->string('ticket_path')->nullable()->after('flight_info');
        });

        Schema::table('document_types', function (Blueprint $table) {
            $table->boolean('after_contract')->default(false)->after('is_active');
        });
        DB::table('document_types')->where('key', 'passport')->update(['after_contract' => true]);

        Schema::table('job_categories', function (Blueprint $table) {
            $table->json('required_documents')->nullable();
        });
    }

    public function down(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->dropColumn([
                'contract_path', 'signed_contract_path', 'signed_contract_at', 'coe_issued_at', 'coe_path',
                'visa_issued_at', 'visa_path', 'departure_at', 'flight_info', 'ticket_path',
            ]);
        });
        Schema::table('document_types', fn (Blueprint $table) => $table->dropColumn('after_contract'));
        Schema::table('job_categories', fn (Blueprint $table) => $table->dropColumn('required_documents'));
    }
};
