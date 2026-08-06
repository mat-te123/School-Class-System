<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('paket_menu_pilihan') && !Schema::hasColumn('paket_menu_pilihan', 'deleted_at')) {
            Schema::table('paket_menu_pilihan', function (Blueprint $table) {
                $table->softDeletes();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (Schema::hasTable('paket_menu_pilihan') && Schema::hasColumn('paket_menu_pilihan', 'deleted_at')) {
            Schema::table('paket_menu_pilihan', function (Blueprint $table) {
                $table->dropSoftDeletes();
                $table->dropTimestamps();
            });
        }
    }
};
