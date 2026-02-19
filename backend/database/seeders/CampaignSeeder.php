<?php

namespace Database\Seeders;

use App\Models\Campaign;
use App\Models\BudgetHistory;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class CampaignSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure we have a test user
        $user = User::firstOrCreate(
            ['email' => 'test@example.com'],
            [
                'name' => 'Test User',
                'password' => bcrypt('password'),
            ]
        );

        $this->command->info('Creating campaigns with 3-month budget history...');

        // Create 3 campaigns with different budget patterns
        $this->createCampaignWithSimpleBudget($user);
        $this->createCampaignWithMultipleBudgetChanges($user);
        $this->createCampaignWithPauseResume($user);

        $this->command->info('✓ Campaigns created successfully');
        $this->command->newLine();
        $this->command->info('You can now generate costs using:');
        $this->command->info('  php artisan campaign:generate-costs --all --from=2024-11-19 --to=2025-02-19');
    }

    /**
     * Create a campaign with a simple, stable budget.
     */
    protected function createCampaignWithSimpleBudget(User $user): void
    {
        $campaign = Campaign::create([
            'user_id' => $user->id,
            'name' => 'Stable Budget Campaign',
            'current_daily_budget' => 150.00,
            'is_active' => true,
            'created_at' => Carbon::now()->subMonths(3),
        ]);

        // Create initial budget history
        BudgetHistory::create([
            'campaign_id' => $campaign->id,
            'previous_budget' => 0,
            'new_budget' => 150.00,
            'changed_at' => Carbon::now()->subMonths(3),
        ]);

        $this->command->info("  Created: {$campaign->name} (ID: {$campaign->id})");
    }

    /**
     * Create a campaign with multiple budget changes over 3 months.
     */
    protected function createCampaignWithMultipleBudgetChanges(User $user): void
    {
        $campaign = Campaign::create([
            'user_id' => $user->id,
            'name' => 'Dynamic Budget Campaign',
            'current_daily_budget' => 250.00,
            'is_active' => true,
            'created_at' => Carbon::now()->subMonths(3),
        ]);

        // Create budget history with multiple changes
        $budgetChanges = [
            ['date' => Carbon::now()->subMonths(3), 'prev' => 0, 'new' => 100.00],
            ['date' => Carbon::now()->subMonths(2)->subDays(15), 'prev' => 100.00, 'new' => 200.00],
            ['date' => Carbon::now()->subMonths(2), 'prev' => 200.00, 'new' => 150.00],
            ['date' => Carbon::now()->subMonth()->subDays(10), 'prev' => 150.00, 'new' => 300.00],
            ['date' => Carbon::now()->subMonth(), 'prev' => 300.00, 'new' => 200.00],
            ['date' => Carbon::now()->subDays(15), 'prev' => 200.00, 'new' => 250.00],
        ];

        foreach ($budgetChanges as $change) {
            BudgetHistory::create([
                'campaign_id' => $campaign->id,
                'previous_budget' => $change['prev'],
                'new_budget' => $change['new'],
                'changed_at' => $change['date'],
            ]);
        }

        $this->command->info("  Created: {$campaign->name} (ID: {$campaign->id}) - {$campaign->budgetHistories()->count()} budget changes");
    }

    /**
     * Create a campaign with pause and resume actions.
     */
    protected function createCampaignWithPauseResume(User $user): void
    {
        $campaign = Campaign::create([
            'user_id' => $user->id,
            'name' => 'Pause/Resume Campaign',
            'current_daily_budget' => 180.00,
            'is_active' => true,
            'created_at' => Carbon::now()->subMonths(3),
        ]);

        // Create budget history with pause and resume
        $budgetChanges = [
            ['date' => Carbon::now()->subMonths(3), 'prev' => 0, 'new' => 200.00],
            ['date' => Carbon::now()->subMonths(2)->subDays(20), 'prev' => 200.00, 'new' => 0], // Paused
            ['date' => Carbon::now()->subMonths(2)->subDays(10), 'prev' => 0, 'new' => 150.00], // Resumed
            ['date' => Carbon::now()->subMonth()->subDays(5), 'prev' => 150.00, 'new' => 0], // Paused again
            ['date' => Carbon::now()->subDays(20), 'prev' => 0, 'new' => 180.00], // Resumed
        ];

        foreach ($budgetChanges as $change) {
            BudgetHistory::create([
                'campaign_id' => $campaign->id,
                'previous_budget' => $change['prev'],
                'new_budget' => $change['new'],
                'changed_at' => $change['date'],
            ]);
        }

        $this->command->info("  Created: {$campaign->name} (ID: {$campaign->id}) - Multiple pause/resume cycles");
    }
}
