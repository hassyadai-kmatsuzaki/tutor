<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CustomerPreference extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'customer_id',
        'preference_type',
        'preference_key',
        'preference_value',
        'priority',
    ];

    /**
     * 条件種別の選択肢
     */
    public const PREFERENCE_TYPES = [
        'area' => 'エリア',
        'station' => '駅・交通',
        'structure' => '構造',
        'age' => '築年数',
        'yield' => '利回り',
        'size' => '面積',
        'other' => 'その他'
    ];

    /**
     * 優先度の選択肢
     */
    public const PRIORITIES = [
        'must' => '必須',
        'want' => '希望',
        'nice_to_have' => 'あれば良い'
    ];

    /**
     * 顧客
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 指定条件種別のスコープ
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('preference_type', $type);
    }

    /**
     * 必須条件のスコープ
     */
    public function scopeMustHave($query)
    {
        return $query->where('priority', 'must');
    }

    /**
     * 希望条件のスコープ
     */
    public function scopeWant($query)
    {
        return $query->where('priority', 'want');
    }

    /**
     * 優先度でソートするスコープ
     */
    public function scopeOrderedByPriority($query)
    {
        return $query->orderByRaw("
            CASE priority 
                WHEN 'must' THEN 1 
                WHEN 'want' THEN 2 
                WHEN 'nice_to_have' THEN 3 
            END
        ");
    }

    /**
     * 条件種別の表示名を取得
     */
    public function getPreferenceTypeLabelAttribute(): string
    {
        return self::PREFERENCE_TYPES[$this->preference_type] ?? $this->preference_type;
    }

    /**
     * 優先度の表示名を取得
     */
    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? $this->priority;
    }

    /**
     * 優先度の重みを取得（マッチング計算用）
     */
    public function getPriorityWeightAttribute(): float
    {
        return match($this->priority) {
            'must' => 3.0,
            'want' => 2.0,
            'nice_to_have' => 1.0,
            default => 1.0
        };
    }

    /**
     * 物件がこの条件を満たすかチェック
     */
    public function isMatchedBy(Property $property): bool
    {
        return match($this->preference_type) {
            'area' => $this->checkAreaMatch($property),
            'station' => $this->checkStationMatch($property),
            'structure' => $this->checkStructureMatch($property),
            'age' => $this->checkAgeMatch($property),
            'yield' => $this->checkYieldMatch($property),
            'size' => $this->checkSizeMatch($property),
            default => false
        };
    }

    /**
     * エリア条件のマッチチェック
     */
    private function checkAreaMatch(Property $property): bool
    {
        $area = $this->preference_value;
        return str_contains($property->full_address, $area) ||
               str_contains($property->prefecture, $area) ||
               str_contains($property->city, $area);
    }

    /**
     * 駅・交通条件のマッチチェック
     */
    private function checkStationMatch(Property $property): bool
    {
        if (!$property->nearest_station) {
            return false;
        }

        $stationCondition = $this->preference_value;
        
        // 駅名での検索
        if (str_contains($property->nearest_station, $stationCondition)) {
            return true;
        }

        // 徒歩分数での検索（例：「徒歩5分以内」）
        if (preg_match('/徒歩(\d+)分以内/', $stationCondition, $matches)) {
            $maxMinutes = (int)$matches[1];
            return $property->walking_minutes && $property->walking_minutes <= $maxMinutes;
        }

        return false;
    }

    /**
     * 構造条件のマッチチェック
     */
    private function checkStructureMatch(Property $property): bool
    {
        if (!$property->structure_floors) {
            return false;
        }

        return str_contains($property->structure_floors, $this->preference_value);
    }

    /**
     * 築年数条件のマッチチェック
     */
    private function checkAgeMatch(Property $property): bool
    {
        if (!$property->building_age) {
            return false;
        }

        $ageCondition = $this->preference_value;
        
        // 「築10年以内」のような条件
        if (preg_match('/築(\d+)年以内/', $ageCondition, $matches)) {
            $maxAge = (int)$matches[1];
            return $property->building_age <= $maxAge;
        }

        // 「新築」条件
        if ($ageCondition === '新築') {
            return $property->building_age <= 1;
        }

        return false;
    }

    /**
     * 利回り条件のマッチチェック
     */
    private function checkYieldMatch(Property $property): bool
    {
        if (!$property->current_profit) {
            return false;
        }

        $yieldCondition = $this->preference_value;
        
        // 「利回り5%以上」のような条件
        if (preg_match('/利回り([\d.]+)%以上/', $yieldCondition, $matches)) {
            $minYield = (float)$matches[1];
            return $property->current_profit >= $minYield;
        }

        return false;
    }

    /**
     * 面積条件のマッチチェック
     */
    private function checkSizeMatch(Property $property): bool
    {
        $sizeCondition = $this->preference_value;
        
        // 「土地面積100㎡以上」のような条件
        if (preg_match('/土地面積(\d+)㎡以上/', $sizeCondition, $matches)) {
            $minSize = (float)$matches[1];
            return $property->land_area && $property->land_area >= $minSize;
        }

        // 「建物面積50㎡以上」のような条件
        if (preg_match('/建物面積(\d+)㎡以上/', $sizeCondition, $matches)) {
            $minSize = (float)$matches[1];
            return $property->building_area && $property->building_area >= $minSize;
        }

        return false;
    }
} 