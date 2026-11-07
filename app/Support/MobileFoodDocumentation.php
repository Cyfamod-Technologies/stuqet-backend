<?php

namespace App\Support;

class MobileFoodDocumentation
{
    public static function schemas(): array
    {
        $pricedRow = ['type' => 'object', 'properties' => ['name' => ['type' => 'string', 'maxLength' => 100], 'price' => ['type' => 'number', 'minimum' => 0, 'nullable' => true]]];
        return [
            'FoodMeta' => [
                'type' => 'object',
                'description' => 'Food settings. Scheduled availability requires nonempty available_days and time_slots. Times use the application timezone; overnight slots end the following day. Size/add-on/combo prices are configuration, not automatic checkout surcharges.',
                'required' => ['short_description', 'availability_mode', 'stock_mode', 'delivery_options', 'prep_time_value', 'prep_time_unit'],
                'properties' => [
                    'short_description' => ['type' => 'string', 'maxLength' => 255],
                    'ingredients' => ['type' => 'string'],
                    'availability_mode' => ['type' => 'string', 'enum' => ['always', 'scheduled']],
                    'available_days' => ['type' => 'array', 'items' => ['type' => 'string', 'enum' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday']]],
                    'time_slots' => ['type' => 'array', 'items' => ['type' => 'object', 'required' => ['start', 'end'], 'properties' => ['start' => ['type' => 'string', 'pattern' => '^([01][0-9]|2[0-3]):[0-5][0-9]$'], 'end' => ['type' => 'string', 'pattern' => '^([01][0-9]|2[0-3]):[0-5][0-9]$']]]],
                    'stock_mode' => ['type' => 'string', 'enum' => ['unlimited', 'limited'], 'description' => 'Limited stock requires current_stock in full create/update requests. Unlimited stock uses an internal quantity sentinel.'],
                    'sizes' => ['type' => 'array', 'items' => $pricedRow],
                    'addons' => ['type' => 'array', 'items' => $pricedRow],
                    'combo_deals' => ['type' => 'array', 'items' => ['type' => 'object', 'properties' => ['title' => ['type' => 'string', 'maxLength' => 150], 'description' => ['type' => 'string', 'maxLength' => 500], 'price' => ['type' => 'number', 'minimum' => 0]]]],
                    'delivery_options' => ['type' => 'array', 'minItems' => 1, 'items' => ['type' => 'string', 'enum' => ['delivery', 'pickup', 'dine_in']]],
                    'prep_time_value' => ['type' => 'integer', 'minimum' => 1],
                    'prep_time_unit' => ['type' => 'string', 'enum' => ['minutes', 'hours']],
                    'special_instructions' => ['type' => 'string', 'maxLength' => 1000],
                    'auto_time_based' => ['oneOf' => [['type' => 'boolean'], ['type' => 'integer', 'enum' => [0, 1]]]],
                    'auto_stock_based' => ['oneOf' => [['type' => 'boolean'], ['type' => 'integer', 'enum' => [0, 1]]]],
                    'manual_override' => ['type' => 'string', 'enum' => ['auto', 'available', 'sold_out'], 'description' => 'An unpublished item is unavailable. Otherwise available/sold_out overrides the automatic checks.'],
                ],
            ],
            'FoodAvailability' => ['type' => 'object', 'nullable' => true, 'properties' => [
                'is_available' => ['type' => 'boolean'],
                'status' => ['type' => 'string', 'enum' => ['available', 'sold_out', 'unpublished', 'outside_schedule']],
                'timezone' => ['type' => 'string', 'example' => 'Africa/Lagos'],
            ]],
        ];
    }

    public static function apply(string $uri, array &$operation): void
    {
        if (in_array($uri, ['/api/v2/seller/products/add', '/api/v2/seller/products/update/{product}'], true)) {
            $schema = &$operation['requestBody']['content']['application/json']['schema'];
            $schema['properties']['food_meta'] = ['$ref' => '#/components/schemas/FoodMeta'];
            $schema['properties']['unit_price'] = ['type' => 'number', 'minimum' => 0, 'exclusiveMinimum' => true, 'description' => 'Required base price for food.'];
            $schema['properties']['current_stock'] = ['type' => 'integer', 'minimum' => 0, 'description' => 'Required when stock_mode=limited.'];
            $schema['properties']['tags'] = ['type' => 'array', 'items' => ['type' => 'string'], 'description' => 'Dietary tags, e.g. vegetarian or spicy.'];
            $schema['properties']['photos'] = ['type' => 'string', 'description' => 'Comma-separated upload IDs returned by POST /api/v2/seller/file/upload.'];
            $schema['properties']['thumbnail_img'] = ['type' => 'integer', 'description' => 'Thumbnail upload ID.'];
            $schema['properties']['description'] = ['type' => 'string'];
            $schema['properties']['button'] = ['type' => 'string', 'enum' => ['publish', 'unpublish', 'draft'], 'default' => 'publish'];
            $operation['requestBody']['required'] = true;
            $operation['requestBody']['content']['application/json']['example'] = [
                'name' => 'Jollof rice', 'listing_type' => 'food', 'category_ids' => [1], 'category_id' => 1,
                'unit_price' => 2500, 'current_stock' => 20, 'description' => 'Freshly prepared rice with vegetables.',
                'tags' => ['spicy'], 'button' => 'publish',
                'food_meta' => [
                    'short_description' => 'A freshly prepared lunch bowl', 'ingredients' => 'Rice, tomato, peppers',
                    'availability_mode' => 'scheduled', 'available_days' => ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'],
                    'time_slots' => [['start' => '10:00', 'end' => '18:00']], 'stock_mode' => 'limited',
                    'sizes' => [['name' => 'Large', 'price' => 3500]], 'addons' => [['name' => 'Extra chicken', 'price' => 1000]],
                    'combo_deals' => [['title' => 'Lunch combo', 'description' => 'Rice and a drink', 'price' => 3000]],
                    'delivery_options' => ['delivery', 'pickup'], 'prep_time_value' => 20, 'prep_time_unit' => 'minutes',
                    'special_instructions' => 'Tell us about any allergies.', 'auto_time_based' => true, 'auto_stock_based' => true, 'manual_override' => 'auto',
                ],
            ];
            $operation['description'] .= ' Food create/update accepts the same food_meta settings as the seller web form. Food requires a positive unit_price. Unit defaults to portion and min_qty to 1. Update is a full product submission; use the availability endpoint for partial changes. Responses from product list/edit include food_meta and food_availability.';
        }
        if ($uri === '/api/v2/seller/products/food/{id}/availability') {
            $partial = self::schemas()['FoodMeta'];
            unset($partial['required']);
            $operation['summary'] = 'Quick-update a food item’s availability and stock';
            $operation['description'] = 'Seller-owned food items only. Unspecified fields are preserved. Send a partial food_meta object; nested arrays replace their previous value. Switching to limited stock requires current_stock. Variant stock must be updated with the full product editor. Availability is a display status; this endpoint does not add checkout enforcement or real-time notifications.';
            $operation['requestBody'] = ['content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['published' => ['type' => 'boolean'], 'current_stock' => ['type' => 'integer', 'minimum' => 0], 'food_meta' => $partial]], 'example' => ['food_meta' => ['manual_override' => 'sold_out']]]]];
            $operation['responses'] = ['200' => ['description' => 'Updated food configuration and computed availability.', 'content' => ['application/json' => ['schema' => ['type' => 'object', 'properties' => ['result' => ['type' => 'boolean'], 'data' => ['type' => 'object', 'properties' => ['id' => ['type' => 'integer'], 'published' => ['type' => 'boolean'], 'current_stock' => ['type' => 'integer'], 'food_meta' => ['$ref' => '#/components/schemas/FoodMeta'], 'food_availability' => ['$ref' => '#/components/schemas/FoodAvailability']]]]]]]], '404' => ['description' => 'Food item not found for this seller.'], '403' => ['description' => 'Seller account required.'], '422' => ['description' => 'Invalid food settings or stock transition.']];
        }
    }
}
