import React, { useState } from 'react';
import type {Campaign} from '../../types/campaign';
import { Input } from '../common/Input';
import { Button } from '../common/Button';
import { formatCurrency } from '../../utils/formatters';

interface BudgetControlProps {
  campaign: Campaign;
  onSubmit: (newBudget: number) => Promise<void>;
  onCancel: () => void;
}

export function BudgetControl({ campaign, onSubmit, onCancel }: BudgetControlProps) {
  const [budget, setBudget] = useState(campaign.current_daily_budget.toString());
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    const budgetNum = parseFloat(budget);
    if (isNaN(budgetNum) || budgetNum < 0) {
      setError('Please enter a valid budget amount');
      return;
    }

    if (budgetNum === campaign.current_daily_budget) {
      setError('New budget must be different from current budget');
      return;
    }

    try {
      setLoading(true);
      await onSubmit(budgetNum);
    } catch (err) {
      setError('Failed to update budget');
    } finally {
      setLoading(false);
    }
  };

  const budgetNum = parseFloat(budget) || 0;
  const difference = budgetNum - campaign.current_daily_budget;
  const newDailyLimit = budgetNum * 2;

  return (
    <form onSubmit={handleSubmit}>
      <div className="mb-4 p-4 bg-gray-50 rounded-lg">
        <div className="grid grid-cols-2 gap-4 text-sm">
          <div>
            <p className="text-gray-600">Current Budget</p>
            <p className="text-lg font-semibold text-gray-900">
              {formatCurrency(campaign.current_daily_budget)}
            </p>
          </div>
          <div>
            <p className="text-gray-600">Current Daily Limit (2x)</p>
            <p className="text-lg font-semibold text-gray-900">
              {formatCurrency(campaign.daily_limit)}
            </p>
          </div>
        </div>
      </div>

      <Input
        label="New Daily Budget ($)"
        type="number"
        step="0.01"
        min="0"
        value={budget}
        onChange={(e) => setBudget(e.target.value)}
        placeholder="Enter new budget"
        required
      />

      {budgetNum > 0 && (
        <div className="mb-4 p-4 bg-blue-50 rounded-lg">
          <div className="grid grid-cols-2 gap-4 text-sm">
            <div>
              <p className="text-gray-600">Change</p>
              <p className={`text-lg font-semibold ${difference >= 0 ? 'text-green-600' : 'text-red-600'}`}>
                {difference >= 0 ? '+' : ''}{formatCurrency(difference)}
              </p>
            </div>
            <div>
              <p className="text-gray-600">New Daily Limit</p>
              <p className="text-lg font-semibold text-blue-600">
                {formatCurrency(newDailyLimit)}
              </p>
            </div>
          </div>
        </div>
      )}

      {error && (
        <div className="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
          {error}
        </div>
      )}

      <div className="flex gap-3">
        <Button type="submit" disabled={loading} className="flex-1">
          {loading ? 'Updating...' : 'Update Budget'}
        </Button>
        <Button type="button" variant="secondary" onClick={onCancel} className="flex-1">
          Cancel
        </Button>
      </div>
    </form>
  );
}
