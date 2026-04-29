<?php

namespace App\Http\Controllers;

use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;


class PropertiesController extends Controller
{

    private function normalizeTranslatedListForResponse($items): array
    {
        if (!is_array($items)) {
            return [];
        }

        $normalized = [];

        // Legacy shape: { "de": [...], "en": [...] }
        if (isset($items['de']) && is_array($items['de'])) {
            $deArray = $items['de'];
            $enArray = (isset($items['en']) && is_array($items['en'])) ? $items['en'] : [];

            foreach ($deArray as $index => $deValue) {
                if (!is_scalar($deValue) || $deValue === '') {
                    continue;
                }

                $enValue = $enArray[$index] ?? $deValue;
                $normalized[] = [
                    'en' => (string) $enValue,
                    'de' => (string) $deValue,
                ];
            }

            return $normalized;
        }

        // New shape: [ { "de": "...", "en": "..." }, ... ]
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }

            $deValue = $item['de'] ?? '';
            if (!is_scalar($deValue) || $deValue === '') {
                continue;
            }

            $enValue = $item['en'] ?? $deValue;
            $normalized[] = [
                'en' => (string) $enValue,
                'de' => (string) $deValue,
            ];
        }

        return $normalized;
    }

    private function normalizePropertyListsForResponse(Property $property): Property
    {
        foreach (['equipment', 'criteria', 'modernization'] as $field) {
            $property->{$field} = $this->normalizeTranslatedListForResponse($property->{$field});
        }

        $property->images = $this->sanitizeImageValues($property->images ?? []);

        if (is_array($property->agent)) {
            $agent = $property->agent;
            $agent['image'] = $this->sanitizeAgentMediaValue($agent['image'] ?? null);
            $agent['logo'] = $this->sanitizeAgentMediaValue($agent['logo'] ?? null);
            $property->agent = $agent;
        }

        if (is_string($property->floor_plan) && trim($property->floor_plan) !== '') {
            $property->floor_plan = [
                'image' => $this->sanitizeSingleUrl(trim($property->floor_plan))
            ];
        }

        return $property;
    }

    private function extractImageFiles(Request $request): array
    {
        $candidates = [
            $request->file('images'),
            $request->file('images[]'),
        ];

        $flatFiles = [];

        foreach ($candidates as $candidate) {
            if ($candidate instanceof UploadedFile) {
                $flatFiles[] = $candidate;
                continue;
            }

            if (is_array($candidate)) {
                array_walk_recursive($candidate, function ($file) use (&$flatFiles) {
                    if ($file instanceof UploadedFile) {
                        $flatFiles[] = $file;
                    }
                });
            }
        }

        // Fallback: scan all uploaded files and pick image keys
        if (empty($flatFiles)) {
            foreach ($request->allFiles() as $key => $value) {
                if (!in_array($key, ['images', 'images[]'], true)) {
                    continue;
                }

                if ($value instanceof UploadedFile) {
                    $flatFiles[] = $value;
                } elseif (is_array($value)) {
                    array_walk_recursive($value, function ($file) use (&$flatFiles) {
                        if ($file instanceof UploadedFile) {
                            $flatFiles[] = $file;
                        }
                    });
                }
            }
        }

        return $flatFiles;
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

    private function sanitizeImageValues($images): array
    {
        if (!is_array($images)) {
            return [];
        }

        $flattened = [];
        array_walk_recursive($images, function ($value) use (&$flattened) {
            if (is_string($value)) {
                $trimmed = trim($value);
                if ($trimmed !== '') {
                    $flattened[] = $this->sanitizeSingleUrl($trimmed);
                }
            }
        });

        return array_values($flattened);
    }

    private function sanitizeSingleUrl(string $url): string
    {
        $parts = preg_split('/https?:\/\//i', $url, -1, PREG_SPLIT_NO_EMPTY);
        if (count($parts) > 1) {
            $last = end($parts);
            return str_starts_with(strtolower($url), 'https://')
                ? 'https://' . ltrim($last, '/')
                : 'http://' . ltrim($last, '/');
        }

        return $url;
    }

    private function sanitizeAgentMediaValue($value): ?string
    {
        if (is_string($value) && trim($value) !== '') {
            $url = $this->sanitizeSingleUrl(trim($value));
            if (Str::startsWith($url, ['http://', 'https://', 'public/storage/'])) {
                return $url;
            }
            return null;
        }

        if (is_array($value)) {
            foreach ($value as $candidate) {
                if (is_string($candidate) && trim($candidate) !== '') {
                    return $this->sanitizeSingleUrl(trim($candidate));
                }
            }
        }

        return null;
    }

    private function extractSingleNestedFile(Request $request, string $key): ?UploadedFile
    {
        $file = $request->file($key);
        if ($file instanceof UploadedFile) {
            return $file;
        }

        if (is_array($file)) {
            foreach ($file as $candidate) {
                if ($candidate instanceof UploadedFile) {
                    return $candidate;
                }
            }
        }

        return null;
    }

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
            ->encode('webp', 80);

        Storage::disk('public')->put($path, (string) $img);

        return $path;
    }

    private function handlePropertyLogic(Request $request, array $data)
    {
        $autoStatus = filter_var($request->input('auto_status', false), FILTER_VALIDATE_BOOLEAN);

        // Boolean → TinyInt
        $data['auto_status'] = $autoStatus ? 1 : 0;

        $listFields = ['equipment', 'criteria', 'modernization'];

        foreach ($listFields as $field) {

            if (!$request->has($field)) {
                continue;
            }

            $items = $request->input($field);
            $transformed = [];

            // Case 1: { "de": [...], "en": [...] } format
            if (is_array($items) && isset($items['de']) && is_array($items['de'])) {
                $deArray = $items['de'];
                $enArray = $items['en'] ?? [];

                foreach ($deArray as $index => $de) {
                    $en = $enArray[$index] ?? '';

                    if ($autoStatus && $de && !$en) {
                        //translationDelayed
                        // $en = $this->translateText($de);
                    }

                    if ($de) {
                        $transformed[] = [
                            'de' => $de,
                            'en' => $en ?: $de,
                        ];
                    }
                }
            } else {
                // Case 2: Already array of objects [ { "de": "...", "en": "..." }, ... ]
                foreach ($items as $item) {
                    if (is_array($item)) {
                        $de = $item['de'] ?? '';
                        $en = $item['en'] ?? '';
                    } else {
                        $decoded = json_decode($item, true);
                        $de = $decoded['de'] ?? $item;
                        $en = $decoded['en'] ?? '';
                    }

                    if ($autoStatus && $de && !$en) {
                        //translationDelayed
                        // $en = $this->translateText($de);
                    }

                    if ($de) {
                        $transformed[] = [
                            'de' => $de,
                            'en' => $en ?: $de,
                        ];
                    }
                }
            }

            $data[$field] = $transformed;
        }

        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value['de'])) {

                if ($autoStatus && !empty($value['de']) && empty($value['en'])) {
                    //    translationdelayed
                    // $data[$key]['en'] = $this->translateText($value['de']);
                }

                if (empty($data[$key]['en'])) {
                    $data[$key]['en'] = $value['de'];
                }
            }
        }

        $existingImages = $this->sanitizeImageValues($data['images'] ?? []);
        $uploadedFiles = $this->extractImageFiles($request);

        if (!empty($uploadedFiles)) {
            $uploadedImageUrls = [];

            foreach ($uploadedFiles as $file) {
                $path = $this->processAndStoreImage($file, 'properties');
                $uploadedImageUrls[] = $this->buildPublicStorageUrl($path);
            }

            // For update: keep old images + append newly uploaded ones
            $data['images'] = array_values(array_merge($existingImages, $uploadedImageUrls));
        } else {
            $data['images'] = $existingImages;
        }

        $agent = is_array($data['agent'] ?? null) ? $data['agent'] : [];
        $agentImageFile = $this->extractSingleNestedFile($request, 'agent.image');
        $agentLogoFile = $this->extractSingleNestedFile($request, 'agent.logo');

        if ($agentImageFile instanceof UploadedFile) {
            $path = $this->processAndStoreImage($agentImageFile, 'agents');
            $agent['image'] = $this->buildPublicStorageUrl($path);
        } else {
            $agent['image'] = $this->sanitizeAgentMediaValue($agent['image'] ?? null);
        }

        if ($agentLogoFile instanceof UploadedFile) {
            $path = $this->processAndStoreImage($agentLogoFile, 'agents');
            $agent['logo'] = $this->buildPublicStorageUrl($path);
        } else {
            $agent['logo'] = $this->sanitizeAgentMediaValue($agent['logo'] ?? null);
        }

        $data['agent'] = $agent;

        $floorPlanFile = $this->extractSingleNestedFile($request, 'floorPlan.image')
            ?? $this->extractSingleNestedFile($request, 'floorPlan')
            ?? $this->extractSingleNestedFile($request, 'floor_plan');

        if ($floorPlanFile instanceof UploadedFile) {
            $path = $this->processAndStoreImage($floorPlanFile, 'floor-plans');
            $data['floor_plan'] = $this->buildPublicStorageUrl($path);
        } elseif (isset($data['floor_plan']) && is_string($data['floor_plan'])) {
            $existingFloorPlan = trim($data['floor_plan']);
            if ($existingFloorPlan !== '') {
                $cleanFloorPlan = $this->sanitizeSingleUrl($existingFloorPlan);
                $data['floor_plan'] = Str::startsWith($cleanFloorPlan, ['http://', 'https://', '/storage/'])
                    ? $cleanFloorPlan
                    : null;
            } else {
                $data['floor_plan'] = null;
            }
        }



        return $data;
    }
    //translationDelayed
    // private function translateText($text)
    // {
    //     try {
    //         $response = Http::timeout(3)->get("https://lingva.ml/api/v1/de/en/" . urlencode($text));
    //         if ($response->successful()) {
    //             $translated = $response->json()['translation'] ?? $text;
    //             return urldecode(str_replace('+', ' ', $translated));
    //         }
    //     } catch (\Exception $e) {
    //         return $text;
    //     }
    //     return $text;
    // }
    public function index(Request $request)
    {
        $query = Property::with([
            'type', 'status', 'region', 'objectType', 'objectCondition',
            'heatingType', 'energyCertificate', 'energyCarrier', 'equipmentLevel'
        ]);

        // Basic ID Filters (handle both camelCase and snake_case)
        if ($request->filled('region_id') || $request->filled('regionId')) {
            $query->where('region_id', $request->input('region_id') ?? $request->input('regionId'));
        }

        if ($request->filled('type_id') || $request->filled('typeId')) {
            $query->where('type_id', $request->input('type_id') ?? $request->input('typeId'));
        }

        if ($request->filled('status_id') || $request->filled('statusId')) {
            $query->where('status_id', $request->input('status_id') ?? $request->input('statusId'));
        }

        if ($request->filled('object_type_id') || $request->filled('objectTypeId')) {
            $query->where('object_type_id', $request->input('object_type_id') ?? $request->input('objectTypeId'));
        }

        // Title Search
        if ($request->filled('title')) {
            $searchTerm = $request->input('title');
            $query->where(function($q) use ($searchTerm) {
                $q->where('title->en', 'like', "%{$searchTerm}%")
                  ->orWhere('title->de', 'like', "%{$searchTerm}%");
            });
        }

        // Price & Area Filters
        if ($request->filled('minPrice')) $query->where('price', '>=', $request->input('minPrice'));
        if ($request->filled('maxPrice')) $query->where('price', '<=', $request->input('maxPrice'));
        if ($request->filled('minLivingArea')) $query->where('living_area', '>=', $request->input('minLivingArea'));
        if ($request->filled('maxLivingArea')) $query->where('living_area', '<=', $request->input('maxLivingArea'));
        if ($request->filled('minRoom')) $query->where('total_rooms', '>=', $request->input('minRoom'));
        if ($request->filled('maxRoom')) $query->where('total_rooms', '<=', $request->input('maxRoom'));

        // Multi-ID Filters (kriterien, konditionen, etc.)
        foreach (['kriterien' => 'criteria_selected', 'konditionen' => 'condition_search', 'stockwerk' => 'floor_info'] as $param => $column) {
            if ($request->filled($param)) {
                $ids = explode(',', (string)$request->input($param));
                $query->where(function($q) use ($column, $ids) {
                    foreach ($ids as $id) {
                        $q->orWhereJsonContains($column, $id);
                    }
                });
            }
        }

        $perPage = $request->input('perPage', 10);
        $properties = $query->latest()->paginate($perPage);

        $properties->getCollection()->transform(function (Property $property) {
            return $this->normalizePropertyListsForResponse($property);
        });

        return response()->json($properties);
    }

    public function store(Request $request)
    {
        $request->merge([
            'type_id' => $request->input('typeId', $request->input('type_id')),
            'status_id' => $request->input('statusId', $request->input('status_id')),
            'region_id' => $request->input('regionId', $request->input('region_id')),
            'living_area' => $request->input('livingArea', $request->input('living_area')),
            'price' => $request->input('price'),
            'search_price' => $request->input('searchPrice'),
            'search_sale_price' => $request->input('searchSalePrice'),
            'price_suffix' => $request->input('priceSuffix', []),
            'floor_types' => $request->input('floorTypes', []),
            'room_count_search' => $request->input('roomCountSearch'),
            'condition_search' => $request->input('conditionSearch', []),
            'purchase_price' => $request->input('purchasePrice'),
            'object_type_id' => $request->input('objectTypeId'),
            'total_rooms' => $request->input('totalRooms'),
            'usable_area' => $request->input('usableArea'),
            'floor_info' => $request->input('floorInfo', []),
            'year_built' => $request->input('yearBuilt'),
            'available_from' => $request->input('availableFrom', []),
            'object_condition_id' => $request->input('objectConditionId'),
            'modernization' => $request->input('modernization', []),
            'heating_type_id' => $request->input('heatingTypeId'),
            'energy_certificate_id' => $request->input('energyCertificateId'),
            'energy_cert_type_id' => $request->input('energyCertTypeId'),
            'energy_carrier_id' => $request->input('energyCarrierId'),
            'equipment_level_id' => $request->input('equipmentLevelId'),
            'address' => $request->input('address', []),
            'location_query' => $request->input('locationQuery', []),
            'postal_code' => $request->input('postalCode', []),
            'latitude' => $request->input('latitude'),
            'longitude' => $request->input('longitude'),
            'overview' => $request->input('overview', []),
            'description' => $request->input('description', []),
            'equipment' => $request->input('equipment', []),
            'criteria' => $request->input('criteria', []),
            'location_description' => $request->input('locationDescription', []),
            'lage_rich' => $request->input('lageRich', []),
            'equipment_description_rich' => $request->input('equipmentDescriptionRich', []),
            'call_to_action_rich' => $request->input('callToActionRich', []),
            'criteria_selected' => $request->input('criteriaSelected', []),
            'agent' => $request->input('agent', []),
            'auto_status' => $request->input('auto_status', $request->input('auto_status', false)),
        ]);

        $request->validate([
            'title' => 'required|array',
            'price' => 'required|numeric',
            'living_area' => 'required|numeric',
            'images' => 'sometimes',
            'images[]' => 'sometimes',
            'images.*' => 'image|mimes:jpeg,png,jpg,webp|max:5120',
            'agent.image' => 'sometimes|file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'agent.logo' => 'sometimes|file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'floorPlan.image' => 'sometimes|file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'floorPlan' => 'sometimes|file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'floor_plan' => 'sometimes|file|image|mimes:jpeg,png,jpg,webp|max:5120',
            'slug' => 'sometimes|array',
        ]);

        $data = $this->handlePropertyLogic($request, $request->all());

        $property = Property::create($data);

        $property = Property::with([
            'type', 'status', 'region', 'objectType', 'objectCondition',
            'heatingType', 'energyCertificate', 'energyCarrier', 'equipmentLevel'
        ])->find($property->id);

        $property = $this->normalizePropertyListsForResponse($property);
        return response()->json($property, 201);
    }

    public function update(Request $request, string $id)
    {
        $property = Property::find($id);

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }

        $inputData = $request->all();
        $existingData = $property->toArray();

        foreach ($inputData as $key => $value) {
            if (is_array($value) && isset($existingData[$key]) && is_array($existingData[$key])) {
                // Check if it's an associative array (like ['en' => '...', 'de' => '...'])
                $isAssoc = count(array_filter(array_keys($value), 'is_string')) > 0;
                if ($isAssoc) {
                    $inputData[$key] = array_merge($existingData[$key], $value);
                }
            }
        }

        $mergedData = array_merge($existingData, $inputData);
        $data = $this->handlePropertyLogic($request, $mergedData);

        $property->update($data);

        $property->load([
            'type', 'status', 'region', 'objectType', 'objectCondition',
            'heatingType', 'energyCertificate', 'energyCarrier', 'equipmentLevel'
        ]);

        $property = $this->normalizePropertyListsForResponse($property);
        return response()->json($property);
    }

    public function show(string $id)
    {
        $property = Property::with([
            'type', 'status', 'region', 'objectType', 'objectCondition',
            'heatingType', 'energyCertificate', 'energyCarrier', 'equipmentLevel'
        ])->find($id);

        if (!$property)
            return response()->json(['message' => 'Not found'], 404);
        $property = $this->normalizePropertyListsForResponse($property);
        return response()->json($property);
    }

    public function showBySlug(string $lang, string $slug)
    {
        $property = Property::with([
            'type', 'status', 'region', 'objectType', 'objectCondition',
            'heatingType', 'energyCertificate', 'energyCarrier', 'equipmentLevel'
        ])->where('slug->' . $lang, $slug)->first();

        if (!$property)
            return response()->json(['message' => 'Property not found'], 404);
        $property = $this->normalizePropertyListsForResponse($property);
        return response()->json($property);
    }

    public function destroy(string $id)
    {
        $property = Property::find($id);
        if (!$property)
            return response()->json(['message' => 'Not found'], 404);

        $property->delete();
        return response()->json(['message' => 'Property deleted successfully']);
    }
}
