<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class TranslateController extends Controller
{
    public function translate(Request $request)
    {
        $request->validate([
            'text' => 'required|string',
            'from' => 'sometimes|string|max:2',
            'to' => 'sometimes|string|max:2'
        ]);

        // URL encode sirf bhejte waqt chahiye
        $text = urlencode($request->text);
        $from = $request->input('from', 'de');
        $to = $request->input('to', 'en');

        // API Call
        $response = Http::get("https://lingva.ml/api/v1/{$from}/{$to}/{$text}");

        if ($response->failed()) {
            return response()->json([
                'error' => 'Translation failed',
                'details' => 'The translation service is currently unavailable.'
            ], 500);
        }

        $data = $response->json();
        $translated = $data['translation'] ?? null;

        if ($translated) {

            $translated = str_replace('+', ' ', $translated);


            $translated = urldecode($translated);


            $translated = htmlspecialchars_decode($translated);
        }

        return response()->json([
            'original' => $request->text,
            'translated' => $translated,
            'source_language' => $from,
            'target_language' => $to
        ]);
    }
}