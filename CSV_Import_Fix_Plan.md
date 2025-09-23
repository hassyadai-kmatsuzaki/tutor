# CSVインポート機能修正方針

## 現状の問題点

### 1. 文字化け問題
- 現在のCSVインポート機能で文字化けが発生している
- 日本語文字（ひらがな、カタカナ、漢字）が正しく表示されない

### 2. 新しいCSVファイル形式への対応
- スプレッドシートから抽出した新しいCSVファイル形式に対応する必要がある
- 買いニーズファイル（顧客情報）を直接インポートできるようにしたい

## 分析結果

### 現在のシステム構造
1. **物件インポート**: `PropertyController::import()`
2. **顧客インポート**: `CustomerController::import()`
3. **フロントエンド**: `CSVImportDialog.tsx`

### 新しいCSVファイル構造

#### 物件情報CSVファイル（京都・京都以外）
```
物件名,種別,担当名,登録日,住所,情報取得先,取引態様,地積(㎡),建物面積(㎡),構造階数,築年,価格【万円】,坪単価【万円】,現況利回,満室利回,備考,物件情報URL,売主ドライブ,販売済み
```

#### 買いニーズファイル（顧客情報）
```
仲介会社,担当,買主名,買主属性,エリア,種目,用途,価格,築年数,構造,利回り,土地面積(坪）,備考
```

## 修正方針

### 1. 文字化け修正

#### バックエンド修正
- **ファイル**: `PropertyController.php`, `CustomerController.php`
- **対応**: 
  - CSVファイル読み込み時の文字エンコーディング指定
  - UTF-8、Shift_JIS、EUC-JP等の自動判定機能追加
  - `mb_convert_encoding()` を使用した文字コード変換

#### フロントエンド修正
- **ファイル**: `CSVImportDialog.tsx`
- **対応**: 
  - CSVテンプレートダウンロード時のBOM付きUTF-8対応
  - ファイルアップロード時の文字コード指定オプション追加

### 2. 新しいCSVファイル形式への対応

#### 物件情報インポート機能拡張
- **現在のフィールドマッピング更新**:
  ```php
  // 現在
  'property_name' => $data['物件名'] ?? '',
  'property_type' => $data['種別'] ?? '',
  
  // 新しいマッピング
  'property_name' => $data['物件名'] ?? '',
  'property_type' => $data['種別'] ?? '',
  'manager_name' => $data['担当名'] ?? '',
  'registration_date' => $data['登録日'] ?? now()->format('Y-m-d'),
  'address' => $data['住所'] ?? '',
  'information_source' => $data['情報取得先'] ?? null,
  'transaction_category' => $data['取引態様'] ?? '',
  'land_area' => $this->parseNumeric($data['地積(㎡)'] ?? null),
  'building_area' => $this->parseNumeric($data['建物面積(㎡)'] ?? null),
  'structure_floors' => $data['構造階数'] ?? null,
  'construction_year' => $this->parseDate($data['築年'] ?? null),
  'price' => $this->parseNumeric($data['価格【万円】'] ?? null),
  'price_per_unit' => $this->parseNumeric($data['坪単価【万円】'] ?? null),
  'current_profit' => $this->parseNumeric($data['現況利回'] ?? null),
  'remarks' => $data['備考'] ?? null,
  ```

#### 顧客情報インポート機能拡張
- **新しいフィールドマッピング作成**:
  ```php
  'customer_name' => $data['買主名'] ?? '',
  'customer_type' => $this->mapCustomerType($data['買主属性'] ?? ''),
  'area_preference' => $data['エリア'] ?? '',
  'property_type_preference' => $this->mapPropertyTypes($data['種目'] ?? ''),
  'detailed_requirements' => $data['用途'] ?? '',
  'budget_max' => $this->parseBudget($data['価格'] ?? null),
  'yield_requirement' => $this->parseNumeric($data['利回り'] ?? null),
  'contact_person' => $data['担当'] ?? '',
  'remarks' => $data['備考'] ?? null,
  ```

### 3. 4つの主要マッチング条件への対応

#### データベース構造確認
- **種目**: `property_type` / `property_type_preference`
- **エリア**: `address` / `area_preference` 
- **坪数（地積）**: `land_area` 
- **価格**: `price` / `budget_min`, `budget_max`

#### マッチング機能強化
- **ファイル**: `PropertyMatchController.php`
- **対応**:
  - 4つの主要条件での基本マッチング機能実装
  - 条件追加可能な拡張性確保

### 4. 実装手順

#### Phase 1: 文字化け修正（優先度：高）
1. CSVファイル読み込み時の文字コード自動判定機能追加
2. 文字コード変換処理の実装
3. テスト用CSVファイルでの動作確認

#### Phase 2: 新しいCSVファイル形式対応（優先度：高）
1. 物件情報インポート機能の拡張
2. 顧客情報インポート機能の拡張
3. フィールドマッピングの更新

#### Phase 3: マッチング機能強化（優先度：中）
1. 4つの主要条件でのマッチング機能実装
2. 条件追加機能の実装
3. マッチング結果表示の改善

#### Phase 4: UI/UX改善（優先度：低）
1. CSVインポートダイアログの改善
2. エラーハンドリングの強化
3. インポート進捗表示の追加

## 技術的な実装詳細

### 文字コード自動判定
```php
private function detectEncoding($content)
{
    $encodings = ['UTF-8', 'Shift_JIS', 'EUC-JP', 'ISO-2022-JP'];
    return mb_detect_encoding($content, $encodings, true) ?: 'UTF-8';
}

private function convertToUtf8($content)
{
    $encoding = $this->detectEncoding($content);
    if ($encoding !== 'UTF-8') {
        return mb_convert_encoding($content, 'UTF-8', $encoding);
    }
    return $content;
}
```

### 数値パース処理
```php
private function parseNumeric($value)
{
    if (empty($value)) return null;
    
    // カンマや全角数字を半角に変換
    $value = str_replace(['，', ','], '', $value);
    $value = mb_convert_kana($value, 'n');
    
    return is_numeric($value) ? (float)$value : null;
}
```

### 日付パース処理
```php
private function parseDate($value)
{
    if (empty($value)) return null;
    
    // 様々な日付形式に対応
    $formats = ['Y/m/d', 'Y-m-d', 'Y年m月d日', 'Y/m', 'Y年m月'];
    
    foreach ($formats as $format) {
        $date = DateTime::createFromFormat($format, $value);
        if ($date) {
            return $date->format('Y-m-d');
        }
    }
    
    return null;
}
```

## 期待される効果

1. **文字化け問題の解決**: 日本語CSVファイルが正常にインポート可能
2. **業務効率の向上**: スプレッドシートから直接CSVエクスポートしてインポート可能
3. **マッチング精度向上**: 4つの主要条件での正確なマッチング
4. **拡張性の確保**: 将来的な条件追加に対応可能な設計

## 注意事項

1. **データ移行**: 既存データとの整合性確保
2. **バックアップ**: インポート前の必須バックアップ
3. **テスト**: 各段階での十分なテスト実施
4. **ユーザー教育**: 新しいCSVフォーマットの利用方法周知 