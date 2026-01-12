<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            if (Schema::hasColumn('products', 'rhc_last_payload')) {
                return;
            }

            $table->json('rhc_last_payload')->nullable()->after('rhc_last_error');
        });
    }

    public function down(): void
    {
        if (!Schema::hasColumn('products', 'rhc_last_payload')) {
            return;
        }

        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn('rhc_last_payload');
        });
    }
};

