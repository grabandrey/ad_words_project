<?php

namespace Database\Factories;

use App\Models\Campaign;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Campaign>
 */
class CampaignFactory extends Factory
{
    protected $model = Campaign::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->catchPhrase() . ' Campaign',
            'current_daily_budget' => fake()->randomFloat(2, 50, 500),
            'is_active' => true,
        ];
    }

    /**
     * Indicate that the campaign is paused.
     */
    public function paused(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => false,
            'current_daily_budget' => 0,
        ]);
    }

    /**
     * Indicate that the campaign is active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_active' => true,
        ]);
    }

    /**
     * Set a specific budget.
     */
    public function withBudget(float $budget): static
    {
        return $this->state(fn (array $attributes) => [
            'current_daily_budget' => $budget,
        ]);
    }
}
