import { useState, useEffect } from 'react';
import type {Campaign} from '../types/campaign';
import { campaignService } from '../services/campaignService';

export function useCampaigns() {
  const [campaigns, setCampaigns] = useState<Campaign[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchCampaigns = async () => {
    try {
      setLoading(true);
      const data = await campaignService.getAll();
      setCampaigns(data);
      setError(null);
    } catch (err) {
      setError('Failed to fetch campaigns');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCampaigns();
  }, []);

  const createCampaign = async (name: string, budget: number) => {
    try {
      await campaignService.create({ name, current_daily_budget: budget });
      await fetchCampaigns();
    } catch (err) {
      console.error('Failed to create campaign', err);
      throw err;
    }
  };

  const updateBudget = async (id: number, newBudget: number) => {
    try {
      await campaignService.updateBudget(id, newBudget);
      await fetchCampaigns();
    } catch (err) {
      console.error('Failed to update budget', err);
      throw err;
    }
  };

  const pauseCampaign = async (id: number) => {
    try {
      await campaignService.pause(id);
      await fetchCampaigns();
    } catch (err) {
      console.error('Failed to pause campaign', err);
      throw err;
    }
  };

  const resumeCampaign = async (id: number, budget?: number) => {
    try {
      await campaignService.resume(id, budget);
      await fetchCampaigns();
    } catch (err) {
      console.error('Failed to resume campaign', err);
      throw err;
    }
  };

  const deleteCampaign = async (id: number) => {
    try {
      await campaignService.delete(id);
      await fetchCampaigns();
    } catch (err) {
      console.error('Failed to delete campaign', err);
      throw err;
    }
  };

  return {
    campaigns,
    loading,
    error,
    refetch: fetchCampaigns,
    createCampaign,
    updateBudget,
    pauseCampaign,
    resumeCampaign,
    deleteCampaign,
  };
}
