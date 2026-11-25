<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('paket_menu_pilihan')->whereNull('deleted_at')
            ->whereIn('periode_id', DB::table('periode_pendaftaran')->whereNotNull('deleted_at')->select('id'))
            ->update(['deleted_at' => now()]);
    }

    public function down(): void
    {
        // Archived packages stay archived; rollback must not expose deleted periods again.
    }
};
