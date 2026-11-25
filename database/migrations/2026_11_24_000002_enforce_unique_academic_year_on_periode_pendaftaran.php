<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('CREATE UNIQUE INDEX periode_pendaftaran_tahun_ajaran_unique
            ON periode_pendaftaran (tahun_ajaran) WHERE deleted_at IS NULL');
    }

    public function down(): void
    {
        DB::statement('DROP INDEX periode_pendaftaran_tahun_ajaran_unique');
    }
};
