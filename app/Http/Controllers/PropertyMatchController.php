<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Customer;
use App\Models\PropertyMatch;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class PropertyMatchController extends Controller
{
    /**
     * マッチング一覧取得
     */
    public function index(Request $request): JsonResponse
    {
        $query = PropertyMatch::with(['property', 'customer', 'creator']);

        // 検索条件の適用
        if ($request->filled('property_id')) {
            $query->where('property_id', $request->property_id);
        }

        if ($request->filled('customer_id')) {
            $query->where('customer_id', $request->customer_id);
        }

        if ($request->filled('status')) {
            $query->byStatus($request->status);
        }

        if ($request->filled('min_score')) {
            $query->where('match_score', '>=', $request->min_score);
        }

        if ($request->boolean('high_score_only')) {
            $query->highScore($request->get('score_threshold', 70));
        }

        if ($request->boolean('not_presented_only')) {
            $query->notPresented();
        }

        // ソート
        $sortBy = $request->get('sort_by', 'match_score');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // ページネーション
        $matches = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $matches,
            'filters' => [
                'statuses' => PropertyMatch::STATUSES,
            ]
        ]);
    }

    /**
     * マッチング詳細取得
     */
    public function show(PropertyMatch $match): JsonResponse
    {
        $match->load([
            'property' => function($query) {
                $query->with(['images', 'creator']);
            },
            'customer' => function($query) {
                $query->with(['assignedUser', 'preferences']);
            },
            'creator'
        ]);
        
        // スコア詳細計算
        $scoreDetails = $this->calculateDetailedScore($match->property, $match->customer);
        
        // ステータス履歴取得
        $statusHistory = $this->getStatusHistory($match);
        
        // 関連活動履歴
        $activities = Activity::where('subject_type', 'match')
            ->where('subject_id', $match->id)
            ->with('user')
            ->orderBy('created_at', 'desc')
            ->take(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => array_merge($match->toArray(), [
                'score_details' => $scoreDetails,
                'status_history' => $statusHistory,
                'activities' => $activities,
            ])
        ]);
    }

    /**
     * マッチング生成
     */
    public function generate(Request $request): JsonResponse
    {
        $request->validate([
            'property_id' => 'nullable|exists:properties,id',
            'customer_id' => 'nullable|exists:customers,id',
            'min_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $propertyQuery = Property::available();
        $customerQuery = Customer::active();

        // 特定の物件または顧客が指定されている場合
        if ($request->filled('property_id')) {
            $propertyQuery->where('id', $request->property_id);
        }

        if ($request->filled('customer_id')) {
            $customerQuery->where('id', $request->customer_id);
        }

        $properties = $propertyQuery->get();
        $customers = $customerQuery->get();

        $minScore = $request->get('min_score', 60);
        $createdMatches = 0;

        DB::transaction(function () use ($properties, $customers, $minScore, &$createdMatches) {
            foreach ($properties as $property) {
                foreach ($customers as $customer) {
                    // 既存のマッチングをチェック
                    $existingMatch = PropertyMatch::where('property_id', $property->id)
                        ->where('customer_id', $customer->id)
                        ->first();

                    if ($existingMatch) {
                        continue;
                    }

                    // マッチングスコアを計算
                    $score = $this->calculateMatchScore($property, $customer);

                    if ($score >= $minScore) {
                        $match = PropertyMatch::create([
                            'property_id' => $property->id,
                            'customer_id' => $customer->id,
                            'match_score' => $score,
                            'match_reason' => PropertyMatch::generateMatchReason($property, $customer, $score),
                            'created_by' => Auth::id(),
                        ]);

                        // 活動履歴を記録
                        Activity::logMatchCreated(Auth::id(), $match);
                        $createdMatches++;
                    }
                }
            }
        });

        return response()->json([
            'success' => true,
            'message' => "{$createdMatches}件のマッチングを作成しました",
            'created_matches' => $createdMatches
        ]);
    }

    /**
     * マッチング状況更新
     */
    public function update(Request $request, PropertyMatch $match): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:matched,presented,interested,rejected,contracted',
            'response_comment' => 'nullable|string',
        ]);

        $match->update($validated);

        // ステータスに応じた処理
        if ($validated['status'] === 'contracted') {
            $match->markAsContracted($validated['response_comment'] ?? null);
            
            // 契約活動を記録
            Activity::logContract(Auth::id(), $match);
        }

        return response()->json([
            'success' => true,
            'message' => 'マッチング状況を更新しました',
            'data' => $match->fresh(['property', 'customer'])
        ]);
    }

    /**
     * 提案実行
     */
    public function present(Request $request, PropertyMatch $match): JsonResponse
    {
        $validated = $request->validate([
            'comment' => 'nullable|string',
        ]);

        $match->markAsPresented($validated['comment'] ?? null);

        // 提案活動を記録
        Activity::logPresentation(Auth::id(), $match, $validated['comment'] ?? null);

        return response()->json([
            'success' => true,
            'message' => '提案を実行しました',
            'data' => $match->fresh(['property', 'customer'])
        ]);
    }

    /**
     * 顧客回答記録
     */
    public function recordResponse(Request $request, PropertyMatch $match): JsonResponse
    {
        $validated = $request->validate([
            'status' => 'required|in:interested,rejected,contracted',
            'comment' => 'nullable|string',
        ]);

        $match->recordResponse($validated['status'], $validated['comment'] ?? null);

        return response()->json([
            'success' => true,
            'message' => '顧客回答を記録しました',
            'data' => $match->fresh(['property', 'customer'])
        ]);
    }

    /**
     * マッチング削除
     */
    public function destroy(PropertyMatch $match): JsonResponse
    {
        $match->delete();

        return response()->json([
            'success' => true,
            'message' => 'マッチングを削除しました'
        ]);
    }

    /**
     * マッチング統計情報取得
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_matches' => PropertyMatch::count(),
            'high_score_matches' => PropertyMatch::highScore(80)->count(),
            'presented_matches' => PropertyMatch::presented()->count(),
            'contracted_matches' => PropertyMatch::contracted()->count(),
            'average_score' => PropertyMatch::avg('match_score'),
            'matches_by_status' => PropertyMatch::selectRaw('status, COUNT(*) as count')
                ->groupBy('status')
                ->get()
                ->pluck('count', 'status'),
            'conversion_rate' => [
                'presentation_to_interest' => $this->calculateConversionRate('presented', 'interested'),
                'interest_to_contract' => $this->calculateConversionRate('interested', 'contracted'),
                'overall_to_contract' => $this->calculateConversionRate('matched', 'contracted'),
            ],
            'top_properties' => Property::withCount(['matches as high_score_matches' => function ($query) {
                    $query->highScore(80);
                }])
                ->orderByDesc('high_score_matches')
                ->limit(5)
                ->get(['id', 'property_name', 'price']),
            'top_customers' => Customer::withCount(['matches as high_score_matches' => function ($query) {
                    $query->highScore(80);
                }])
                ->orderByDesc('high_score_matches')
                ->limit(5)
                ->get(['id', 'customer_name', 'customer_type']),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * 物件の推奨顧客取得
     */
    public function getRecommendedCustomers(Property $property): JsonResponse
    {
        $matches = $property->matches()
            ->with('customer')
            ->highScore(70)
            ->orderByDesc('match_score')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $matches
        ]);
    }

    /**
     * 顧客の推奨物件取得
     */
    public function getRecommendedProperties(Customer $customer): JsonResponse
    {
        $matches = $customer->matches()
            ->with('property.images')
            ->highScore(70)
            ->orderByDesc('match_score')
            ->limit(10)
            ->get();

        return response()->json([
            'success' => true,
            'data' => $matches
        ]);
    }

    /**
     * マッチングスコア計算
     */
    private function calculateMatchScore(Property $property, Customer $customer): float
    {
        $score = 0;
        $totalWeight = 0;

        // 1. 種目マッチ度 (30%)
        $typeWeight = 30;
        $typeMatch = $this->calculateTypeMatch($property, $customer);
        $score += $typeMatch * $typeWeight / 100;
        $totalWeight += $typeWeight;

        // 2. エリアマッチ度 (25%)
        $areaWeight = 25;
        $areaMatch = $this->calculateAreaMatch($property, $customer);
        $score += $areaMatch * $areaWeight / 100;
        $totalWeight += $areaWeight;

        // 3. 坪数（土地面積）マッチ度 (25%)
        $landAreaWeight = 25;
        $landAreaMatch = $this->calculateLandAreaMatch($property, $customer);
        $score += $landAreaMatch * $landAreaWeight / 100;
        $totalWeight += $landAreaWeight;

        // 4. 価格マッチ度 (20%)
        $priceWeight = 20;
        $priceMatch = $customer->calculatePriceMatch($property);
        $score += $priceMatch * $priceWeight / 100;
        $totalWeight += $priceWeight;

        return round($score, 2);
    }

    /**
     * エリアマッチ度計算
     */
    private function calculateAreaMatch(Property $property, Customer $customer): float
    {
        if (!$customer->area_preference) {
            return 50.0; // 希望なしの場合は中間値
        }

        $preference = $customer->area_preference;
        
        if (str_contains($property->full_address, $preference) ||
            str_contains($preference, $property->prefecture) ||
            str_contains($preference, $property->city)) {
            return 100.0;
        }

        return 0.0;
    }

    /**
     * 種別マッチ度計算
     */
    private function calculateTypeMatch(Property $property, Customer $customer): float
    {
        $customerTypes = $customer->property_type_preference_array;
        
        if (empty($customerTypes)) {
            return 50.0; // 希望なしの場合は中間値
        }

        return in_array($property->property_type, $customerTypes) ? 100.0 : 0.0;
    }

    /**
     * 利回りマッチ度計算
     */
    private function calculateYieldMatch(Property $property, Customer $customer): float
    {
        if (!$customer->yield_requirement || !$property->current_profit) {
            return 50.0; // どちらかが未設定の場合は中間値
        }

        if ($property->current_profit >= $customer->yield_requirement) {
            return 100.0;
        }

        // 要求を下回る場合のペナルティ計算
        $shortfall = $customer->yield_requirement - $property->current_profit;
        $penaltyRate = min($shortfall / $customer->yield_requirement, 1.0);
        
        return max(0, 100 - ($penaltyRate * 100));
    }

    /**
     * 土地面積マッチ度計算
     */
    private function calculateLandAreaMatch(Property $property, Customer $customer): float
    {
        // 物件の土地面積（㎡）
        $propertyLandArea = $property->land_area;
        
        if (!$propertyLandArea) {
            return 30.0; // 物件の土地面積が未設定の場合は低スコア
        }
        
        // ㎡を坪に変換 (1坪 = 3.30579㎡)
        $propertyLandAreaTsubo = $propertyLandArea / 3.30579;
        
        // 顧客の希望坪数を取得（備考から抽出または別フィールドから）
        $customerLandAreaTsubo = $this->extractLandAreaRequirement($customer);
        
        if (!$customerLandAreaTsubo) {
            return 50.0; // 顧客の希望坪数が未設定の場合は中間値
        }
        
        // 希望坪数との差を計算
        $difference = abs($propertyLandAreaTsubo - $customerLandAreaTsubo);
        $percentageDiff = $difference / $customerLandAreaTsubo;
        
        // マッチ度計算
        if ($percentageDiff <= 0.1) { // 10%以内の差
            return 100.0;
        } elseif ($percentageDiff <= 0.2) { // 20%以内の差
            return 80.0;
        } elseif ($percentageDiff <= 0.3) { // 30%以内の差
            return 60.0;
        } elseif ($percentageDiff <= 0.5) { // 50%以内の差
            return 40.0;
        } else {
            return 20.0; // 50%を超える差
        }
    }

    /**
     * 顧客の土地面積要求を抽出
     */
    private function extractLandAreaRequirement(Customer $customer): ?float
    {
        // 備考から坪数を抽出
        if ($customer->remarks) {
            // 「100坪」「50坪以上」「30-50坪」などのパターンを検索
            if (preg_match('/(\d+(?:\.\d+)?)坪/', $customer->remarks, $matches)) {
                return (float)$matches[1];
            }
            
            if (preg_match('/(\d+(?:\.\d+)?)坪以上/', $customer->remarks, $matches)) {
                return (float)$matches[1];
            }
            
            if (preg_match('/(\d+(?:\.\d+)?)-(\d+(?:\.\d+)?)坪/', $customer->remarks, $matches)) {
                // 範囲の場合は平均値を使用
                return ((float)$matches[1] + (float)$matches[2]) / 2;
            }
        }
        
        // 詳細要求から抽出
        if ($customer->detailed_requirements) {
            if (preg_match('/(\d+(?:\.\d+)?)坪/', $customer->detailed_requirements, $matches)) {
                return (float)$matches[1];
            }
        }
        
        return null;
    }

    /**
     * その他条件マッチ度計算
     */
    private function calculateOtherMatch(Property $property, Customer $customer): float
    {
        $preferences = $customer->preferences;
        
        if ($preferences->isEmpty()) {
            return 50.0;
        }

        $totalWeight = 0;
        $matchedWeight = 0;

        foreach ($preferences as $preference) {
            $weight = $preference->priority_weight;
            $totalWeight += $weight;

            if ($preference->isMatchedBy($property)) {
                $matchedWeight += $weight;
            }
        }

        return $totalWeight > 0 ? ($matchedWeight / $totalWeight) * 100 : 50.0;
    }

    /**
     * コンバージョン率計算
     */
    private function calculateConversionRate(string $fromStatus, string $toStatus): float
    {
        $fromCount = PropertyMatch::byStatus($fromStatus)->count();
        $toCount = PropertyMatch::byStatus($toStatus)->count();

        return $fromCount > 0 ? round(($toCount / $fromCount) * 100, 2) : 0.0;
    }

    /**
     * ステータス更新
     */
    public function updateStatus(Request $request, PropertyMatch $match): JsonResponse
    {
        $request->validate([
            'status' => 'required|string|in:matched,reviewed,presented,interested,not_interested,contracted,expired',
            'notes' => 'nullable|string|max:1000',
        ]);

        $oldStatus = $match->status;
        $newStatus = $request->status;

        // ステータス更新
        $match->update([
            'status' => $newStatus,
            'notes' => $request->notes ?? $match->notes,
            'reviewed_at' => $newStatus === 'reviewed' ? now() : $match->reviewed_at,
            'presented_at' => $newStatus === 'presented' ? now() : $match->presented_at,
            'response_at' => in_array($newStatus, ['interested', 'not_interested', 'contracted']) ? now() : $match->response_at,
        ]);

        // 活動履歴記録
        Activity::log(
            Auth::id(),
            'match_status_updated',
            'match',
            $match->id,
            "マッチングステータスを「{$oldStatus}」から「{$newStatus}」に変更しました",
            $request->notes
        );

        return response()->json([
            'success' => true,
            'data' => $match->fresh(),
            'message' => 'ステータスを更新しました'
        ]);
    }

    /**
     * メモ追加
     */
    public function addNote(Request $request, PropertyMatch $match): JsonResponse
    {
        $request->validate([
            'note' => 'required|string|max:1000',
        ]);

        $currentNotes = $match->notes ?? '';
        $newNote = '[' . now()->format('Y-m-d H:i') . '] ' . $request->note;
        $updatedNotes = $currentNotes ? $currentNotes . "\n" . $newNote : $newNote;

        $match->update(['notes' => $updatedNotes]);

        // 活動履歴記録
        Activity::log(
            Auth::id(),
            'match_note_added',
            'match',
            $match->id,
            'マッチングにメモを追加しました',
            $request->note
        );

        return response()->json([
            'success' => true,
            'data' => $match->fresh(),
            'message' => 'メモを追加しました'
        ]);
    }

    /**
     * 詳細スコア計算
     */
    private function calculateDetailedScore(Property $property, Customer $customer): array
    {
        // 予算適合度計算（重み: 30%）
        $budgetScore = $this->calculateBudgetScore($property->price, $customer->budget_min, $customer->budget_max);
        
        // エリア適合度計算（重み: 25%）
        $areaScore = $this->calculateAreaScore($property->prefecture, $customer->area_preference);
        
        // 物件タイプ適合度計算（重み: 20%）
        $typeScore = $this->calculateTypeScore($property->property_type, $customer->property_type_preference);
        
        // 面積適合度計算（重み: 15%）
        $sizeScore = $this->calculateSizeScore($property->building_area, $customer->area_requirement);
        
        // 利回り適合度計算（重み: 10%）
        $yieldScore = $this->calculateYieldScore($property->current_profit, $customer->expected_yield);

        $totalScore = ($budgetScore * 0.3) + ($areaScore * 0.25) + ($typeScore * 0.2) + ($sizeScore * 0.15) + ($yieldScore * 0.1);

        return [
            'total_score' => round($totalScore * 100, 1),
            'budget_score' => round($budgetScore * 100, 1),
            'area_score' => round($areaScore * 100, 1),
            'type_score' => round($typeScore * 100, 1),
            'size_score' => round($sizeScore * 100, 1),
            'yield_score' => round($yieldScore * 100, 1),
            'weights' => [
                'budget' => 30,
                'area' => 25,
                'type' => 20,
                'size' => 15,
                'yield' => 10,
            ]
        ];
    }

    /**
     * 予算適合度計算
     */
    private function calculateBudgetScore(int $price, ?int $budgetMin, ?int $budgetMax): float
    {
        if (!$budgetMin || !$budgetMax) return 0.5;

        if ($price <= $budgetMin) return 0.3;
        if ($price <= $budgetMax) return 1.0;
        if ($price <= $budgetMax * 1.1) return 0.8;
        if ($price <= $budgetMax * 1.2) return 0.6;
        if ($price <= $budgetMax * 1.3) return 0.4;
        return 0.0;
    }

    /**
     * エリア適合度計算
     */
    private function calculateAreaScore(string $propertyArea, ?string $customerArea): float
    {
        if (!$customerArea) return 0.5;
        return $propertyArea === $customerArea ? 1.0 : 0.0;
    }

    /**
     * 物件タイプ適合度計算
     */
    private function calculateTypeScore(string $propertyType, ?string $customerType): float
    {
        if (!$customerType) return 0.5;
        return $propertyType === $customerType ? 1.0 : 0.0;
    }

    /**
     * 面積適合度計算
     */
    private function calculateSizeScore(?float $propertySize, ?float $customerSize): float
    {
        if (!$propertySize || !$customerSize) return 0.5;

        $ratio = $propertySize / $customerSize;
        if ($ratio >= 0.8 && $ratio <= 1.2) return 1.0;
        if (($ratio >= 0.6 && $ratio < 0.8) || ($ratio > 1.2 && $ratio <= 1.4)) return 0.8;
        if (($ratio >= 0.4 && $ratio < 0.6) || ($ratio > 1.4 && $ratio <= 1.6)) return 0.6;
        if (($ratio >= 0.2 && $ratio < 0.4) || ($ratio > 1.6 && $ratio <= 1.8)) return 0.4;
        return 0.0;
    }

    /**
     * 利回り適合度計算
     */
    private function calculateYieldScore(?float $propertyYield, ?float $customerYield): float
    {
        if (!$propertyYield || !$customerYield) return 0.5;

        if ($propertyYield >= $customerYield) return 1.0;
        if ($propertyYield >= $customerYield * 0.9) return 0.8;
        if ($propertyYield >= $customerYield * 0.8) return 0.6;
        if ($propertyYield >= $customerYield * 0.7) return 0.4;
        return 0.0;
    }

    /**
     * ステータス履歴取得
     */
    private function getStatusHistory(PropertyMatch $match): array
    {
        $history = [];
        
        if ($match->created_at) {
            $history[] = [
                'status' => 'matched',
                'date' => $match->created_at,
                'user' => $match->creator->name ?? '自動',
            ];
        }
        
        if ($match->reviewed_at) {
            $history[] = [
                'status' => 'reviewed',
                'date' => $match->reviewed_at,
                'user' => 'システム',
            ];
        }
        
        if ($match->presented_at) {
            $history[] = [
                'status' => 'presented',
                'date' => $match->presented_at,
                'user' => 'システム',
            ];
        }
        
        if ($match->response_at) {
            $history[] = [
                'status' => $match->status,
                'date' => $match->response_at,
                'user' => 'システム',
            ];
        }

        return $history;
    }
} 