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
        
        // ファイル内容を読み込み、文字コードを自動判定・変換
        $content = file_get_contents($file->path());
        $content = $this->convertToUtf8($content);
        
        // 変換後の内容を一時ファイルに書き出し
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_');
        file_put_contents($tempFile, $content);
        
        // fgetcsvで改行含むセル（備考等）を安全に読み取り（区切りはカンマ固定）
        $data = [];
        if (($handle = fopen($tempFile, 'r')) !== false) {
            while (($row = fgetcsv($handle, 0, ',', '"')) !== false) {
                $data[] = $row;
            }
            fclose($handle);
        }
        $headers = array_shift($data);
        // ヘッダー整形（BOM除去・トリム・改行除去）
        $headers = array_map(function ($h) {
            $h = is_string($h) ? $h : '';
            // BOM除去
            if (str_starts_with($h, "\xEF\xBB\xBF")) {
                $h = substr($h, 3);
            }
            $h = trim(preg_replace("/\r?\n/", ' ', $h));
            return $h;
        }, $headers ?? []);
        
        // 一時ファイルを削除
        unlink($tempFile);

        $results = [
            'success' => 0,
            'failed' => 0,
            'updated' => 0,
            'created' => 0,
            'errors' => []
        ];

        foreach ($data as $index => $row) {
            try {
                // 空行スキップ
                if (!is_array($row) || count(array_filter($row, function ($v) { return trim((string)$v) !== ''; })) === 0) {
                    continue;
                }
                // 行トリム（改行は保持）
                $row = array_map(function ($v) { return is_string($v) ? rtrim($v) : $v; }, $row);
                // 末尾の空要素削除
                while (count($row) > 0 && trim((string)end($row)) === '') {
                    array_pop($row);
                    if (count($row) <= count($headers)) break;
                }
                // 列不足はnullパディング
                if (count($row) < count($headers)) {
                    $row = array_pad($row, count($headers), null);
                }
                // 多い場合は切り詰め
                if (count($row) > count($headers)) {
                    $row = array_slice($row, 0, count($headers));
                }

                if (count($row) !== count($headers)) {
                    throw new \RuntimeException('列数不一致');
                }

                $rawData = array_combine($headers, $row);
                
                // 新しいCSVフォーマット（買いニーズ）に対応したデータマッピング
                $customerData = $this->mapCustomerData($rawData);
                // 顧客名が空の場合は、仲介会社名→デフォルトの優先で補完
                if (empty($customerData['customer_name'])) {
                    $broker = $rawData['仲介会社名'] ?? ($rawData['仲介会社'] ?? null);
                    if (is_string($broker) && trim($broker) !== '') {
                        $customerData['customer_name'] = trim($broker);
                    } else {
                        $customerData['customer_name'] = '買主名未入力';
                    }
                }
                
                // コードが指定されている場合は更新、そうでなければ新規作成
                if (!empty($customerData['customer_code'])) {
                    $customer = Customer::where('customer_code', $customerData['customer_code'])->first();
                    if ($customer) {
                        $customer->update($customerData);
                        $results['updated']++;
                    } else {
                        Customer::create($customerData);
                        $results['created']++;
                    }
                } else {
                    Customer::create($customerData);
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

    /**
     * 文字エンコーディングを自動判定
     */
    private function detectEncoding($content)
    {
        $encodings = ['UTF-8', 'Shift_JIS', 'EUC-JP', 'ISO-2022-JP'];
        return mb_detect_encoding($content, $encodings, true) ?: 'UTF-8';
    }

    /**
     * UTF-8に文字コード変換
     */
    private function convertToUtf8($content)
    {
        $encoding = $this->detectEncoding($content);
        if ($encoding !== 'UTF-8') {
            return mb_convert_encoding($content, 'UTF-8', $encoding);
        }
        return $content;
    }

    /**
     * 買いニーズCSVデータを顧客データにマッピング
     */
    private function mapCustomerData($rawData)
    {
        return [
            'customer_name' => $rawData['買主名'] ?? $rawData['customer_name'] ?? '',
            'customer_type' => $this->mapCustomerType($rawData['買主属性'] ?? $rawData['customer_type'] ?? '法人'),
            'area_preference' => $rawData['エリア'] ?? $rawData['area_preference'] ?? '',
            'property_type_preference' => $this->mapPropertyTypes($rawData['種目'] ?? $rawData['property_type_preference'] ?? ''),
            'detailed_requirements' => $rawData['用途'] ?? $rawData['detailed_requirements'] ?? '',
            'budget_min' => null,
            'budget_max' => $this->parseBudget($rawData['価格'] ?? $rawData['budget_max'] ?? null),
            'yield_requirement' => $this->parseNumeric($rawData['利回り'] ?? $rawData['yield_requirement'] ?? null),
            'contact_person' => $rawData['担当'] ?? $rawData['contact_person'] ?? '',
            'phone' => $rawData['電話番号'] ?? $rawData['phone'] ?? null,
            'email' => $rawData['メールアドレス'] ?? $rawData['email'] ?? null,
            'address' => $rawData['住所'] ?? $rawData['address'] ?? null,
            'priority' => '中',
            'status' => 'active',
            'last_contact_date' => null,
            'next_contact_date' => null,
            'assigned_to' => auth()->id(),
            'remarks' => $rawData['備考'] ?? $rawData['remarks'] ?? null,
            'customer_code' => $rawData['customer_code'] ?? null,
        ];
    }

    /**
     * 顧客属性をマッピング
     */
    private function mapCustomerType($type)
    {
        $mapping = [
            '法人' => '法人',
            '個人' => '個人',
            'エンド' => 'エンド法人',
            'エンド法人' => 'エンド法人',
            'エンド（中国系）' => 'エンド（中国系）',
            '中国系' => 'エンド（中国系）',
            '飲食経営者' => '飲食経営者',
            '不動明屋' => '不動明屋',
            '半法商事' => '半法商事',
        ];
        
        return $mapping[$type] ?? '法人';
    }

    /**
     * 物件種別をマッピング
     */
    private function mapPropertyTypes($types)
    {
        if (empty($types)) return '';
        
        $mapping = [
            '店舗' => '店舗',
            'レジ' => 'レジ', 
            '土地' => '土地',
            '事務所' => '事務所',
            '区分' => '区分',
            '一棟ビル' => '一棟ビル',
            '十地' => '十地',
            '新築ホテル' => '新築ホテル',
            'ホテル' => '新築ホテル',
            'オフィス' => 'オフィス',
            '収益' => 'レジ', // 収益物件はレジに分類
        ];
        
        // 複数の種別が含まれている場合の処理
        $result = [];
        foreach ($mapping as $key => $value) {
            if (strpos($types, $key) !== false) {
                $result[] = $value;
            }
        }
        
        return !empty($result) ? implode(',', array_unique($result)) : $types;
    }

    /**
     * 予算をパース
     */
    private function parseBudget($value)
    {
        if (empty($value) || $value === '' || $value === null) return null;
        
        // 「万円」「億円」などの単位を処理
        $value = str_replace(['，', ',', '円'], '', $value);
        $value = mb_convert_kana($value, 'n');
        
        // 億円の場合は万円に変換
        if (strpos($value, '億') !== false) {
            $value = str_replace('億', '', $value);
            if (is_numeric($value)) {
                return (float)$value * 10000; // 億円を万円に変換
            }
        }
        
        // 万円の場合
        if (strpos($value, '万') !== false) {
            $value = str_replace('万', '', $value);
        }
        
        return is_numeric($value) ? (float)$value : null;
    }

    /**
     * 数値をパース
     */
    private function parseNumeric($value)
    {
        if (empty($value) || $value === '' || $value === null) return null;
        
        // パーセント記号を除去
        $value = str_replace(['%', '％'], '', $value);
        $value = str_replace(['，', ','], '', $value);
        $value = mb_convert_kana($value, 'n');
        
        return is_numeric($value) ? (float)$value : null;
    }
} 