<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class NewsController extends Controller
{
    private function normalizeBracketNotationInput(array $input): array
    {
        $normalized = [];
        foreach ($input as $key => $value) {
            if (!is_string($key) || strpos($key, '[') === false) {
                $normalized[$key] = $value;
                continue;
            }
            $path = preg_replace('/\]/', '', $key);
            $path = str_replace('[', '.', $path);
            data_set($normalized, $path, $value);
        }
        return $normalized;
    }

    private function translateText(string $text): string
    {
        try {
            $response = Http::get('https://lingva.ml/api/v1/de/en/' . urlencode($text));
            if ($response->successful()) {
                $translated = (string) ($response->json()['translation'] ?? $text);
                return urldecode(str_replace('+', ' ', $translated));
            }
        } catch (\Throwable $e) {
            return $text;
        }
        return $text;
    }

    private function buildPublicStorageUrl(string $path): string
    {
        $url = Storage::disk('public')->url($path);

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        $baseUrl = request()->getSchemeAndHttpHost();
        if (!empty($baseUrl)) {
            return rtrim($baseUrl, '/') . $url;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        return $appUrl !== '' ? $appUrl . $url : $url;
    }

    private function normalizeNewsResponse(News $news): News
    {
        $news->image_url = $news->image ? $this->buildPublicStorageUrl($news->image) : null;
        return $news;
    }

    private function handleNewsLogic(array $data, bool $isUpdate = false, array $originalData = []): array
    {
        $autoStatus = filter_var($data['auto_status'] ?? false, FILTER_VALIDATE_BOOLEAN);
        $data['auto_status'] = $autoStatus;

        foreach (['title', 'short_description', 'description'] as $field) {
            if (!isset($data[$field]) || !is_array($data[$field])) {
                continue;
            }

            $de = $data[$field]['de'] ?? '';
            $en = $data[$field]['en'] ?? '';

            if ($isUpdate && $autoStatus && !empty($de)) {
                $oldDe = $originalData[$field]['de'] ?? '';
                if ($de !== $oldDe) {
                    $data[$field]['en'] = $this->translateText((string) $de);
                }
            } elseif ($autoStatus && empty($en) && !empty($de)) {
                $data[$field]['en'] = $this->translateText((string) $de);
            }

            if (empty($data[$field]['en'])) {
                $data[$field]['en'] = $de;
            }
            if (empty($data[$field]['de'])) {
                $data[$field]['de'] = $data[$field]['en'];
            }
        }

        $slugSourceEn = $data['title']['en'] ?? '';
        $slugSourceDe = $data['title']['de'] ?? '';
        $data['slug'] = [
            'en' => Str::slug((string) ($slugSourceEn !== '' ? $slugSourceEn : $slugSourceDe)),
            'de' => Str::slug((string) ($slugSourceDe !== '' ? $slugSourceDe : $slugSourceEn)),
        ];

        return $data;
    }

    public function store(Request $request)
    {
        $input = $this->normalizeBracketNotationInput($request->all());
        $request->validate([
            'title' => 'required|array',
            'short_description' => 'required|array',
            'description' => 'required|array',
            'auto_status' => 'sometimes|boolean',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $data = $this->handleNewsLogic($input);
        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('news', 'public');
        }
        $data['date'] = now()->toDateString();

        $news = News::create($data);
        return response()->json($this->normalizeNewsResponse($news), 201);
    }

    // public function update(Request $request, string $id)
    // {
    //     $news = News::find($id);
    //     if (!$news) return response()->json(['message' => 'Not found'], 404);

    //     $inputData = $this->normalizeBracketNotationInput($request->all());
    //     $request->validate([
    //         'title' => 'sometimes|array',
    //         'short_description' => 'sometimes|array',
    //         'description' => 'sometimes|array',
    //         'auto_status' => 'sometimes|boolean',
    //         'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
    //     ]);

    //     $currentData = $news->toArray();
    //     $mergedData = array_replace_recursive($currentData, $inputData);

    //     if ($request->hasFile('image')) {
    //         if ($news->image && Storage::disk('public')->exists($news->image)) {
    //             Storage::disk('public')->delete($news->image);
    //         }
    //         $mergedData['image'] = $request->file('image')->store('news', 'public');
    //     }

    //     $processedData = $this->handleNewsLogic($mergedData, true, $currentData);

    //     $news->update($processedData);
    //     return response()->json($this->normalizeNewsResponse($news->fresh()));
    // }
    public function update(Request $request, string $id)
    {
        $news = News::find($id);
        if (!$news)
            return response()->json(['message' => 'Not found'], 404);

        // FIX 1: Normalize input FIRST (title[de] -> title['de'])
        $inputData = $this->normalizeBracketNotationInput($request->all());

        // FIX 2: Validation simplified for form-data compatibility
        $request->validate([
            'title' => 'sometimes|array',
            'short_description' => 'sometimes|array',
            'description' => 'sometimes|array',
            'auto_status' => 'sometimes',
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $currentData = $news->toArray();
        $mergedData = array_replace_recursive($currentData, $inputData);

        // Handle Image
        if ($request->hasFile('image')) {
            if ($news->image && Storage::disk('public')->exists($news->image)) {
                Storage::disk('public')->delete($news->image);
            }
            $mergedData['image'] = $request->file('image')->store('news', 'public');
        }

        // Handle Logic (Translation + Slugs)
        $processedData = $this->handleNewsLogic($mergedData, true, $currentData);

        $news->update($processedData);
        return response()->json($this->normalizeNewsResponse($news->fresh()));
    }
    public function index(Request $request)
    {
        $perPage = (int) $request->get('perPage', $request->get('per_page', 10));
        if ($perPage <= 0) {
            $perPage = 10;
        }
        $news = News::orderBy('date', 'desc')->paginate($perPage);

        $news->getCollection()->transform(function ($item) {
            return $this->normalizeNewsResponse($item);
        });

        return response()->json($news);
    }
    public function show(string $id)
    {
        $news = News::find($id);
        if (!$news) {
            return response()->json(['message' => 'News not found'], 404);
        }
        return response()->json($this->normalizeNewsResponse($news));
    }

    public function showBySlug(string $lang, string $slug)
    {
        $news = News::where("slug->{$lang}", $slug)->first();
        if (!$news) {
            return response()->json(['message' => 'News not found'], 404);
        }
        return response()->json($this->normalizeNewsResponse($news));
    }

    public function destroy(string $id)
    {
        $news = News::find($id);
        if (!$news) {
            return response()->json(['message' => 'News not found'], 404);
        }

        if ($news->image && Storage::disk('public')->exists($news->image)) {
            Storage::disk('public')->delete($news->image);
        }

        $news->delete();
        return response()->json(['message' => 'News deleted']);
    }
}