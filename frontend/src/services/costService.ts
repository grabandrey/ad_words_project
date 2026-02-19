import apiClient from './api';
import type {Cost, DailySummary} from '../types/campaign';

export const costService = {
  // Get costs for a campaign
  async getCosts(campaignId: number, from?: string, to?: string) {
    const params = new URLSearchParams();
    if (from) params.append('from', from);
    if (to) params.append('to', to);

    const response = await apiClient.get<{ data: Cost[] }>(
      `/campaigns/${campaignId}/costs?${params.toString()}`
    );
    return response.data.data;
  },

  // Get daily summary
  async getDailySummary(campaignId: number, from?: string, to?: string): Promise<DailySummary[]> {
    const params = new URLSearchParams();
    if (from) params.append('from', from);
    if (to) params.append('to', to);

    const response = await apiClient.get<{ data: DailySummary[] }>(
      `/campaigns/${campaignId}/daily-summary?${params.toString()}`
    );
    return response.data.data;
  },
};
