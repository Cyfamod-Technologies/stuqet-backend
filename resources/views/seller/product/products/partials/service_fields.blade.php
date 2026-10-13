@php
    $serviceMeta = $serviceMeta ?? [];
    $servicePackages = old('service_meta.packages', data_get($serviceMeta, 'packages', [
        ['name' => 'Basic', 'duration' => '', 'sessions' => '', 'revisions' => '', 'price' => '', 'description' => ''],
        ['name' => 'Standard', 'duration' => '', 'sessions' => '', 'revisions' => '', 'price' => '', 'description' => ''],
        ['name' => 'Premium', 'duration' => '', 'sessions' => '', 'revisions' => '', 'price' => '', 'description' => ''],
    ]));
    $serviceAddons = old('service_meta.addons', data_get($serviceMeta, 'addons', [['name' => '', 'price' => '']]));
    $serviceAttributes = old('service_meta.attributes', collect(data_get($serviceMeta, 'attributes', [['name' => '', 'values' => []]]))
        ->map(function ($attribute) {
            return [
                'name' => data_get($attribute, 'name'),
                'values' => is_array(data_get($attribute, 'values'))
                    ? implode(', ', data_get($attribute, 'values'))
                    : data_get($attribute, 'values'),
            ];
        })->all());
    $serviceSlotMode = old('service_meta.slot_mode', data_get($serviceMeta, 'slot_mode', 'limited'));
    $servicePricingType = old('service_meta.pricing_type', data_get($serviceMeta, 'pricing_type', 'fixed_price'));
@endphp

<div class="card" id="service-stage-3">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Stage 3: Service Packages & Variations') }}</h5>
    </div>
    <div class="card-body">
        <div class="service-package-rows">
            @foreach ($servicePackages as $index => $package)
                <div class="border rounded p-3 mb-3 service-package-row">
                    <div class="form-group row">
                        <label class="col-md-3 col-from-label">{{ translate('Package Name') }}</label>
                        <div class="col-md-9">
                            <input type="text" class="form-control" name="service_meta[packages][{{ $index }}][name]" value="{{ data_get($package, 'name') }}" placeholder="{{ translate('Basic / Standard / Premium') }}">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 col-from-label">{{ translate('Duration') }}</label>
                        <div class="col-md-3">
                            <input type="text" class="form-control" name="service_meta[packages][{{ $index }}][duration]" value="{{ data_get($package, 'duration') }}" placeholder="{{ translate('2 hours / 3 days') }}">
                        </div>
                        <label class="col-md-2 col-from-label">{{ translate('Sessions') }}</label>
                        <div class="col-md-4">
                            <input type="number" min="0" class="form-control" name="service_meta[packages][{{ $index }}][sessions]" value="{{ data_get($package, 'sessions') }}" placeholder="0">
                        </div>
                    </div>
                    <div class="form-group row">
                        <label class="col-md-3 col-from-label">{{ translate('No of times changes are Allowed') }}</label>
                        <div class="col-md-3">
                            <input type="number" min="0" class="form-control" name="service_meta[packages][{{ $index }}][revisions]" value="{{ data_get($package, 'revisions') }}" placeholder="0">
                        </div>
                        <label class="col-md-2 col-from-label">{{ translate('Package Price') }}</label>
                        <div class="col-md-4">
                            <input type="number" min="0" step="0.01" class="form-control" name="service_meta[packages][{{ $index }}][price]" value="{{ data_get($package, 'price') }}" placeholder="0.00">
                        </div>
                    </div>
                    <div class="form-group row mb-0">
                        <label class="col-md-3 col-from-label">{{ translate('Notes') }}</label>
                        <div class="col-md-9">
                            <textarea class="form-control" rows="2" name="service_meta[packages][{{ $index }}][description]" placeholder="{{ translate('What is included in this package?') }}">{{ data_get($package, 'description') }}</textarea>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <h6 class="fw-600 mt-4">{{ translate('Add-ons') }}</h6>
        <div class="service-addon-rows">
            @foreach ($serviceAddons as $index => $addon)
                <div class="form-group row service-addon-row">
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="service_meta[addons][{{ $index }}][name]" value="{{ data_get($addon, 'name') }}" placeholder="{{ translate('Add-on name') }}">
                    </div>
                    <div class="col-md-4">
                        <input type="number" min="0" step="0.01" class="form-control" name="service_meta[addons][{{ $index }}][price]" value="{{ data_get($addon, 'price') }}" placeholder="{{ translate('Price') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-soft-danger btn-icon btn-circle mt-1" data-toggle="remove-parent" data-parent=".service-addon-row">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-soft-primary btn-sm" id="add-service-addon">{{ translate('Add Add-on') }}</button>

        <h6 class="fw-600 mt-4">{{ translate('Optional Attributes') }}</h6>
        <div class="service-attribute-rows">
            @foreach ($serviceAttributes as $index => $attribute)
                <div class="form-group row service-attribute-row">
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="service_meta[attributes][{{ $index }}][name]" value="{{ data_get($attribute, 'name') }}" placeholder="{{ translate('Attribute name') }}">
                    </div>
                    <div class="col-md-6">
                        <input type="text" class="form-control" name="service_meta[attributes][{{ $index }}][values]" value="{{ data_get($attribute, 'values') }}" placeholder="{{ translate('Comma separated values') }}">
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-soft-danger btn-icon btn-circle mt-1" data-toggle="remove-parent" data-parent=".service-attribute-row">
                            <i class="las la-times"></i>
                        </button>
                    </div>
                </div>
            @endforeach
        </div>
        <button type="button" class="btn btn-soft-primary btn-sm" id="add-service-attribute">{{ translate('Add Attribute') }}</button>
    </div>
</div>

<div class="card" id="service-stage-4">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Stage 4: Pricing & Availability') }}</h5>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Price Type') }}</label>
            <div class="col-md-8">
                <select class="form-control aiz-selectpicker" name="service_meta[pricing_type]">
                    <option value="fixed_price" @selected($servicePricingType === 'fixed_price')>{{ translate('Fixed Price') }}</option>
                    <option value="hourly_rate" @selected($servicePricingType === 'hourly_rate')>{{ translate('Hourly Rate') }}</option>
                    <option value="daily_rate" @selected($servicePricingType === 'daily_rate')>{{ translate('Daily Rate') }}</option>
                    <option value="weekly_rate" @selected($servicePricingType === 'weekly_rate')>{{ translate('Weekly Rate') }}</option>
                    <option value="monthly_subscription" @selected($servicePricingType === 'monthly_subscription')>{{ translate('Monthly Subscription') }}</option>
                    <option value="custom_quote" @selected($servicePricingType === 'custom_quote')>{{ translate('Custom Quote') }}</option>
                    <option value="free_consultation" @selected($servicePricingType === 'free_consultation')>{{ translate('Free Consultation') }}</option>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Unit Price') }} <span class="text-danger">*</span></label>
            <div class="col-md-8">
                <input type="number" min="0" step="0.01" class="form-control" name="unit_price" value="{{ old('unit_price', $unitPriceValue ?? 0) }}" required>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Discount') }}</label>
            <div class="col-md-4">
                <input type="number" min="0" step="0.01" class="form-control" name="discount" value="{{ old('discount', $discountValue ?? 0) }}" placeholder="0">
            </div>
            <div class="col-md-4">
                <select class="form-control aiz-selectpicker" name="discount_type">
                    <option value="amount" @selected(old('discount_type', $discountTypeValue ?? 'amount') === 'amount')>{{ translate('Flat') }}</option>
                    <option value="percent" @selected(old('discount_type', $discountTypeValue ?? 'amount') === 'percent')>%</option>
                </select>
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Discount Date Range') }}</label>
            <div class="col-md-8">
                <input type="text" class="form-control aiz-date-range" name="date_range" value="{{ old('date_range', $dateRangeValue ?? '') }}" placeholder="{{ translate('Select date range') }}" data-time-picker="true" data-format="DD-MM-Y HH:mm:ss" data-separator=" to " autocomplete="off">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Availability') }}</label>
            <div class="col-md-8">
                <div class="d-flex flex-wrap">
                    <label class="aiz-megabox d-block mr-3 mb-2">
                        <input type="radio" name="service_meta[slot_mode]" value="limited" @checked($serviceSlotMode === 'limited')>
                        <span class="d-block p-3 aiz-megabox-elem">
                            <span class="fw-600 d-block">{{ translate('Limited Slots') }}</span>
                            <span class="text-muted fs-12">{{ translate('Manage available quantity manually') }}</span>
                        </span>
                    </label>
                    <label class="aiz-megabox d-block mb-2">
                        <input type="radio" name="service_meta[slot_mode]" value="unlimited" @checked($serviceSlotMode === 'unlimited')>
                        <span class="d-block p-3 aiz-megabox-elem">
                            <span class="fw-600 d-block">{{ translate('Unlimited') }}</span>
                            <span class="text-muted fs-12">{{ translate('Always available unless unpublished') }}</span>
                        </span>
                    </label>
                </div>
            </div>
        </div>
        <div class="form-group row service-slot-quantity {{ $serviceSlotMode === 'unlimited' ? 'd-none' : '' }}">
            <label class="col-md-3 col-from-label">{{ translate('Available Slots / Quantity') }}</label>
            <div class="col-md-8">
                <input type="number" min="0" class="form-control" name="current_stock" value="{{ old('current_stock', $currentStockValue ?? '') }}" placeholder="{{ translate('Available slots') }}">
            </div>
        </div>
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('SKU') }}</label>
            <div class="col-md-8">
                <input type="text" class="form-control" name="sku" value="{{ old('sku', $skuValue ?? '') }}" placeholder="{{ translate('Optional reference code') }}">
            </div>
        </div>
        @if (get_setting('product_external_link_for_seller') == 1)
            <div class="form-group row">
                <label class="col-md-3 col-from-label">{{ translate('External Link') }}</label>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="external_link" value="{{ old('external_link', $externalLinkValue ?? '') }}" placeholder="{{ translate('Booking / WhatsApp / Calendly link') }}">
                </div>
            </div>
            <div class="form-group row mb-0">
                <label class="col-md-3 col-from-label">{{ translate('External Link Button Text') }}</label>
                <div class="col-md-8">
                    <input type="text" class="form-control" name="external_link_btn" value="{{ old('external_link_btn', $externalLinkBtnValue ?? '') }}" placeholder="{{ translate('Book Now / Contact on WhatsApp') }}">
                </div>
            </div>
        @endif
    </div>
</div>

<div class="card" id="service-stage-5">
    <div class="card-header">
        <h5 class="mb-0 h6">{{ translate('Stage 5: Service Description & Details') }}</h5>
    </div>
    <div class="card-body">
        <div class="form-group row">
            <label class="col-md-3 col-from-label">{{ translate('Service Description') }}</label>
            <div class="col-md-8">
                <textarea class="aiz-text-editor" name="description">{!! old('description', $descriptionValue ?? '') !!}</textarea>
            </div>
        </div>
    </div>
</div>
