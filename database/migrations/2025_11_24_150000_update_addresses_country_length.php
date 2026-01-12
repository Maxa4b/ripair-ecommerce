<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::statement('ALTER TABLE addresses MODIFY country_code VARCHAR(50)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE addresses MODIFY country_code VARCHAR(2)');
    }
};
