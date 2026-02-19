import React, { useState } from 'react';
import { Input } from '../common/Input';
import { Button } from '../common/Button';

interface CampaignFormProps {
  onSubmit: (name: string, budget: number) => Promise<void>;
  onCancel: () => void;
}

export function CampaignForm({ onSubmit, onCancel }: CampaignFormProps) {
  const [name, setName] = useState('');
  const [budget, setBudget] = useState('');
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState('');

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');

    if (!name.trim()) {
      setError('Campaign name is required');
      return;
    }

    const budgetNum = parseFloat(budget);
    if (isNaN(budgetNum) || budgetNum < 0) {
      setError('Please enter a valid budget amount');
      return;
    }

    try {
      setLoading(true);
      await onSubmit(name, budgetNum);
    } catch (err) {
      setError('Failed to create campaign');
    } finally {
      setLoading(false);
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <Input
        label="Campaign Name"
        type="text"
        value={name}
        onChange={(e) => setName(e.target.value)}
        placeholder="e.g., Summer Sale Campaign"
        required
      />

      <Input
        label="Daily Budget ($)"
        type="number"
        step="0.01"
        min="0"
        value={budget}
        onChange={(e) => setBudget(e.target.value)}
        placeholder="e.g., 150.00"
        required
      />

      {error && (
        <div className="mb-4 p-3 bg-red-100 border border-red-400 text-red-700 rounded">
          {error}
        </div>
      )}

      <div className="flex gap-3">
        <Button type="submit" disabled={loading} className="flex-1">
          {loading ? 'Creating...' : 'Create Campaign'}
        </Button>
        <Button type="button" variant="secondary" onClick={onCancel} className="flex-1">
          Cancel
        </Button>
      </div>
    </form>
  );
}
