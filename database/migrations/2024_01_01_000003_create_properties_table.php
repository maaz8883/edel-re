<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('properties', function (Blueprint $table) {
            $table->id();
            $table->string('type_id')->nullable();
            $table->json('slug');
            $table->json('title');
            $table->boolean('auto_status')->default(false);
            $table->boolean('featured')->default(false);
            $table->unsignedInteger('status_id')->nullable();
            $table->decimal('price', 15, 2)->default(0);
            $table->decimal('search_price', 15, 2)->default(0);
            $table->decimal('search_sale_price', 15, 2)->default(0);
            $table->json('price_suffix')->nullable();
            $table->json('floor_types')->nullable();       // array
            $table->unsignedInteger('region_id')->nullable();
            $table->unsignedInteger('room_count_search')->default(0);
            $table->json('condition_search')->nullable();  // array
            $table->decimal('purchase_price', 15, 2)->default(0);
            $table->unsignedInteger('object_type_id')->nullable();
            $table->unsignedInteger('total_rooms')->default(0);
            $table->decimal('living_area', 8, 2)->default(0);
            $table->decimal('usable_area', 8, 2)->default(0);
            $table->json('floor_info')->nullable();
            $table->unsignedInteger('year_built')->nullable();
            $table->json('available_from')->nullable();
            $table->unsignedInteger('object_condition_id')->nullable();
            $table->json('modernization')->nullable();
            $table->unsignedInteger('heating_type_id')->nullable();
            $table->unsignedInteger('energy_certificate_id')->nullable();
            $table->string('energy_cert_type_id')->nullable();
            $table->unsignedInteger('energy_carrier_id')->nullable();
            $table->unsignedInteger('equipment_level_id')->nullable();
            $table->json('address')->nullable();
            $table->json('location_query')->nullable();
            $table->json('postal_code')->nullable();
            $table->string('latitude')->nullable();
            $table->string('longitude')->nullable();
            $table->json('images')->nullable();            // array
            $table->json('overview')->nullable();          // { area, bedrooms, bathrooms }
            $table->json('description')->nullable();
            $table->json('equipment')->nullable();         // array
            $table->json('criteria')->nullable();          // array
            $table->json('location_description')->nullable();
            $table->json('lage_rich')->nullable();
            $table->json('equipment_description_rich')->nullable();
            $table->json('call_to_action_rich')->nullable();
            $table->json('criteria_selected')->nullable(); // array
            $table->json('agent')->nullable();             // { name, image, logo }
            $table->string('floor_plan')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
