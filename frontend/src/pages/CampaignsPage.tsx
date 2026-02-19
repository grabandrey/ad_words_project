import { useState } from 'react';
import { useNavigate } from 'react-router-dom';
import { useCampaigns } from '../hooks/useCampaigns';
import { CampaignCard } from '../components/campaigns/CampaignCard';
import { CampaignForm } from '../components/campaigns/CampaignForm';
import { BudgetControl } from '../components/campaigns/BudgetControl';
import { Modal } from '../components/common/Modal';
import { Button } from '../components/common/Button';
import { Layout } from '../components/common/Layout';

export function CampaignsPage() {
  const navigate = useNavigate();
  const { campaigns, loading, error, createCampaign, updateBudget, pauseCampaign, resumeCampaign } = useCampaigns();
  const [isCreateModalOpen, setIsCreateModalOpen] = useState(false);
  const [budgetModalCampaignId, setBudgetModalCampaignId] = useState<number | null>(null);

  const handleCreateCampaign = async (name: string, budget: number) => {
    await createCampaign(name, budget);
    setIsCreateModalOpen(false);
  };

  const handleUpdateBudget = async (newBudget: number) => {
    if (budgetModalCampaignId) {
      await updateBudget(budgetModalCampaignId, newBudget);
      setBudgetModalCampaignId(null);
    }
  };

  const selectedCampaign = campaigns.find(c => c.id === budgetModalCampaignId);

  if (loading) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="text-xl text-gray-600">Loading campaigns...</div>
      </div>
    );
  }

  if (error) {
    return (
      <div className="flex items-center justify-center h-screen">
        <div className="text-xl text-red-600">{error}</div>
      </div>
    );
  }

  return (
    <Layout>
      <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div className="flex justify-between items-center mb-8">
          <div>
            <h1 className="text-3xl font-bold text-gray-900">AdWords Campaigns</h1>
            <p className="mt-2 text-gray-600">Manage your campaign budgets and monitor spending</p>
          </div>
          <Button onClick={() => setIsCreateModalOpen(true)}>
            Create Campaign
          </Button>
        </div>

        {campaigns.length === 0 ? (
          <div className="text-center py-12">
            <p className="text-xl text-gray-600 mb-4">No campaigns yet</p>
            <Button onClick={() => setIsCreateModalOpen(true)}>
              Create Your First Campaign
            </Button>
          </div>
        ) : (
          <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            {campaigns.map((campaign) => (
              <CampaignCard
                key={campaign.id}
                campaign={campaign}
                onViewDetails={(id) => navigate(`/campaigns/${id}`)}
                onPause={pauseCampaign}
                onResume={resumeCampaign}
                onUpdateBudget={setBudgetModalCampaignId}
              />
            ))}
          </div>
        )}
      </div>

      {/* Create Campaign Modal */}
      <Modal
        isOpen={isCreateModalOpen}
        onClose={() => setIsCreateModalOpen(false)}
        title="Create New Campaign"
      >
        <CampaignForm
          onSubmit={handleCreateCampaign}
          onCancel={() => setIsCreateModalOpen(false)}
        />
      </Modal>

      {/* Budget Update Modal */}
      {selectedCampaign && (
        <Modal
          isOpen={budgetModalCampaignId !== null}
          onClose={() => setBudgetModalCampaignId(null)}
          title={`Update Budget - ${selectedCampaign.name}`}
        >
          <BudgetControl
            campaign={selectedCampaign}
            onSubmit={handleUpdateBudget}
            onCancel={() => setBudgetModalCampaignId(null)}
          />
        </Modal>
      )}
    </Layout>
  );
}
