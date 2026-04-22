<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\News;
use App\Models\Property;
use App\Models\Taxonomy;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // Admin user
        User::firstOrCreate(
            ['email' => 'admin@admin.com'],
            ['name' => 'Admin', 'password' => Hash::make('password')]
        );

        // Categories
        $categories = json_decode(file_get_contents(base_path('mock-data/categories.json')), true)['categories'];
        foreach ($categories as $cat) {
            Category::firstOrCreate(['id' => $cat['id']], ['name' => $cat['name']]);
        }

        // News
        $news = json_decode(file_get_contents(base_path('mock-data/news.json')), true);
        foreach ($news as $item) {
            News::firstOrCreate(['id' => $item['id']], [
                'title'             => $item['title'],
                'slug'              => $item['slug'],
                'short_description' => $item['shortDescription'],
                'description'       => $item['description'],
                'image'             => $item['image'] ?? null,
                'date'              => $item['date'],
            ]);
        }

        // Properties
        $properties = json_decode(file_get_contents(base_path('mock-data/properties.json')), true);
        foreach ($properties as $prop) {
            Property::firstOrCreate(['id' => $prop['id']], [
                'type_id'                    => $prop['typeId'] ?? null,
                'slug'                       => $prop['slug'],
                'title'                      => $prop['title'],
                'featured'                   => $prop['featured'] ?? false,
                'status_id'                  => $prop['statusId'] ?? null,
                'price'                      => $prop['price'] ?? 0,
                'search_price'               => $prop['searchPrice'] ?? 0,
                'search_sale_price'          => $prop['searchSalePrice'] ?? 0,
                'price_suffix'               => $prop['priceSuffix'] ?? null,
                'floor_types'               => $prop['floorTypes'] ?? null,
                'region_id'                  => $prop['regionId'] ?? null,
                'room_count_search'          => $prop['roomCountSearch'] ?? 0,
                'condition_search'           => $prop['conditionSearch'] ?? null,
                'purchase_price'             => $prop['purchasePrice'] ?? 0,
                'object_type_id'             => $prop['objectTypeId'] ?? null,
                'total_rooms'                => $prop['totalRooms'] ?? 0,
                'living_area'                => $prop['livingArea'] ?? 0,
                'usable_area'                => $prop['usableArea'] ?? 0,
                'floor_info'                 => $prop['floorInfo'] ?? null,
                'year_built'                 => $prop['yearBuilt'] ?? null,
                'available_from'             => $prop['availableFrom'] ?? null,
                'object_condition_id'        => $prop['objectConditionId'] ?? null,
                'modernization'              => $prop['modernization'] ?? null,
                'heating_type_id'            => $prop['heatingTypeId'] ?? null,
                'energy_certificate_id'      => $prop['energyCertificateId'] ?? null,
                'energy_cert_type_id'        => $prop['energyCertTypeId'] ?? null,
                'energy_carrier_id'          => $prop['energyCarrierId'] ?? null,
                'equipment_level_id'         => $prop['equipmentLevelId'] ?? null,
                'address'                    => $prop['address'] ?? null,
                'location_query'             => $prop['locationQuery'] ?? null,
                'postal_code'                => $prop['postalCode'] ?? null,
                'latitude'                   => $prop['latitude'] ?? null,
                'longitude'                  => $prop['longitude'] ?? null,
                'images'                     => $prop['images'] ?? null,
                'overview'                   => $prop['overview'] ?? null,
                'description'                => $prop['description'] ?? null,
                'equipment'                  => $prop['equipment'] ?? null,
                'criteria'                   => $prop['criteria'] ?? null,
                'location_description'       => $prop['locationDescription'] ?? null,
                'lage_rich'                  => $prop['lageRich'] ?? null,
                'equipment_description_rich' => $prop['equipmentDescriptionRich'] ?? null,
                'call_to_action_rich'        => $prop['callToActionRich'] ?? null,
                'criteria_selected'          => $prop['criteriaSelected'] ?? null,
                'agent'                      => $prop['agent'] ?? null,
                'floor_plan'                 => $prop['floorPlan'] ?? null,
            ]);
        }

        // Taxonomies
        $taxonomyFiles = [
            'energy-carrier'     => 'taxonomy-energy-carrier.json',
            'energy-cert-type'   => 'taxonomy-energy-cert-type.json',
            'energy-certificate' => 'taxonomy-energy-certificate.json',
            'equipment-level'    => 'taxonomy-equipment-level.json',
            'heating-type'       => 'taxonomy-heating-type.json',
            'konditionen'        => 'taxonomy-konditionen.json',
            'kriterien'          => 'taxonomy-kriterien.json',
            'object-condition'   => 'taxonomy-object-condition.json',
            'object-type'        => 'taxonomy-object-type.json',
            'region'             => 'taxonomy-region.json',
            'status'             => 'taxonomy-status.json',
            'stockwerk'          => 'taxonomy-stockwerk.json',
        ];

        foreach ($taxonomyFiles as $type => $file) {
            
    $items = json_decode(file_get_contents(base_path("mock-data/{$file}")), true);
    
    foreach ($items as $item) {
        
        // Title ko check karein, agar array hai toh string banayein ya JSON mein convert karein
        $titleValue = $item['title'];
        if (is_array($titleValue)) {
            
            // Agar aapko sirf English title chahiye:
            $titleValue = $titleValue['en'] ?? $titleValue['de'] ?? '';
            
            // YA phir agar pura JSON save karna hai (agar column text/json hai):
            // $titleValue = json_encode($titleValue);
        }

      Taxonomy::updateOrCreate(
    [
        'type' => (string) $type,
        'external_id' => (string) $item['id'], 
    ],
    [
        'title' => $titleValue
    ]
);
    }
}
    }

}