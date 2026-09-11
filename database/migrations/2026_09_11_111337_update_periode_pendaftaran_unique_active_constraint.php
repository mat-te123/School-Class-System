<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Drop old index
            DB::statement('DROP INDEX IF EXISTS periode_pendaftaran_unique_active');

            // Create new index that ignores soft deleted records
            DB::statement(
                'CREATE UNIQUE INDEX periode_pendaftaran_unique_active
                 ON periode_pendaftaran (is_active)
                 WHERE is_active = true AND deleted_at IS NULL'
            );
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            // Drop new index
            DB::statement('DROP INDEX IF EXISTS periode_pendaftaran_unique_active');

            // Recreate old index
            DB::statement(
                'CREATE UNIQUE INDEX periode_pendaftaran_unique_active
                 ON periode_pendaftaran (is_active)
                 WHERE is_active = true'
            );
        }
    }
};
