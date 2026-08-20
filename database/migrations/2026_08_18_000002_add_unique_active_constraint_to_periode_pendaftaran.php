<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Partial unique index hanya didukung PostgreSQL.
        // SQLite dilewati karena hanya dipakai di testing.
        if (DB::getDriverName() === 'pgsql') {
            DB::statement(
                'CREATE UNIQUE INDEX periode_pendaftaran_unique_active
                 ON periode_pendaftaran (is_active)
                 WHERE is_active = true'
            );
        }
    }

    public function down(): void
    {
        if (DB::getDriverName() === 'pgsql') {
            DB::statement('DROP INDEX IF EXISTS periode_pendaftaran_unique_active');
        }
    }
};
