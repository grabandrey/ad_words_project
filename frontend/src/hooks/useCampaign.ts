import { useState, useEffect } from 'react';
import type {Campaign, CampaignStats} from '../types/campaign';
import { campaignService } from '../services/campaignService';

export function useCampaign(id: number) {
  const [campaign, setCampaign] = useState<Campaign | null>(null);
  const [stats, setStats] = useState<CampaignStats | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchCampaign = async () => {
    try {
      setLoading(true);
      const [campaignData, statsData] = await Promise.all([
        campaignService.getById(id),
        campaignService.getStats(id, 'month'),
      ]);
      setCampaign(campaignData);
      setStats(statsData);
      setError(null);
    } catch (err) {
      setError('Failed to fetch campaign');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    fetchCampaign();
  }, [id]);

  return {
    campaign,
    stats,
    loading,
    error,
    refetch: fetchCampaign,
  };
}
