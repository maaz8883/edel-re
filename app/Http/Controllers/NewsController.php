<?php

namespace App\Http\Controllers;

use App\Models\News;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class NewsController extends Controller
{
    public function index(Request $request)
    {
        $perPage = (int) $request->get('perPage', 10);

        $news = News::orderBy('date', 'desc')->paginate($perPage);

        $news->getCollection()->transform(function ($item) {
            $item->image_url = $item->image
                ? asset('storage/' . $item->image)
                : null;
            return $item;
        });

        return response()->json($news);
    }

    public function show(string $id)
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json(['message' => 'News not found'], 404);
        }

        $news->image_url = $news->image
            ? asset('storage/' . $news->image)
            : null;

        return response()->json($news);
    }

    public function showBySlug(string $lang, string $slug)
    {
        $news = News::whereJsonContains("slug->{$lang}", $slug)->first();

        if (!$news) {
            return response()->json(['message' => 'News not found'], 404);
        }

        $news->image_url = $news->image
            ? asset('storage/' . $news->image)
            : null;

        return response()->json($news);
    }

    public function store(Request $request)
    {
        $request->validate([
            'title'             => 'required|array',
            'slug'              => 'required|array',
            'short_description' => 'required|array',
            'description'       => 'required|array',
            'image'             => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        $imagePath = null;

        // ✅ SINGLE IMAGE UPLOAD
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('news', 'public');
        }

        $news = News::create([
            'title'             => $request->title,
            'slug'              => $request->slug,
            'short_description' => $request->short_description,
            'description'       => $request->description,
            'image'             => $imagePath,
            'date'              => $request->date ?? now()->toDateString(),
        ]);

        $news->image_url = $imagePath
            ? asset('storage/' . $imagePath)
            : null;

        return response()->json($news, 201);
    }

    public function update(Request $request, string $id)
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json(['message' => 'News not found'], 404);
        }

        $request->validate([
            'image' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);

        // ✅ UPDATE IMAGE
        if ($request->hasFile('image')) {

            // delete old image
            if ($news->image && Storage::disk('public')->exists($news->image)) {
                Storage::disk('public')->delete($news->image);
            }

            // store new image
            $news->image = $request->file('image')->store('news', 'public');
        }

        $news->update($request->only([
            'title',
            'slug',
            'short_description',
            'description',
            'date',
        ]));

        $news->image_url = $news->image
            ? asset('storage/' . $news->image)
            : null;

        return response()->json($news);
    }

    public function destroy(string $id)
    {
        $news = News::find($id);

        if (!$news) {
            return response()->json(['message' => 'News not found'], 404);
        }

        // delete image
        if ($news->image && Storage::disk('public')->exists($news->image)) {
            Storage::disk('public')->delete($news->image);
        }

        $news->delete();

        return response()->json(['message' => 'News deleted']);
    }
}