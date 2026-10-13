<script type="text/javascript">
    $(document).ready(function () {
        function toggleServiceSlotQuantity() {
            var mode = $('input[name="service_meta[slot_mode]"]:checked').val();
            $('.service-slot-quantity').toggleClass('d-none', mode === 'unlimited');
        }

        $(document).on('change', 'input[name="service_meta[slot_mode]"]', toggleServiceSlotQuantity);
        toggleServiceSlotQuantity();

        $('#add-service-addon').on('click', function () {
            var index = $('.service-addon-row').length;
            $('.service-addon-rows').append(
                '<div class="form-group row service-addon-row">'
                + '<div class="col-md-6"><input type="text" class="form-control" name="service_meta[addons][' + index + '][name]" placeholder="{{ translate('Add-on name') }}"></div>'
                + '<div class="col-md-4"><input type="number" min="0" step="0.01" class="form-control" name="service_meta[addons][' + index + '][price]" placeholder="{{ translate('Price') }}"></div>'
                + '<div class="col-md-2"><button type="button" class="btn btn-soft-danger btn-icon btn-circle mt-1" data-toggle="remove-parent" data-parent=".service-addon-row"><i class="las la-times"></i></button></div>'
                + '</div>'
            );
        });

        $('#add-service-attribute').on('click', function () {
            var index = $('.service-attribute-row').length;
            $('.service-attribute-rows').append(
                '<div class="form-group row service-attribute-row">'
                + '<div class="col-md-4"><input type="text" class="form-control" name="service_meta[attributes][' + index + '][name]" placeholder="{{ translate('Attribute name') }}"></div>'
                + '<div class="col-md-6"><input type="text" class="form-control" name="service_meta[attributes][' + index + '][values]" placeholder="{{ translate('Comma separated values') }}"></div>'
                + '<div class="col-md-2"><button type="button" class="btn btn-soft-danger btn-icon btn-circle mt-1" data-toggle="remove-parent" data-parent=".service-attribute-row"><i class="las la-times"></i></button></div>'
                + '</div>'
            );
        });
    });
</script>
