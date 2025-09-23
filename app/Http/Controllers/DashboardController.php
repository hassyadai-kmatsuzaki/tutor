<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Customer;
use App\Models\PropertyMatch;
use App\Models\Activity;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    /**
     * ダッシュボード統計データ取得
     */
    public function stats(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month'); // day, week, month, year
        $userId = Auth::id();
        $isAdmin = Auth::user()->isAdmin();

        $stats = [
            'overview' => $this->getOverviewStats($isAdmin, $userId),
            'properties' => $this->getPropertyStats($isAdmin, $userId),
            'customers' => $this->getCustomerStats($isAdmin, $userId),
            'matches' => $this->getMatchStats($isAdmin, $userId),
            'activities' => $this->getActivityStats($period, $isAdmin, $userId),
            'performance' => $this->getPerformanceStats($period, $isAdmin, $userId),
            'alerts' => $this->getAlerts($isAdmin, $userId),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * 最近の活動取得
     */
    public function activities(Request $request): JsonResponse
    {
        $limit = $request->get('limit', 20);
        $userId = Auth::id();
        $isAdmin = Auth::user()->isAdmin();

        $query = Activity::with(['user']);

        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }

        $activities = $query->latest('activity_date')
            ->limit($limit)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    /**
     * アラート一覧取得
     */
    public function alerts(Request $request): JsonResponse
    {
        $userId = Auth::id();
        $isAdmin = Auth::user()->isAdmin();

        $alerts = $this->getAlerts($isAdmin, $userId);

        return response()->json([
            'success' => true,
            'data' => $alerts
        ]);
    }

    /**
     * 売上分析データ取得
     */
    public function salesAnalysis(Request $request): JsonResponse
    {
        $period = $request->get('period', 'month');
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $salesData = [
            'total_contracts' => PropertyMatch::contracted()
                ->whereBetween('response_at', [$startDate, $endDate])
                ->count(),
            'total_revenue' => $this->calculateTotalRevenue($startDate, $endDate),
            'average_deal_size' => $this->calculateAverageDealSize($startDate, $endDate),
            'conversion_funnel' => $this->getConversionFunnel($startDate, $endDate),
            'monthly_trend' => $this->getMonthlySalesTrend(),
            'top_performers' => $this->getTopPerformers($startDate, $endDate),
            'property_type_performance' => $this->getPropertyTypePerformance($startDate, $endDate),
        ];

        return response()->json([
            'success' => true,
            'data' => $salesData
        ]);
    }

    /**
     * 概要統計取得
     */
    private function getOverviewStats(bool $isAdmin, int $userId): array
    {
        $propertyQuery = Property::query();
        $customerQuery = Customer::query();
        $matchQuery = PropertyMatch::query();

        if (!$isAdmin) {
            $propertyQuery->where('created_by', $userId);
            $customerQuery->where('assigned_to', $userId);
            $matchQuery->where('created_by', $userId);
        }

        return [
            'total_properties' => $propertyQuery->count(),
            'available_properties' => $propertyQuery->available()->count(),
            'total_customers' => $customerQuery->count(),
            'active_customers' => $customerQuery->active()->count(),
            'total_matches' => $matchQuery->count(),
            'high_score_matches' => $matchQuery->highScore(80)->count(),
            'contracts_this_month' => PropertyMatch::contracted()
                ->whereMonth('response_at', now()->month)
                ->whereYear('response_at', now()->year)
                ->count(),
        ];
    }

    /**
     * 物件統計取得
     */
    private function getPropertyStats(bool $isAdmin, int $userId): array
    {
        $baseQuery = function() use ($isAdmin, $userId) {
            $query = Property::query();
            if (!$isAdmin) {
                $query->where('created_by', $userId);
            }
            return $query;
        };

        return [
            'by_status' => $baseQuery()->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray(),
            'by_type' => $baseQuery()->selectRaw('property_type, COUNT(*) as count')
                ->groupBy('property_type')
                ->get()
                ->pluck('count', 'property_type')
                ->toArray(),
            'by_prefecture' => $baseQuery()->selectRaw('prefecture, COUNT(*) as count')
                ->groupBy('prefecture')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'prefecture')
                ->toArray(),
            'price_distribution' => $this->getPriceDistribution($baseQuery()),
            'yield_distribution' => $this->getYieldDistribution($baseQuery()),
        ];
    }

    /**
     * 顧客統計取得
     */
    private function getCustomerStats(bool $isAdmin, int $userId): array
    {
        $baseQuery = function() use ($isAdmin, $userId) {
            $query = Customer::query();
            if (!$isAdmin) {
                $query->where('assigned_to', $userId);
            }
            return $query;
        };

        return [
            'by_status' => $baseQuery()->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray(),
            'by_type' => $baseQuery()->selectRaw('customer_type, COUNT(*) as count')
                ->groupBy('customer_type')
                ->get()
                ->pluck('count', 'customer_type')
                ->toArray(),
            'by_priority' => $baseQuery()->selectRaw('priority, COUNT(*) as count')
                ->groupBy('priority')
                ->get()
                ->pluck('count', 'priority')
                ->toArray(),
            'budget_distribution' => $this->getBudgetDistribution($baseQuery()),
        ];
    }

    /**
     * マッチング統計取得
     */
    private function getMatchStats(bool $isAdmin, int $userId): array
    {
        $baseQuery = function() use ($isAdmin, $userId) {
            $query = PropertyMatch::query();
            if (!$isAdmin) {
                $query->where('created_by', $userId);
            }
            return $query;
        };

        return [
            'by_status' => $baseQuery()->selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status')
                ->toArray(),
            'score_distribution' => $this->getScoreDistribution($baseQuery()),
            'conversion_rates' => [
                'match_to_presentation' => $this->calculateConversionRate($baseQuery(), 'matched', 'presented'),
                'presentation_to_interest' => $this->calculateConversionRate($baseQuery(), 'presented', 'interested'),
                'interest_to_contract' => $this->calculateConversionRate($baseQuery(), 'interested', 'contracted'),
            ],
        ];
    }

    /**
     * 活動統計取得
     */
    private function getActivityStats(string $period, bool $isAdmin, int $userId): array
    {
        $startDate = $this->getStartDate($period);
        $endDate = now();

        $query = Activity::whereBetween('activity_date', [$startDate, $endDate]);

        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }

        return [
            'total_activities' => $query->count(),
            'by_type' => $query->selectRaw('activity_type, COUNT(*) as count')
                ->groupBy('activity_type')
                ->get()
                ->pluck('count', 'activity_type'),
            'daily_trend' => $this->getDailyActivityTrend($startDate, $endDate, $isAdmin, $userId),
        ];
    }

    /**
     * パフォーマンス統計取得
     */
    private function getPerformanceStats(string $period, bool $isAdmin, int $userId): array
    {
        $startDate = $this->getStartDate($period);
        $endDate = now();

        if ($isAdmin) {
            return [
                'top_sales_users' => User::withCount(['matches as contracts' => function ($query) use ($startDate, $endDate) {
                        $query->contracted()->whereBetween('response_at', [$startDate, $endDate]);
                    }])
                    ->orderByDesc('contracts')
                    ->limit(5)
                    ->get(['id', 'name', 'department'])
                    ->toArray(),
                'team_performance' => $this->getTeamPerformance($startDate, $endDate),
            ];
        } else {
            return [
                'my_contracts' => PropertyMatch::where('created_by', $userId)
                    ->contracted()
                    ->whereBetween('response_at', [$startDate, $endDate])
                    ->count(),
                'my_presentations' => PropertyMatch::where('created_by', $userId)
                    ->presented()
                    ->whereBetween('presented_at', [$startDate, $endDate])
                    ->count(),
            ];
        }
    }

    /**
     * アラート取得
     */
    private function getAlerts(bool $isAdmin, int $userId): array
    {
        $alerts = [];

        // 長期未接触顧客
        $longTimeNoContactQuery = Customer::longTimeNoContact(30);
        if (!$isAdmin) {
            $longTimeNoContactQuery->where('assigned_to', $userId);
        }
        $longTimeNoContactCount = $longTimeNoContactQuery->count();

        if ($longTimeNoContactCount > 0) {
            $alerts[] = [
                'type' => 'warning',
                'title' => '長期未接触顧客',
                'message' => "{$longTimeNoContactCount}名の顧客が30日以上未接触です",
                'count' => $longTimeNoContactCount,
                'action_url' => '/customers?long_time_no_contact=1'
            ];
        }

        // 次回接触予定
        $upcomingContactQuery = Customer::upcomingContact(7);
        if (!$isAdmin) {
            $upcomingContactQuery->where('assigned_to', $userId);
        }
        $upcomingContactCount = $upcomingContactQuery->count();

        if ($upcomingContactCount > 0) {
            $alerts[] = [
                'type' => 'info',
                'title' => '接触予定',
                'message' => "7日以内に{$upcomingContactCount}名の顧客との接触が予定されています",
                'count' => $upcomingContactCount,
                'action_url' => '/customers?upcoming_contact=1'
            ];
        }

        // 高スコアマッチング（未提案）
        $highScoreMatchesQuery = PropertyMatch::highScore(80)->notPresented();
        if (!$isAdmin) {
            $highScoreMatchesQuery->where('created_by', $userId);
        }
        $highScoreMatchesCount = $highScoreMatchesQuery->count();

        if ($highScoreMatchesCount > 0) {
            $alerts[] = [
                'type' => 'success',
                'title' => '高スコアマッチング',
                'message' => "{$highScoreMatchesCount}件の高スコアマッチングが未提案です",
                'count' => $highScoreMatchesCount,
                'action_url' => '/matches?high_score_only=1&not_presented_only=1'
            ];
        }

        return $alerts;
    }

    /**
     * 期間の開始日取得
     */
    private function getStartDate(string $period): \Carbon\Carbon
    {
        return match($period) {
            'day' => now()->startOfDay(),
            'week' => now()->startOfWeek(),
            'month' => now()->startOfMonth(),
            'year' => now()->startOfYear(),
            default => now()->startOfMonth()
        };
    }

    /**
     * 価格分布取得
     */
    private function getPriceDistribution($query): array
    {
        return $query->selectRaw('
            CASE 
                WHEN price < 1000 THEN "1000万円未満"
                WHEN price < 3000 THEN "1000-3000万円"
                WHEN price < 5000 THEN "3000-5000万円"
                WHEN price < 10000 THEN "5000万円-1億円"
                ELSE "1億円以上"
            END as price_range,
            COUNT(*) as count
        ')
        ->groupBy('price_range')
        ->get()
        ->pluck('count', 'price_range')
        ->toArray();
    }

    /**
     * 利回り分布取得
     */
    private function getYieldDistribution($query): array
    {
        return $query->whereNotNull('current_profit')
            ->selectRaw('
                CASE 
                    WHEN current_profit < 3 THEN "3%未満"
                    WHEN current_profit < 5 THEN "3-5%"
                    WHEN current_profit < 7 THEN "5-7%"
                    WHEN current_profit < 10 THEN "7-10%"
                    ELSE "10%以上"
                END as yield_range,
                COUNT(*) as count
            ')
            ->groupBy('yield_range')
            ->get()
            ->pluck('count', 'yield_range')
            ->toArray();
    }

    /**
     * 予算分布取得
     */
    private function getBudgetDistribution($query): array
    {
        return $query->whereNotNull('budget_max')
            ->selectRaw('
                CASE 
                    WHEN budget_max < 1000 THEN "1000万円未満"
                    WHEN budget_max < 3000 THEN "1000-3000万円"
                    WHEN budget_max < 5000 THEN "3000-5000万円"
                    WHEN budget_max < 10000 THEN "5000万円-1億円"
                    ELSE "1億円以上"
                END as budget_range,
                COUNT(*) as count
            ')
            ->groupBy('budget_range')
            ->get()
            ->pluck('count', 'budget_range')
            ->toArray();
    }

    /**
     * スコア分布取得
     */
    private function getScoreDistribution($query): array
    {
        return $query->selectRaw('
            CASE 
                WHEN match_score >= 90 THEN "90-100点"
                WHEN match_score >= 80 THEN "80-89点"
                WHEN match_score >= 70 THEN "70-79点"
                WHEN match_score >= 60 THEN "60-69点"
                ELSE "60点未満"
            END as score_range,
            COUNT(*) as count
        ')
        ->groupBy('score_range')
        ->get()
        ->pluck('count', 'score_range')
        ->toArray();
    }

    /**
     * コンバージョン率計算
     */
    private function calculateConversionRate($query, string $fromStatus, string $toStatus): float
    {
        $fromCount = (clone $query)->where('status', $fromStatus)->count();
        $toCount = (clone $query)->where('status', $toStatus)->count();

        return $fromCount > 0 ? round(($toCount / $fromCount) * 100, 2) : 0.0;
    }

    /**
     * 日次活動トレンド取得
     */
    private function getDailyActivityTrend(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate, bool $isAdmin, int $userId): array
    {
        $query = Activity::selectRaw('DATE(activity_date) as date, COUNT(*) as count')
            ->whereBetween('activity_date', [$startDate, $endDate])
            ->groupBy('date')
            ->orderBy('date');

        if (!$isAdmin) {
            $query->where('user_id', $userId);
        }

        return $query->get()->pluck('count', 'date')->toArray();
    }

    /**
     * 総売上計算
     */
    private function calculateTotalRevenue(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): int
    {
        return PropertyMatch::contracted()
            ->whereBetween('response_at', [$startDate, $endDate])
            ->join('properties', 'property_matches.property_id', '=', 'properties.id')
            ->sum('properties.price');
    }

    /**
     * 平均取引額計算
     */
    private function calculateAverageDealSize(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): float
    {
        return PropertyMatch::contracted()
            ->whereBetween('response_at', [$startDate, $endDate])
            ->join('properties', 'property_matches.property_id', '=', 'properties.id')
            ->avg('properties.price') ?? 0;
    }

    /**
     * コンバージョンファネル取得
     */
    private function getConversionFunnel(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $baseQuery = PropertyMatch::whereBetween('created_at', [$startDate, $endDate]);

        return [
            'matches' => (clone $baseQuery)->count(),
            'presented' => (clone $baseQuery)->presented()->count(),
            'interested' => (clone $baseQuery)->where('status', 'interested')->count(),
            'contracted' => (clone $baseQuery)->contracted()->count(),
        ];
    }

    /**
     * 月次売上トレンド取得
     */
    private function getMonthlySalesTrend(): array
    {
        return PropertyMatch::contracted()
            ->selectRaw('YEAR(response_at) as year, MONTH(response_at) as month, COUNT(*) as contracts, SUM(properties.price) as revenue')
            ->join('properties', 'property_matches.property_id', '=', 'properties.id')
            ->where('response_at', '>=', now()->subMonths(12))
            ->groupBy('year', 'month')
            ->orderBy('year')
            ->orderBy('month')
            ->get()
            ->map(function ($item) {
                return [
                    'period' => $item->year . '-' . str_pad($item->month, 2, '0', STR_PAD_LEFT),
                    'contracts' => $item->contracts,
                    'revenue' => $item->revenue,
                ];
            })
            ->toArray();
    }

    /**
     * トップパフォーマー取得
     */
    private function getTopPerformers(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        return User::withCount(['matches as contracts' => function ($query) use ($startDate, $endDate) {
                $query->contracted()->whereBetween('response_at', [$startDate, $endDate]);
            }])
            ->with(['matches' => function ($query) use ($startDate, $endDate) {
                $query->contracted()
                    ->whereBetween('response_at', [$startDate, $endDate])
                    ->join('properties', 'property_matches.property_id', '=', 'properties.id')
                    ->selectRaw('property_matches.created_by, SUM(properties.price) as total_revenue')
                    ->groupBy('property_matches.created_by');
            }])
            ->orderByDesc('contracts')
            ->limit(10)
            ->get(['id', 'name', 'department'])
            ->toArray();
    }

    /**
     * 物件種別パフォーマンス取得
     */
    private function getPropertyTypePerformance(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        return PropertyMatch::contracted()
            ->whereBetween('response_at', [$startDate, $endDate])
            ->join('properties', 'property_matches.property_id', '=', 'properties.id')
            ->selectRaw('properties.property_type, COUNT(*) as contracts, SUM(properties.price) as revenue, AVG(properties.price) as avg_price')
            ->groupBy('properties.property_type')
            ->orderByDesc('contracts')
            ->get()
            ->toArray();
    }

    /**
     * チームパフォーマンス取得
     */
    private function getTeamPerformance(\Carbon\Carbon $startDate, \Carbon\Carbon $endDate): array
    {
        $subquery = DB::table('property_matches')
            ->select('created_by', DB::raw('COUNT(*) as contracts'))
            ->where('status', 'contracted')
            ->whereBetween('response_at', [$startDate, $endDate])
            ->groupBy('created_by');

        return User::selectRaw('
                department, 
                COUNT(DISTINCT users.id) as members,
                COALESCE(SUM(contract_counts.contracts), 0) as total_contracts
            ')
            ->leftJoinSub($subquery, 'contract_counts', function ($join) {
                $join->on('users.id', '=', 'contract_counts.created_by');
            })
            ->whereNotNull('department')
            ->groupBy('department')
            ->orderByDesc('total_contracts')
            ->get()
            ->toArray();
    }
} 