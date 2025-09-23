# 不動産営業支援システム データベース設計書

## 概要
不動産売買を行う会社の営業支援ツールとして、物件情報と顧客情報を管理し、マッチング機能を提供するシステムのデータベース設計です。

## システム要件
- フロントエンド: React + TypeScript + MUI
- バックエンド: Laravel 12 (BFF)
- 機能: 物件管理、顧客管理、マッチング、ダッシュボード、アカウント管理

## テーブル設計

### 1. users（ユーザー管理）
営業担当者や管理者のアカウント情報を管理

```sql
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL COMMENT '氏名',
    email VARCHAR(255) UNIQUE NOT NULL COMMENT 'メールアドレス',
    email_verified_at TIMESTAMP NULL,
    password VARCHAR(255) NOT NULL COMMENT 'パスワード',
    role ENUM('admin', 'sales') DEFAULT 'sales' COMMENT '権限（管理者/営業）',
    department VARCHAR(100) NULL COMMENT '部署',
    phone VARCHAR(20) NULL COMMENT '電話番号',
    is_active BOOLEAN DEFAULT TRUE COMMENT 'アクティブフラグ',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);
```

### 2. properties（物件情報）
売買対象の物件情報を管理

```sql
CREATE TABLE properties (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_name VARCHAR(255) NOT NULL COMMENT '物件名',
    property_type ENUM('店舗', 'レジ', '土地', '事務所', '区分', '一棟ビル', '十地', '新築ホテル') NOT NULL COMMENT '種別',
    manager_name VARCHAR(100) NOT NULL COMMENT '担当者名',
    registration_date DATE NOT NULL COMMENT '登録日',
    address TEXT NOT NULL COMMENT '住所',
    information_source VARCHAR(255) NULL COMMENT '情報取得先',
    transaction_category ENUM('先物', '元付', '売主') NOT NULL COMMENT '取引区分',
    land_area DECIMAL(10,2) NULL COMMENT '地積(㎡)',
    building_area DECIMAL(10,2) NULL COMMENT '建物面積(㎡)',
    structure_floors VARCHAR(100) NULL COMMENT '構造階数',
    construction_year DATE NULL COMMENT '築年',
    price BIGINT NOT NULL COMMENT '価格（万円）',
    price_per_unit DECIMAL(10,2) NULL COMMENT '坪単価（万円）',
    current_profit DECIMAL(15,2) NULL COMMENT '現況利回り(%)',
    prefecture VARCHAR(50) NOT NULL COMMENT '都道府県',
    city VARCHAR(100) NOT NULL COMMENT '市区町村',
    nearest_station VARCHAR(100) NULL COMMENT '最寄り駅',
    walking_minutes INT NULL COMMENT '徒歩分数',
    remarks TEXT NULL COMMENT '備考',
    status ENUM('available', 'reserved', 'sold', 'suspended') DEFAULT 'available' COMMENT 'ステータス',
    created_by BIGINT UNSIGNED NOT NULL COMMENT '登録者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### 3. customers（顧客情報）
見込み客の情報と購買条件を管理

```sql
CREATE TABLE customers (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_name VARCHAR(255) NOT NULL COMMENT '顧客名',
    customer_type ENUM('法人', '個人', '自社', 'エンド法人', 'エンド（中国系）', '飲食経営者', '不動明屋', '半法商事') NOT NULL COMMENT '顧客属性',
    area_preference VARCHAR(255) NULL COMMENT 'エリア希望',
    property_type_preference SET('店舗', 'レジ', '土地', '事務所', '区分', '一棟ビル', '十地', '新築ホテル', 'オフィス') NULL COMMENT '種別希望',
    detailed_requirements TEXT NULL COMMENT '詳細要望',
    budget_min BIGINT NULL COMMENT '予算下限（万円）',
    budget_max BIGINT NULL COMMENT '予算上限（万円）',
    yield_requirement DECIMAL(5,2) NULL COMMENT '利回り要求(%)',
    contact_person VARCHAR(255) NULL COMMENT '担当者名',
    phone VARCHAR(20) NULL COMMENT '電話番号',
    email VARCHAR(255) NULL COMMENT 'メールアドレス',
    address TEXT NULL COMMENT '住所',
    priority ENUM('高', '中', '低') DEFAULT '中' COMMENT '優先度',
    status ENUM('active', 'negotiating', 'closed', 'suspended') DEFAULT 'active' COMMENT 'ステータス',
    last_contact_date DATE NULL COMMENT '最終接触日',
    next_contact_date DATE NULL COMMENT '次回接触予定日',
    assigned_to BIGINT UNSIGNED NOT NULL COMMENT '担当営業ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users(id)
);
```

### 4. property_matches（マッチング情報）
物件と顧客のマッチング結果を管理

```sql
CREATE TABLE property_matches (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    customer_id BIGINT UNSIGNED NOT NULL,
    match_score DECIMAL(5,2) NOT NULL COMMENT 'マッチングスコア(0-100)',
    match_reason TEXT NULL COMMENT 'マッチング理由',
    status ENUM('matched', 'presented', 'interested', 'rejected', 'contracted') DEFAULT 'matched' COMMENT 'ステータス',
    presented_at TIMESTAMP NULL COMMENT '提案日時',
    response_at TIMESTAMP NULL COMMENT '回答日時',
    response_comment TEXT NULL COMMENT '顧客からのコメント',
    created_by BIGINT UNSIGNED NOT NULL COMMENT '作成者ID',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id),
    UNIQUE KEY unique_match (property_id, customer_id)
);
```

### 5. activities（活動履歴）
営業活動の履歴を管理

```sql
CREATE TABLE activities (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL COMMENT '実行者ID',
    activity_type ENUM('property_created', 'property_updated', 'customer_created', 'customer_updated', 'match_created', 'presentation', 'contact', 'meeting', 'contract') NOT NULL COMMENT '活動種別',
    subject_type ENUM('property', 'customer', 'match') NOT NULL COMMENT '対象種別',
    subject_id BIGINT UNSIGNED NOT NULL COMMENT '対象ID',
    title VARCHAR(255) NOT NULL COMMENT 'タイトル',
    description TEXT NULL COMMENT '詳細',
    activity_date TIMESTAMP NOT NULL COMMENT '活動日時',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    INDEX idx_subject (subject_type, subject_id),
    INDEX idx_activity_date (activity_date)
);
```

### 6. property_images（物件画像）
物件の画像を管理

```sql
CREATE TABLE property_images (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    property_id BIGINT UNSIGNED NOT NULL,
    image_path VARCHAR(500) NOT NULL COMMENT '画像パス',
    image_type ENUM('exterior', 'interior', 'layout', 'other') DEFAULT 'other' COMMENT '画像種別',
    caption VARCHAR(255) NULL COMMENT 'キャプション',
    sort_order INT DEFAULT 0 COMMENT '表示順',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (property_id) REFERENCES properties(id) ON DELETE CASCADE,
    INDEX idx_property_sort (property_id, sort_order)
);
```

### 7. customer_preferences（顧客詳細条件）
顧客の詳細な希望条件を管理

```sql
CREATE TABLE customer_preferences (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    customer_id BIGINT UNSIGNED NOT NULL,
    preference_type ENUM('area', 'station', 'structure', 'age', 'yield', 'size', 'other') NOT NULL COMMENT '条件種別',
    preference_key VARCHAR(100) NOT NULL COMMENT '条件キー',
    preference_value TEXT NOT NULL COMMENT '条件値',
    priority ENUM('must', 'want', 'nice_to_have') DEFAULT 'want' COMMENT '優先度',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (customer_id) REFERENCES customers(id) ON DELETE CASCADE,
    INDEX idx_customer_type (customer_id, preference_type)
);
```

## インデックス設計

### 検索パフォーマンス向上のための主要インデックス

```sql
-- 物件検索用
ALTER TABLE properties ADD INDEX idx_search (property_type, prefecture, city, price, status);
ALTER TABLE properties ADD INDEX idx_price_range (price, status);
ALTER TABLE properties ADD INDEX idx_area (land_area, building_area);
ALTER TABLE properties ADD INDEX idx_yield (current_profit, status);

-- 顧客検索用
ALTER TABLE customers ADD INDEX idx_budget (budget_min, budget_max, status);
ALTER TABLE customers ADD INDEX idx_assigned (assigned_to, status);
ALTER TABLE customers ADD INDEX idx_priority (priority, status);

-- マッチング検索用
ALTER TABLE property_matches ADD INDEX idx_score (match_score, status);
ALTER TABLE property_matches ADD INDEX idx_customer_status (customer_id, status);
ALTER TABLE property_matches ADD INDEX idx_property_status (property_id, status);
```

## マッチングロジック

### 基本マッチング条件
1. **価格条件**: 物件価格が顧客予算範囲内
2. **エリア条件**: 物件所在地が顧客希望エリアに合致
3. **種別条件**: 物件種別が顧客希望種別に含まれる
4. **利回り条件**: 物件利回りが顧客要求以上
5. **面積条件**: 物件面積が顧客希望範囲内

### マッチングスコア計算
- 価格マッチ度: 30%
- エリアマッチ度: 25%
- 種別マッチ度: 20%
- 利回りマッチ度: 15%
- その他条件マッチ度: 10%

## API設計指針

### RESTful API エンドポイント例
```
GET    /api/properties          # 物件一覧取得
POST   /api/properties          # 物件登録
GET    /api/properties/{id}     # 物件詳細取得
PUT    /api/properties/{id}     # 物件更新
DELETE /api/properties/{id}     # 物件削除

GET    /api/customers           # 顧客一覧取得
POST   /api/customers           # 顧客登録
GET    /api/customers/{id}      # 顧客詳細取得
PUT    /api/customers/{id}      # 顧客更新

GET    /api/matches             # マッチング一覧
POST   /api/matches/generate    # マッチング実行
PUT    /api/matches/{id}        # マッチング状況更新

GET    /api/dashboard/stats     # ダッシュボード統計
GET    /api/activities          # 活動履歴
```

## セキュリティ考慮事項

1. **認証・認可**: Laravel Sanctum使用
2. **データ暗号化**: 個人情報の暗号化
3. **アクセス制御**: ロールベースアクセス制御
4. **監査ログ**: 重要操作の履歴保存
5. **データバックアップ**: 定期バックアップ

## パフォーマンス最適化

1. **キャッシュ戦略**: Redis使用
2. **画像最適化**: 複数サイズ生成
3. **ページネーション**: 大量データ対応
4. **非同期処理**: マッチング処理のQueue化

## 今後の拡張性

1. **AI機能**: 機械学習によるマッチング精度向上
2. **外部連携**: 不動産ポータルサイトとの連携
3. **モバイル対応**: PWA対応
4. **レポート機能**: 売上分析・営業効率分析 