import { useState, useEffect } from 'react';
import type {DailySummary} from '../types/campaign';
import { costService } from '../services/costService';
import { format, subMonths } from 'date-fns';

export function useDailySummary(campaignId: number) {
  const [summary, setSummary] = useState<DailySummary[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);

  const fetchSummary = async () => {
    try {
      setLoading(true);
      const from = format(subMonths(new Date(), 3), 'yyyy-MM-dd');
      const to = format(new Date(), 'yyyy-MM-dd');
      const data = await costService.getDailySummary(campaignId, from, to);
      setSummary(data);
      setError(null);
    } catch (err) {
      setError('Failed to fetch daily summary');
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  useEffect(() => {
    if (campaignId) {
      fetchSummary();
    }
  }, [campaignId]);

  return {
    summary,
    loading,
    error,
    refetch: fetchSummary,
  };
}
