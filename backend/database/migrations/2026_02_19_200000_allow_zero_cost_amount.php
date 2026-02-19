<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Replace the amount > 0 constraint with amount >= 0 to allow zero-cost days
        DB::statement('ALTER TABLE costs DROP CONSTRAINT IF EXISTS chk_cost_positive');
        DB::statement('ALTER TABLE costs ADD CONSTRAINT chk_cost_non_negative CHECK (amount >= 0)');
    }

    public function down(): void
    {
        DB::statement('ALTER TABLE costs DROP CONSTRAINT IF EXISTS chk_cost_non_negative');
        DB::statement('ALTER TABLE costs ADD CONSTRAINT chk_cost_positive CHECK (amount > 0)');
    }
};
