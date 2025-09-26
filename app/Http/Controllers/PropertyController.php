<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\Activity;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class PropertyController extends Controller
{
    /**
     * 物件一覧取得
     */
    public function index(Request $request): JsonResponse
    {
        $query = Property::with(['creator', 'images', 'matches.customer']);

        // 検索条件の適用
        if ($request->filled('property_type')) {
            $query->ofType($request->property_type);
        }

        if ($request->filled('prefecture')) {
            $query->where('prefecture', $request->prefecture);
        }

        if ($request->filled('city')) {
            $query->where('city', $request->city);
        }

        if ($request->filled('price_min') || $request->filled('price_max')) {
            $query->priceRange($request->price_min, $request->price_max);
        }

        if ($request->filled('yield_min') || $request->filled('yield_max')) {
            $query->yieldRange($request->yield_min, $request->yield_max);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // ソート
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // ページネーション
        $properties = $query->paginate($request->get('per_page', 15));

        return response()->json([
            'success' => true,
            'data' => $properties,
            'filters' => [
                'property_types' => Property::PROPERTY_TYPES,
                'transaction_categories' => Property::TRANSACTION_CATEGORIES,
                'statuses' => Property::STATUSES,
            ]
        ]);
    }

    /**
     * 物件詳細取得
     */
    public function show(Property $property): JsonResponse
    {
        $property->load([
            'creator',
            'images' => function ($query) {
                $query->ordered();
            },
            'matches' => function ($query) {
                $query->with('customer')->orderByDesc('match_score');
            },
            'activities' => function ($query) {
                $query->with('user')->latest()->limit(10);
            }
        ]);

        return response()->json([
            'success' => true,
            'data' => $property
        ]);
    }

    /**
     * 物件登録
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'property_name' => 'required|string|max:255',
            'property_type' => ['required', Rule::in(Property::PROPERTY_TYPES)],
            'manager_name' => 'required|string|max:100',
            'registration_date' => 'required|date',
            'address' => 'required|string',
            'information_source' => 'nullable|string|max:255',
            'transaction_category' => ['required', Rule::in(Property::TRANSACTION_CATEGORIES)],
            'land_area' => 'nullable|numeric|min:0',
            'building_area' => 'nullable|numeric|min:0',
            'structure_floors' => 'nullable|string|max:100',
            'construction_year' => 'nullable|date',
            'price' => 'required|integer|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'current_profit' => 'nullable|numeric|min:0|max:100',
            'prefecture' => 'required|string|max:50',
            'city' => 'required|string|max:100',
            'nearest_station' => 'nullable|string|max:100',
            'walking_minutes' => 'nullable|integer|min:0|max:999',
            'remarks' => 'nullable|string',
            'status' => ['nullable', Rule::in(array_keys(Property::STATUSES))],
        ]);

        $validated['created_by'] = Auth::id();

        $property = Property::create($validated);

        // 活動履歴を記録
        Activity::logPropertyCreated(Auth::id(), $property);

        return response()->json([
            'success' => true,
            'message' => '物件を登録しました',
            'data' => $property->load('creator')
        ], 201);
    }

    /**
     * 物件更新
     */
    public function update(Request $request, Property $property): JsonResponse
    {
        $validated = $request->validate([
            'property_name' => 'sometimes|required|string|max:255',
            'property_type' => ['sometimes', 'required', Rule::in(Property::PROPERTY_TYPES)],
            'manager_name' => 'sometimes|required|string|max:100',
            'registration_date' => 'sometimes|required|date',
            'address' => 'sometimes|required|string',
            'information_source' => 'nullable|string|max:255',
            'transaction_category' => ['sometimes', 'required', Rule::in(Property::TRANSACTION_CATEGORIES)],
            'land_area' => 'nullable|numeric|min:0',
            'building_area' => 'nullable|numeric|min:0',
            'structure_floors' => 'nullable|string|max:100',
            'construction_year' => 'nullable|date',
            'price' => 'sometimes|required|integer|min:0',
            'price_per_unit' => 'nullable|numeric|min:0',
            'current_profit' => 'nullable|numeric|min:0|max:100',
            'prefecture' => 'sometimes|required|string|max:50',
            'city' => 'sometimes|required|string|max:100',
            'nearest_station' => 'nullable|string|max:100',
            'walking_minutes' => 'nullable|integer|min:0|max:999',
            'remarks' => 'nullable|string',
            'status' => ['nullable', Rule::in(array_keys(Property::STATUSES))],
        ]);

        $property->update($validated);

        // 活動履歴を記録
        Activity::log(
            Auth::id(),
            'property_updated',
            'property',
            $property->id,
            "物件「{$property->property_name}」を更新しました"
        );

        return response()->json([
            'success' => true,
            'message' => '物件を更新しました',
            'data' => $property->load('creator')
        ]);
    }

    /**
     * 物件削除
     */
    public function destroy(Property $property): JsonResponse
    {
        // 関連画像の削除
        foreach ($property->images as $image) {
            Storage::disk('public')->delete($image->image_path);
        }

        $propertyName = $property->property_name;
        $property->delete();

        // 活動履歴を記録
        Activity::log(
            Auth::id(),
            'property_updated',
            'property',
            $property->id,
            "物件「{$propertyName}」を削除しました"
        );

        return response()->json([
            'success' => true,
            'message' => '物件を削除しました'
        ]);
    }

    /**
     * 物件画像一覧取得
     */
    public function getImages(Property $property): JsonResponse
    {
        $images = $property->images()->ordered()->get();

        return response()->json([
            'success' => true,
            'data' => $images
        ]);
    }

    /**
     * 物件画像アップロード
     */
    public function uploadImage(Request $request, Property $property): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
            'image_type' => 'required|in:exterior,interior,layout,other',
            'caption' => 'nullable|string|max:255',
        ]);

        $image = $request->file('image');
        $path = $image->store('properties/' . $property->id, 'public');

        $propertyImage = $property->images()->create([
            'image_path' => $path,
            'image_type' => $request->image_type,
            'caption' => $request->caption,
            'sort_order' => $property->images()->max('sort_order') + 1,
        ]);

        return response()->json([
            'success' => true,
            'message' => '画像をアップロードしました',
            'data' => $propertyImage
        ], 201);
    }

    /**
     * 物件画像削除
     */
    public function deleteImage(Property $property, $imageId): JsonResponse
    {
        $image = $property->images()->findOrFail($imageId);
        
        Storage::disk('public')->delete($image->image_path);
        $image->delete();

        return response()->json([
            'success' => true,
            'message' => '画像を削除しました'
        ]);
    }

    /**
     * 物件の統計情報取得
     */
    public function statistics(): JsonResponse
    {
        $stats = [
            'total_properties' => Property::count(),
            'available_properties' => Property::available()->count(),
            'sold_properties' => Property::where('status', 'sold')->count(),
            'average_price' => Property::available()->avg('price'),
            'average_yield' => Property::available()->avg('current_profit'),
            'properties_by_type' => Property::selectRaw('property_type, COUNT(*) as count')
                ->groupBy('property_type')
                ->get()
                ->pluck('count', 'property_type'),
            'properties_by_prefecture' => Property::selectRaw('prefecture, COUNT(*) as count')
                ->groupBy('prefecture')
                ->orderByDesc('count')
                ->limit(10)
                ->get()
                ->pluck('count', 'prefecture'),
        ];

        return response()->json([
            'success' => true,
            'data' => $stats
        ]);
    }

    /**
     * CSV一括インポート
     */
    public function import(Request $request): JsonResponse
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt',
        ]);

        $file = $request->file('file');
        
        // ファイル内容を読み込み、文字コードを自動判定・変換
        $content = file_get_contents($file->path());
        $content = $this->convertToUtf8($content);
        
        // 変換後の内容を一時ファイルに書き出し
        $tempFile = tempnam(sys_get_temp_dir(), 'csv_import_');
        file_put_contents($tempFile, $content);
        
        $csvData = array_map('str_getcsv', file($tempFile));
        $header = array_shift($csvData);
        
        // ヘッダー整形（BOM除去・トリム）
        $header = array_map(function ($h) {
            $h = $this->removeBom((string)$h);
            return trim($h);
        }, $header ?? []);
        
        // 一時ファイルを削除
        unlink($tempFile);

        $imported = 0;
        $errors = [];

        foreach ($csvData as $index => $row) {
            try {
                // 空行スキップ
                if (!is_array($row) || count(array_filter($row, function ($v) { return trim((string)$v) !== ''; })) === 0) {
                    continue;
                }
                
                // 行のトリム
                $row = array_map(function ($v) { return is_string($v) ? trim($v) : $v; }, $row);
                
                // 末尾の空要素を削る（テンプレ末尾カンマ対策）
                while (count($row) > 0 && trim((string)end($row)) === '') {
                    array_pop($row);
                    if (count($row) <= count($header)) break;
                }
                
                // 列数が足りなければ null でパディング
                if (count($row) < count($header)) {
                    $row = array_pad($row, count($header), null);
                }
                
                // まだ列数が多い場合はエラーとして記録してスキップ
                if (count($row) !== count($header)) {
                    $errors[] = "行 " . ($index + 2) . ": ヘッダー列数(" . count($header) . ")とデータ列数(" . count($row) . ")が一致しません";
                    continue;
                }
                
                $data = array_combine($header, $row);
                
                // データの変換・検証（新しいCSVフォーマットに対応）
                // 値の正規化
                $rawType = $data['種別'] ?? null;
                $propertyType = in_array($rawType, Property::PROPERTY_TYPES, true) ? $rawType : null;
                $rawTxn = $data['取引態様'] ?? ($data['取引区分'] ?? null);
                $transactionCategory = in_array($rawTxn, Property::TRANSACTION_CATEGORIES, true) ? $rawTxn : null;
                $address = $data['住所'] ?? null;

                $propertyData = [
                    'property_name' => $data['物件名'] ?? null,
                    'property_type' => $propertyType,
                    'manager_name' => $data['担当名'] ?? ($data['担当者名'] ?? null),
                    'registration_date' => $this->parseDate($data['登録日'] ?? null),
                    // 住所は一旦 address のみに格納
                    'address' => $address,
                    'information_source' => $data['情報取得先'] ?? null,
                    'transaction_category' => $transactionCategory,
                    'land_area' => $this->parseNumeric($data['地積(㎡)'] ?? ($data['地積'] ?? null)),
                    'building_area' => $this->parseNumeric($data['建物面積(㎡)'] ?? ($data['建物面積'] ?? null)),
                    'structure_floors' => $data['構造階数'] ?? null,
                    'construction_year' => $this->parseDate($data['築年'] ?? null),
                    'price' => $this->parseNumeric($data['価格【万円】'] ?? ($data['価格'] ?? null)),
                    'price_per_unit' => $this->parseNumeric($data['坪単価【万円】'] ?? ($data['坪単価'] ?? null)),
                    'current_profit' => $this->parseNumeric($data['現況利回'] ?? ($data['利回り'] ?? null)),
                    // 都道府県・市区町村は当面nullにしておく（住所解析は後段で対応）
                    'prefecture' => null,
                    'city' => null,
                    'nearest_station' => $data['最寄り駅'] ?? null,
                    'walking_minutes' => $this->parseNumeric($data['徒歩分数'] ?? null),
                    'remarks' => $data['備考'] ?? null,
                    'created_by' => Auth::id(),
                ];

                Property::create($propertyData);
                $imported++;

            } catch (\Exception $e) {
                $errors[] = "行 " . ($index + 2) . ": " . $e->getMessage();
            }
        }

        // UTF-8不正文字をサニタイズしてから返却
        $safe = function ($value) {
            if (is_string($value)) {
                // 不正UTF-8を置換
                return mb_convert_encoding($value, 'UTF-8', 'UTF-8');
            }
            if (is_array($value)) {
                return array_map(function ($v) use ($safe) { return $safe($v); }, $value);
            }
            return $value;
        };

        $response = [
            'success' => true,
            'message' => "{$imported}件の物件をインポートしました",
            'imported' => $imported,
            'errors' => array_map(fn($e) => $safe($e), $errors),
        ];

        return response()->json($response, 200, [], JSON_INVALID_UTF8_SUBSTITUTE);
    }

    /**
     * BOM除去
     */
    private function removeBom(string $text): string
    {
        if (str_starts_with($text, "\xEF\xBB\xBF")) {
            return substr($text, 3);
        }
        return $text;
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
     * 数値をパース（カンマ除去、全角→半角変換）
     */
    private function parseNumeric($value)
    {
        if (empty($value) || $value === '' || $value === null) return null;
        
        // カンマや全角数字を半角に変換
        $value = str_replace(['，', ',', '円', '万円'], '', $value);
        $value = mb_convert_kana($value, 'n');
        
        return is_numeric($value) ? (float)$value : null;
    }

    /**
     * 日付をパース
     */
    private function parseDate($value)
    {
        if (empty($value) || $value === '' || $value === null) return null;
        
        // 様々な日付形式に対応
        $formats = ['Y/m/d', 'Y-m-d', 'Y年m月d日', 'Y/m', 'Y年m月', 'Y'];
        
        foreach ($formats as $format) {
            $date = \DateTime::createFromFormat($format, $value);
            if ($date) {
                return $date->format('Y-m-d');
            }
        }
        
        return null;
    }

    /**
     * 住所から都道府県を抽出
     */
    private function extractPrefecture($address)
    {
        if (empty($address)) return '';
        
        $prefectures = [
            '北海道', '青森県', '岩手県', '宮城県', '秋田県', '山形県', '福島県',
            '茨城県', '栃木県', '群馬県', '埼玉県', '千葉県', '東京都', '神奈川県',
            '新潟県', '富山県', '石川県', '福井県', '山梨県', '長野県', '岐阜県',
            '静岡県', '愛知県', '三重県', '滋賀県', '京都府', '大阪府', '兵庫県',
            '奈良県', '和歌山県', '鳥取県', '島根県', '岡山県', '広島県', '山口県',
            '徳島県', '香川県', '愛媛県', '高知県', '福岡県', '佐賀県', '長崎県',
            '熊本県', '大分県', '宮崎県', '鹿児島県', '沖縄県'
        ];
        
        foreach ($prefectures as $prefecture) {
            if (strpos($address, $prefecture) !== false) {
                return $prefecture;
            }
        }
        
        return '';
    }

    /**
     * 住所から市区町村を抽出
     */
    private function extractCity($address)
    {
        if (empty($address)) return '';
        
        // 都道府県を除去
        $prefecture = $this->extractPrefecture($address);
        if ($prefecture) {
            $address = str_replace($prefecture, '', $address);
        }
        
        // 市区町村のパターンマッチング
        if (preg_match('/^([^0-9]+?[市区町村])/', $address, $matches)) {
            return $matches[1];
        }
        
        // 京都市の特別区
        if (preg_match('/^(京都市[^区]+区)/', $address, $matches)) {
            return $matches[1];
        }
        
        return '';
    }
} 