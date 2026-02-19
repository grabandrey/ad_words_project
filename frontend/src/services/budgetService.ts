import apiClient from './api';
import { BudgetHistory } from '../types/campaign';

export const budgetService = {
  // Get budget history for a campaign
  async getHistory(campaignId: number, from?: string, to?: string) {
    const params = new URLSearchParams();
    if (from) params.append('from', from);
    if (to) params.append('to', to);

    const response = await apiClient.get<{ data: BudgetHistory[] }>(
      `/campaigns/${campaignId}/budget-history?${params.toString()}`
    );
    return response.data.data;
  },
};
