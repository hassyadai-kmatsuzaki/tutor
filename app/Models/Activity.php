<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Activity extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'user_id',
        'activity_type',
        'subject_type',
        'subject_id',
        'title',
        'description',
        'activity_date',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'activity_date' => 'datetime',
    ];

    /**
     * 活動種別の選択肢
     */
    public const ACTIVITY_TYPES = [
        'property_created' => '物件登録',
        'property_updated' => '物件更新',
        'customer_created' => '顧客登録',
        'customer_updated' => '顧客更新',
        'match_created' => 'マッチング作成',
        'match_status_updated' => 'マッチングステータス更新',
        'match_note_added' => 'マッチングメモ追加',
        'presentation' => '提案実施',
        'contact' => '顧客接触',
        'meeting' => '商談・面談',
        'contract' => '契約締結'
    ];

    /**
     * 対象種別の選択肢
     */
    public const SUBJECT_TYPES = [
        'property' => '物件',
        'customer' => '顧客',
        'match' => 'マッチング'
    ];

    /**
     * 実行者
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * 対象オブジェクトを取得（ポリモーフィック）
     */
    public function subject()
    {
        return match($this->subject_type) {
            'property' => $this->belongsTo(Property::class, 'subject_id'),
            'customer' => $this->belongsTo(Customer::class, 'subject_id'),
            'match' => $this->belongsTo(PropertyMatch::class, 'subject_id'),
            default => null
        };
    }

    /**
     * 指定期間の活動スコープ
     */
    public function scopeInPeriod($query, $startDate, $endDate)
    {
        return $query->whereBetween('activity_date', [$startDate, $endDate]);
    }

    /**
     * 今日の活動スコープ
     */
    public function scopeToday($query)
    {
        return $query->whereDate('activity_date', today());
    }

    /**
     * 今週の活動スコープ
     */
    public function scopeThisWeek($query)
    {
        return $query->whereBetween('activity_date', [
            now()->startOfWeek(),
            now()->endOfWeek()
        ]);
    }

    /**
     * 今月の活動スコープ
     */
    public function scopeThisMonth($query)
    {
        return $query->whereBetween('activity_date', [
            now()->startOfMonth(),
            now()->endOfMonth()
        ]);
    }

    /**
     * 指定ユーザーの活動スコープ
     */
    public function scopeByUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * 指定活動種別のスコープ
     */
    public function scopeByType($query, $type)
    {
        return $query->where('activity_type', $type);
    }

    /**
     * 物件関連の活動スコープ
     */
    public function scopePropertyRelated($query)
    {
        return $query->where('subject_type', 'property');
    }

    /**
     * 顧客関連の活動スコープ
     */
    public function scopeCustomerRelated($query)
    {
        return $query->where('subject_type', 'customer');
    }

    /**
     * マッチング関連の活動スコープ
     */
    public function scopeMatchRelated($query)
    {
        return $query->where('subject_type', 'match');
    }

    /**
     * 活動種別の表示名を取得
     */
    public function getActivityTypeLabelAttribute(): string
    {
        return self::ACTIVITY_TYPES[$this->activity_type] ?? $this->activity_type;
    }

    /**
     * 対象種別の表示名を取得
     */
    public function getSubjectTypeLabelAttribute(): string
    {
        return self::SUBJECT_TYPES[$this->subject_type] ?? $this->subject_type;
    }

    /**
     * 活動の重要度を取得
     */
    public function getImportanceAttribute(): string
    {
        return match($this->activity_type) {
            'contract' => 'high',
            'presentation', 'meeting' => 'medium',
            'property_created', 'customer_created', 'match_created' => 'medium',
            default => 'low'
        };
    }

    /**
     * 活動を記録する静的メソッド
     */
    public static function log(
        int $userId,
        string $activityType,
        string $subjectType,
        int $subjectId,
        string $title,
        string $description = null,
        \DateTime $activityDate = null
    ): self {
        return self::create([
            'user_id' => $userId,
            'activity_type' => $activityType,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'title' => $title,
            'description' => $description,
            'activity_date' => $activityDate ?? now(),
        ]);
    }

    /**
     * 物件作成の活動を記録
     */
    public static function logPropertyCreated(int $userId, Property $property): self
    {
        return self::log(
            $userId,
            'property_created',
            'property',
            $property->id,
            "物件「{$property->property_name}」を登録しました",
            "種別: {$property->property_type}、価格: {$property->price}万円"
        );
    }

    /**
     * 顧客作成の活動を記録
     */
    public static function logCustomerCreated(int $userId, Customer $customer): self
    {
        return self::log(
            $userId,
            'customer_created',
            'customer',
            $customer->id,
            "顧客「{$customer->customer_name}」を登録しました",
            "顧客属性: {$customer->customer_type}、予算: {$customer->budget_range}"
        );
    }

    /**
     * マッチング作成の活動を記録
     */
    public static function logMatchCreated(int $userId, PropertyMatch $match): self
    {
        return self::log(
            $userId,
            'match_created',
            'match',
            $match->id,
            "マッチングを作成しました",
            "物件: {$match->property->property_name}、顧客: {$match->customer->customer_name}、スコア: {$match->match_score}点"
        );
    }

    /**
     * 提案実施の活動を記録
     */
    public static function logPresentation(int $userId, PropertyMatch $match, string $comment = null): self
    {
        return self::log(
            $userId,
            'presentation',
            'match',
            $match->id,
            "物件提案を実施しました",
            "物件: {$match->property->property_name}、顧客: {$match->customer->customer_name}" . ($comment ? "、備考: {$comment}" : '')
        );
    }

    /**
     * 顧客接触の活動を記録
     */
    public static function logCustomerContact(int $userId, Customer $customer, string $method, string $content): self
    {
        return self::log(
            $userId,
            'contact',
            'customer',
            $customer->id,
            "顧客接触（{$method}）",
            $content
        );
    }

    /**
     * 契約締結の活動を記録
     */
    public static function logContract(int $userId, PropertyMatch $match): self
    {
        return self::log(
            $userId,
            'contract',
            'match',
            $match->id,
            "契約締結",
            "物件: {$match->property->property_name}、顧客: {$match->customer->customer_name}、成約価格: {$match->property->price}万円"
        );
    }
} 