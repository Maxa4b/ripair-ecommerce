<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->string('rhc_status')->nullable()->index()->after('is_best_seller');
            $table->unsignedInteger('rhc_attempts')->default(0)->after('rhc_status');
            $table->timestamp('rhc_last_run_at')->nullable()->after('rhc_attempts');
            $table->string('rhc_generated_title')->nullable()->after('rhc_last_run_at');
            $table->text('rhc_generated_description')->nullable()->after('rhc_generated_title');
            $table->string('rhc_generated_image')->nullable()->after('rhc_generated_description');
            $table->text('rhc_last_error')->nullable()->after('rhc_generated_image');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'rhc_status',
                'rhc_attempts',
                'rhc_last_run_at',
                'rhc_generated_title',
                'rhc_generated_description',
                'rhc_generated_image',
                'rhc_last_error',
            ]);
        });
    }
};
