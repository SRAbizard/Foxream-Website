<?php

use App\Support\PrivateFiles;
use Illuminate\Database\Migrations\Migration;

/**
 * Candidates' documents and application files leave the public /storage
 * link for storage/app/private (see App\Support\PrivateFiles). Paths stay
 * the same, so no column changes; files left behind by replaced uploads
 * move too. Tests migrate a throwaway database and must not touch the
 * real files, so they skip it.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! app()->runningUnitTests()) {
            PrivateFiles::moveFromPublic();
        }
    }

    public function down(): void
    {
        if (! app()->runningUnitTests()) {
            PrivateFiles::moveToPublic();
        }
    }
};
