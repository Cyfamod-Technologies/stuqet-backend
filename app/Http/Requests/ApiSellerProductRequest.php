<?php

namespace App\Http\Requests;

class ApiSellerProductRequest extends ProductRequest
{
    public function authorize()
    {
        return $this->user()?->user_type === 'seller';
    }

    protected function prepareForValidation()
    {
        $type = $this->input('listing_type') ?: $this->user()?->shop?->business_category;
        if ($type !== 'food') {
            return;
        }

        // The shared web service expects Tagify input. Mobile clients send strings.
        $tags = $this->input('tags') ?? [];
        if (is_array($tags) && count(array_filter($tags, 'is_string')) === count($tags) && (!isset($tags[0]) || !is_array(json_decode($tags[0], true)))) {
            $tags = [json_encode(array_map(fn ($tag) => ['value' => $tag], $tags))];
        }
        $this->merge([
            'listing_type' => 'food',
            'unit' => $this->input('unit', 'portion'),
            'min_qty' => $this->input('min_qty', 1),
            'button' => $this->input('button', 'publish'),
            'tags' => $tags,
        ]);
        if ($this->input('food_meta.stock_mode') === 'unlimited') {
            // Ignore stale quantity controls when switching away from limited stock.
            $this->merge(['current_stock' => null]);
        }
    }

    public function rules()
    {
        $rules = parent::rules();
        if ($this->input('listing_type') === 'food') {
            $rules['unit_price'] = 'required|numeric|gt:0';
            $rules['category_ids'] = 'required|array|min:1';
            $rules['category_ids.*'] = 'integer|exists:categories,id';
            $rules['tags'] = 'nullable|array';
            $rules['tags.*'] = 'string';
            $rules['food_meta.time_slots.*.start'] = 'required|date_format:H:i';
            $rules['food_meta.time_slots.*.end'] = 'required|date_format:H:i';
        }
        return $rules;
    }
}
