<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    /**
     * 顧客一覧取得
     */
    public function index(Request $request): JsonResponse
    {
        $query = Customer::with(['assignedUser', 'preferences', 'matches.property']);

        // 検索条件の適用
        if ($request->filled('customer_type')) {
            $query->where('customer_type', $request->customer_type);
        }

        if ($request->filled('assigned_to')) {
            $query->assignedTo($request->assigned_to);
        }

        if ($request->filled('budget_min') || $request->filled('budget_max')) {
            $query->budgetRange($request->budget_min, $request->budget_max);
        }

        if ($request->filled('priority')) {
            $query->byPriority($request->priority);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('area_preference')) {
            $query->where('area_preference', 'like', '%' . $request->area_preference . '%');
        }

        // 特別な条件
        if ($request->boolean('long_time_no_contact')) {
            $query->longTimeNoContact($request->get('no_contact_days', 30));
        }

        if ($request->boolean('upcoming_contact')) {
            $query->upcomingContact($request->get('upcoming_days', 7));
        }

        // ソート
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // ページネーション
        $customers = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $customers,
            'filters' => [
                'customer_types' => Customer::CUSTOMER_TYPES,
                'priorities' => Customer::PRIORITIES,
                'statuses' => Customer::STATUSES,
            ]
        ]);
    }

    /**
     * 顧客詳細取得
     */
    public function show(Customer $customer): JsonResponse
    {
        $customer->load([
            'assignedUser',
            'preferences' => function ($query) {
                $query->orderedByPriority();
            },
            'matches' => function ($query) {
                $query->with('property')->orderByDesc('match_score');
            },
            'activities' => function ($query) {
                $query->with('user')->latest()->limit(10);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $customer
        ]);
    }

    /**
     * 顧客登録
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'required|string|max:255',
            'customer_type' => ['required', Rule::in(Customer::CUSTOMER_TYPES)],
            'area_preference' => 'nullable|string|max:255',
            'property_type_preference' => 'nullable|string',
            'detailed_requirements' => 'nullable|string',
            'budget_min' => 'nullable|integer|min:0',
            'budget_max' => 'nullable|integer|min:0|gte:budget_min',
            'yield_requirement' => 'nullable|numeric|min:0|max:100',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'priority' => ['nullable', Rule::in(array_keys(Customer::PRIORITIES))],
            'status' => ['nullable', Rule::in(array_keys(Customer::STATUSES))],
            'last_contact_date' => 'nullable|date',
            'next_contact_date' => 'nullable|date|after_or_equal:today',
            'assigned_to' => 'required|exists:users,id',
        ]);

        $customer = Customer::create($validated);

        // 活動履歴を記録
        Activity::logCustomerCreated(Auth::id(), $customer);

        return response()->json([
            'success' => true,
            'message' => '顧客を登録しました',
            'data' => $customer->load('assignedUser')
        ], 201);
    }

    /**
     * 顧客更新
     */
    public function update(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'customer_name' => 'sometimes|required|string|max:255',
            'customer_type' => ['sometimes', 'required', Rule::in(Customer::CUSTOMER_TYPES)],
            'area_preference' => 'nullable|string|max:255',
            'property_type_preference' => 'nullable|string',
            'detailed_requirements' => 'nullable|string',
            'budget_min' => 'nullable|integer|min:0',
            'budget_max' => 'nullable|integer|min:0|gte:budget_min',
            'yield_requirement' => 'nullable|numeric|min:0|max:100',
            'contact_person' => 'nullable|string|max:255',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string',
            'priority' => ['nullable', Rule::in(array_keys(Customer::PRIORITIES))],
            'status' => ['nullable', Rule::in(array_keys(Customer::STATUSES))],
            'last_contact_date' => 'nullable|date',
            'next_contact_date' => 'nullable|date|after_or_equal:today',
            'assigned_to' => 'sometimes|required|exists:users,id',
        ]);

        $customer->update($validated);

        // 活動履歴を記録
        Activity::log(
            Auth::id(),
            'customer_updated',
            'customer',
            $customer->id,
            "顧客「{$customer->customer_name}」を更新しました"
        );

        return response()->json([
            'success' => true,
            'message' => '顧客を更新しました',
            'data' => $customer->load('assignedUser')
        ]);
    }

    /**
     * 顧客削除
     */
    public function destroy(Customer $customer): JsonResponse
    {
        $customerName = $customer->customer_name;
        $customer->delete();

        // 活動履歴を記録
        Activity::log(
            Auth::id(),
            'customer_updated',
            'customer',
            $customer->id,
            "顧客「{$customerName}」を削除しました"
        );

        return response()->json([
            'success' => true,
            'message' => '顧客を削除しました'
        ]);
    }

    /**
     * 顧客の詳細条件取得
     */
    public function getPreferences(Customer $customer): JsonResponse
    {
        $preferences = $customer->preferences()->orderedByPriority()->get();

        return response()->json([
            'success' => true,
            'data' => $preferences
        ]);
    }

    /**
     * 顧客の詳細条件更新
     */
    public function updatePreferences(Request $request, Customer $customer): JsonResponse
    {
        $request->validate([
            'preferences' => 'required|array',
            'preferences.*.preference_type' => 'required|in:area,station,structure,age,yield,size,other',
            'preferences.*.preference_key' => 'required|string|max:100',
            'preferences.*.preference_value' => 'required|string',
            'preferences.*.priority' => 'required|in:must,want,nice_to_have',
        ]);

        // 既存の条件を削除
        $customer->preferences()->delete();

        // 新しい条件を作成
        foreach ($request->preferences as $preference) {
            $customer->preferences()->create($preference);
        }

        return response()->json([
            'success' => true,
            'message' => '顧客条件を更新しました',
            'data' => $customer->preferences()->orderedByPriority()->get()
        ]);
    }

    /**
     * 顧客の活動履歴取得
     */
    public function getActivities(Customer $customer): JsonResponse
    {
        $activities = $customer->activities()
            ->with('user')
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $activities
        ]);
    }

    /**
     * 顧客の活動記録
     */
    public function addActivity(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'activity_type' => 'required|in:contact,meeting,presentation',
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'activity_date' => 'nullable|date',
        ]);

        $activity = Activity::log(
            Auth::id(),
            $validated['activity_type'],
            'customer',
            $customer->id,
            $validated['title'],
            $validated['description'] ?? null,
            $validated['activity_date'] ? new \DateTime($validated['activity_date']) : null
        );

        // 最終接触日を更新
        if ($validated['activity_type'] === 'contact') {
            $customer->update([
                'last_contact_date' => $validated['activity_date'] ?? now()->format('Y-m-d')
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => '活動を記録しました',
            'data' => $activity->load('user')
        ], 201);
    }

    /**
     * 顧客接触記録
     */
    public function recordContact(Request $request, Customer $customer): JsonResponse
    {
        $validated = $request->validate([
            'contact_method' => 'required|string|in:電話,メール,面談,その他',
            'content' => 'required|string',
            'next_contact_date' => 'nullable|date|after_or_equal:today',
        ]);

        // 活動履歴を記録
        Activity::logCustomerContact(
            Auth::id(),
            $customer,
            $validated['contact_method'],
            $validated['content']
        );

        // 顧客情報を更新
        $customer->update([
            'last_contact_date' => now()->format('Y-m-d'),
            'next_contact_date' => $validated['next_contact_date'] ?? null,
        ]);

        return response()->json([
            'success' => true,
            'message' => '接触記録を保存しました',
            'data' => $customer->fresh()
        ]);
    }

    /**
     * 顧客の統計情報取得
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_customers' => Customer::count(),
            'active_customers' => Customer::active()->count(),
            'negotiating_customers' => Customer::where('status', 'negotiating')->count(),
            'closed_customers' => Customer::where('status', 'closed')->count(),
            'high_priority_customers' => Customer::byPriority('高')->count(),
            'long_time_no_contact' => Customer::longTimeNoContact(30)->count(),
            'upcoming_contacts' => Customer::upcomingContact(7)->count(),
            'customers_by_type' => Customer::selectRaw('customer_type, COUNT(*) as count')
                ->groupBy('customer_type')
                ->get()
                ->pluck('count', 'customer_type'),
            'average_budget' => [
                'min' => Customer::whereNotNull('budget_min')->avg('budget_min'),
                'max' => Customer::whereNotNull('budget_max')->avg('budget_max'),
            ],
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * 担当者別顧客一覧
     */
    public function byAssignee(Request $request): JsonResponse
    {
        $assigneeId = $request->get('assignee_id', Auth::id());
        
        $customers = Customer::with(['matches.property'])
            ->assignedTo($assigneeId)
            ->active()
            ->orderBy('priority')
            ->orderBy('next_contact_date')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $customers
        ]);
    }

    /**
     * CSVインポート
     */
    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt'
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();
        $data = array_map('str_getcsv', file($path));
        $headers = array_shift($data);

        $results = [
            'success' => 0,
            'failed' => 0,
            'updated' => 0,
            'created' => 0,
            'errors' => []
        ];

        foreach ($data as $index => $row) {
            try {
                $rowData = array_combine($headers, $row);
                
                // コードが指定されている場合は更新、そうでなければ新規作成
                if (!empty($rowData['customer_code'])) {
                    $customer = Customer::where('customer_code', $rowData['customer_code'])->first();
                    if ($customer) {
                        $customer->update($rowData);
                        $results['updated']++;
                    } else {
                        Customer::create(array_merge($rowData, ['assigned_user_id' => auth()->id()]));
                        $results['created']++;
                    }
                } else {
                    Customer::create(array_merge($rowData, ['assigned_user_id' => auth()->id()]));
                    $results['created']++;
                }
                
                $results['success']++;
            } catch (\Exception $e) {
                $results['failed']++;
                $results['errors'][] = [
                    'row' => $index + 2, // ヘッダー行を考慮
                    'message' => $e->getMessage()
                ];
            }
        }

        return response()->json([
            'success' => true,
            'data' => [
                'summary' => $results
            ]
        ]);
    }
} 