<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Property;
use App\Models\Customer;
use App\Models\PropertyMatch;
use App\Models\Activity;
use App\Models\CustomerPreference;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class RealEstateSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // ユーザー作成
        $this->createUsers();
        
        // 物件作成
        $this->createProperties();
        
        // 顧客作成
        $this->createCustomers();
        
        // マッチング作成
        $this->createMatches();
        
        // 活動履歴作成
        $this->createActivities();
    }

    /**
     * ユーザー作成
     */
    private function createUsers(): void
    {
        // 管理者
        User::create([
            'name' => '管理者',
            'email' => 'admin@realestate.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
            'department' => '管理部',
            'phone' => '03-1234-5678',
            'is_active' => true,
        ]);

        // 営業担当者
        $salesUsers = [
            ['name' => '田中太郎', 'email' => 'tanaka@realestate.com', 'department' => '営業1部'],
            ['name' => '佐藤花子', 'email' => 'sato@realestate.com', 'department' => '営業1部'],
            ['name' => '鈴木一郎', 'email' => 'suzuki@realestate.com', 'department' => '営業2部'],
            ['name' => '高橋美咲', 'email' => 'takahashi@realestate.com', 'department' => '営業2部'],
            ['name' => '伊藤健太', 'email' => 'ito@realestate.com', 'department' => '営業3部'],
        ];

        foreach ($salesUsers as $userData) {
            User::create([
                'name' => $userData['name'],
                'email' => $userData['email'],
                'password' => Hash::make('password'),
                'role' => 'sales',
                'department' => $userData['department'],
                'phone' => '090-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'is_active' => true,
            ]);
        }
    }

    /**
     * 物件作成
     */
    private function createProperties(): void
    {
        $properties = [
            [
                'property_name' => 'プレアール北田',
                'property_type' => '店舗',
                'manager_name' => '津田',
                'registration_date' => '2025-09-01',
                'address' => '東住吉区湯里1-1-12',
                'information_source' => 'ライズアセット',
                'transaction_category' => '先物',
                'land_area' => 128.26,
                'building_area' => 396.28,
                'structure_floors' => '鉄骨造3階×ツ',
                'construction_year' => '1905-06-10',
                'price' => 8600,
                'price_per_unit' => 123,
                'current_profit' => null,
                'prefecture' => '大阪府',
                'city' => '大阪市東住吉区',
                'nearest_station' => null,
                'walking_minutes' => null,
                'remarks' => null,
                'status' => 'available',
            ],
            [
                'property_name' => '東住吉区湯里',
                'property_type' => '土地',
                'manager_name' => '坂元',
                'registration_date' => '2025-08-27',
                'address' => '東住吉区湯里',
                'information_source' => '有馬不動産',
                'transaction_category' => '元付',
                'land_area' => 331,
                'building_area' => null,
                'structure_floors' => null,
                'construction_year' => null,
                'price' => 24200,
                'price_per_unit' => 346,
                'current_profit' => null,
                'prefecture' => '大阪府',
                'city' => '大阪市東住吉区',
                'nearest_station' => null,
                'walking_minutes' => null,
                'remarks' => null,
                'status' => 'available',
            ],
            [
                'property_name' => '今林9丁目',
                'property_type' => '事務所',
                'manager_name' => '宮田',
                'registration_date' => '2025-08-08',
                'address' => '東住吉区今林9丁目',
                'information_source' => 'ＢＲＡＶＩ不動産',
                'transaction_category' => '元付',
                'land_area' => 264.49,
                'building_area' => 861.79,
                'structure_floors' => '鉄骨造地上4階',
                'construction_year' => '1988-01-01',
                'price' => 15000,
                'price_per_unit' => 215,
                'current_profit' => null,
                'prefecture' => '大阪府',
                'city' => '大阪市東住吉区',
                'nearest_station' => null,
                'walking_minutes' => null,
                'remarks' => null,
                'status' => 'available',
            ],
            [
                'property_name' => '住吉区収益店舗',
                'property_type' => '店舗',
                'manager_name' => '津田',
                'registration_date' => '2025-07-24',
                'address' => '住吉区',
                'information_source' => '住友不動産ネットワーク',
                'transaction_category' => '元付',
                'land_area' => 565.28,
                'building_area' => 1334.77,
                'structure_floors' => 'ＲＣ造地上×ツ',
                'construction_year' => '1988-01-01',
                'price' => 39800,
                'price_per_unit' => 570,
                'current_profit' => null,
                'prefecture' => '大阪府',
                'city' => '大阪市住吉区',
                'nearest_station' => null,
                'walking_minutes' => null,
                'remarks' => null,
                'status' => 'available',
            ],
            [
                'property_name' => '西今川2丁目ハイツ',
                'property_type' => 'レジ',
                'manager_name' => '遠藤',
                'registration_date' => '2025-07-08',
                'address' => '東住吉区西今川2丁目',
                'information_source' => 'みずほ',
                'transaction_category' => '先物',
                'land_area' => 329.67,
                'building_area' => 856.78,
                'structure_floors' => '鉄骨造陸屋根4階',
                'construction_year' => '1986-04-01',
                'price' => 21000,
                'price_per_unit' => 301,
                'current_profit' => null,
                'prefecture' => '大阪府',
                'city' => '大阪市東住吉区',
                'nearest_station' => null,
                'walking_minutes' => null,
                'remarks' => null,
                'status' => 'available',
            ],
        ];

        $users = User::where('role', 'sales')->get();

        foreach ($properties as $propertyData) {
            $propertyData['created_by'] = $users->random()->id;
            Property::create($propertyData);
        }

        // 追加の物件データ（ランダム生成）
        $propertyTypes = ['店舗', 'レジ', '土地', '事務所', '区分', '一棟ビル'];
        $transactionCategories = ['先物', '元付', '売主'];
        $prefectures = ['東京都', '大阪府', '神奈川県', '愛知県', '福岡県'];
        $cities = [
            '東京都' => ['新宿区', '渋谷区', '港区', '千代田区'],
            '大阪府' => ['大阪市中央区', '大阪市北区', '大阪市西区', '大阪市東住吉区'],
            '神奈川県' => ['横浜市中区', '川崎市川崎区', '藤沢市'],
            '愛知県' => ['名古屋市中区', '名古屋市東区'],
            '福岡県' => ['福岡市中央区', '福岡市博多区'],
        ];

        for ($i = 1; $i <= 20; $i++) {
            $prefecture = $prefectures[array_rand($prefectures)];
            $city = $cities[$prefecture][array_rand($cities[$prefecture])];
            
            Property::create([
                'property_name' => "サンプル物件{$i}",
                'property_type' => $propertyTypes[array_rand($propertyTypes)],
                'manager_name' => $users->random()->name,
                'registration_date' => now()->subDays(rand(1, 365)),
                'address' => "{$city}" . rand(1, 5) . "-" . rand(1, 20) . "-" . rand(1, 30),
                'information_source' => ['不動産会社A', '不動産会社B', '直接'][array_rand(['不動産会社A', '不動産会社B', '直接'])],
                'transaction_category' => $transactionCategories[array_rand($transactionCategories)],
                'land_area' => rand(50, 500) + (rand(0, 99) / 100),
                'building_area' => rand(100, 1000) + (rand(0, 99) / 100),
                'structure_floors' => ['RC造3階建', '鉄骨造2階建', '木造2階建'][array_rand(['RC造3階建', '鉄骨造2階建', '木造2階建'])],
                'construction_year' => now()->subYears(rand(1, 50)),
                'price' => rand(1000, 50000),
                'price_per_unit' => rand(50, 500) + (rand(0, 99) / 100),
                'current_profit' => rand(30, 120) / 10,
                'prefecture' => $prefecture,
                'city' => $city,
                'nearest_station' => "最寄り駅{$i}",
                'walking_minutes' => rand(1, 15),
                'remarks' => rand(0, 1) ? "備考{$i}" : null,
                'status' => ['available', 'reserved', 'sold'][array_rand(['available', 'reserved', 'sold'])],
                'created_by' => $users->random()->id,
            ]);
        }
    }

    /**
     * 顧客作成
     */
    private function createCustomers(): void
    {
        $customerTypes = ['法人', '個人', 'エンド法人', '飲食経営者'];
        $priorities = ['高', '中', '低'];
        $statuses = ['active', 'negotiating', 'closed', 'suspended'];
        $propertyTypePreferences = ['店舗', 'レジ', '土地', '事務所', '区分'];
        $areas = ['東京都', '大阪府', '神奈川県', '愛知県'];

        $users = User::where('role', 'sales')->get();

        for ($i = 1; $i <= 30; $i++) {
            $customer = Customer::create([
                'customer_name' => "顧客{$i}",
                'customer_type' => $customerTypes[array_rand($customerTypes)],
                'area_preference' => $areas[array_rand($areas)],
                'property_type_preference' => implode(',', array_slice($propertyTypePreferences, 0, rand(1, 3))),
                'detailed_requirements' => rand(0, 1) ? "詳細要望{$i}" : null,
                'budget_min' => rand(500, 5000),
                'budget_max' => rand(5000, 50000),
                'yield_requirement' => rand(30, 100) / 10,
                'contact_person' => "担当者{$i}",
                'phone' => '090-' . rand(1000, 9999) . '-' . rand(1000, 9999),
                'email' => "customer{$i}@example.com",
                'address' => "住所{$i}",
                'priority' => $priorities[array_rand($priorities)],
                'status' => $statuses[array_rand($statuses)],
                'last_contact_date' => rand(0, 1) ? now()->subDays(rand(1, 90)) : null,
                'next_contact_date' => rand(0, 1) ? now()->addDays(rand(1, 30)) : null,
                'assigned_to' => $users->random()->id,
            ]);

            // 顧客の詳細条件を作成
            $this->createCustomerPreferences($customer);
        }
    }

    /**
     * 顧客の詳細条件作成
     */
    private function createCustomerPreferences(Customer $customer): void
    {
        $preferenceTypes = ['area', 'station', 'structure', 'age', 'yield', 'size'];
        $priorities = ['must', 'want', 'nice_to_have'];

        $numPreferences = rand(2, 5);
        $selectedTypes = array_slice($preferenceTypes, 0, $numPreferences);

        foreach ($selectedTypes as $type) {
            CustomerPreference::create([
                'customer_id' => $customer->id,
                'preference_type' => $type,
                'preference_key' => $type . '_condition',
                'preference_value' => $this->generatePreferenceValue($type),
                'priority' => $priorities[array_rand($priorities)],
            ]);
        }
    }

    /**
     * 条件値生成
     */
    private function generatePreferenceValue(string $type): string
    {
        return match($type) {
            'area' => ['東京都内', '大阪市内', '駅近'][array_rand(['東京都内', '大阪市内', '駅近'])],
            'station' => ['徒歩5分以内', '徒歩10分以内', 'JR沿線'][array_rand(['徒歩5分以内', '徒歩10分以内', 'JR沿線'])],
            'structure' => ['RC造', '鉄骨造', '耐震基準適合'][array_rand(['RC造', '鉄骨造', '耐震基準適合'])],
            'age' => ['築10年以内', '築20年以内', '新築'][array_rand(['築10年以内', '築20年以内', '新築'])],
            'yield' => ['利回り5%以上', '利回り7%以上'][array_rand(['利回り5%以上', '利回り7%以上'])],
            'size' => ['土地面積100㎡以上', '建物面積200㎡以上'][array_rand(['土地面積100㎡以上', '建物面積200㎡以上'])],
            default => '条件値'
        };
    }

    /**
     * マッチング作成
     */
    private function createMatches(): void
    {
        $properties = Property::available()->get();
        $customers = Customer::active()->get();
        $users = User::where('role', 'sales')->get();
        $statuses = ['matched', 'presented', 'interested', 'rejected', 'contracted'];

        // ランダムにマッチングを作成
        for ($i = 0; $i < 50; $i++) {
            $property = $properties->random();
            $customer = $customers->random();

            // 既存のマッチングをチェック
            $existingMatch = PropertyMatch::where('property_id', $property->id)
                ->where('customer_id', $customer->id)
                ->first();

            if ($existingMatch) {
                continue;
            }

            $score = rand(50, 100) + (rand(0, 99) / 100);
            $status = $statuses[array_rand($statuses)];

            $match = PropertyMatch::create([
                'property_id' => $property->id,
                'customer_id' => $customer->id,
                'match_score' => $score,
                'match_reason' => $this->generateMatchReason($property, $customer, $score),
                'status' => $status,
                'presented_at' => in_array($status, ['presented', 'interested', 'rejected', 'contracted']) ? now()->subDays(rand(1, 30)) : null,
                'response_at' => in_array($status, ['interested', 'rejected', 'contracted']) ? now()->subDays(rand(1, 20)) : null,
                'response_comment' => rand(0, 1) ? "コメント{$i}" : null,
                'created_by' => $users->random()->id,
            ]);

            // 成約の場合は物件と顧客のステータスを更新
            if ($status === 'contracted') {
                $property->update(['status' => 'sold']);
                $customer->update(['status' => 'closed']);
            }
        }
    }

    /**
     * マッチング理由生成
     */
    private function generateMatchReason(Property $property, Customer $customer, float $score): string
    {
        $reasons = [];

        // 価格条件
        if ($customer->budget_min && $customer->budget_max && 
            $property->price >= $customer->budget_min && $property->price <= $customer->budget_max) {
            $reasons[] = '予算条件に適合';
        }

        // エリア条件
        if ($customer->area_preference && str_contains($property->prefecture, $customer->area_preference)) {
            $reasons[] = '希望エリアに該当';
        }

        // 種別条件
        $customerTypes = explode(',', $customer->property_type_preference ?? '');
        if (in_array($property->property_type, $customerTypes)) {
            $reasons[] = '希望物件種別に該当';
        }

        if (empty($reasons)) {
            $reasons[] = '総合的な条件でマッチング';
        }

        return implode('、', $reasons) . "（スコア: {$score}点）";
    }

    /**
     * 活動履歴作成
     */
    private function createActivities(): void
    {
        $users = User::where('role', 'sales')->get();
        $properties = Property::all();
        $customers = Customer::all();
        $matches = PropertyMatch::all();

        $activityTypes = [
            'property_created', 'property_updated', 'customer_created', 'customer_updated',
            'match_created', 'presentation', 'contact', 'meeting', 'contract'
        ];

        // 各種活動を作成
        for ($i = 0; $i < 100; $i++) {
            $activityType = $activityTypes[array_rand($activityTypes)];
            $user = $users->random();

            [$subjectType, $subjectId, $title, $description] = $this->generateActivityData(
                $activityType, $properties, $customers, $matches
            );

            Activity::create([
                'user_id' => $user->id,
                'activity_type' => $activityType,
                'subject_type' => $subjectType,
                'subject_id' => $subjectId,
                'title' => $title,
                'description' => $description,
                'activity_date' => now()->subDays(rand(1, 90))->subHours(rand(0, 23))->subMinutes(rand(0, 59)),
            ]);
        }
    }

    /**
     * 活動データ生成
     */
    private function generateActivityData(string $activityType, $properties, $customers, $matches): array
    {
        return match($activityType) {
            'property_created', 'property_updated' => [
                'property',
                $properties->random()->id,
                '物件を' . ($activityType === 'property_created' ? '登録' : '更新') . 'しました',
                "活動詳細: {$activityType}"
            ],
            'customer_created', 'customer_updated' => [
                'customer',
                $customers->random()->id,
                '顧客を' . ($activityType === 'customer_created' ? '登録' : '更新') . 'しました',
                "活動詳細: {$activityType}"
            ],
            'match_created' => [
                'match',
                $matches->random()->id,
                'マッチングを作成しました',
                "活動詳細: {$activityType}"
            ],
            'presentation' => [
                'match',
                $matches->random()->id,
                '物件提案を実施しました',
                "提案内容の詳細説明"
            ],
            'contact' => [
                'customer',
                $customers->random()->id,
                '顧客に連絡しました',
                "電話での連絡、次回面談の調整"
            ],
            'meeting' => [
                'customer',
                $customers->random()->id,
                '顧客と面談しました',
                "面談内容の詳細、要望のヒアリング"
            ],
            'contract' => [
                'match',
                $matches->where('status', 'contracted')->random()->id ?? $matches->random()->id,
                '契約を締結しました',
                "契約締結の詳細"
            ],
            default => [
                'property',
                $properties->random()->id,
                'その他の活動',
                "活動詳細"
            ]
        };
    }
} 