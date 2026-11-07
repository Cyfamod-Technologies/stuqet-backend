<?php

namespace Tests\Feature;

use App\Http\Controllers\Api\V2\Seller\FoodProductController;
use App\Models\Product;
use App\Models\User;
use App\Models\Shop;
use App\Http\Requests\ApiSellerProductRequest;
use App\Support\MobileFoodDocumentation;
use App\Services\ProductService;
use App\Services\ProductStockService;
use App\Support\FoodAvailability;
use App\Support\FoodListingRules;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Tests\TestCase;

class SellerFoodApiTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        config(['database.default' => 'food_test', 'database.connections.food_test' => ['driver' => 'sqlite', 'database' => ':memory:', 'prefix' => '', 'foreign_key_constraints' => true]]);
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('user_id');
            $table->string('listing_type');
            $table->text('food_meta');
            $table->boolean('published')->default(true);
            $table->boolean('variant_product')->default(false);
            $table->integer('current_stock')->default(0);
            $table->decimal('unit_price')->default(2500);
            $table->timestamps();
        });
        Schema::create('product_stocks', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('product_id');
            $table->string('variant')->default('');
            $table->decimal('price');
            $table->integer('qty');
            $table->string('sku')->nullable();
            $table->timestamps();
        });
        Schema::create('categories', function (Blueprint $table) {
            $table->id();
        });
        DB::table('categories')->insert(['id' => 1]);
    }

    private function meta(): array
    {
        return ['short_description' => 'Lunch bowl', 'ingredients' => 'Rice', 'availability_mode' => 'always', 'stock_mode' => 'unlimited', 'delivery_options' => ['pickup'], 'prep_time_value' => 20, 'prep_time_unit' => 'minutes', 'manual_override' => 'auto', 'sizes' => [['name' => 'Large', 'price' => 3500]]];
    }

    private function product(): Product
    {
        $id = DB::table('products')->insertGetId(['user_id' => 5, 'listing_type' => 'food', 'food_meta' => json_encode($this->meta()), 'published' => 1]);
        return Product::without(['product_translations', 'taxes', 'thumbnail', 'school'])->findOrFail($id);
    }

    private function update(int $id, array $data, int $seller = 5)
    {
        $user = (new User)->forceFill(['user_type' => 'seller']);
        $user->id = $seller;
        $request = Request::create('/', 'POST', $data);
        $request->setUserResolver(fn () => $user);
        return app(FoodProductController::class)->updateAvailability($request, $id, app(ProductService::class), app(FoodAvailability::class));
    }

    public function test_unlimited_food_stock_is_created_with_a_purchasable_quantity(): void
    {
        $product = $this->product();
        app(ProductStockService::class)->store(['product_id' => $product->id, 'unit_price' => 2500, 'food_meta' => ['stock_mode' => 'unlimited']], $product);
        $this->assertSame(999999, (int) $product->stocks()->first()->qty);
    }

    public function test_quick_edit_preserves_food_options_and_updates_limited_stock_atomically(): void
    {
        $product = $this->product();
        $response = $this->update($product->id, ['current_stock' => 8, 'food_meta' => ['stock_mode' => 'limited', 'auto_stock_based' => true]]);
        $data = $response->getData(true)['data'];
        $this->assertSame(8, $data['current_stock']);
        $this->assertSame($this->meta()['sizes'], $data['food_meta']['sizes']);
        $this->assertSame('Rice', $data['food_meta']['ingredients']);
        $this->assertTrue($data['food_availability']['is_available']);
        $response = $this->update($product->id, ['food_meta' => ['manual_override' => 'sold_out']]);
        $this->assertSame('sold_out', $response->getData(true)['data']['food_availability']['status']);
        $this->assertSame(8, (int) DB::table('product_stocks')->value('qty'));
    }

    public function test_another_seller_cannot_change_food(): void
    {
        $product = $this->product();
        $this->expectException(ModelNotFoundException::class);
        $this->update($product->id, ['published' => false], 99);
    }

    public function test_switching_to_limited_stock_requires_quantity_without_mutating_the_product(): void
    {
        $product = $this->product();
        try {
            $this->update($product->id, ['published' => false, 'food_meta' => ['stock_mode' => 'limited']]);
            $this->fail('Expected validation failure.');
        } catch (ValidationException $exception) {
            $this->assertArrayHasKey('current_stock', $exception->errors());
            $this->assertSame(1, (int) DB::table('products')->value('published'));
            $this->assertSame('unlimited', json_decode(DB::table('products')->value('food_meta'), true)['stock_mode']);
        }
    }

    public function test_scheduled_food_requires_days_and_time_slots(): void
    {
        $meta = array_replace($this->meta(), ['availability_mode' => 'scheduled']);
        $validator = Validator::make(['food_meta' => $meta], FoodListingRules::rules());
        $this->assertTrue($validator->fails());
        $this->assertArrayHasKey('food_meta.available_days', $validator->errors()->toArray());
        $this->assertArrayHasKey('food_meta.time_slots', $validator->errors()->toArray());
    }

    public function test_documented_food_payload_passes_validation_and_mobile_defaults_are_applied(): void
    {
        $operation = ['requestBody' => ['content' => ['application/json' => ['schema' => []]]], 'description' => ''];
        MobileFoodDocumentation::apply('/api/v2/seller/products/add', $operation);
        $payload = $operation['requestBody']['content']['application/json']['example'];
        $user = (new User)->forceFill(['id' => 5, 'user_type' => 'seller']);
        $user->setRelation('shop', (new Shop)->forceFill(['business_category' => 'food']));
        $this->actingAs($user);
        $request = ApiSellerProductRequest::create('/', 'POST', $payload);
        $request->setUserResolver(fn () => $user);
        $prepare = new \ReflectionMethod($request, 'prepareForValidation');
        $prepare->setAccessible(true);
        $prepare->invoke($request);
        $this->assertTrue($request->authorize());
        $this->assertFalse(Validator::make($request->all(), $request->rules())->fails());
        $this->assertSame('portion', $request->input('unit'));
        $this->assertSame(1, $request->input('min_qty'));
        $this->assertSame([['value' => 'spicy']], json_decode($request->input('tags')[0], true));
        $normalized = app(ProductService::class)->normalizeFoodMeta($request->input('food_meta'), 'food');
        $this->assertSame(3500.0, $normalized['sizes'][0]['price']);
        $this->assertSame(['delivery', 'pickup'], $normalized['delivery_options']);
        $this->assertSame('Rice, tomato, peppers', $normalized['ingredients']);
    }
}
