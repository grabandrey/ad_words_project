import type {DailySummary} from '../../types/campaign';
import { formatCurrency, formatDate, formatPercentage } from '../../utils/formatters';

interface DailyHistoryTableProps {
  summary: DailySummary[];
}

export function DailyHistoryTable({ summary }: DailyHistoryTableProps) {
  if (summary.length === 0) {
    return (
      <div className="text-center py-8 text-gray-500">
        No cost data available for this period
      </div>
    );
  }

  return (
    <div className="overflow-x-auto">
      <table className="min-w-full divide-y divide-gray-200">
        <thead className="bg-gray-50">
          <tr>
            <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
              Date
            </th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Max Budget
            </th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Daily Limit (2x)
            </th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Total Cost
            </th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Cost Count
            </th>
            <th className="px-6 py-3 text-right text-xs font-medium text-gray-500 uppercase tracking-wider">
              Utilization
            </th>
          </tr>
        </thead>
        <tbody className="bg-white divide-y divide-gray-200">
          {summary.map((day, index) => (
            <tr key={index} className="hover:bg-gray-50">
              <td className="px-6 py-4 whitespace-nowrap text-sm font-medium text-gray-900">
                {formatDate(day.date)}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-900">
                {formatCurrency(day.max_budget)}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                {formatCurrency(day.daily_limit)}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-right font-semibold text-blue-600">
                {formatCurrency(day.total_cost)}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-right text-gray-600">
                {day.cost_count}
              </td>
              <td className="px-6 py-4 whitespace-nowrap text-sm text-right">
                <span className={`font-medium ${
                  day.utilization > 90 ? 'text-red-600' :
                  day.utilization > 70 ? 'text-yellow-600' :
                  'text-green-600'
                }`}>
                  {formatPercentage(day.utilization)}
                </span>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
