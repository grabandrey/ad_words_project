import { useState } from 'react';
import { useParams, useNavigate } from 'react-router-dom';
import { useCampaign } from '../hooks/useCampaign';
import { useDailySummary } from '../hooks/useDailySummary';
import { Card } from '../components/common/Card';
import { Button } from '../components/common/Button';
import { Layout } from '../components/common/Layout';
import { BudgetControl } from '../components/campaigns/BudgetControl';
import { DailyHistoryTable } from '../components/costs/DailyHistoryTable';
import { DailyHistoryChart } from '../components/costs/DailyHistoryChart';
import { Modal } from '../components/common/Modal';
import { formatCurrency } from '../utils/formatters';
import { campaignService } from '../services/campaignService';

export function CampaignDetailPage() {
  const { id } = useParams<{ id: string }>();
  const navigate = useNavigate();
  const campaignId = parseInt(id || '0');
  const { campaign, stats, loading, refetch } = useCampaign(campaignId);
  const { summary, loading: summaryLoading } = useDailySummary(campaignId);
  const [isBudgetModalOpen, setIsBudgetModalOpen] = useState(false);
  const [activeTab, setActiveTab] = useState<'table' | 'chart'>('table');

  const handleUpdateBudget = async (newBudget: number) => {
    await campaignService.updateBudget(campaignId, newBudget);
    await refetch();
    setIsBudgetModalOpen(false);
  };

  if (loading) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="text-xl text-gray-600">Loading campaign...</div>
      </div>
    );
  }

  if (!campaign) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="text-xl text-red-600">Campaign not found</div>
      </div>
    );
  }

  return (
    <Layout>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        {/* Header */}
        <div className="mb-8">
          <Button variant="secondary" onClick={() => navigate('/')} className="mb-4">
            ← Back to Campaigns
          </Button>
          <div className="flex justify-between items-start">
            <div>
              <h1 className="text-3xl font-bold text-gray-900">{campaign.name}</h1>
              <p className="mt-2 text-gray-600">Campaign #{campaign.id}</p>
            </div>
            <Button onClick={() => setIsBudgetModalOpen(true)}>
              Adjust Budget
            </Button>
          </div>
        </div>

        {/* Stats Cards */}
        {stats && (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <Card>
              <p className="text-sm text-gray-500">Daily Budget</p>
              <p className="text-2xl font-bold text-gray-900 mt-1">
                {formatCurrency(stats.daily_budget)}
              </p>
              <p className="text-xs text-gray-500 mt-1">Limit: {formatCurrency(stats.daily_limit)}</p>
            </Card>

            <Card>
              <p className="text-sm text-gray-500">Today's Spent</p>
              <p className="text-2xl font-bold text-blue-600 mt-1">
                {formatCurrency(stats.daily_spent)}
              </p>
              <p className="text-xs text-gray-500 mt-1">Remaining: {formatCurrency(stats.daily_remaining)}</p>
            </Card>

            <Card>
              <p className="text-sm text-gray-500">Monthly Budget Limit</p>
              <p className="text-2xl font-bold text-gray-900 mt-1">
                {formatCurrency(stats.monthly_limit)}
              </p>
              <p className="text-xs text-gray-500 mt-1">Sum of max daily budgets</p>
            </Card>

            <Card>
              <p className="text-sm text-gray-500">Monthly Spent</p>
              <p className="text-2xl font-bold text-green-600 mt-1">
                {formatCurrency(stats.monthly_spent)}
              </p>
              <p className="text-xs text-gray-500 mt-1">Remaining: {formatCurrency(stats.monthly_remaining)}</p>
            </Card>
          </div>
        )}

        {/* Daily History */}
        <Card title="Daily History (Last 3 Months)">
          {/* Tab Navigation */}
          <div className="flex gap-4 mb-6 border-b">
            <button
              onClick={() => setActiveTab('table')}
              className={`pb-2 px-4 font-medium transition-colors ${
                activeTab === 'table'
                  ? 'border-b-2 border-blue-600 text-blue-600'
                  : 'text-gray-500 hover:text-gray-700'
              }`}
            >
              Table View
            </button>
            <button
              onClick={() => setActiveTab('chart')}
              className={`pb-2 px-4 font-medium transition-colors ${
                activeTab === 'chart'
                  ? 'border-b-2 border-blue-600 text-blue-600'
                  : 'text-gray-500 hover:text-gray-700'
              }`}
            >
              Chart View
            </button>
          </div>

          {summaryLoading ? (
            <div className="text-center py-8 text-gray-600">Loading data...</div>
          ) : (
            <>
              {activeTab === 'table' && <DailyHistoryTable summary={summary} />}
              {activeTab === 'chart' && <DailyHistoryChart summary={summary} />}
            </>
          )}
        </Card>
      </div>

      {/* Budget Update Modal */}
      <Modal
        isOpen={isBudgetModalOpen}
        onClose={() => setIsBudgetModalOpen(false)}
        title={`Update Budget - ${campaign.name}`}
      >
        <BudgetControl
          campaign={campaign}
          onSubmit={handleUpdateBudget}
          onCancel={() => setIsBudgetModalOpen(false)}
        />
      </Modal>
    </Layout>
  );
}
