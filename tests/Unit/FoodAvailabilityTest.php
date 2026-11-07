<?php

namespace Tests\Unit;

use App\Models\Product;
use App\Models\ProductStock;
use App\Support\FoodAvailability;
use Carbon\Carbon;
use PHPUnit\Framework\TestCase;

class FoodAvailabilityTest extends TestCase
{
    private function food(array $meta, int $qty = 5, bool $published = true): Product
    {
        $product = (new Product)->forceFill(['listing_type' => 'food', 'published' => $published, 'food_meta' => $meta]);
        $product->setRelation('stocks', collect([new ProductStock(['qty' => $qty])]));
        return $product;
    }

    public function test_stock_manual_overrides_and_publication_are_respected(): void
    {
        $service = new FoodAvailability;
        $at = Carbon::parse('2026-11-09 12:00', 'Africa/Lagos');
        $meta = ['stock_mode' => 'limited', 'auto_stock_based' => true];
        $this->assertSame('sold_out', $service->forProduct($this->food($meta, 0), $at)['status']);
        $this->assertTrue($service->forProduct($this->food($meta + ['manual_override' => 'available'], 0), $at)['is_available']);
        $this->assertSame('unpublished', $service->forProduct($this->food(['manual_override' => 'available'], 5, false), $at)['status']);
        $this->assertSame('sold_out', $service->forProduct($this->food(['manual_override' => 'sold_out']), $at)['status']);
        $this->assertTrue($service->forProduct($this->food(['stock_mode' => 'unlimited', 'auto_stock_based' => true], 0), $at)['is_available']);
    }

    public function test_overnight_schedule_uses_the_previous_days_window_and_excludes_the_end(): void
    {
        $service = new FoodAvailability;
        $food = $this->food(['auto_time_based' => true, 'availability_mode' => 'scheduled', 'available_days' => ['monday'], 'time_slots' => [['start' => '22:00', 'end' => '02:00']]]);
        $this->assertTrue($service->forProduct($food, Carbon::parse('2026-11-09 22:00', 'Africa/Lagos'))['is_available']);
        $this->assertTrue($service->forProduct($food, Carbon::parse('2026-11-10 01:59', 'Africa/Lagos'))['is_available']);
        $this->assertSame('outside_schedule', $service->forProduct($food, Carbon::parse('2026-11-10 02:00', 'Africa/Lagos'))['status']);
        $this->assertFalse($service->forProduct($food, Carbon::parse('2026-11-10 23:00', 'Africa/Lagos'))['is_available']);
    }
}
