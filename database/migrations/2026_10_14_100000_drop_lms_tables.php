<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Learning moved to the separate learning platform — this site is job matching only.
 *
 * The original LMS migrations were deleted from the repo, so on a fresh
 * install these drops are no-ops. On databases that already ran them,
 * this removes the tables and their now-orphaned migration records.
 */
return new class extends Migration
{
    private const TABLES = [
        'quiz_attempts',
        'quiz_questions',
        'passages',
        'quizzes',
        'certificates',
        'lesson_progress',
        'enrollments',
        'materials',
        'chapters',
        'courses',
        'course_categories',
    ];

    private const MIGRATIONS = [
        '2026_05_13_200000_create_course_categories_table',
        '2026_05_13_200001_create_courses_table',
        '2026_05_13_200002_create_chapters_table',
        '2026_05_13_200003_create_materials_table',
        '2026_05_13_200004_create_enrollments_table',
        '2026_05_13_200005_create_lesson_progress_table',
        '2026_05_14_100000_create_quizzes_table',
        '2026_05_14_100001_create_certificates_table',
        '2026_05_17_100000_create_passages_table',
        '2026_05_17_100001_add_chapter_and_type_to_quizzes',
        '2026_05_17_100002_add_section_and_media_to_quiz_questions',
        '2026_05_18_100000_lms_dicoding_style_restructure',
        '2026_05_18_110000_drop_elementary_level',
    ];

    public function up(): void
    {
        Schema::disableForeignKeyConstraints();
        foreach (self::TABLES as $table) {
            Schema::dropIfExists($table);
        }
        Schema::enableForeignKeyConstraints();

        DB::table('migrations')->whereIn('migration', self::MIGRATIONS)->delete();

        // Old "certificate issued" bell notifications would link to dead routes.
        if (Schema::hasTable('notifications')) {
            DB::table('notifications')->where('type', 'App\\Notifications\\CertificateIssued')->delete();
        }
    }

    public function down(): void
    {
        // Irreversible: the LMS lives in the learning platform now.
    }
};
