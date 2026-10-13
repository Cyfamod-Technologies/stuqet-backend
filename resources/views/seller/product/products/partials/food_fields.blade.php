@php
    $foodMeta = is_array($foodMeta ?? null) ? $foodMeta : [];
    $foodTimeSlots = old('food_meta.time_slots', data_get($foodMeta, 'time_slots', [['start' => '', 'end' => '']]));
    $foodSizes = old('food_meta.sizes', data_get($foodMeta, 'sizes', [['name' => '', 'price' => '']]));
    $foodAddons = old('food_meta.addons', data_get($foodMeta, 'addons', [['name' => '', 'price' => '']]));
    $foodCombos = old('food_meta.combo_deals', data_get($foodMeta, 'combo_deals', [['title' => '', 'description' => '', 'price' => '']]));
    $foodAvailabilityMode = old('food_meta.availability_mode', data_get($foodMeta, 'availability_mode', 'always'));
    $foodStockMode = old('food_meta.stock_mode', data_get($foodMeta, 'stock_mode', 'limited'));
    $foodDeliveryOptions = old('food_meta.delivery_options', data_get($foodMeta, 'delivery_options', []));
    $foodAvailableDays = old('food_meta.available_days', data_get($foodMeta, 'available_days', []));
    $foodPrepUnit = old('food_meta.prep_time_unit', data_get($foodMeta, 'prep_time_unit', 'minutes'));
    $foodManualOverride = old('food_meta.manual_override', data_get($foodMeta, 'manual_override', 'auto'));

    if (empty($foodTimeSlots)) {
        $foodTimeSlots = [['start' => '', 'end' => '']];
    }
    if (empty($foodSizes)) {
        $foodSizes = [['name' => '', 'price' => '']];
    }
    if (empty($foodAddons)) {
        $foodAddons = [['name' => '', 'price' => '']];
    }
    if (empty($foodCombos)) {
        $foodCombos = [['title' => '', 'description' => '', 'price' => '']];
    }

    $foodDays = [
        'monday' => translate('Monday'),
        'tuesday' => translate('Tuesday'),
        'wednesday' => translate('Wednesday'),
        'thursday' => translate('Thursday'),
        'friday' => translate('Friday'),
        'saturday' => translate('Saturday'),
        'sunday' => translate('Sunday'),
    ];
@endphp

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Food Description') }}</h5>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Short Description') }} <span class="text-danger">*</span></label>
            <div class="col-md-8">
                <input
                    type="text"
                    class="form-control"
                    name="food_meta[short_description]"
                    value="{{ old('food_meta.short_description', data_get($foodMeta, 'short_description')) }}"
                    placeholder="{{ translate('Short summary shown in menu cards') }}"
                    required
                >
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Ingredients') }}</label>
            <div class="col-md-8">
                <textarea
                    class="form-control"
                    rows="4"
                    name="food_meta[ingredients]"
                    placeholder="{{ translate('List ingredients, spice level, or key preparation notes') }}"
                >{{ old('food_meta.ingredients', data_get($foodMeta, 'ingredients')) }}</textarea>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Full Description') }}</label>
            <div class="col-md-8">
                <textarea class="aiz-text-editor" name="description">{{ old('description', $descriptionValue ?? '') }}</textarea>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Food Pricing, Availability & Fulfillment') }}</h5>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Base Price') }} <span class="text-danger">*</span></label>
            <div class="col-md-6">
                <input
                    type="number"
                    lang="en"
                    min="0"
                    step="0.01"
                    name="unit_price"
                    class="form-control"
                    value="{{ old('unit_price', $unitPriceValue ?? 0) }}"
                    placeholder="{{ translate('Base Price') }}"
                    required
                >
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Availability Mode') }} <span class="text-danger">*</span></label>
            <div class="col-md-8">
                <select class="form-control aiz-selectpicker" name="food_meta[availability_mode]" id="food-availability-mode" required>
                    <option value="always" @selected($foodAvailabilityMode === 'always')>{{ translate('Always Available') }}</option>
                    <option value="scheduled" @selected($foodAvailabilityMode === 'scheduled')>{{ translate('Scheduled Availability') }}</option>
                </select>
            </div>
        </div>
        <div class="food-schedule-fields {{ $foodAvailabilityMode === 'scheduled' ? '' : 'd-none' }}">
            <div class="form-group row">
                <label class="col-md-3 col-from-label">{{ translate('Available Days') }}</label>
                <div class="col-md-8">
                    <div class="row">
                        @foreach ($foodDays as $dayKey => $dayLabel)
                            <div class="col-md-4 mb-2">
                                <label class="aiz-checkbox d-block mb-0">
                                    <input type="checkbox" name="food_meta[available_days][]" value="{{ $dayKey }}" @checked(in_array($dayKey, $foodAvailableDays, true))>
                                    <span class="aiz-square-check"></span>
                                    <span class="ml-2">{{ $dayLabel }}</span>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="form-group row">
                <label class="col-md-3 col-from-label">{{ translate('Time Slots') }}</label>
                <div class="col-md-8">
                    <div id="food-time-slots">
                        @foreach ($foodTimeSlots as $index => $slot)
                            <div class="row gutters-5 align-items-center mb-2 food-repeat-row">
                                <div class="col-md-5">
                                    <input type="time" class="form-control" name="food_meta[time_slots][{{ $index }}][start]" value="{{ data_get($slot, 'start') }}">
                                </div>
                                <div class="col-md-5">
                                    <input type="time" class="form-control" name="food_meta[time_slots][{{ $index }}][end]" value="{{ data_get($slot, 'end') }}">
                                </div>
                                <div class="col-md-2">
                                    <button type="button" class="btn btn-soft-danger btn-icon btn-circle btn-sm remove-food-row">
                                        <i class="las la-trash"></i>
                                    </button>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    <button type="button" class="btn btn-soft-primary btn-sm mt-2" id="add-food-time-slot">
                        {{ translate('Add Time Slot') }}
                    </button>
                </div>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Stock Mode') }} <span class="text-danger">*</span></label>
            <div class="col-md-8">
                <select class="form-control aiz-selectpicker" name="food_meta[stock_mode]" id="food-stock-mode" required>
                    <option value="unlimited" @selected($foodStockMode === 'unlimited')>{{ translate('Always In Stock') }}</option>
                    <option value="limited" @selected($foodStockMode === 'limited')>{{ translate('Quantity Based / Limited Stock') }}</option>
                </select>
            </div>
        </div>
        <div class="form-group row food-stock-fields {{ $foodStockMode === 'limited' ? '' : 'd-none' }}">
            <label class="col-md-3 col-from-label">{{ translate('Quantity') }}</label>
            <div class="col-md-6">
                <input
                    type="number"
                    lang="en"
                    min="0"
                    step="1"
                    name="current_stock"
                    class="form-control"
                    value="{{ old('current_stock', $currentStockValue ?? '') }}"
                    placeholder="{{ translate('Quantity') }}"
                >
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('SKU') }}</label>
            <div class="col-md-6">
                <input
                    type="text"
                    name="sku"
                    class="form-control"
                    value="{{ old('sku', $skuValue ?? '') }}"
                    placeholder="{{ translate('Optional internal SKU') }}"
                >
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Delivery Options') }} <span class="text-danger">*</span></label>
            <div class="col-md-8">
                <div class="row">
                    @foreach (['delivery' => 'Delivery', 'pickup' => 'Pickup', 'dine_in' => 'Dine In'] as $optionKey => $optionLabel)
                        <div class="col-md-4 mb-2">
                            <label class="aiz-checkbox d-block mb-0">
                                <input type="checkbox" name="food_meta[delivery_options][]" value="{{ $optionKey }}" @checked(in_array($optionKey, $foodDeliveryOptions, true))>
                                <span class="aiz-square-check"></span>
                                <span class="ml-2">{{ translate($optionLabel) }}</span>
                            </label>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Prep Time') }} <span class="text-danger">*</span></label>
            <div class="col-md-3">
                <input
                    type="number"
                    min="1"
                    step="1"
                    class="form-control"
                    name="food_meta[prep_time_value]"
                    value="{{ old('food_meta.prep_time_value', data_get($foodMeta, 'prep_time_value')) }}"
                    placeholder="20"
                    required
                >
            </div>
            <div class="col-md-3">
                <select class="form-control aiz-selectpicker" name="food_meta[prep_time_unit]" required>
                    <option value="minutes" @selected($foodPrepUnit === 'minutes')>{{ translate('Minutes') }}</option>
                    <option value="hours" @selected($foodPrepUnit === 'hours')>{{ translate('Hours') }}</option>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Special Instructions') }}</label>
            <div class="col-md-8">
                <textarea
                    class="form-control"
                    rows="3"
                    name="food_meta[special_instructions]"
                    placeholder="{{ translate('Example: deliver hot, no cutlery, call on arrival') }}"
                >{{ old('food_meta.special_instructions', data_get($foodMeta, 'special_instructions')) }}</textarea>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Manual Status') }}</label>
            <div class="col-md-8">
                <select class="form-control aiz-selectpicker" name="food_meta[manual_override]">
                    <option value="auto" @selected($foodManualOverride === 'auto')>{{ translate('Follow auto rules') }}</option>
                    <option value="available" @selected($foodManualOverride === 'available')>{{ translate('Force Available') }}</option>
                    <option value="sold_out" @selected($foodManualOverride === 'sold_out')>{{ translate('Mark Sold Out') }}</option>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Auto Status') }}</label>
            <div class="col-md-8">
                <div class="mb-2">
                    <label class="aiz-checkbox mb-0">
                        <input type="checkbox" name="food_meta[auto_time_based]" value="1" @checked((bool) old('food_meta.auto_time_based', data_get($foodMeta, 'auto_time_based', 0)))>
                        <span class="aiz-square-check"></span>
                        <span class="ml-2">{{ translate('Set availability based on time slots') }}</span>
                    </label>
                </div>
                <div>
                    <label class="aiz-checkbox mb-0">
                        <input type="checkbox" name="food_meta[auto_stock_based]" value="1" @checked((bool) old('food_meta.auto_stock_based', data_get($foodMeta, 'auto_stock_based', 0)))>
                        <span class="aiz-square-check"></span>
                        <span class="ml-2">{{ translate('Mark sold out when quantity reaches zero') }}</span>
                    </label>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="card">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Food Sizes, Add-ons & Combos') }}</h5>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Sizes') }}</label>
            <div class="col-md-8">
                <div id="food-sizes">
                    @foreach ($foodSizes as $index => $size)
                        <div class="row gutters-5 align-items-center mb-2 food-repeat-row">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="food_meta[sizes][{{ $index }}][name]" value="{{ data_get($size, 'name') }}" placeholder="{{ translate('Size name') }}">
                            </div>
                            <div class="col-md-4">
                                <input type="number" min="0" step="0.01" class="form-control" name="food_meta[sizes][{{ $index }}][price]" value="{{ data_get($size, 'price') }}" placeholder="{{ translate('Price') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-soft-danger btn-icon btn-circle btn-sm remove-food-row">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-soft-primary btn-sm mt-2" id="add-food-size">
                    {{ translate('Add Size') }}
                </button>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Add-ons') }}</label>
            <div class="col-md-8">
                <div id="food-addons">
                    @foreach ($foodAddons as $index => $addon)
                        <div class="row gutters-5 align-items-center mb-2 food-repeat-row">
                            <div class="col-md-6">
                                <input type="text" class="form-control" name="food_meta[addons][{{ $index }}][name]" value="{{ data_get($addon, 'name') }}" placeholder="{{ translate('Add-on name') }}">
                            </div>
                            <div class="col-md-4">
                                <input type="number" min="0" step="0.01" class="form-control" name="food_meta[addons][{{ $index }}][price]" value="{{ data_get($addon, 'price') }}" placeholder="{{ translate('Price') }}">
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-soft-danger btn-icon btn-circle btn-sm remove-food-row">
                                    <i class="las la-trash"></i>
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-soft-primary btn-sm mt-2" id="add-food-addon">
                    {{ translate('Add Add-on') }}
                </button>
            </div>
        </div>

        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Combo Deals') }}</label>
            <div class="col-md-8">
                <div id="food-combos">
                    @foreach ($foodCombos as $index => $combo)
                        <div class="border rounded p-3 mb-3 food-repeat-row">
                            <div class="row gutters-5">
                                <div class="col-md-5 mb-2">
                                    <input type="text" class="form-control" name="food_meta[combo_deals][{{ $index }}][title]" value="{{ data_get($combo, 'title') }}" placeholder="{{ translate('Combo title') }}">
                                </div>
                                <div class="col-md-4 mb-2">
                                    <input type="number" min="0" step="0.01" class="form-control" name="food_meta[combo_deals][{{ $index }}][price]" value="{{ data_get($combo, 'price') }}" placeholder="{{ translate('Price') }}">
                                </div>
                                <div class="col-md-3 mb-2 text-right">
                                    <button type="button" class="btn btn-soft-danger btn-icon btn-circle btn-sm remove-food-row">
                                        <i class="las la-trash"></i>
                                    </button>
                                </div>
                                <div class="col-md-12">
                                    <textarea class="form-control" rows="2" name="food_meta[combo_deals][{{ $index }}][description]" placeholder="{{ translate('What is included in this combo?') }}">{{ data_get($combo, 'description') }}</textarea>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-soft-primary btn-sm" id="add-food-combo">
                    {{ translate('Add Combo Deal') }}
                </button>
            </div>
        </div>
    </div>
</div>
