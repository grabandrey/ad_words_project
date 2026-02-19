export interface Campaign {
  id: number;
  user_id: number;
  name: string;
  current_daily_budget: number;
  is_active: boolean;
  is_paused: boolean;
  created_at: string;
  updated_at: string;
  today_spent: number;
  today_remaining: number;
  daily_limit: number;
}

export interface BudgetHistory {
  id: number;
  campaign_id: number;
  previous_budget: number;
  new_budget: number;
  changed_at: string;
}

export interface Cost {
  id: number;
  campaign_id: number;
  amount: number;
  generated_at: string;
  budget_at_generation: number;
  daily_limit_at_generation: number;
  created_at: string;
}

export interface DailySummary {
  date: string;
  max_budget: number;
  total_cost: number;
  cost_count: number;
  daily_limit: number;
  utilization: number;
}

export interface CampaignStats {
  daily_spent: number;
  daily_budget: number;
  daily_limit: number;
  daily_remaining: number;
  monthly_spent: number;
  monthly_limit: number;
  monthly_remaining: number;
  period_spent: number;
  period_costs_count: number;
  average_cost: number;
  cost_trend: Array<{ date: string; total: number }>;
}
