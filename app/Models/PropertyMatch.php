<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyMatch extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'property_id',
        'customer_id',
        'match_score',
        'match_reason',
        'status',
        'presented_at',
        'response_at',
        'response_comment',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'match_score' => 'decimal:2',
        'presented_at' => 'datetime',
        'response_at' => 'datetime',
    ];

    /**
     * ステータスの選択肢
     */
    public const STATUSES = [
        'matched' => 'マッチング済み',
        'presented' => '提案済み',
        'interested' => '興味あり',
        'rejected' => '見送り',
        'contracted' => '成約'
    ];

    /**
     * 物件
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * 顧客
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * 作成者
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * 高スコアのマッチングスコープ
     */
    public function scopeHighScore($query, $threshold = 70)
    {
        return $query->where('match_score', '>=', $threshold);
    }

    /**
     * 提案済みのマッチングスコープ
     */
    public function scopePresented($query)
    {
        return $query->whereNotNull('presented_at');
    }

    /**
     * 未提案のマッチングスコープ
     */
    public function scopeNotPresented($query)
    {
        return $query->whereNull('presented_at');
    }

    /**
     * 指定ステータスのマッチングスコープ
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * 成約済みのマッチングスコープ
     */
    public function scopeContracted($query)
    {
        return $query->where('status', 'contracted');
    }

    /**
     * ステータス表示名を取得
     */
    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? $this->status;
    }

    /**
     * マッチングスコアのレベルを取得
     */
    public function getScoreLevelAttribute(): string
    {
        if ($this->match_score >= 90) {
            return 'excellent';
        } elseif ($this->match_score >= 80) {
            return 'very_good';
        } elseif ($this->match_score >= 70) {
            return 'good';
        } elseif ($this->match_score >= 60) {
            return 'fair';
        } else {
            return 'poor';
        }
    }

    /**
     * マッチングスコアのレベル表示名を取得
     */
    public function getScoreLevelLabelAttribute(): string
    {
        return match($this->score_level) {
            'excellent' => '優秀',
            'very_good' => '非常に良い',
            'good' => '良い',
            'fair' => '普通',
            'poor' => '低い',
            default => '不明'
        };
    }

    /**
     * 提案からの経過日数を取得
     */
    public function getDaysSincePresentedAttribute(): ?int
    {
        if (!$this->presented_at) {
            return null;
        }
        return $this->presented_at->diffInDays(now());
    }

    /**
     * 回答までの日数を取得
     */
    public function getDaysToResponseAttribute(): ?int
    {
        if (!$this->presented_at || !$this->response_at) {
            return null;
        }
        return $this->presented_at->diffInDays($this->response_at);
    }

    /**
     * 提案を記録
     */
    public function markAsPresented(string $comment = null): void
    {
        $this->update([
            'status' => 'presented',
            'presented_at' => now(),
            'response_comment' => $comment
        ]);
    }

    /**
     * 顧客の回答を記録
     */
    public function recordResponse(string $status, string $comment = null): void
    {
        $this->update([
            'status' => $status,
            'response_at' => now(),
            'response_comment' => $comment
        ]);
    }

    /**
     * 成約を記録
     */
    public function markAsContracted(string $comment = null): void
    {
        $this->recordResponse('contracted', $comment);
        
        // 物件のステータスも更新
        $this->property->update(['status' => 'sold']);
        
        // 顧客のステータスも更新
        $this->customer->update(['status' => 'closed']);
    }

    /**
     * マッチング理由を生成
     */
    public static function generateMatchReason(Property $property, Customer $customer, float $score): string
    {
        $reasons = [];

        // 価格マッチ
        $priceMatch = $customer->calculatePriceMatch($property);
        if ($priceMatch >= 80) {
            $reasons[] = "予算条件に適合";
        }

        // 種別マッチ
        $customerTypes = $customer->property_type_preference_array;
        if (in_array($property->property_type, $customerTypes)) {
            $reasons[] = "希望物件種別に該当";
        }

        // エリアマッチ
        if ($customer->area_preference && 
            (str_contains($property->full_address, $customer->area_preference) ||
             str_contains($customer->area_preference, $property->prefecture) ||
             str_contains($customer->area_preference, $property->city))) {
            $reasons[] = "希望エリアに該当";
        }

        // 利回りマッチ
        if ($customer->yield_requirement && 
            $property->current_profit && 
            $property->current_profit >= $customer->yield_requirement) {
            $reasons[] = "利回り条件を満たす";
        }

        if (empty($reasons)) {
            $reasons[] = "総合的な条件でマッチング";
        }

        return implode('、', $reasons) . "（スコア: {$score}点）";
    }
} 