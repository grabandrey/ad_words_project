import apiClient from './api';
import type {Campaign} from '../types/campaign';

export const campaignService = {
  // Get all campaigns
  async getAll() {
    const response = await apiClient.get<{ data: Campaign[] }>('/campaigns');
    return response.data.data;
  },

  // Get single campaign
  async getById(id: number) {
    const response = await apiClient.get<{ data: Campaign }>(`/campaigns/${id}`);
    return response.data.data;
  },

  // Create campaign
  async create(data: { name: string; current_daily_budget?: number }) {
    const response = await apiClient.post<{ data: Campaign }>('/campaigns', data);
    return response.data.data;
  },

  // Update campaign
  async update(id: number, data: { name?: string; current_daily_budget?: number }) {
    const response = await apiClient.put<{ data: Campaign }>(`/campaigns/${id}`, data);
    return response.data.data;
  },

  // Delete campaign
  async delete(id: number) {
    await apiClient.delete(`/campaigns/${id}`);
  },

  // Pause campaign
  async pause(id: number) {
    const response = await apiClient.post<{ data: Campaign }>(`/campaigns/${id}/pause`);
    return response.data.data;
  },

  // Resume campaign
  async resume(id: number, daily_budget?: number) {
    const response = await apiClient.post<{ data: Campaign }>(`/campaigns/${id}/resume`, {
      daily_budget,
    });
    return response.data.data;
  },

  // Update budget
  async updateBudget(id: number, new_budget: number) {
    const response = await apiClient.post<{ data: Campaign }>(`/campaigns/${id}/budget`, {
      new_budget,
    });
    return response.data.data;
  },

  // Get daily summary
  async getDailySummary(id: number, from?: string, to?: string) {
    const params = new URLSearchParams();
    if (from) params.append('from', from);
    if (to) params.append('to', to);

    const response = await apiClient.get(`/campaigns/${id}/daily-summary?${params.toString()}`);
    return response.data.data;
  },

  // Get statistics
  async getStats(id: number, period: string = 'today') {
    const response = await apiClient.get(`/campaigns/${id}/stats?period=${period}`);
    return response.data;
  },
};
