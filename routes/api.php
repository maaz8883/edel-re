<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\PropertyImageController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\TaxonomyController;
use Illuminate\Support\Facades\Artisan;
use App\Http\Controllers\TranslateController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/




// Public routes
Route::post('/login', [AuthController::class, 'login']);

// Protected routes
Route::middleware('auth:sanctum')->group(function () {

    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::post('/translate', [TranslateController::class, 'translate']); // optional secure


    // Properties
    Route::prefix('properties')->group(function () {

        Route::post('/', [PropertiesController::class, 'store']);
        Route::post('/{id}', [PropertiesController::class, 'update']);
        Route::patch('/{id}', [PropertiesController::class, 'update']);
        Route::delete('/{id}', [PropertiesController::class, 'destroy']);

        // Independent Image Management
        Route::post('/{id}/images', [PropertyImageController::class, 'store']);
        Route::delete('/{id}/images/{filename}', [PropertyImageController::class, 'destroy']);
    });

    Route::prefix('news')->group(function () {

        Route::post('/', [NewsController::class, 'store']);
        Route::post('/{id}', [NewsController::class, 'update']);
        Route::delete('/{id}', [NewsController::class, 'destroy']);
    });



    Route::prefix('categories')->group(function () {

        Route::post('/', [CategoriesController::class, 'store']);
        Route::put('/{id}', [CategoriesController::class, 'update']);
        Route::patch('/{id}', [CategoriesController::class, 'update']);
        Route::delete('/{id}', [CategoriesController::class, 'destroy']);
    });


    Route::prefix('taxonomies')->group(function () {
        Route::post('/{taxonomy}', [TaxonomyController::class, 'storeItem']);
        Route::put('/{taxonomy}/{id}', [TaxonomyController::class, 'updateItem']);
        Route::patch('/{taxonomy}/{id}', [TaxonomyController::class, 'updateItem']);
        Route::delete('/{taxonomy}/{id}', [TaxonomyController::class, 'destroyItem']);
    });
});






// Properties
Route::prefix('properties')->group(function () {

    Route::get('/', [PropertiesController::class, 'index']);
    Route::get('/slug/{lang}/{slug}', [PropertiesController::class, 'showBySlug']);
    Route::get('/{id}', [PropertiesController::class, 'show']);

});

// News
Route::prefix('news')->group(function () {
    Route::get('/', [NewsController::class, 'index']);
    Route::get('/{id}', [NewsController::class, 'show']);
    Route::get('/slug/{lang}/{slug}', [NewsController::class, 'showBySlug']);

});

// Categories
Route::prefix('categories')->group(function () {
    Route::get('/', [CategoriesController::class, 'index']);
    Route::get('/{id}', [CategoriesController::class, 'show']);

});

// Taxonomies
Route::prefix('taxonomies')->group(function () {
    Route::get('/', [TaxonomyController::class, 'index']);
    Route::get('/{taxonomy}', [TaxonomyController::class, 'show']);
    Route::get('/{taxonomy}/{id}', [TaxonomyController::class, 'showItem']);
});









