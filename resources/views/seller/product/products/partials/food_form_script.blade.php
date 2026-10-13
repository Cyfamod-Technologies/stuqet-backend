<script type="text/javascript">
    function toggleFoodScheduleFields() {
        var isScheduled = $('#food-availability-mode').val() === 'scheduled';
        $('.food-schedule-fields').toggleClass('d-none', !isScheduled);
    }

    function toggleFoodStockFields() {
        var isLimited = $('#food-stock-mode').val() === 'limited';
        $('.food-stock-fields').toggleClass('d-none', !isLimited);
        $('.food-stock-fields').find('input[name="current_stock"]').prop('disabled', !isLimited);
    }

    function bindFoodRemoveButtons() {
        $(document).off('click.foodRemove', '.remove-food-row').on('click.foodRemove', '.remove-food-row', function () {
            var $container = $(this).closest('[id^="food-"], .food-repeat-row').parent();
            if ($container.children('.food-repeat-row').length > 1) {
                $(this).closest('.food-repeat-row').remove();
            } else {
                $(this).closest('.food-repeat-row').find('input, textarea').val('');
            }
        });
    }

    function addFoodRow(target, template) {
        var index = $(target).children('.food-repeat-row').length;
        $(target).append(template(index));
    }

    $(document).ready(function () {
        toggleFoodScheduleFields();
        toggleFoodStockFields();
        bindFoodRemoveButtons();

        $(document).on('change', '#food-availability-mode', toggleFoodScheduleFields);
        $(document).on('change', '#food-stock-mode', toggleFoodStockFields);

        $('#add-food-time-slot').on('click', function () {
            addFoodRow('#food-time-slots', function (index) {
                return '<div class="row gutters-5 align-items-center mb-2 food-repeat-row">'
                    + '<div class="col-md-5"><input type="time" class="form-control" name="food_meta[time_slots][' + index + '][start]"></div>'
                    + '<div class="col-md-5"><input type="time" class="form-control" name="food_meta[time_slots][' + index + '][end]"></div>'
                    + '<div class="col-md-2"><button type="button" class="btn btn-soft-danger btn-icon btn-circle btn-sm remove-food-row"><i class="las la-trash"></i></button></div>'
                    + '</div>';
            });
        });

        $('#add-food-size').on('click', function () {
            addFoodRow('#food-sizes', function (index) {
                return '<div class="row gutters-5 align-items-center mb-2 food-repeat-row">'
                    + '<div class="col-md-6"><input type="text" class="form-control" name="food_meta[sizes][' + index + '][name]" placeholder="{{ translate('Size name') }}"></div>'
                    + '<div class="col-md-4"><input type="number" min="0" step="0.01" class="form-control" name="food_meta[sizes][' + index + '][price]" placeholder="{{ translate('Price') }}"></div>'
                    + '<div class="col-md-2"><button type="button" class="btn btn-soft-danger btn-icon btn-circle btn-sm remove-food-row"><i class="las la-trash"></i></button></div>'
                    + '</div>';
            });
        });

        $('#add-food-addon').on('click', function () {
            addFoodRow('#food-addons', function (index) {
                return '<div class="row gutters-5 align-items-center mb-2 food-repeat-row">'
                    + '<div class="col-md-6"><input type="text" class="form-control" name="food_meta[addons][' + index + '][name]" placeholder="{{ translate('Add-on name') }}"></div>'
                    + '<div class="col-md-4"><input type="number" min="0" step="0.01" class="form-control" name="food_meta[addons][' + index + '][price]" placeholder="{{ translate('Price') }}"></div>'
                    + '<div class="col-md-2"><button type="button" class="btn btn-soft-danger btn-icon btn-circle btn-sm remove-food-row"><i class="las la-trash"></i></button></div>'
                    + '</div>';
            });
        });

        $('#add-food-combo').on('click', function () {
            addFoodRow('#food-combos', function (index) {
                return '<div class="border rounded p-3 mb-3 food-repeat-row">'
                    + '<div class="row gutters-5">'
                    + '<div class="col-md-5 mb-2"><input type="text" class="form-control" name="food_meta[combo_deals][' + index + '][title]" placeholder="{{ translate('Combo title') }}"></div>'
                    + '<div class="col-md-4 mb-2"><input type="number" min="0" step="0.01" class="form-control" name="food_meta[combo_deals][' + index + '][price]" placeholder="{{ translate('Price') }}"></div>'
                    + '<div class="col-md-3 mb-2 text-right"><button type="button" class="btn btn-soft-danger btn-icon btn-circle btn-sm remove-food-row"><i class="las la-trash"></i></button></div>'
                    + '<div class="col-md-12"><textarea class="form-control" rows="2" name="food_meta[combo_deals][' + index + '][description]" placeholder="{{ translate('What is included in this combo?') }}"></textarea></div>'
                    + '</div>'
                    + '</div>';
            });
        });
    });
</script>
