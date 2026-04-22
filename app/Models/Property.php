<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Property extends Model
{
    protected $fillable = [
        'type_id', 'slug', 'title', 'featured', 'status_id', 'price',
        'search_price', 'search_sale_price', 'price_suffix', 'floor_types',
        'region_id', 'room_count_search', 'condition_search', 'purchase_price',
        'object_type_id', 'total_rooms', 'living_area', 'usable_area',
        'floor_info', 'year_built', 'available_from', 'object_condition_id',
        'modernization', 'heating_type_id', 'energy_certificate_id',
        'energy_cert_type_id', 'energy_carrier_id', 'equipment_level_id',
        'address', 'location_query', 'postal_code', 'latitude', 'longitude',
        'images', 'overview', 'description', 'equipment', 'criteria',
        'location_description', 'lage_rich', 'equipment_description_rich',
        'call_to_action_rich', 'criteria_selected', 'agent', 'floor_plan',
    ];

    protected $casts = [
        'slug'                       => 'array',
        'title'                      => 'array',
        'featured'                   => 'boolean',
        'price'                      => 'float',
        'search_price'               => 'float',
        'search_sale_price'          => 'float',
        'price_suffix'               => 'array',
        'floor_types'                => 'array',
        'condition_search'           => 'array',
        'purchase_price'             => 'float',
        'living_area'                => 'float',
        'usable_area'                => 'float',
        'floor_info'                 => 'array',
        'available_from'             => 'array',
        'modernization'              => 'array',
        'address'                    => 'array',
        'location_query'             => 'array',
        'postal_code'                => 'array',
        'images'                     => 'array',
        'overview'                   => 'array',
        'description'                => 'array',
        'equipment'                  => 'array',
        'criteria'                   => 'array',
        'location_description'       => 'array',
        'lage_rich'                  => 'array',
        'equipment_description_rich' => 'array',
        'call_to_action_rich'        => 'array',
        'criteria_selected'          => 'array',
        'agent'                      => 'array',
    ];
}
