<?php

namespace App\Http\Controllers;

use App\Models\Taxonomy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TaxonomyController extends Controller
{
    private array $validTypes = [
        'energy-carrier', 'energy-cert-type', 'energy-certificate',
        'equipment-level', 'heating-type', 'konditionen', 'kriterien',
        'object-condition', 'object-type', 'region', 'status', 'stockwerk',
    ];

    private function isValidType(string $type): bool
    {
        return in_array($type, $this->validTypes);
    }

    public function index(): JsonResponse
    {
        return response()->json($this->validTypes);
    }

    public function show(string $taxonomy): JsonResponse
    {
        if (!$this->isValidType($taxonomy)) {
            return response()->json(['message' => 'Taxonomy not found'], 404);
        }

        return response()->json(Taxonomy::where('type', $taxonomy)->get());
    }

    public function showItem(string $taxonomy, string $id): JsonResponse
    {
        if (!$this->isValidType($taxonomy)) {
            return response()->json(['message' => 'Taxonomy not found'], 404);
        }

        $item = Taxonomy::where('type', $taxonomy)->where('external_id', $id)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return response()->json($item);
    }

    public function storeItem(Request $request, string $taxonomy): JsonResponse
    {
        if (!$this->isValidType($taxonomy)) {
            return response()->json(['message' => 'Taxonomy not found'], 404);
        }

        $request->validate([
            'external_id' => 'required|string',
            'title'       => 'required|array',
        ]);

        $item = Taxonomy::create([
            'type'        => $taxonomy,
            'external_id' => $request->external_id,
            'title'       => $request->title,
        ]);

        return response()->json($item, 201);
    }

    public function updateItem(Request $request, string $taxonomy, string $id): JsonResponse
    {
        if (!$this->isValidType($taxonomy)) {
            return response()->json(['message' => 'Taxonomy not found'], 404);
        }

        $item = Taxonomy::where('type', $taxonomy)->where('external_id', $id)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->update($request->only(['title', 'external_id']));

        return response()->json($item);
    }

    public function destroyItem(string $taxonomy, string $id): JsonResponse
    {
        if (!$this->isValidType($taxonomy)) {
            return response()->json(['message' => 'Taxonomy not found'], 404);
        }

        $item = Taxonomy::where('type', $taxonomy)->where('external_id', $id)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Item deleted']);
    }
}
