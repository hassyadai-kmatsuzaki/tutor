<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PropertyImage extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<string>
     */
    protected $fillable = [
        'property_id',
        'image_path',
        'image_type',
        'caption',
        'sort_order',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * 画像種別の選択肢
     */
    public const IMAGE_TYPES = [
        'exterior' => '外観',
        'interior' => '内観',
        'layout' => '間取り',
        'other' => 'その他'
    ];

    /**
     * 物件
     */
    public function property(): BelongsTo
    {
        return $this->belongsTo(Property::class);
    }

    /**
     * 指定種別の画像スコープ
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('image_type', $type);
    }

    /**
     * 表示順でソートするスコープ
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    /**
     * 画像種別の表示名を取得
     */
    public function getImageTypeLabelAttribute(): string
    {
        return self::IMAGE_TYPES[$this->image_type] ?? $this->image_type;
    }

    /**
     * 完全な画像URLを取得
     */
    public function getImageUrlAttribute(): string
    {
        return asset('storage/' . $this->image_path);
    }

    /**
     * サムネイル画像URLを取得
     */
    public function getThumbnailUrlAttribute(): string
    {
        // サムネイル用のパスを生成（実際の実装では画像リサイズライブラリを使用）
        $pathInfo = pathinfo($this->image_path);
        $thumbnailPath = $pathInfo['dirname'] . '/thumbnails/' . $pathInfo['filename'] . '_thumb.' . $pathInfo['extension'];
        
        return asset('storage/' . $thumbnailPath);
    }

    /**
     * 画像ファイルが存在するかチェック
     */
    public function imageExists(): bool
    {
        return file_exists(storage_path('app/public/' . $this->image_path));
    }
} 