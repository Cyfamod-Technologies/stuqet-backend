<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     *
     * @return bool
     */
    public function authorize()
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array
     */
    public function rules()
    {
        $rules = [];
        $listingType = $this->input('listing_type') ?: optional(optional(auth()->user())->shop)->business_category ?: 'product';
        $isFoodListing = $listingType === 'food';
        $isServiceListing = $listingType === 'service';

        $rules['name']          = 'required|max:255';
        $rules['category_ids']  = 'required';
        $rules['category_id']   = ['required', Rule::in($this->category_ids)];
        $rules['listing_type']  = ['nullable', Rule::in(['product', 'food', 'service'])];
        $rules['school_id']     = 'nullable|exists:schools,id';
        $rules['unit']         = 'sometimes|required|regex:/^[A-Za-z\s]+$/';
        $rules['min_qty']      = 'sometimes|required|numeric';
        $rules['unit_price']    = 'sometimes|required|numeric|gt:0';
        if ($this->get('discount_type') == 'amount') {
            $rules['discount'] = 'nullable|numeric|lt:unit_price';
        } else {
            $rules['discount'] = 'nullable|numeric|lt:100';
        }
        $rules['current_stock'] = $isFoodListing
            ? 'nullable|numeric|min:0|required_if:food_meta.stock_mode,limited'
            : 'sometimes|required|numeric';
        $rules['starting_bid']  = 'sometimes|required|numeric|min:1';
        $rules['auction_date_range']  = 'sometimes|required';

        if ($isFoodListing) {
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
        }

        if ($isServiceListing) {
            $rules['service_meta'] = 'nullable|array';
            $rules['service_meta.service_type'] = ['required', Rule::in(['consultation', 'one_time', 'recurring', 'installation', 'repair_maintenance', 'training_coaching', 'custom', 'on_site', 'remote_virtual', 'package', 'subscription', 'emergency'])];
            $rules['service_meta.pricing_type'] = ['nullable', Rule::in(['fixed_price', 'hourly_rate', 'daily_rate', 'weekly_rate', 'monthly_subscription', 'custom_quote', 'free_consultation'])];
            $rules['service_meta.location'] = 'nullable|string|max:255';
            $rules['service_meta.service_area'] = 'nullable|string|max:255';
            $rules['service_meta.slot_mode'] = ['required', Rule::in(['unlimited', 'limited'])];
            $rules['current_stock'] = 'nullable|numeric|min:0|required_if:service_meta.slot_mode,limited';
            $rules['service_meta.packages'] = 'nullable|array';
            $rules['service_meta.packages.*.name'] = 'nullable|string|max:100';
            $rules['service_meta.packages.*.duration'] = 'nullable|string|max:100';
            $rules['service_meta.packages.*.sessions'] = 'nullable|integer|min:0';
            $rules['service_meta.packages.*.revisions'] = 'nullable|integer|min:0';
            $rules['service_meta.packages.*.price'] = 'nullable|numeric|min:0';
            $rules['service_meta.packages.*.description'] = 'nullable|string|max:1000';
            $rules['service_meta.addons'] = 'nullable|array';
            $rules['service_meta.addons.*.name'] = 'nullable|string|max:100';
            $rules['service_meta.addons.*.price'] = 'nullable|numeric|min:0';
            $rules['service_meta.attributes'] = 'nullable|array';
            $rules['service_meta.attributes.*.name'] = 'nullable|string|max:100';
            $rules['service_meta.attributes.*.values'] = 'nullable|string|max:255';
            $rules['sku'] = 'nullable|string|max:100';
            $rules['external_link'] = 'nullable|url|max:1000';
            $rules['external_link_btn'] = 'nullable|string|max:100';
        }

        return $rules;
    }


    

    /**
     * Get the validation messages of rules that apply to the request.
     *
     * @return array
     */
    public function messages()
    {
        $messages = [
            'name.required'             => translate('Product name is required'),
            'category_ids.required'     => translate('Product category is required'),
            'category_id.required'      => translate('Main Category is required'),
            'category_id.in'            => translate('Main Category must be within selected categories'),
            'unit.required'             => translate('Product unit is required'),
            'unit.regex' => 'The unit may only contain letters and spaces.',
            'min_qty.required'          => translate('Minimum purchase quantity is required'),
            'min_qty.numeric'           => translate('Minimum purchase must be numeric'),
            'unit_price.gt'             => translate('The unit price must be greater than 0'),
            'unit_price.required'       => translate('Unit price is required'),
            'unit_price.numeric'        => translate('Unit price must be numeric'),
            'discount.required'         => translate('Discount is required'),
            'discount.numeric'          => translate('Discount must be numeric'),
            'discount.lt'               => translate('Discount should be less than unit price'),
            'current_stock.required'    => translate('Current stock is required'),
            'current_stock.numeric'     => translate('Current stock must be numeric'),
            'starting_bid.required'     => translate('Starting Bid is required'),
            'starting_bid.numeric'      => translate('Starting Bid must be numeric'),
            'starting_bid.required'     => translate('Minimum Starting Bid is 1'),
            'auction_date_range.required' => translate('Auction Date Range is required'),
            'food_meta.short_description.required' => translate('Short description is required for food listings'),
            'food_meta.availability_mode.required' => translate('Availability mode is required for food listings'),
            'food_meta.available_days.required_if' => translate('Select at least one available day for scheduled food listings'),
            'food_meta.time_slots.required_if' => translate('Add at least one time slot for scheduled food listings'),
            'food_meta.stock_mode.required' => translate('Stock mode is required for food listings'),
            'food_meta.delivery_options.required' => translate('Select at least one delivery option for food listings'),
            'food_meta.prep_time_value.required' => translate('Prep time is required for food listings'),
            'food_meta.prep_time_unit.required' => translate('Prep time unit is required for food listings'),
            'current_stock.required_if' => translate('Quantity is required when stock mode is limited'),
            'service_meta.service_type.required' => translate('Service type is required for service listings'),
            'service_meta.slot_mode.required' => translate('Availability mode is required for service listings'),
        ];

        return $messages;
    }

}
