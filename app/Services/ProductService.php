<?php

namespace App\Services;

use AizPackages\CombinationGenerate\Services\CombinationService;
use App\Models\Cart;
use App\Models\Category;
use App\Models\Color;
use App\Models\Product;
use App\Models\SellerCategory;
use App\Models\Shop;
use App\Models\User;
use App\Models\Wishlist;
use App\Utility\ProductUtility;
use Combinations;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ProductService
{
    public function store(array $data)
    {
        $collection = collect($data);
        $sellerShop = auth()->check() && auth()->user()->user_type === 'seller' ? auth()->user()->shop : null;
        $collection['draft'] = ($collection['button'] ?? null) === 'draft' ? 1 : 0;
        $collection['discount']= $collection['discount'] ?? 0.00;
        $collection['weight']= $collection['weight'] ?? 0.00;
        $collection['listing_type'] = $collection['listing_type'] ?? ($sellerShop?->business_category ?: 'product');
        $collection['school_id'] = $sellerShop && $sellerShop->student_status === 'student' ? $sellerShop->school_id : ($collection['school_id'] ?? null);
        $collection['food_meta'] = $this->normalizeFoodMeta($collection['food_meta'] ?? null, $collection['listing_type']);
        $collection['service_meta'] = $this->normalizeServiceMeta($collection['service_meta'] ?? null, $collection['listing_type']);
        if ($collection['listing_type'] === 'food' && data_get($collection['food_meta'], 'stock_mode') === 'unlimited') {
            $collection['current_stock'] = $collection['current_stock'] ?? 999999;
            $collection['low_stock_quantity'] = 0;
        }
        if ($collection['listing_type'] === 'service' && data_get($collection['service_meta'], 'slot_mode') === 'unlimited') {
            $collection['current_stock'] = $collection['current_stock'] ?? 999999;
            $collection['low_stock_quantity'] = 0;
        }

        $collection['show_estimated_shipping_time']= $collection['show_estimated_shipping_time'] ?? 0;
        $collection['show_shipping_note']= $collection['show_shipping_note'] ?? 0;
        $collection['show_warranty_note']= $collection['show_warranty_note'] ?? 0;
        $collection['show_delivery_notes']= $collection['show_delivery_notes'] ?? 0;

        if(addon_is_activated('refund_request')){
            $collection['show_refund_notes'] = $collection['show_refund_notes'] ?? 0;
        }

        if(!isset($collection['gst_rate']) && addon_is_activated('gst_system')){
            $collection['tax'] = 0;
            $collection['tax_type'] = 'amount';
        }   

        $approved = 1;
        if (auth()->user()->user_type == 'seller') {
            $user_id = auth()->user()->id;
            if (get_setting('product_approve_by_admin') == 1) {
                $approved = 0;
            }
        } else {
            $user_id = User::where('user_type', 'admin')->first()->id;
        }
        $tags = array();
        if ($collection['tags'][0] != null) {
            foreach (json_decode($collection['tags'][0]) as $key => $tag) {
                array_push($tags, $tag->value);
            }
        }
        $collection['tags'] = implode(',', $tags);
        $discount_start_date = null;
        $discount_end_date   = null;
        if (!empty($collection['date_range'])) {
            $date_var               = explode(" to ", $collection['date_range']);
            $discount_start_date = strtotime($date_var[0]);
            $discount_end_date   = strtotime($date_var[1]);
        }
        unset($collection['date_range']);
        
        if (empty($collection['meta_title'])) {
            $collection['meta_title'] = $collection['name'] ?? '';
        }
        if (empty($collection['meta_description'])) {
            $collection['meta_description'] = strip_tags($collection['description'] ?? '');
        }

        if (empty($collection['meta_img'])) {
            $collection['meta_img'] = $collection['thumbnail_img'] ?? null;
        }
        $meta_keywords = $collection['meta_keywords'] ?? null;
        if (is_array($meta_keywords) && !empty($meta_keywords[0])) {
            $keywords = json_decode($meta_keywords[0]);
            $meta_keywords = [];
            if (is_array($keywords)) {
                foreach ($keywords as $keyword) {
                    $meta_keywords[] = $keyword->value ?? null;
                }
            }
            $collection['meta_keywords'] = json_encode($meta_keywords);
        }


        $shipping_cost = 0;
        if (isset($collection['shipping_type'])) {
            if ($collection['shipping_type'] == 'free') {
                $shipping_cost = 0;
            } elseif ($collection['shipping_type'] == 'flat_rate') {
                $shipping_cost = $collection['flat_shipping_cost'];
            }
        }
        unset($collection['flat_shipping_cost']);

        $slug = Str::slug($collection['name']);
        $same_slug_count = Product::where('slug', 'LIKE', $slug . '%')->count();
        $slug_suffix = $same_slug_count ? '-' . $same_slug_count + 1 : '';
        $slug .= $slug_suffix;

        $colors = json_encode(array());
        if (
            isset($collection['colors_active']) &&
            $collection['colors_active'] &&
            $collection['colors'] &&
            count($collection['colors']) > 0
        ) {
            $colors = json_encode($collection['colors']);
        }

        $options = ProductUtility::get_attribute_options($collection);

        $combinations = (new CombinationService())->generate_combination($options);
        
        if (count($combinations) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = ProductUtility::get_combination_string($combination, $collection);

                unset($collection['price_' . str_replace('.', '_', $str)]);
                unset($collection['sku_' . str_replace('.', '_', $str)]);
                unset($collection['qty_' . str_replace('.', '_', $str)]);
                unset($collection['img_' . str_replace('.', '_', $str)]);
            }
        }

        unset($collection['colors_active']);

        $choice_options = array();
        if (isset($collection['choice_no']) && $collection['choice_no']) {
            $str = '';
            $item = array();
            foreach ($collection['choice_no'] as $key => $no) {
                $str = 'choice_options_' . $no;
                $item['attribute_id'] = $no;
                $attribute_data = array();
                // foreach (json_decode($request[$str][0]) as $key => $eachValue) {
                foreach ($collection[$str] as $key => $eachValue) {
                    // array_push($data, $eachValue->value);
                    array_push($attribute_data, $eachValue);
                }
                unset($collection[$str]);

                $item['values'] = $attribute_data;
                array_push($choice_options, $item);
            }
        }

        // Ensure proper JSON encoding
        $choice_options = json_encode($choice_options, JSON_UNESCAPED_UNICODE);
        
        if (isset($collection['choice_no']) && $collection['choice_no']) {
            $attributes = json_encode(array_values($collection['choice_no']));
            unset($collection['choice_no']);
        } else {
            $attributes = json_encode(array());
        }

        $published = 1;
        if ($collection['button'] == 'unpublish' || $collection['button'] == 'draft') {
            $published = 0;
        }
        unset($collection['button']);

        $collection['has_warranty'] = isset($collection['has_warranty']) ? 1 : 0;

        $data = $collection->merge(compact(
            'user_id',
            'approved',
            'discount_start_date',
            'discount_end_date',
            'shipping_cost',
            'slug',
            'colors',
            'choice_options',
            'attributes',
            'published',
        ))->toArray();

        // Debug: Check which fields might be arrays
        foreach ($data as $key => $value) {
            if (in_array($key, ['food_meta', 'service_meta'], true)) {
                continue;
            }
            if (is_array($value)) {
                \Log::warning("Field '{$key}' is still an array: ", $value);
                // Convert any remaining arrays to JSON
                $data[$key] = json_encode($value);
            }
        }

        return Product::create($data);
    }

    public function update(array $data, Product $product)
    {
        $collection = collect($data);
        $sellerShop = auth()->check() && auth()->user()->user_type === 'seller' ? auth()->user()->shop : null;
        //Log::info('Filter Products Request: ', $data);
        $slug = Str::slug($collection['name']);
        $slug = Str::slug($collection['slug'] ?? $collection['name']);
        $collection['discount']= $collection['discount'] ?? 0.00;
        $collection['weight']= $collection['weight'] ?? 0.00;
        $collection['listing_type'] = $collection['listing_type'] ?? ($sellerShop?->business_category ?: ($product->listing_type ?: 'product'));
        $collection['school_id'] = $sellerShop && $sellerShop->student_status === 'student'
            ? $sellerShop->school_id
            : ($collection['school_id'] ?? $product->school_id);
        $collection['food_meta'] = $this->normalizeFoodMeta($collection['food_meta'] ?? ($product->food_meta ?? null), $collection['listing_type']);
        $collection['service_meta'] = $this->normalizeServiceMeta($collection['service_meta'] ?? ($product->service_meta ?? null), $collection['listing_type']);
        if ($collection['listing_type'] === 'food' && data_get($collection['food_meta'], 'stock_mode') === 'unlimited') {
            $collection['current_stock'] = $collection['current_stock'] ?? 999999;
            $collection['low_stock_quantity'] = 0;
        }
        if ($collection['listing_type'] === 'service' && data_get($collection['service_meta'], 'slot_mode') === 'unlimited') {
            $collection['current_stock'] = $collection['current_stock'] ?? 999999;
            $collection['low_stock_quantity'] = 0;
        }
        $same_slug_count = Product::where('slug', 'LIKE', $slug . '%')->count();
        $slug_suffix = $same_slug_count > 1 ? '-' . $same_slug_count + 1 : '';
        $slug .= $slug_suffix;
        $collection['draft'] = ($collection['button'] ?? null) === 'draft' ? 1 : 0;
        $collection['show_estimated_shipping_time']= $collection['show_estimated_shipping_time'] ?? 0;
        $collection['show_shipping_note']= $collection['show_shipping_note'] ?? 0;
        $collection['show_warranty_note']= $collection['show_warranty_note'] ?? 0;
        $collection['show_delivery_notes']= $collection['show_delivery_notes'] ?? 0;

        if(addon_is_activated('refund_request')){
            $collection['refundable'] = $collection['refundable'] ?? 0;
            $collection['show_refund_notes'] = $collection['show_refund_notes'] ?? 0;
        }
        if(!isset($collection['is_quantity_multiplied'])){
            $collection['is_quantity_multiplied'] = 0;
        }

        if(!isset($collection['cash_on_delivery'])){
            $collection['cash_on_delivery'] = 0;
        }
        if(!isset($collection['featured'])){
            $collection['featured'] = 0;
        }
        if(!isset($collection['todays_deal'])){
            $collection['todays_deal'] = 0;
        }

        if(!isset($collection['gst_rate']) && addon_is_activated('gst_system')){
            $collection['tax'] = 0;
            $collection['tax_type'] = 'amount';
        }


        $tags = array();
        if ($collection['tags'][0] != null) {
            foreach (json_decode($collection['tags'][0]) as $key => $tag) {
                array_push($tags, $tag->value);
            }
        }
        $collection['tags'] = implode(',', $tags);
        $discount_start_date = null;
        $discount_end_date   = null;
        if (!empty($collection['date_range'])) {
            $date_var               = explode(" to ", $collection['date_range']);
            $discount_start_date = strtotime($date_var[0]);
            $discount_end_date   = strtotime($date_var[1]);
        }
        unset($collection['date_range']);
        
        if (empty($collection['meta_title'])) {
            $collection['meta_title'] = $collection['name'] ?? '';
        }
        if (empty($collection['meta_description'])) {
            $collection['meta_description'] = strip_tags($collection['description'] ?? '');
        }

        if (empty($collection['meta_img'])) {
            $collection['meta_img'] = $collection['thumbnail_img'] ?? null;
        }

        $meta_keywords = $collection['meta_keywords'] ?? null;
        if (is_array($meta_keywords) && !empty($meta_keywords[0])) {
            $keywords = json_decode($meta_keywords[0]);
            $meta_keywords = [];
            if (is_array($keywords)) {
                foreach ($keywords as $keyword) {
                    $meta_keywords[] = $keyword->value ?? null;
                }
            }
            $collection['meta_keywords'] = json_encode($meta_keywords);
        }



        if (isset($collection['lang']) && $collection['lang'] != env('DEFAULT_LANGUAGE')) {
            unset($collection['name']);
            unset($collection['unit']);
            unset($collection['description']);
        }
        unset($collection['lang']);

        
        $shipping_cost = 0;
        if (isset($collection['shipping_type'])) {
            if ($collection['shipping_type'] == 'free') {
                $shipping_cost = 0;
            } elseif ($collection['shipping_type'] == 'flat_rate') {
                $shipping_cost = $collection['flat_shipping_cost'];
            }
        }
        unset($collection['flat_shipping_cost']);

        $colors = json_encode(array());
        if (
            isset($collection['colors_active']) && 
            $collection['colors_active'] &&
            $collection['colors'] &&
            count($collection['colors']) > 0
        ) {
            $colors = json_encode($collection['colors']);
        }

        $options = ProductUtility::get_attribute_options($collection);

        $combinations = (new CombinationService())->generate_combination($options);
        if (count($combinations) > 0) {
            foreach ($combinations as $key => $combination) {
                $str = ProductUtility::get_combination_string($combination, $collection);

                unset($collection['price_' . str_replace('.', '_', $str)]);
                unset($collection['sku_' . str_replace('.', '_', $str)]);
                unset($collection['qty_' . str_replace('.', '_', $str)]);
                unset($collection['img_' . str_replace('.', '_', $str)]);
            }
        }

        unset($collection['colors_active']);

        $choice_options = array();
        if (isset($collection['choice_no']) && $collection['choice_no']) {
            $str = '';
            $item = array();
            foreach ($collection['choice_no'] as $key => $no) {
                $str = 'choice_options_' . $no;
                $item['attribute_id'] = $no;
                $attribute_data = array();
                // foreach (json_decode($request[$str][0]) as $key => $eachValue) {
                foreach ($collection[$str] as $key => $eachValue) {
                    // array_push($data, $eachValue->value);
                    array_push($attribute_data, $eachValue);
                }
                unset($collection[$str]);

                $item['values'] = $attribute_data;
                array_push($choice_options, $item);
            }
        }

        $choice_options = json_encode($choice_options, JSON_UNESCAPED_UNICODE);

        if (isset($collection['choice_no']) && $collection['choice_no']) {
            $attributes = json_encode($collection['choice_no']);
            unset($collection['choice_no']);
        } else {
            $attributes = json_encode(array());
        }

        $published = 1;
        if (isset($collection['button']) && ($collection['button'] == 'unpublish' || $collection['button'] == 'draft')) {
            $published = 0;
        }

        $collection['has_warranty'] = isset($collection['has_warranty']) ? 1 : 0;
        
        unset($collection['button']);
        
        $data = $collection->merge(compact(
            'discount_start_date',
            'discount_end_date',
            'shipping_cost',
            'slug',
            'colors',
            'choice_options',
            'attributes',
            'published',
        ))->toArray();
        $product->update($data);

        return $product;
    }

    protected function normalizeFoodMeta($foodMeta, $listingType)
    {
        if ($listingType !== 'food') {
            return null;
        }

        $foodMeta = is_array($foodMeta) ? $foodMeta : [];

        $timeSlots = collect($foodMeta['time_slots'] ?? [])
            ->map(function ($slot) {
                $start = trim((string) data_get($slot, 'start', ''));
                $end = trim((string) data_get($slot, 'end', ''));

                if ($start === '' && $end === '') {
                    return null;
                }

                return [
                    'start' => $start,
                    'end' => $end,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $sizes = $this->normalizePricedRows($foodMeta['sizes'] ?? [], 'name');
        $addons = $this->normalizePricedRows($foodMeta['addons'] ?? [], 'name');

        $comboDeals = collect($foodMeta['combo_deals'] ?? [])
            ->map(function ($combo) {
                $title = trim((string) data_get($combo, 'title', ''));
                $description = trim((string) data_get($combo, 'description', ''));
                $price = data_get($combo, 'price');

                if ($title === '' && $description === '' && ($price === null || $price === '')) {
                    return null;
                }

                return [
                    'title' => $title,
                    'description' => $description,
                    'price' => $price === null || $price === '' ? null : (float) $price,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $deliveryOptions = collect($foodMeta['delivery_options'] ?? [])
            ->filter(fn ($option) => in_array($option, ['delivery', 'pickup', 'dine_in'], true))
            ->values()
            ->all();

        $availableDays = collect($foodMeta['available_days'] ?? [])
            ->filter(fn ($day) => in_array($day, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'], true))
            ->values()
            ->all();

        return [
            'short_description' => trim((string) ($foodMeta['short_description'] ?? '')),
            'ingredients' => trim((string) ($foodMeta['ingredients'] ?? '')),
            'availability_mode' => in_array(($foodMeta['availability_mode'] ?? 'always'), ['always', 'scheduled'], true)
                ? $foodMeta['availability_mode']
                : 'always',
            'available_days' => $availableDays,
            'time_slots' => $timeSlots,
            'stock_mode' => in_array(($foodMeta['stock_mode'] ?? 'limited'), ['unlimited', 'limited'], true)
                ? $foodMeta['stock_mode']
                : 'limited',
            'sizes' => $sizes,
            'addons' => $addons,
            'combo_deals' => $comboDeals,
            'delivery_options' => $deliveryOptions,
            'prep_time_value' => isset($foodMeta['prep_time_value']) && $foodMeta['prep_time_value'] !== ''
                ? (int) $foodMeta['prep_time_value']
                : null,
            'prep_time_unit' => in_array(($foodMeta['prep_time_unit'] ?? 'minutes'), ['minutes', 'hours'], true)
                ? $foodMeta['prep_time_unit']
                : 'minutes',
            'special_instructions' => trim((string) ($foodMeta['special_instructions'] ?? '')),
            'auto_time_based' => !empty($foodMeta['auto_time_based']) ? 1 : 0,
            'auto_stock_based' => !empty($foodMeta['auto_stock_based']) ? 1 : 0,
            'manual_override' => in_array(($foodMeta['manual_override'] ?? 'auto'), ['auto', 'available', 'sold_out'], true)
                ? $foodMeta['manual_override']
                : 'auto',
        ];
    }

    protected function normalizeServiceMeta($serviceMeta, $listingType)
    {
        if ($listingType !== 'service') {
            return null;
        }

        $serviceMeta = is_array($serviceMeta) ? $serviceMeta : [];

        $packages = collect($serviceMeta['packages'] ?? [])
            ->map(function ($package) {
                $name = trim((string) data_get($package, 'name', ''));
                $duration = trim((string) data_get($package, 'duration', ''));
                $sessions = data_get($package, 'sessions');
                $revisions = data_get($package, 'revisions');
                $price = data_get($package, 'price');
                $description = trim((string) data_get($package, 'description', ''));

                if ($name === '' && $duration === '' && $description === '' && ($sessions === null || $sessions === '') && ($revisions === null || $revisions === '') && ($price === null || $price === '')) {
                    return null;
                }

                return [
                    'name' => $name,
                    'duration' => $duration,
                    'sessions' => $sessions === null || $sessions === '' ? null : (int) $sessions,
                    'revisions' => $revisions === null || $revisions === '' ? null : (int) $revisions,
                    'price' => $price === null || $price === '' ? null : (float) $price,
                    'description' => $description,
                ];
            })
            ->filter()
            ->values()
            ->all();

        $addons = $this->normalizePricedRows($serviceMeta['addons'] ?? [], 'name');

        $attributes = collect($serviceMeta['attributes'] ?? [])
            ->map(function ($attribute) {
                $name = trim((string) data_get($attribute, 'name', ''));
                $values = trim((string) data_get($attribute, 'values', ''));

                if ($name === '' && $values === '') {
                    return null;
                }

                return [
                    'name' => $name,
                    'values' => $this->splitCommaSeparatedValues($values),
                ];
            })
            ->filter()
            ->values()
            ->all();

        return [
            'service_type' => in_array(($serviceMeta['service_type'] ?? 'one_time'), ['consultation', 'one_time', 'recurring', 'installation', 'repair_maintenance', 'training_coaching', 'custom', 'on_site', 'remote_virtual', 'package', 'subscription', 'emergency'], true)
                ? $serviceMeta['service_type']
                : 'one_time',
            'pricing_type' => in_array(($serviceMeta['pricing_type'] ?? 'fixed_price'), ['fixed_price', 'hourly_rate', 'daily_rate', 'weekly_rate', 'monthly_subscription', 'custom_quote', 'free_consultation'], true)
                ? $serviceMeta['pricing_type']
                : 'fixed_price',
            'location' => trim((string) ($serviceMeta['location'] ?? '')),
            'service_area' => trim((string) ($serviceMeta['service_area'] ?? '')),
            'slot_mode' => in_array(($serviceMeta['slot_mode'] ?? 'limited'), ['unlimited', 'limited'], true)
                ? $serviceMeta['slot_mode']
                : 'limited',
            'packages' => $packages,
            'addons' => $addons,
            'attributes' => $attributes,
        ];
    }

    protected function normalizePricedRows($rows, $nameKey)
    {
        return collect($rows)
            ->map(function ($row) use ($nameKey) {
                $name = trim((string) data_get($row, $nameKey, ''));
                $price = data_get($row, 'price');

                if ($name === '' && ($price === null || $price === '')) {
                    return null;
                }

                return [
                    $nameKey => $name,
                    'price' => $price === null || $price === '' ? null : (float) $price,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    protected function splitCommaSeparatedValues($values)
    {
        return collect(explode(',', (string) $values))
            ->map(fn ($value) => trim($value))
            ->filter()
            ->values()
            ->all();
    }
    
    public function product_duplicate_store($product)
    {
        $product_new = $product->replicate();
        if($product->added_by == 'seller' && auth()->user()->user_type != 'seller'){
            $product_new->photos= clone_images($product->photos);
            $product_new->thumbnail_img= clone_file($product->thumbnail_img);
            $product_new->short_video= clone_file($product->short_video);
            $product_new->short_video_thumbnail= clone_images($product->short_video_thumbnail);

        }
        $product_new->slug = $product_new->slug . '-' . Str::random(5);
        
        $product_new->published = 0;
        $product_new->rating = 0.0;
        $product_new->num_of_sale = 0.0;
        if(addon_is_activated('club_point')){
            $product_new->earn_point = 0.0;
        }
        $product_new->added_by = auth()->user()->user_type != 'seller' ? 'admin' : 'seller';
        if($product_new->added_by != 'seller'){
            $product_new->draft = 1; 
        }
        $product_new->user_id = auth()->user()->user_type == 'seller' ? auth()->user()->id : User::where('user_type', 'admin')->first()->id;

        $product_new->approved = (get_setting('product_approve_by_admin') == 1 && $product_new->added_by != 'admin') ? 0 : 1;
        $product_new->save();

        return $product_new;
    }

    public function destroy($id)
    {
        $product = Product::findOrFail($id);
        $product->product_translations()->delete();
        $product->categories()->detach();
        $product->stocks()->delete();
        $product->taxes()->delete();
        $product->wishlists()->delete();
        $product->carts()->delete();
        $product->frequently_bought_products()->delete();
        $product->last_viewed_products()->delete();
        $product->flash_deal_products()->delete();
        deleteProductReview($product);
        Product::destroy($id);
    }

    public function product_search(array $data)
    {   
        $collection     = collect($data);
        $auth_user      = auth()->user();
        $productType    = $collection['product_type'];
        $products       = Product::query();
    
        if($collection['category'] != null ) {
            $category = Category::with('childrenCategories')->find($collection['category']);
            $products = $category->products();
        }
        
        $products = in_array($auth_user->user_type, ['admin', 'staff']) ? $products->where('products.added_by', 'admin') : $products->where('products.user_id', $auth_user->id);
        $products->where('published', '1')->where('auction_product', 0)->where('approved', '1');

        if($productType == 'physical'){
            $products->where('digital', 0)->where('wholesale_product', 0);
        }
        elseif($productType == 'digital'){
            $products->where('digital', 1);
        }
        elseif($productType == 'wholesale'){
            $products->where('wholesale_product', 1);
        }

        if($collection['product_id'] != null){
            $products->where('id', '!=' , $collection['product_id']);
        }
        
        if ($collection['search_key'] != null) {
            $products->where('name','like', '%' . $collection['search_key'] . '%');
        }    

        return $products->limit(20)->get();
    }

    public function setCategoryWiseDiscount(array $data)
    {
       try {
        $auth_user      = auth()->user();
        $discount_start_date = null;
        $discount_end_date   = null;
        $seller_discount_start_date = null;
        $seller_discount_end_date = null;
        $admin_discount_start_date = null;
        $admin_discount_end_date = null;
        
        if (!empty($data['date_range'])) {
            $date_var               = explode(" to ", $data['date_range']);
            $discount_start_date = strtotime($date_var[0]);
            $discount_end_date   = strtotime($date_var[1]);
            $seller_discount_start_date = $discount_start_date;
            $seller_discount_end_date = $discount_end_date;
            $admin_discount_start_date = $discount_start_date;
            $admin_discount_end_date = $discount_end_date;
        }
        $seller_product_discount =  isset($data['seller_product_discount']) ? $data['seller_product_discount'] : null ;
        $admin_id = User::where('user_type', 'admin')->first()->id;
        
        $admin_discount = null;
        $seller_discount = null;

        $category = Category::find($data['category_id']);
        $products = Product::where('category_id', $data['category_id'])->where('auction_product', 0);

       if (in_array($auth_user->user_type, ['admin', 'staff'])) {
            $admin_discount = $data['discount'];
            if ($seller_product_discount == 1) {
                $shops = Shop::all();
                foreach ($shops as $shop) {
                    $seller_cat = SellerCategory::where('category_id', $data['category_id'])
                        ->where('seller_id', $shop->user_id)
                        ->first();

                    if ($seller_cat)  {
                        // Update if record exists
                        $seller_cat->update([
                            'discount' => $admin_discount,
                            'discount_start_date' => $admin_discount_start_date,
                            'discount_end_date' => $admin_discount_end_date,
                        ]);
                    } else {
                        // Create if not found
                        SellerCategory::create([
                            'category_id' => $data['category_id'],
                            'seller_id' => $shop->user_id,
                            'discount' => $admin_discount,
                            'discount_start_date' => $admin_discount_start_date,
                            'discount_end_date' => $admin_discount_end_date,
                        ]);
                    }
                }
            }

            if ($seller_product_discount == 0) {
                $products->where('user_id', $admin_id);
            }
            // Save to category
            $category->update([
                'discount' => $admin_discount,
                'discount_start_date' => $admin_discount_start_date,
                'discount_end_date' => $admin_discount_end_date,
            ]);


        }
         elseif ($auth_user->user_type == 'seller') {
            $products->where('user_id', $auth_user->id);
            $seller_discount = $data['discount'];
            //save to sellerCategory
            $sellerCat = SellerCategory::where('seller_id', $auth_user->id)
                ->where('category_id', $data['category_id'])
                ->first();

            if ($sellerCat) {
                $sellerCat->discount = $seller_discount;
                $sellerCat->discount_start_date = $seller_discount_start_date;
                $sellerCat->discount_end_date = $seller_discount_end_date;
                $sellerCat->save();
            } else {
                $sellerCat = new SellerCategory();
                $sellerCat->seller_id = $auth_user->id;
                $sellerCat->category_id = $data['category_id'];
                $sellerCat->discount = $seller_discount;
                $sellerCat->discount_start_date = $seller_discount_start_date;
                $sellerCat->discount_end_date = $seller_discount_end_date;
                $sellerCat->save();
            }


        }

        $products->update([
            'discount' => $data['discount'],
            'discount_type' => 'percent',
            'discount_start_date' => $discount_start_date,
            'discount_end_date' => $discount_end_date,
        ]);
        return 1;
    } catch (\Exception $e) {
        Log::error('Discount update failed', [
            'error' => $e->getMessage(),
            'trace' => $e->getTraceAsString(),
            'user_id' => auth()->id(),
            'input_data' => $data,
        ]);

        return 0;
    }
    }

    public function storeOrUpdateDraft(array $data)
    {
        $collection = collect($data);
        $collection['discount']= $collection['discount'] ?? 0.00;
        $collection['weight']= $collection['weight'] ?? 0.00;
        
        $collection['show_estimated_shipping_time']= $collection['show_estimated_shipping_time'] ?? 0;
        $collection['show_shipping_note']= $collection['show_shipping_note'] ?? 0;
        $collection['show_warranty_note']= $collection['show_warranty_note'] ?? 0;
        $collection['show_delivery_notes']= $collection['show_delivery_notes'] ?? 0;

        if(addon_is_activated('refund_request')){
            $collection['show_refund_notes'] = $collection['show_refund_notes'] ?? 0;
        }

        $user_id= auth()->user()->user_type == 'seller' ? auth()->user()->id : User::where('user_type', 'admin')->first()->id;
        $approved = 1;
        if (auth()->user()->user_type == 'seller') {
            if (get_setting('product_approve_by_admin') == 1) {
                $approved = 0;
            }
        } 
        $published = 0;

        $tags = array();
        if (isset($collection['tags'][0]) && $collection['tags'][0] != null) {
            foreach (json_decode($collection['tags'][0]) as $key => $tag) {
                array_push($tags, $tag->value);
            }
        }
        $collection['tags'] = implode(',', $tags);

        $discount_start_date = null;
        $discount_end_date   = null;
        if (isset($collection['date_range']) && $collection['date_range'] != null) {
            $date_var = explode(" to ", $collection['date_range']);
            $discount_start_date = strtotime($date_var[0]);
            $discount_end_date   = strtotime($date_var[1]);
        }
        unset($collection['date_range']);

        if (!isset($collection['meta_title']) || $collection['meta_title'] == null) {
            $collection['meta_title'] = $collection['name'] ?? 'Draft Product';
        }
        if (!isset($collection['meta_description']) || $collection['meta_description'] == null) {
            $collection['meta_description'] = isset($collection['description']) ? strip_tags($collection['description']) : '';
        }
        if (!isset($collection['meta_img']) || $collection['meta_img'] == null) {
            $collection['meta_img'] = $collection['thumbnail_img'] ?? null;
        }

        $meta_keywords = $collection['meta_keywords'] ?? null;
        if (is_array($meta_keywords) && !empty($meta_keywords[0])) {
            $keywords = json_decode($meta_keywords[0]);
            $meta_keywords = [];
            if (is_array($keywords)) {
                foreach ($keywords as $keyword) {
                    $meta_keywords[] = $keyword->value ?? null;
                }
            }
            $collection['meta_keywords'] = json_encode($meta_keywords);
        }


        $shipping_cost = 0;
        if (isset($collection['shipping_type'])) {
            if ($collection['shipping_type'] == 'free') {
                $shipping_cost = 0;
            } elseif ($collection['shipping_type'] == 'flat_rate') {
                $shipping_cost = $collection['flat_shipping_cost'] ?? 0;
            }
        }
        unset($collection['flat_shipping_cost']);
        $slug = Str::slug($collection['name'] ?? 'draft-product');
        $same_slug_count = Product::where('slug', 'LIKE', $slug . '%')->count();
        $slug_suffix = $same_slug_count ? '-' . ($same_slug_count + 1) : '';
        $slug .= $slug_suffix;

        $colors = json_encode(array());
        if (isset($collection['colors_active']) && 
            $collection['colors_active'] && 
            isset($collection['colors']) && 
            count($collection['colors']) > 0) {
            $colors = json_encode($collection['colors']);
        }
        unset($collection['colors_active']);

        $choice_options = array();
        if (isset($collection['choice_no']) && $collection['choice_no']) {
            $item = array();
            foreach ($collection['choice_no'] as $key => $no) {
                $str = 'choice_options_' . $no;
                $item['attribute_id'] = $no;
                $attribute_data = array();
                
                if (isset($collection[$str])) {
                    foreach ($collection[$str] as $eachValue) {
                        array_push($attribute_data, $eachValue);
                    }
                    unset($collection[$str]);
                }

                $item['values'] = $attribute_data;
                array_push($choice_options, $item);
            }
        }
        $choice_options = json_encode($choice_options, JSON_UNESCAPED_UNICODE);

        $attributes = isset($collection['choice_no']) ? json_encode($collection['choice_no']) : json_encode(array());
        unset($collection['choice_no']);

       
        $collection['has_warranty'] = isset($collection['has_warranty']) ? 1 : 0;

        
        unset($collection['button']);

     
        $productData = $collection->merge(compact(
            'user_id',
            'approved',
            'published',
            'discount_start_date',
            'discount_end_date',
            'shipping_cost',
            'slug',
            'colors',
            'choice_options',
            'attributes'
        ))->toArray();


        // Create or update the product
        if (isset($collection['id']) && $collection['id']) {
            $product = Product::find($collection['id']);
            if ($product) {
                $product->update($productData);
                return $product;
            }
        }

        return Product::create($productData);
    }

    public function products_search(array $data)
    {   
        $collection     = collect($data);
        $auth_user      = auth()->user();
        $productType    = $collection['product_type'];
        $products       = Product::query();
    
        if($collection['category'] != null ) {
            $category = Category::with('childrenCategories')->find($collection['category']);
            $products = $category->products();
        }
        
        $products = ($auth_user->user_type === 'seller') ? $products->where('products.user_id', $auth_user->id): $products;
        $products->where('published', '1')->where('auction_product', 0)->where('approved', '1');

        if($productType == 'physical'){
            $products->where('digital', 0)->where('wholesale_product', 0);
        }
        elseif($productType == 'digital'){
            $products->where('digital', 1);
        }
        elseif($productType == 'wholesale'){
            $products->where('wholesale_product', 1);
        }

        if($collection['product_id'] != null){
            $products->where('id', '!=' , $collection['product_id']);
        }
        
        if ($collection['search_key'] != null) {
            $products->where('name','like', '%' . $collection['search_key'] . '%');
        }    

        return $products->get();
    }

    public function pos_products_search(array $data)
    {   
        $collection     = collect($data);
        $auth_user      = auth()->user();
        $productType    = $collection['product_type'];
        $products       = Product::query();
        if($collection['category'] != null ) {
            $category = Category::with('childrenCategories')->find($collection['category']);
            $products = $category->products();
        }
        
        $products = in_array($auth_user->user_type, ['admin', 'staff']) ? $products->where('products.added_by', 'admin') : $products->where('products.user_id', $auth_user->id);
        $products->where('published', '1')->where('auction_product', 0)->where('approved', '1');

        if($productType == 'physical'){
            $products->where('digital', 0)->where('wholesale_product', 0);
        }
        elseif($productType == 'digital'){
            $products->where('digital', 1);
        }
        elseif($productType == 'wholesale'){
            $products->where('wholesale_product', 1);
        }
        elseif($productType == 'physical_digital'){
             $products->where('wholesale_product', 0);
        }

        if($collection['product_id'] != null){
            $products->where('id', '!=' , $collection['product_id']);
        }
        
        if ($collection['search_key'] != null) {
            $products->where('name','like', '%' . $collection['search_key'] . '%');
        }   
        
        //$products->where('pos', $pos);
        return $products->get();
    }
}
