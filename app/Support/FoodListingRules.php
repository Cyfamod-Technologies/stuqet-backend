<?php

namespace App\Support;

use Illuminate\Validation\Rule;

class FoodListingRules
{
    public static function rules(): array
    {
        $rules = [];
        $rules['food_meta'] = 'nullable|array';
        $rules['food_meta.short_description'] = 'required|string|max:255';
        $rules['food_meta.ingredients'] = 'nullable|string';
        $rules['food_meta.availability_mode'] = ['required', Rule::in(['always', 'scheduled'])];
        $rules['food_meta.available_days'] = 'nullable|array|required_if:food_meta.availability_mode,scheduled';
        $rules['food_meta.available_days.*'] = [Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])];
        $rules['food_meta.time_slots'] = 'nullable|array|required_if:food_meta.availability_mode,scheduled';
        $rules['food_meta.time_slots.*.start'] = 'nullable|date_format:H:i';
        $rules['food_meta.time_slots.*.end'] = 'nullable|date_format:H:i';
        $rules['food_meta.stock_mode'] = ['required', Rule::in(['unlimited', 'limited'])];
        $rules['food_meta.sizes'] = 'nullable|array';
        $rules['food_meta.sizes.*.name'] = 'nullable|string|max:100';
        $rules['food_meta.sizes.*.price'] = 'nullable|numeric|min:0';
        $rules['food_meta.addons'] = 'nullable|array';
        $rules['food_meta.addons.*.name'] = 'nullable|string|max:100';
        $rules['food_meta.addons.*.price'] = 'nullable|numeric|min:0';
        $rules['food_meta.combo_deals'] = 'nullable|array';
        $rules['food_meta.combo_deals.*.title'] = 'nullable|string|max:150';
        $rules['food_meta.combo_deals.*.description'] = 'nullable|string|max:500';
        $rules['food_meta.combo_deals.*.price'] = 'nullable|numeric|min:0';
        $rules['food_meta.delivery_options'] = 'required|array|min:1';
        $rules['food_meta.delivery_options.*'] = [Rule::in(['delivery', 'pickup', 'dine_in'])];
        $rules['food_meta.prep_time_value'] = 'required|integer|min:1';
        $rules['food_meta.prep_time_unit'] = ['required', Rule::in(['minutes', 'hours'])];
        $rules['food_meta.special_instructions'] = 'nullable|string|max:1000';
        $rules['food_meta.auto_time_based'] = 'nullable|boolean';
        $rules['food_meta.auto_stock_based'] = 'nullable|boolean';
        $rules['food_meta.manual_override'] = ['nullable', Rule::in(['auto', 'available', 'sold_out'])];
        return $rules;
    }
}
