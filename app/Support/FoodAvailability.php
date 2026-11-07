<?php

namespace App\Support;

use App\Models\Product;
use Carbon\CarbonInterface;

class FoodAvailability
{
    public function forProduct(Product $product, ?CarbonInterface $at = null): ?array
    {
        if ($product->listing_type !== 'food') {
            return null;
        }
        $now = $at ?: now(config('app.timezone'));
        $meta = $product->food_meta ?? [];
        $state = 'available';
        if (!$product->published) {
            $state = 'unpublished';
        } elseif (($meta['manual_override'] ?? 'auto') === 'sold_out') {
            $state = 'sold_out';
        } elseif (($meta['manual_override'] ?? 'auto') !== 'available') {
            if (!empty($meta['auto_stock_based']) && ($meta['stock_mode'] ?? 'limited') === 'limited' && $product->stocks->sum('qty') <= 0) {
                $state = 'sold_out';
            } elseif (!empty($meta['auto_time_based']) && ($meta['availability_mode'] ?? 'always') === 'scheduled' && !$this->inSchedule($meta, $now)) {
                $state = 'outside_schedule';
            }
        }
        return ['is_available' => $state === 'available', 'status' => $state, 'timezone' => $now->getTimezone()->getName()];
    }

    private function inSchedule(array $meta, CarbonInterface $now): bool
    {
        $days = $meta['available_days'] ?? [];
        $time = $now->format('H:i');
        foreach ($meta['time_slots'] ?? [] as $slot) {
            $start = $slot['start'] ?? '';
            $end = $slot['end'] ?? '';
            if (!preg_match('/^\d{2}:\d{2}$/', $start) || !preg_match('/^\d{2}:\d{2}$/', $end) || $start === $end) {
                continue;
            }
            $today = in_array(strtolower($now->format('l')), $days, true);
            if ($start < $end && $today && $time >= $start && $time < $end) {
                return true;
            }
            if ($start > $end && (($today && $time >= $start) || (in_array(strtolower($now->copy()->subDay()->format('l')), $days, true) && $time < $end))) {
                return true;
            }
        }
        return false;
    }
}
