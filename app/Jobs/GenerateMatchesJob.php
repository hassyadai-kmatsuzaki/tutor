<?php

namespace App\Jobs;

use App\Models\Property;
use App\Models\Customer;
use App\Models\PropertyMatch;
use App\Models\Activity;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;

class GenerateMatchesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public ?int $propertyId;
    public ?int $customerId;
    public int $minScore;
    public ?int $userId;

    public function __construct(?int $propertyId, ?int $customerId, int $minScore = 60, ?int $userId = null)
    {
        $this->propertyId = $propertyId;
        $this->customerId = $customerId;
        $this->minScore = $minScore;
        $this->userId = $userId;
    }

    public function handle(): void
    {
        // 物件をチャンクで処理
        $propertyQuery = Property::available();
        if ($this->propertyId) {
            $propertyQuery->where('id', $this->propertyId);
        }

        $propertyQuery->orderBy('id')->chunkById(100, function ($properties) {
            foreach ($properties as $property) {
                // 物件に基づく候補顧客を絞り込み
                $customersQuery = Customer::active();
                if ($this->customerId) {
                    $customersQuery->where('id', $this->customerId);
                }

                // 価格で一次絞り込み（予算上限・下限のどちらかにヒットする顧客）
                if ($property->price) {
                    $price = (int)$property->price;
                    $customersQuery->where(function ($q) use ($price) {
                        $q->whereNull('budget_min')->orWhere('budget_min', '<=', $price);
                    })->where(function ($q) use ($price) {
                        $q->whereNull('budget_max')->orWhere('budget_max', '>=', $price);
                    });
                }

                // 種別で一次絞り込み
                if ($property->property_type) {
                    $customersQuery->where(function ($q) use ($property) {
                        $q->whereNull('property_type_preference')
                          ->orWhere('property_type_preference', 'like', '%' . $property->property_type . '%');
                    });
                }

                // エリアで一次絞り込み
                if ($property->address) {
                    $addr = $property->address;
                    $customersQuery->where(function ($q) use ($addr) {
                        $q->whereNull('area_preference')
                          ->orWhere('area_preference', 'like', '%' . $addr . '%');
                    });
                }

                $customersQuery->orderBy('id')->chunkById(200, function ($customers) use ($property) {
                    DB::transaction(function () use ($customers, $property) {
                        foreach ($customers as $customer) {
                            // 既存チェック
                            $exists = PropertyMatch::where('property_id', $property->id)
                                ->where('customer_id', $customer->id)
                                ->exists();
                            if ($exists) {
                                continue;
                            }

                            // スコア計算（Controllerのメソッドに依存しない簡易評価）
                            $score = $this->calculateScore($property, $customer);
                            if ($score < $this->minScore) {
                                continue;
                            }

                            $match = PropertyMatch::create([
                                'property_id' => $property->id,
                                'customer_id' => $customer->id,
                                'match_score' => $score,
                                'match_reason' => $this->generateReason($property, $customer, $score),
                                'created_by' => $this->userId,
                            ]);

                            Activity::logMatchCreated($this->userId, $match);
                        }
                    });
                });
            }
        });
    }

    private function calculateScore(Property $property, Customer $customer): float
    {
        $score = 0; $total = 0;

        // 種目 30
        $w = 30; $total += $w;
        $typeOk = 50.0;
        $prefs = $customer->property_type_preference ? explode(',', $customer->property_type_preference) : [];
        if (!empty($prefs)) {
            $typeOk = in_array($property->property_type, $prefs, true) ? 100.0 : 0.0;
        }
        $score += $typeOk * $w / 100.0;

        // エリア 25
        $w = 25; $total += $w;
        $areaOk = 50.0;
        if ($customer->area_preference) {
            $areaOk = (str_contains($property->address ?? '', $customer->area_preference)) ? 100.0 : 0.0;
        }
        $score += $areaOk * $w / 100.0;

        // 面積 25（情報なければ中間）
        $w = 25; $total += $w;
        $landOk = 50.0;
        if ($property->land_area) {
            $landOk = 60.0; // 簡易評価（詳細はControllerロジックで閲覧時算出）
        }
        $score += $landOk * $w / 100.0;

        // 価格 20
        $w = 20; $total += $w;
        $priceOk = 50.0;
        if ($property->price) {
            $p = (int)$property->price;
            $min = $customer->budget_min; $max = $customer->budget_max;
            if (($min === null || $p >= $min) && ($max === null || $p <= $max)) {
                $priceOk = 100.0;
            } elseif ($max !== null && $p > $max) {
                $over = ($p - $max) / max($max, 1);
                $priceOk = max(0, 100 - $over * 100);
            } elseif ($min !== null && $p < $min) {
                $under = ($min - $p) / max($min, 1);
                $priceOk = max(0, 100 - $under * 50);
            }
        }
        $score += $priceOk * $w / 100.0;

        return round($score, 2);
    }

    private function generateReason(Property $property, Customer $customer, float $score): string
    {
        $reasons = [];
        if ($customer->budget_max && $property->price && $property->price <= $customer->budget_max) {
            $reasons[] = '予算条件に適合';
        }
        if ($customer->property_type_preference && $property->property_type && str_contains($customer->property_type_preference, $property->property_type)) {
            $reasons[] = '希望種別に適合';
        }
        if ($customer->area_preference && $property->address && str_contains($property->address, $customer->area_preference)) {
            $reasons[] = '希望エリアに適合';
        }
        if (empty($reasons)) {
            $reasons[] = '総合的に適合';
        }
        return implode('、', $reasons) . "（スコア: {$score}点）";
    }
}


