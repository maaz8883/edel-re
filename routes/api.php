<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PropertiesController;
use App\Http\Controllers\NewsController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\TaxonomyController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Auth
Route::post('/login', [AuthController::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me',     [AuthController::class, 'me']);
});

// Properties
Route::prefix('properties')->group(function () {
    Route::get('/',                        [PropertiesController::class, 'index']);
    Route::post('/',                       [PropertiesController::class, 'store']);
    Route::get('/slug/{lang}/{slug}',      [PropertiesController::class, 'showBySlug']);
    Route::get('/{id}',                    [PropertiesController::class, 'show']);
    Route::put('/{id}',                    [PropertiesController::class, 'update']);
    Route::patch('/{id}',                  [PropertiesController::class, 'update']);
    Route::delete('/{id}',                 [PropertiesController::class, 'destroy']);
});

// News
Route::prefix('news')->group(function () {
    Route::get('/',                        [NewsController::class, 'index']);
    Route::post('/',                       [NewsController::class, 'store']);
    Route::get('/slug/{lang}/{slug}',      [NewsController::class, 'showBySlug']);
    Route::get('/{id}',                    [NewsController::class, 'show']);
    Route::put('/{id}',                    [NewsController::class, 'update']);
    Route::patch('/{id}',                  [NewsController::class, 'update']);
    Route::delete('/{id}',                 [NewsController::class, 'destroy']);
});

// Categories
Route::prefix('categories')->group(function () {
    Route::get('/',                        [CategoriesController::class, 'index']);
    Route::post('/',                       [CategoriesController::class, 'store']);
    Route::get('/{id}',                    [CategoriesController::class, 'show']);
    Route::put('/{id}',                    [CategoriesController::class, 'update']);
    Route::patch('/{id}',                  [CategoriesController::class, 'update']);
    Route::delete('/{id}',                 [CategoriesController::class, 'destroy']);
});

// Taxonomies
Route::prefix('taxonomies')->group(function () {
    Route::get('/',                        [TaxonomyController::class, 'index']);
    Route::get('/{taxonomy}',              [TaxonomyController::class, 'show']);
    Route::post('/{taxonomy}',             [TaxonomyController::class, 'storeItem']);
    Route::get('/{taxonomy}/{id}',         [TaxonomyController::class, 'showItem']);
    Route::put('/{taxonomy}/{id}',         [TaxonomyController::class, 'updateItem']);
    Route::patch('/{taxonomy}/{id}',       [TaxonomyController::class, 'updateItem']);
    Route::delete('/{taxonomy}/{id}',      [TaxonomyController::class, 'destroyItem']);
});
