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
        Schema::create('campaigns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->onDelete('cascade');
            $table->string('name');
            $table->decimal('current_daily_budget', 10, 2)
                ->default(0.00);
            $table->boolean('is_active')
                ->default(true);
            $table->timestamps();

            // Indexes
            $table->index('user_id');
            $table->index('is_active');
        });

        // Add check constraint using raw SQL
        DB::statement('ALTER TABLE campaigns ADD CONSTRAINT chk_budget_positive CHECK (current_daily_budget >= 0)');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('campaigns');
    }
};
