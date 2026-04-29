<?php

namespace App\Http\Controllers;

use App\Models\Taxonomy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Http;

class TaxonomyController extends Controller
{
    private array $validTypes = [
        'energy-carrier',
        'energy-cert-type',
        'energy-certificate',
        'equipment-level',
        'heating-type',
        'konditionen',
        'kriterien',
        'object-condition',
        'object-type',
        'region',
        'status',
        'stockwerk',
    ];

    private function isValidType(string $type): bool
    {
        return in_array($type, $this->validTypes);
    }

    /**
     * Helper for Auto Translation with + sign fix
     */
    private function handleTranslation(Request $request, array $data)
    {
        $autoStatus = filter_var($request->input('auto_status', false), FILTER_VALIDATE_BOOLEAN);

        if ($autoStatus && isset($data['title']['de'])) {
            $text = urlencode($data['title']['de']);
            $response = Http::get("https://lingva.ml/api/v1/de/en/{$text}");

            if ($response->successful()) {
                $res = $response->json();
                $translated = $res['translation'] ?? ($data['title']['en'] ?? $data['title']['de']);

                // FIXED: '+' ko space se badalna aur URL decode karna
                $cleanText = str_replace('+', ' ', $translated);
                $data['title']['en'] = urldecode($cleanText);
            }
        }
        return $data;
    }


    public function index(): JsonResponse
    {
        return response()->json($this->validTypes);
    }

    public function show(Request $request, string $taxonomy): JsonResponse
    {
        if (!$this->isValidType($taxonomy)) {
            return response()->json(['message' => 'Taxonomy type not found'], 404);
        }

        // Yahan paginate() `lagana zaroori hai agar withQueryString() use karna hai
        $perPage = $request->input('perPage', 10);

        $items = Taxonomy::where('type', $taxonomy)
            ->latest()
            ->paginate($perPage) // Pehle data fetch karein
            ->withQueryString(); // Phir query string add karein

        return response()->json($items);
    }

    public function showItem(string $taxonomy, int $id): JsonResponse
    {
        if (!$this->isValidType($taxonomy)) {
            return response()->json(['message' => 'Taxonomy type not found'], 404);
        }

        $item = Taxonomy::where('type', $taxonomy)->where('id', $id)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        return response()->json($item);
    }

    public function storeItem(Request $request, string $taxonomy): JsonResponse
    {
        if (!$this->isValidType($taxonomy)) {
            return response()->json(['message' => 'Taxonomy type not found'], 404);
        }

        $request->validate([
            'title' => 'required|array',
        ]);

        $data = $this->handleTranslation($request, $request->all());

        $item = Taxonomy::create([
            'type' => $taxonomy,
            'title' => $data['title'],
        ]);

        return response()->json($item, 201);
    }

    public function updateItem(Request $request, string $taxonomy, int $id): JsonResponse
    {
        if (!$this->isValidType($taxonomy)) {
            return response()->json(['message' => 'Taxonomy type not found'], 404);
        }

        $item = Taxonomy::where('type', $taxonomy)->where('id', $id)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $request->validate([
            'title' => 'sometimes|array',
        ]);

        $mergedData = array_merge($item->toArray(), $request->all());
        $data = $this->handleTranslation($request, $mergedData);

        $item->update($data);

        return response()->json($item);
    }

    public function destroyItem(string $taxonomy, int $id): JsonResponse
    {
        if (!$this->isValidType($taxonomy)) {
            return response()->json(['message' => 'Taxonomy type not found'], 404);
        }

        $item = Taxonomy::where('type', $taxonomy)->where('id', $id)->first();

        if (!$item) {
            return response()->json(['message' => 'Item not found'], 404);
        }

        $item->delete();

        return response()->json(['message' => 'Item successfully deleted']);
    }
}