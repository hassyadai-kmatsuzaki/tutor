<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Property extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::created(function (Property $property): void {
            // property_code未設定なら、idを8桁ゼロ埋めで設定
            if (empty($property->property_code ?? null)) {
                $property->property_code = str_pad((string)$property->id, 8, '0', STR_PAD_LEFT);
                // 競合回避のためサイレントに保存（例外はそのまま投げる）
                $property->saveQuietly();
            }
        });
    }

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'property_name',
        'property_type',
        'manager_name',
        'registration_date',
        'address',
        'information_source',
        'transaction_category',
        'land_area',
        'building_area',
        'structure_floors',
        'construction_year',
        'price',
        'price_per_unit',
        'current_profit',
        'prefecture',
        'city',
        'nearest_station',
        'walking_minutes',
        'remarks',
        'status',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'registration_date' => 'date',
        'construction_year' => 'date',
        'land_area' => 'decimal:2',
        'building_area' => 'decimal:2',
        'price' => 'integer',
        'price_per_unit' => 'decimal:2',
        'current_profit' => 'decimal:2',
        'walking_minutes' => 'integer',
    ];

    /**
     * 物件種別の選択肢
     */
    public const PROPERTY_TYPES = [
        '店舗', 'レジ', '土地', '事務所', '区分', '一棟ビル', '十地', '新築ホテル'
    ];

    /**
     * 取引区分の選択肢
     */
    public const TRANSACTION_CATEGORIES = [
        '先物', '元付', '売主'
    ];

    /**
     * ステータスの選択肢
     */
    public const STATUSES = [
        'available' => '販売中',
        'reserved' => '商談中',
        'sold' => '成約済み',
        'suspended' => '一時停止'
    ];

    /**
     * 登録者（営業担当者）
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 物件画像
     */
    public function images(): HasMany
    {
        return $this->hasMany(PropertyImage::class)->orderBy('sort_order');
    }

    /**
     * マッチング情報
     */
    public function matches(): HasMany
    {
        return $this->hasMany(PropertyMatch::class);
    }

    /**
     * 活動履歴
     */
    public function activities(): HasMany
    {
        return $this->hasMany(Activity::class, 'subject_id')
                    ->where('subject_type', 'property');
    }

    /**
     * 販売中の物件のスコープ
     */
    public function scopeAvailable($query)
    {
        return $query->where('status', 'available');
    }

    /**
     * 指定エリアの物件のスコープ
     */
    public function scopeInArea($query, $prefecture = null, $city = null)
    {
        if ($prefecture) {
            $query->where('prefecture', $prefecture);
        }
        if ($city) {
            $query->where('city', $city);
        }
        return $query;
    }

    /**
     * 価格帯での絞り込みスコープ
     */
    public function scopePriceRange($query, $min = null, $max = null)
    {
        if ($min) {
            $query->where('price', '>=', $min);
        }
        if ($max) {
            $query->where('price', '<=', $max);
        }
        return $query;
    }

    /**
     * 利回り範囲での絞り込みスコープ
     */
    public function scopeYieldRange($query, $min = null, $max = null)
    {
        if ($min) {
            $query->where('current_profit', '>=', $min);
        }
        if ($max) {
            $query->where('current_profit', '<=', $max);
        }
        return $query;
    }

    /**
     * 物件種別での絞り込みスコープ
     */
    public function scopeOfType($query, $types)
    {
        if (is_array($types)) {
            return $query->whereIn('property_type', $types);
        }
        return $query->where('property_type', $types);
    }

    /**
     * ステータス表示名を取得
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * メイン画像を取得
     */
    public function getMainImageAttribute(): ?PropertyImage
    {
        return $this->images()->first();
    }

    /**
     * 築年数を計算
     */
    public function getBuildingAgeAttribute(): ?int
    {
        if (!$this->construction_year) {
            return null;
        }
        return now()->year - $this->construction_year->year;
    }

    /**
     * 完全な住所を取得
     */
    public function getFullAddressAttribute(): string
    {
        return $this->prefecture . $this->city . $this->address;
    }

    /**
     * 坪単価を自動計算（土地面積ベース）
     */
    public function calculatePricePerTsubo(): ?float
    {
        if (!$this->land_area || $this->land_area <= 0) {
            return null;
        }
        // 1坪 = 3.30578平方メートル
        $tsubo = $this->land_area / 3.30578;
        return $this->price / $tsubo;
    }

    /**
     * マッチングスコアの高い顧客を取得
     */
    public function getTopMatchedCustomers($limit = 5)
    {
        return $this->matches()
                    ->with('customer')
                    ->orderByDesc('match_score')
                    ->limit($limit)
                    ->get()
                    ->pluck('customer');
    }
} 