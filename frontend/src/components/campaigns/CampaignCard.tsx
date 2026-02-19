import type {Campaign} from '../../types/campaign';
import { formatCurrency } from '../../utils/formatters';
import { Button } from '../common/Button';
import { Card } from '../common/Card';

interface CampaignCardProps {
  campaign: Campaign;
  onViewDetails: (id: number) => void;
  onPause: (id: number) => void;
  onResume: (id: number) => void;
  onUpdateBudget: (id: number) => void;
}

export function CampaignCard({
  campaign,
  onViewDetails,
  onPause,
  onResume,
  onUpdateBudget,
}: CampaignCardProps) {
  const utilizationPercent = campaign.daily_limit > 0
    ? (campaign.today_spent / campaign.daily_limit) * 100
    : 0;

  return (
    <Card>
      <div className="flex justify-between items-start mb-4">
        <div>
          <h3 className="text-lg font-semibold text-gray-900">{campaign.name}</h3>
          <p className="text-sm text-gray-500">Campaign #{campaign.id}</p>
        </div>
        <div className="flex gap-2">
          {campaign.is_paused ? (
            <Button size="sm" variant="success" onClick={() => onResume(campaign.id)}>
              Resume
            </Button>
          ) : (
            <Button size="sm" variant="secondary" onClick={() => onPause(campaign.id)}>
              Pause
            </Button>
          )}
        </div>
      </div>

      <div className="grid grid-cols-2 gap-4 mb-4">
        <div>
          <p className="text-sm text-gray-500">Daily Budget</p>
          <p className="text-xl font-bold text-gray-900">
            {formatCurrency(campaign.current_daily_budget)}
          </p>
        </div>
        <div>
          <p className="text-sm text-gray-500">Today's Spent</p>
          <p className="text-xl font-bold text-blue-600">
            {formatCurrency(campaign.today_spent)}
          </p>
        </div>
      </div>

      <div className="mb-4">
        <div className="flex justify-between text-sm mb-1">
          <span className="text-gray-600">Daily Utilization</span>
          <span className="font-medium">{utilizationPercent.toFixed(1)}%</span>
        </div>
        <div className="w-full bg-gray-200 rounded-full h-2">
          <div
            className={`h-2 rounded-full ${
              utilizationPercent > 90 ? 'bg-red-600' :
              utilizationPercent > 70 ? 'bg-yellow-500' :
              'bg-green-500'
            }`}
            style={{ width: `${Math.min(utilizationPercent, 100)}%` }}
          />
        </div>
        <div className="flex justify-between text-xs text-gray-500 mt-1">
          <span>Spent: {formatCurrency(campaign.today_spent)}</span>
          <span>Limit: {formatCurrency(campaign.daily_limit)}</span>
        </div>
      </div>

      <div className="flex gap-2">
        <Button
          size="sm"
          variant="primary"
          onClick={() => onViewDetails(campaign.id)}
          className="flex-1"
        >
          View Details
        </Button>
        <Button
          size="sm"
          variant="secondary"
          onClick={() => onUpdateBudget(campaign.id)}
          className="flex-1"
        >
          Adjust Budget
        </Button>
      </div>
    </Card>
  );
}
