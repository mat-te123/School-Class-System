<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('siswa') && !Schema::hasColumn('siswa', 'deleted_at')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->softDeletes();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('siswa') && Schema::hasColumn('siswa', 'deleted_at')) {
            Schema::table('siswa', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};