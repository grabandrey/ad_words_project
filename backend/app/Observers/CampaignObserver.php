<?php

namespace App\Observers;

use App\Models\Campaign;
use App\Models\BudgetHistory;

class CampaignObserver
{
    /**
     * Handle the Campaign "updating" event.
     * Create a budget history record when budget changes.
     */
    public function updating(Campaign $campaign): void
    {
        // Check if budget is being changed
        if ($campaign->isDirty('current_daily_budget')) {
            $previousBudget = $campaign->getOriginal('current_daily_budget');
            $newBudget = $campaign->current_daily_budget;

            // Only create history if there's an actual change
            if ($previousBudget != $newBudget) {
                BudgetHistory::create([
                    'campaign_id' => $campaign->id,
                    'previous_budget' => $previousBudget,
                    'new_budget' => $newBudget,
                    'changed_at' => now(),
                ]);
            }
        }
    }

    /**
     * Handle the Campaign "created" event.
     * Create initial budget history record.
     */
    public function created(Campaign $campaign): void
    {
        // Create initial budget history record
        if ($campaign->current_daily_budget > 0) {
            BudgetHistory::create([
                'campaign_id' => $campaign->id,
                'previous_budget' => 0,
                'new_budget' => $campaign->current_daily_budget,
                'changed_at' => $campaign->created_at,
            ]);
        }
    }
}
