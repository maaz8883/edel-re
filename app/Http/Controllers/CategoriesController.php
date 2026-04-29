<?php

namespace App\Http\Controllers;

use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class CategoriesController extends Controller
{
    private function handleTranslation(array $name, bool $autoStatus)
    {
        if ($autoStatus && isset($name['de'])) {
            $text = urlencode($name['de']);
            $response = Http::get("https://lingva.ml/api/v1/de/en/{$text}");

            if ($response->successful()) {
                $data = $response->json();
                $name['en'] = $data['translation'] ?? ($name['en'] ?? $name['de']);
            }
        }
        return $name;
    }

    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 10);
        return response()->json(Category::paginate($perPage));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|array',
            'auto_status' => 'boolean'
        ]);

        $name = $request->input('name');
        $autoStatus = $request->boolean('auto_status');

        $finalName = $this->handleTranslation($name, $autoStatus);

        $category = Category::create([
            'name' => $finalName,
            'auto_status' => $autoStatus
        ]);

        return response()->json($category, 201);
    }

    public function show(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        return response()->json($category);
    }

    public function update(Request $request, string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $request->validate([
            'name' => 'sometimes|array',
            'auto_status' => 'sometimes|boolean'
        ]);

        $name = $request->input('name', $category->name);
        $autoStatus = $request->input('auto_status', $category->auto_status);

        $finalName = $this->handleTranslation($name, $autoStatus);

        $category->update([
            'name' => $finalName,
            'auto_status' => $autoStatus
        ]);

        return response()->json($category);
    }


    public function destroy(string $id)
    {
        $category = Category::find($id);

        if (!$category) {
            return response()->json(['message' => 'Category not found'], 404);
        }

        $category->delete();

        return response()->json(['message' => 'Category deleted successfully']);
    }
}