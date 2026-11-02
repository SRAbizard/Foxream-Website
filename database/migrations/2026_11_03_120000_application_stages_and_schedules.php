<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Applications follow the candidate's steps after applying: screening
 * with the Indonesia team, with the Japan team, the job interview, then
 * passed → contract → COE → visa → departure. Each step can have
 * schedules (date, time, meeting link), also for kaiwa practice.
 */
return new class extends Migration {
    /** Old status => step. */
    private const MOVES = [
        'under_review'        => 'screening_id',
        'interview_scheduled' => 'interview',
        'offered'             => 'passed',
        'accepted'            => 'passed',
    ];

    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->string('status', 32)->default('submitted')->change();
        });
        foreach (self::MOVES as $from => $to) {
            DB::table('applications')->where('status', $from)->update(['status' => $to]);
        }

        Schema::create('application_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('application_id')->constrained()->cascadeOnDelete();
            $table->string('type', 32);                    // screening_id, screening_jp, interview, kaiwa
            $table->dateTime('starts_at')->nullable();     // null = date still to come
            $table->string('meeting_url', 500)->nullable();
            $table->text('notes')->nullable();
            $table->string('result', 16)->nullable();      // passed, failed
            $table->timestamps();

            $table->index(['application_id', 'starts_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('application_schedules');
    }
};
