<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')
                ->constrained()
                ->onDelete('cascade');
            $table->decimal('amount', 10, 2);
            $table->timestamp('generated_at');
            $table->decimal('budget_at_generation', 10, 2);
            $table->decimal('daily_limit_at_generation', 10, 2);
            $table->timestamp('created_at');

            // Indexes for efficient queries
            $table->index('campaign_id');
            $table->index('generated_at');
            $table->index(['campaign_id', 'generated_at']);
        });

        // Add composite index for daily aggregation
        DB::statement('CREATE INDEX idx_costs_campaign_daily ON costs(campaign_id, DATE(generated_at))');

        // Add check constraint
        DB::statement('ALTER TABLE costs ADD CONSTRAINT chk_cost_positive CHECK (amount > 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('costs');
    }
};
