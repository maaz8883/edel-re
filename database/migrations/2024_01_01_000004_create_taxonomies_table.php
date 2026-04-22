<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('taxonomies', function (Blueprint $table) {
            $table->id();
            $table->string('type');        // e.g. "region", "heating-type"
            $table->string('external_id'); // original id from JSON (e.g. "152", "bedarf")
            $table->json('title');         // { "en": "...", "de": "..." }
            $table->timestamps();

            $table->unique(['type', 'external_id']);
            $table->index('type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('taxonomies');
    }
};
