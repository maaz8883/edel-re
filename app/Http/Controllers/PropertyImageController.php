<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class PropertyImageController extends Controller
{
    private function processAndStoreImage(UploadedFile $file, string $directory): string
    {
        $filename = uniqid() . '_' . time() . '.webp';
        $path = $directory . '/' . $filename;

        Storage::disk('public')->makeDirectory($directory);

        $img = \Intervention\Image\Facades\Image::make($file)
            ->orientate()
            ->resize(1200, null, function ($constraint) {
                $constraint->aspectRatio();
                $constraint->upsize();
            })
            ->encode('webp', 70);

        Storage::disk('public')->put($path, (string) $img);

        return $path;
    }

    private function buildPublicStorageUrl(string $path): string
    {
        $url = Storage::disk('public')->url($path);

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return $url;
        }

        $appUrl = rtrim((string) config('app.url'), '/');
        return $appUrl !== '' ? $appUrl . $url : $url;
    }

    public function store(Request $request, string $id)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        $request->validate([
            'images' => 'required',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
        ]);

        $uploadedFiles = [];
        if ($request->hasFile('images')) {
            $files = $request->file('images');
            if (is_array($files)) {
                $uploadedFiles = $files;
            } else {
                $uploadedFiles[] = $files;
            }
        }

        if (empty($uploadedFiles)) {
            return response()->json(['message' => 'No images provided'], 400);
        }

        $existingImages = is_array($property->images) ? $property->images : [];
        $newImageUrls = [];

        foreach ($uploadedFiles as $file) {
            if ($file instanceof UploadedFile) {
                $path = $this->processAndStoreImage($file, 'properties');
                $newImageUrls[] = $this->buildPublicStorageUrl($path);
            }
        }

        // Add new images to existing ones (Merge them)
        $updatedImages = array_values(array_merge($existingImages, $newImageUrls));
        
        $property->images = $updatedImages;
        $property->save();

        return response()->json([
            'message' => 'Images added successfully',
            'images' => $property->images
        ]);
    }

    public function destroy(string $id, string $filename)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        $existingImages = is_array($property->images) ? $property->images : [];
        $updatedImages = [];
        $deleted = false;

        foreach ($existingImages as $imageUrl) {
            // Check if the current image URL ends with the requested filename
            if (Str::endsWith($imageUrl, $filename)) {
                // Delete from physical storage
                $path = 'properties/' . $filename;
                if (Storage::disk('public')->exists($path)) {
                    Storage::disk('public')->delete($path);
                }
                $deleted = true;
                // Skip adding it back to the new array
            } else {
                $updatedImages[] = $imageUrl;
            }
        }

        if (!$deleted) {
            return response()->json(['message' => 'Image not found in this property'], 404);
        }

        $property->images = array_values($updatedImages);
        $property->save();

        return response()->json([
            'message' => 'Image deleted successfully',
            'images' => $property->images
        ]);
    }
}
