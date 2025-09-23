<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Customer extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'customer_name',
        'customer_type',
        'area_preference',
        'property_type_preference',
        'detailed_requirements',
        'budget_min',
        'budget_max',
        'yield_requirement',
        'contact_person',
        'phone',
        'email',
        'address',
        'priority',
        'status',
        'last_contact_date',
        'next_contact_date',
        'assigned_to',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'budget_min' => 'integer',
        'budget_max' => 'integer',
        'yield_requirement' => 'decimal:2',
        'last_contact_date' => 'date',
        'next_contact_date' => 'date',
    ];

    /**
     * 顧客属性の選択肢
     */
    public const CUSTOMER_TYPES = [
        '法人', '個人', '自社', 'エンド法人', 'エンド（中国系）', 
        '飲食経営者', '不動明屋', '半法商事'
    ];

    /**
     * 希望物件種別の選択肢
     */
    public const PROPERTY_TYPE_PREFERENCES = [
        '店舗', 'レジ', '土地', '事務所', '区分', '一棟ビル', '十地', '新築ホテル', 'オフィス'
    ];

    /**
     * 優先度の選択肢
     */
    public const PRIORITIES = [
        '高' => 'high',
        '中' => 'medium',
        '低' => 'low'
    ];

    /**
     * ステータスの選択肢
     */
    public const STATUSES = [
        'active' => 'アクティブ',
        'negotiating' => '商談中',
        'closed' => '成約',
        'suspended' => '休眠'
    ];

    /**
     * 担当営業者
     */
    public function assignedUser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_to');
    }

    /**
     * 詳細条件
     */
    public function preferences(): HasMany
    {
        return $this->hasMany(CustomerPreference::class);
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
                    ->where('subject_type', 'customer');
    }

    /**
     * アクティブな顧客のスコープ
     */
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }

    /**
     * 担当者による絞り込みスコープ
     */
    public function scopeAssignedTo($query, $userId)
    {
        return $query->where('assigned_to', $userId);
    }

    /**
     * 予算帯による絞り込みスコープ
     */
    public function scopeBudgetRange($query, $min = null, $max = null)
    {
        if ($min) {
            $query->where('budget_max', '>=', $min);
        }
        if ($max) {
            $query->where('budget_min', '<=', $max);
        }
        return $query;
    }

    /**
     * 優先度による絞り込みスコープ
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * 長期未接触の顧客スコープ
     */
    public function scopeLongTimeNoContact($query, $days = 30)
    {
        return $query->where('last_contact_date', '<=', now()->subDays($days))
                    ->orWhereNull('last_contact_date');
    }

    /**
     * 次回接触予定が近い顧客スコープ
     */
    public function scopeUpcomingContact($query, $days = 7)
    {
        return $query->whereBetween('next_contact_date', [
            now(),
            now()->addDays($days)
        ]);
    }

    /**
     * ステータス表示名を取得
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * 優先度表示名を取得
     */
    public function getPriorityLabelAttribute(): string
    {
        return array_search($this->priority, self::PRIORITIES) ?: $this->priority;
    }

    /**
     * 予算範囲の文字列表現を取得
     */
    public function getBudgetRangeAttribute(): string
    {
        if (!$this->budget_min && !$this->budget_max) {
            return '予算未設定';
        }
        
        if (!$this->budget_min) {
            return "〜{$this->budget_max}万円";
        }
        
        if (!$this->budget_max) {
            return "{$this->budget_min}万円〜";
        }
        
        return "{$this->budget_min}〜{$this->budget_max}万円";
    }

    /**
     * 希望物件種別の配列を取得
     */
    public function getPropertyTypePreferenceArrayAttribute(): array
    {
        if (!$this->property_type_preference) {
            return [];
        }
        return explode(',', $this->property_type_preference);
    }

    /**
     * 最終接触からの経過日数を取得
     */
    public function getDaysSinceLastContactAttribute(): ?int
    {
        if (!$this->last_contact_date) {
            return null;
        }
        return $this->last_contact_date->diffInDays(now());
    }

    /**
     * 次回接触までの日数を取得
     */
    public function getDaysUntilNextContactAttribute(): ?int
    {
        if (!$this->next_contact_date) {
            return null;
        }
        return now()->diffInDays($this->next_contact_date, false);
    }

    /**
     * マッチングスコアの高い物件を取得
     */
    public function getTopMatchedProperties($limit = 5)
    {
        return $this->matches()
                    ->with('property')
                    ->orderByDesc('match_score')
                    ->limit($limit)
                    ->get()
                    ->pluck('property');
    }

    /**
     * 指定物件との価格マッチ度を計算
     */
    public function calculatePriceMatch(Property $property): float
    {
        if (!$this->budget_min && !$this->budget_max) {
            return 50.0; // 予算未設定の場合は中間値
        }

        $price = $property->price;
        
        // 予算範囲内の場合は100%
        if (($this->budget_min === null || $price >= $this->budget_min) &&
            ($this->budget_max === null || $price <= $this->budget_max)) {
            return 100.0;
        }

        // 予算を上回る場合の計算
        if ($this->budget_max && $price > $this->budget_max) {
            $overRate = ($price - $this->budget_max) / $this->budget_max;
            return max(0, 100 - ($overRate * 100));
        }

        // 予算を下回る場合の計算
        if ($this->budget_min && $price < $this->budget_min) {
            $underRate = ($this->budget_min - $price) / $this->budget_min;
            return max(0, 100 - ($underRate * 50)); // 下回る場合のペナルティは軽め
        }

        return 0.0;
    }
} 