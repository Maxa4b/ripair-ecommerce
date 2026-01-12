<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('repairs')) {
            return;
        }

        Schema::table('repairs', function (Blueprint $table) {
            if (Schema::hasColumn('repairs', 'update_done')) {
                return;
            }

            $table->boolean('update_done')->default(false)->index();
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('repairs')) {
            return;
        }

        if (!Schema::hasColumn('repairs', 'update_done')) {
            return;
        }

        Schema::table('repairs', function (Blueprint $table) {
            $table->dropColumn('update_done');
        });
    }
};

