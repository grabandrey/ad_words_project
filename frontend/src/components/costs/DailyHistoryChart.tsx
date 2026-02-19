import { LineChart, Line, BarChart, Bar, XAxis, YAxis, CartesianGrid, Tooltip, Legend, ResponsiveContainer } from 'recharts';
import type {DailySummary} from '../../types/campaign';
import { formatCurrency } from '../../utils/formatters';
import { format, parseISO } from 'date-fns';

interface DailyHistoryChartProps {
  summary: DailySummary[];
}

export function DailyHistoryChart({ summary }: DailyHistoryChartProps) {
  if (summary.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        No data available to display chart
      </div>
    );
  }

  const chartData = summary.map(day => ({
    date: format(parseISO(day.date), 'MMM dd'),
    'Max Budget': day.max_budget,
    'Total Cost': day.total_cost,
    'Daily Limit': day.daily_limit,
  }));

  return (
    <div className="space-y-8">
      {/* Cost vs Budget Chart */}
      <div>
        <h3 className="text-lg font-semibold mb-4 text-gray-800">Daily Cost Comparison</h3>
        <ResponsiveContainer width="100%" height={300}>
          <BarChart data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="date" />
            <YAxis tickFormatter={(value) => `$${value}`} />
            <Tooltip formatter={(value: number) => formatCurrency(value)} />
            <Legend />
            <Bar dataKey="Total Cost" fill="#3b82f6" />
            <Bar dataKey="Max Budget" fill="#10b981" />
          </BarChart>
        </ResponsiveContainer>
      </div>

      {/* Budget Trend Chart */}
      <div>
        <h3 className="text-lg font-semibold mb-4 text-gray-800">Budget & Cost Trends</h3>
        <ResponsiveContainer width="100%" height={300}>
          <LineChart data={chartData}>
            <CartesianGrid strokeDasharray="3 3" />
            <XAxis dataKey="date" />
            <YAxis tickFormatter={(value) => `$${value}`} />
            <Tooltip formatter={(value: number) => formatCurrency(value)} />
            <Legend />
            <Line type="monotone" dataKey="Max Budget" stroke="#10b981" strokeWidth={2} />
            <Line type="monotone" dataKey="Total Cost" stroke="#3b82f6" strokeWidth={2} />
            <Line type="monotone" dataKey="Daily Limit" stroke="#ef4444" strokeWidth={2} strokeDasharray="5 5" />
          </LineChart>
        </ResponsiveContainer>
      </div>
    </div>
  );
}
