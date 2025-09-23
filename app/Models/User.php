<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable, HasApiTokens;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
        'department',
        'phone',
        'is_active',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var list<string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    /**
     * 管理者かどうかを判定
     */
    public function isAdmin(): bool
    {
        return $this->role === 'admin';
    }

    /**
     * 営業担当者かどうかを判定
     */
    public function isSales(): bool
    {
        return $this->role === 'sales';
    }

    /**
     * アクティブなユーザーかどうかを判定
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }

    /**
     * 担当している物件
     */
    public function properties()
    {
        return $this->hasMany(Property::class, 'created_by');
    }

    /**
     * 担当している顧客
     */
    public function customers()
    {
        return $this->hasMany(Customer::class, 'assigned_to');
    }

    /**
     * 作成したマッチング
     */
    public function matches()
    {
        return $this->hasMany(PropertyMatch::class, 'created_by');
    }

    /**
     * 活動履歴
     */
    public function activities()
    {
        return $this->hasMany(Activity::class);
    }
}
