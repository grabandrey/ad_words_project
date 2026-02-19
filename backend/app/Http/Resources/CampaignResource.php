<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CampaignResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $todaySpent = $this->costs()
            ->whereDate('generated_at', today())
            ->sum('amount');

        $dailyLimit = $this->current_daily_budget * 2;
        $todayRemaining = max(0, $dailyLimit - $todaySpent);

        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'name' => $this->name,
            'current_daily_budget' => (float) $this->current_daily_budget,
            'is_active' => $this->is_active,
            'is_paused' => $this->isPaused(),
            'created_at' => $this->created_at->toIso8601String(),
            'updated_at' => $this->updated_at->toIso8601String(),
            'today_spent' => (float) $todaySpent,
            'today_remaining' => (float) $todayRemaining,
            'daily_limit' => (float) $dailyLimit,
            'costs_count' => $this->whenCounted('costs'),
            'budget_histories' => $this->whenLoaded('budgetHistories'),
        ];
    }
}
