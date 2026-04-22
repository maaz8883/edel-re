<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;

class PropertiesController extends Controller
{
    public function index(Request $request)
    {
        $query = Property::query();

        if ($request->filled('typeId'))      $query->where('type_id', $request->typeId);
        if ($request->filled('statusId'))    $query->where('status_id', $request->statusId);
        if ($request->filled('regionId'))    $query->where('region_id', $request->regionId);
        if ($request->filled('objectTypeId')) $query->where('object_type_id', $request->objectTypeId);
        if ($request->filled('minPrice'))    $query->where('price', '>=', $request->minPrice);
        if ($request->filled('maxPrice'))    $query->where('price', '<=', $request->maxPrice);
        if ($request->filled('minArea'))     $query->where('living_area', '>=', $request->minArea);
        if ($request->filled('rooms'))       $query->where('room_count_search', $request->rooms);
        if ($request->filled('featured'))    $query->where('featured', filter_var($request->featured, FILTER_VALIDATE_BOOLEAN));

        return response()->json($query->get());
    }

    public function show(string $id)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        return response()->json($property);
    }

    public function showBySlug(string $lang, string $slug)
    {
        $property = Property::whereJsonContains("slug->{$lang}", $slug)->first();

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        return response()->json($property);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'      => 'required|array',
            'price'      => 'required|numeric',
            'living_area' => 'required|numeric',
        ]);

        $property = Property::create($request->all());

        return response()->json($property, 201);
    }

    public function update(Request $request, string $id)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        $property->update($request->all());

        return response()->json($property);
    }

    public function destroy(string $id)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        $property->delete();

        return response()->json(['message' => 'Property deleted']);
    }
}
