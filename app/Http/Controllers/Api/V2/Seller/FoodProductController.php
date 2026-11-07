<?php

namespace App\Http\Controllers\Api\V2\Seller;

use App\Models\Product;
use App\Services\ProductService;
use App\Support\FoodAvailability;
use App\Support\FoodListingRules;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class FoodProductController extends Controller
{
    public function updateAvailability(Request $request, $id, ProductService $service, FoodAvailability $availability)
    {
        abort_unless($request->user()->user_type === 'seller', 403);

        return DB::transaction(function () use ($request, $id, $service, $availability) {
            $product = Product::without(['product_translations', 'taxes', 'thumbnail', 'school'])
                ->where('user_id', $request->user()->id)->where('listing_type', 'food')
                ->lockForUpdate()->findOrFail($id);

            $input = $request->validate([
                'published' => 'sometimes|boolean',
                'current_stock' => 'sometimes|integer|min:0',
                'food_meta' => 'sometimes|array',
            ]);
            $oldMeta = $product->food_meta ?? [];
            $meta = array_replace($oldMeta, $input['food_meta'] ?? []);
            $rules = FoodListingRules::rules();
            $rules['food_meta.time_slots.*.start'] = 'required|date_format:H:i';
            $rules['food_meta.time_slots.*.end'] = 'required|date_format:H:i';
            Validator::make(['food_meta' => $meta], $rules)->validate();

            $stockModeChanged = ($meta['stock_mode'] ?? 'limited') !== ($oldMeta['stock_mode'] ?? 'limited');
            if ($product->variant_product && ($stockModeChanged || array_key_exists('current_stock', $input))) {
                throw ValidationException::withMessages(['current_stock' => 'Update variant stock through the full product editor.']);
            }
            if ($stockModeChanged && $meta['stock_mode'] === 'limited' && !array_key_exists('current_stock', $input)) {
                throw ValidationException::withMessages(['current_stock' => 'Specify quantity when changing to limited stock.']);
            }

            $product->food_meta = $service->normalizeFoodMeta($meta, 'food');
            if (array_key_exists('published', $input)) {
                $product->published = $input['published'];
            }
            if (array_key_exists('current_stock', $input) || $stockModeChanged) {
                $qty = $meta['stock_mode'] === 'unlimited' ? 999999 : $input['current_stock'];
                $product->current_stock = $qty;
                $product->stocks()->updateOrCreate(['variant' => ''], ['price' => $product->unit_price, 'qty' => $qty]);
            }
            $product->save();
            $product->unsetRelation('stocks');

            return response()->json(['result' => true, 'data' => [
                'id' => $product->id,
                'published' => (bool) $product->published,
                'current_stock' => (int) $product->stocks->sum('qty'),
                'food_meta' => $product->food_meta,
                'food_availability' => $availability->forProduct($product),
            ]]);
        });
    }
}
