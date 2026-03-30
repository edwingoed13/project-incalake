<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Tour;
use App\Models\TourTranslation;
use App\Models\Language;
use App\Models\AITranslationSetting;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TourCloneController extends Controller
{
    /**
     * Add a new translation to an existing tour (manual mode)
     */
    public function cloneManual(Request $request, int $id): JsonResponse
    {
        try {
            // Validate the request
            $validatedData = $request->validate([
                'language_id' => 'required|integer|exists:languages,id',
            ]);

            $tour = Tour::with(['translations'])->findOrFail($id);
            $targetLanguageId = $validatedData['language_id'];

            // Check if translation already exists
            $existingTranslation = $tour->translations()
                ->where('language_id', $targetLanguageId)
                ->first();

            if ($existingTranslation) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este tour ya tiene una traducción en el idioma seleccionado.'
                ], 400);
            }

            DB::beginTransaction();

            // Get the source translation (preferably in Spanish)
            $sourceTranslation = $tour->translations()
                ->where('language_id', 1) // Spanish
                ->first();

            if (!$sourceTranslation) {
                // If no Spanish translation, get any available
                $sourceTranslation = $tour->translations()->first();
            }

            if (!$sourceTranslation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una traducción base para clonar.'
                ], 400);
            }

            // Get the target language details
            $language = Language::find($targetLanguageId);

            // Create new translation based on the source
            $newTranslation = new TourTranslation();
            $newTranslation->tour_id = $tour->id;
            $newTranslation->language_id = $targetLanguageId;

            // Copy all fields but mark them for translation (respecting DB field limits)
            $newTranslation->slug = Str::slug('translate-' . Str::limit($sourceTranslation->h1_title, 50) . '-' . $language->code);
            $newTranslation->h1_title = Str::limit('[' . $language->code . '] ' . $sourceTranslation->h1_title, 255);
            // meta_title is limited to 60 chars in DB, so we need to be careful
            $newTranslation->meta_title = Str::limit($sourceTranslation->meta_title, 50) . ' [' . $language->code . ']';
            $newTranslation->meta_description = Str::limit('[' . $language->code . '] ' . $sourceTranslation->meta_description, 160);
            $newTranslation->short_description = Str::limit('[' . $language->code . '] ' . $sourceTranslation->short_description, 255);
            $newTranslation->long_description = '<p><strong>[Esta es una traducción pendiente al ' . $language->country . ']</strong></p>' . $sourceTranslation->long_description;
            $newTranslation->what_includes = $sourceTranslation->what_includes;
            $newTranslation->what_not_includes = $sourceTranslation->what_not_includes;
            $newTranslation->itinerary = $sourceTranslation->itinerary;
            $newTranslation->recommendations = $sourceTranslation->recommendations;
            $newTranslation->what_to_bring = $sourceTranslation->what_to_bring;
            $newTranslation->policies = $sourceTranslation->policies;
            $newTranslation->cancellation_policy = $sourceTranslation->cancellation_policy;

            // Copy SEO and marketing fields
            $newTranslation->canonical_url = $sourceTranslation->canonical_url;
            $newTranslation->og_title = $sourceTranslation->og_title;
            $newTranslation->og_description = $sourceTranslation->og_description;
            $newTranslation->og_image_path = $sourceTranslation->og_image_path;
            $newTranslation->twitter_title = $sourceTranslation->twitter_title;
            $newTranslation->twitter_description = $sourceTranslation->twitter_description;
            $newTranslation->twitter_image_path = $sourceTranslation->twitter_image_path;
            $newTranslation->cta_text = $sourceTranslation->cta_text ?? 'Book Now';
            $newTranslation->price_from_label = $sourceTranslation->price_from_label ?? 'From';
            $newTranslation->ads_headline = $sourceTranslation->ads_headline;
            $newTranslation->ads_description = $sourceTranslation->ads_description;

            // Copy new fields (youtube_url, booking_texts, media_texts)
            $newTranslation->youtube_url = $sourceTranslation->youtube_url;
            $newTranslation->booking_texts = $sourceTranslation->booking_texts;
            $newTranslation->media_texts = $sourceTranslation->media_texts;

            $newTranslation->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Traducción creada exitosamente. Ahora puedes editarla manualmente.',
                'data' => [
                    'tour_id' => $tour->id,
                    'translation_id' => $newTranslation->id,
                    'language' => $language->country,
                    'redirect_url' => '/admin/tours/' . $tour->id . '/edit?lang=' . $language->code
                ]
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error adding translation to tour', [
                'tour_id' => $id,
                'language_id' => $request->language_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la traducción al tour.',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Add a new translation to an existing tour with AI translation.
     * Sends ALL translatable text fields in a single AI call for efficiency.
     */
    public function cloneWithAI(Request $request, int $id): JsonResponse
    {
        try {
            $validatedData = $request->validate([
                'language_id' => 'required|integer|exists:languages,id',
            ]);

            $tour = Tour::with(['translations'])->findOrFail($id);
            $targetLanguageId = $validatedData['language_id'];

            // Check if translation already exists
            if ($tour->translations()->where('language_id', $targetLanguageId)->exists()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Este tour ya tiene una traducción en el idioma seleccionado.'
                ], 400);
            }

            DB::beginTransaction();

            // Get the source translation (prefer Spanish)
            $sourceTranslation = $tour->translations()->where('language_id', 1)->first()
                ?? $tour->translations()->first();

            if (!$sourceTranslation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una traducción base para traducir.'
                ], 400);
            }

            $language = Language::find($targetLanguageId);
            $targetLangName = $this->getLanguageNameForTranslation($language->code);

            // ── Collect ALL translatable text fields into one payload ──
            $fieldsToTranslate = $this->collectTranslatableFields($sourceTranslation);

            // ── Single AI call with all fields ──
            $translatedFields = $this->translateAllFieldsWithAI($fieldsToTranslate, $targetLangName);

            // ── Build new translation ──
            $newTranslation = new TourTranslation();
            $newTranslation->tour_id = $tour->id;
            $newTranslation->language_id = $targetLanguageId;

            // Generate unique slug from translated title
            $translatedTitle = $translatedFields['h1_title'] ?? $sourceTranslation->h1_title;
            $baseSlug = Str::slug(Str::limit($translatedTitle, 80));
            $slug = $baseSlug ?: Str::slug($sourceTranslation->h1_title . '-' . $language->code);
            $counter = 1;
            while (TourTranslation::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $language->code . '-' . $counter;
                $counter++;
            }
            $newTranslation->slug = $slug;

            // Assign translated text fields (mb_substr for strict DB column limits)
            $newTranslation->h1_title = mb_substr($translatedFields['h1_title'] ?? $sourceTranslation->h1_title ?? '', 0, 255);
            $newTranslation->meta_title = mb_substr($translatedFields['meta_title'] ?? $sourceTranslation->meta_title ?? '', 0, 60);
            $newTranslation->meta_description = mb_substr($translatedFields['meta_description'] ?? $sourceTranslation->meta_description ?? '', 0, 160);
            $newTranslation->short_description = mb_substr($translatedFields['short_description'] ?? $sourceTranslation->short_description ?? '', 0, 255);
            $newTranslation->long_description = $translatedFields['long_description'] ?? $sourceTranslation->long_description;
            $newTranslation->what_includes = $translatedFields['what_includes'] ?? $sourceTranslation->what_includes;
            $newTranslation->what_not_includes = $translatedFields['what_not_includes'] ?? $sourceTranslation->what_not_includes;
            $newTranslation->itinerary = $translatedFields['itinerary'] ?? $sourceTranslation->itinerary;
            $newTranslation->recommendations = $translatedFields['recommendations'] ?? $sourceTranslation->recommendations;
            $newTranslation->what_to_bring = $translatedFields['what_to_bring'] ?? $sourceTranslation->what_to_bring;
            $newTranslation->policies = $translatedFields['policies'] ?? $sourceTranslation->policies;
            $newTranslation->cancellation_policy = $translatedFields['cancellation_policy'] ?? $sourceTranslation->cancellation_policy;
            $newTranslation->cta_text = $translatedFields['cta_text'] ?? $sourceTranslation->cta_text ?? 'Book Now';
            $newTranslation->price_from_label = $translatedFields['price_from_label'] ?? $sourceTranslation->price_from_label ?? 'From';
            $newTranslation->og_title = $translatedFields['og_title'] ?? $sourceTranslation->og_title;
            $newTranslation->og_description = $translatedFields['og_description'] ?? $sourceTranslation->og_description;
            $newTranslation->twitter_title = $translatedFields['twitter_title'] ?? $sourceTranslation->twitter_title;
            $newTranslation->twitter_description = $translatedFields['twitter_description'] ?? $sourceTranslation->twitter_description;
            $newTranslation->ads_headline = $translatedFields['ads_headline'] ?? $sourceTranslation->ads_headline;
            $newTranslation->ads_description = $translatedFields['ads_description'] ?? $sourceTranslation->ads_description;
            $newTranslation->booking_texts = $translatedFields['booking_texts'] ?? $sourceTranslation->booking_texts;
            $newTranslation->media_texts = $translatedFields['media_texts'] ?? $sourceTranslation->media_texts;

            // ── Copy NON-translatable fields as-is (URLs, images, videos) ──
            $newTranslation->canonical_url = $sourceTranslation->canonical_url;
            $newTranslation->og_image_path = $sourceTranslation->og_image_path;
            $newTranslation->twitter_image_path = $sourceTranslation->twitter_image_path;
            $newTranslation->youtube_url = $sourceTranslation->youtube_url;

            $newTranslation->save();
            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Traducción creada y traducida con IA exitosamente.',
                'data' => [
                    'tour_id' => $tour->id,
                    'translation_id' => $newTranslation->id,
                    'language' => $language->country,
                    'translated_fields' => count($fieldsToTranslate),
                    'redirect_url' => '/admin/tours/' . $tour->id . '/edit?lang=' . $language->code
                ]
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error adding AI translation to tour', [
                'tour_id' => $id,
                'language_id' => $request->language_id ?? null,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al agregar la traducción con IA al tour.',
                'error' => config('app.debug') ? $e->getMessage() : 'Error interno del servidor'
            ], 500);
        }
    }

    /**
     * Collect all translatable text fields from a source translation.
     * Skips empty fields, URLs, image paths, and non-text content.
     */
    private function collectTranslatableFields(TourTranslation $source): array
    {
        $fields = [];

        // Simple text fields
        $textFields = [
            'h1_title', 'meta_title', 'meta_description', 'short_description',
            'long_description', 'cancellation_policy',
            'cta_text', 'price_from_label',
            'og_title', 'og_description', 'twitter_title', 'twitter_description',
            'ads_headline', 'ads_description',
        ];

        foreach ($textFields as $field) {
            $value = $source->$field;
            if (!empty($value) && is_string($value)) {
                $fields[$field] = $value;
            }
        }

        // Array fields (JSON) - convert to string for translation, will be parsed back
        $arrayFields = [
            'what_includes', 'what_not_includes', 'itinerary',
            'recommendations', 'what_to_bring', 'policies',
        ];

        foreach ($arrayFields as $field) {
            $value = $source->$field;
            if (!empty($value)) {
                // Arrays are cast automatically by Eloquent
                $fields[$field] = is_array($value) ? json_encode($value, JSON_UNESCAPED_UNICODE) : $value;
            }
        }

        // booking_texts - translate only text descriptions, not structure
        if (!empty($source->booking_texts)) {
            $bt = is_array($source->booking_texts) ? $source->booking_texts : json_decode($source->booking_texts, true);
            if ($bt) {
                $fields['booking_texts'] = json_encode($bt, JSON_UNESCAPED_UNICODE);
            }
        }

        // media_texts - translate image alt texts and captions
        if (!empty($source->media_texts)) {
            $mt = is_array($source->media_texts) ? $source->media_texts : json_decode($source->media_texts, true);
            if ($mt) {
                $fields['media_texts'] = json_encode($mt, JSON_UNESCAPED_UNICODE);
            }
        }

        return $fields;
    }

    /**
     * Send all fields to AI in a single request for translation.
     * Returns associative array with translated values.
     */
    private function translateAllFieldsWithAI(array $fields, string $targetLanguage): array
    {
        if (empty($fields)) {
            return [];
        }

        try {
            $settings = AITranslationSetting::where('is_active', true)->first();

            if (!$settings || empty($settings->api_key) || empty($settings->model)) {
                Log::warning('AI translation settings not configured, returning original text');
                return $fields;
            }

            // Build a structured prompt with all fields
            $systemPrompt = "You are a professional tourism content translator. Your task is to translate tour content to {$targetLanguage}.\n\n"
                . "RULES:\n"
                . "- Translate ONLY the text content, preserving the exact JSON structure\n"
                . "- Preserve ALL HTML tags exactly as they are (<p>, <strong>, <ul>, <li>, etc.)\n"
                . "- Do NOT translate: prices, numbers, currencies, URLs, email addresses, proper nouns of places (Cusco, Machu Picchu, etc.)\n"
                . "- Keep tourism-specific terminology natural in {$targetLanguage}\n"
                . "- For SEO fields (meta_title, meta_description): optimize for the target language audience\n"
                . "- For JSON array fields: translate the text values inside, keep the JSON structure valid\n"
                . "- Return ONLY a valid JSON object with the same keys, no extra text or markdown\n";

            if ($settings->custom_prompt) {
                $customPrompt = str_replace(
                    ['{target_language}', '{source_language}'],
                    [$targetLanguage, 'Spanish'],
                    $settings->custom_prompt
                );
                $systemPrompt .= "\nAdditional instructions: " . $customPrompt . "\n";
            }

            $userMessage = "Translate this JSON object to {$targetLanguage}. Return ONLY the translated JSON, no markdown fences:\n\n"
                . json_encode($fields, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);

            $result = match ($settings->provider) {
                'gemini' => $this->callGeminiBatch($systemPrompt, $userMessage, $settings),
                'openai' => $this->callOpenAIBatch($systemPrompt, $userMessage, $settings),
                'anthropic' => $this->callAnthropicBatch($systemPrompt, $userMessage, $settings),
                'deepseek' => $this->callDeepSeekBatch($systemPrompt, $userMessage, $settings),
                default => null,
            };

            if ($result) {
                // Parse the JSON response
                $translated = $this->parseAIJsonResponse($result);
                if ($translated && is_array($translated)) {
                    // Decode JSON array fields back to arrays
                    $arrayFields = ['what_includes', 'what_not_includes', 'itinerary',
                        'recommendations', 'what_to_bring', 'policies', 'booking_texts', 'media_texts'];

                    foreach ($arrayFields as $field) {
                        if (isset($translated[$field]) && is_string($translated[$field])) {
                            $decoded = json_decode($translated[$field], true);
                            if (json_last_error() === JSON_ERROR_NONE) {
                                $translated[$field] = $decoded;
                            }
                        }
                    }

                    return $translated;
                }
            }

            Log::warning('AI translation returned invalid response, using original text');
            return $fields;

        } catch (\Exception $e) {
            Log::error('Error in batch AI translation', [
                'error' => $e->getMessage(),
                'fields_count' => count($fields)
            ]);
            return $fields;
        }
    }

    /**
     * Parse AI response to extract JSON, handling markdown fences and extra text.
     */
    private function parseAIJsonResponse(string $response): ?array
    {
        $response = trim($response);

        // Remove markdown code fences if present
        if (preg_match('/```(?:json)?\s*\n?(.*?)\n?\s*```/s', $response, $matches)) {
            $response = trim($matches[1]);
        }

        // Try direct JSON parse
        $decoded = json_decode($response, true);
        if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
            return $decoded;
        }

        // Try to find first { ... } block
        if (preg_match('/\{.*\}/s', $response, $matches)) {
            $decoded = json_decode($matches[0], true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                return $decoded;
            }
        }

        Log::warning('Could not parse AI JSON response', ['response_preview' => Str::limit($response, 200)]);
        return null;
    }

    // ── AI Provider Methods (Batch - single call for all fields) ──

    private function callGeminiBatch(string $systemPrompt, string $userMessage, $settings): ?string
    {
        $response = Http::timeout(120)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$settings->model}:generateContent?key={$settings->api_key}",
            [
                'systemInstruction' => ['parts' => [['text' => $systemPrompt]]],
                'contents' => [['parts' => [['text' => $userMessage]]]],
                'generationConfig' => [
                    'temperature' => $settings->settings['temperature'] ?? 0.3,
                    'maxOutputTokens' => $settings->settings['max_tokens'] ?? 8000,
                    'responseMimeType' => 'application/json',
                ]
            ]
        );

        if ($response->successful()) {
            return $response->json('candidates.0.content.parts.0.text');
        }

        Log::error('Gemini batch API error', ['status' => $response->status(), 'body' => Str::limit($response->body(), 300)]);
        return null;
    }

    private function callOpenAIBatch(string $systemPrompt, string $userMessage, $settings): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->api_key,
        ])->timeout(120)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $settings->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => $settings->settings['temperature'] ?? 0.3,
            'max_tokens' => $settings->settings['max_tokens'] ?? 8000,
            'response_format' => ['type' => 'json_object'],
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        Log::error('OpenAI batch API error', ['status' => $response->status(), 'body' => Str::limit($response->body(), 300)]);
        return null;
    }

    private function callAnthropicBatch(string $systemPrompt, string $userMessage, $settings): ?string
    {
        $response = Http::withHeaders([
            'x-api-key' => $settings->api_key,
            'anthropic-version' => '2023-06-01',
        ])->timeout(120)->post('https://api.anthropic.com/v1/messages', [
            'model' => $settings->model,
            'system' => $systemPrompt,
            'messages' => [
                ['role' => 'user', 'content' => $userMessage],
            ],
            'max_tokens' => $settings->settings['max_tokens'] ?? 8000,
        ]);

        if ($response->successful()) {
            return $response->json('content.0.text');
        }

        Log::error('Anthropic batch API error', ['status' => $response->status(), 'body' => Str::limit($response->body(), 300)]);
        return null;
    }

    private function callDeepSeekBatch(string $systemPrompt, string $userMessage, $settings): ?string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->api_key,
        ])->timeout(120)->post('https://api.deepseek.com/v1/chat/completions', [
            'model' => $settings->model,
            'messages' => [
                ['role' => 'system', 'content' => $systemPrompt],
                ['role' => 'user', 'content' => $userMessage],
            ],
            'temperature' => $settings->settings['temperature'] ?? 0.3,
            'max_tokens' => $settings->settings['max_tokens'] ?? 8000,
        ]);

        if ($response->successful()) {
            return $response->json('choices.0.message.content');
        }

        Log::error('DeepSeek batch API error', ['status' => $response->status(), 'body' => Str::limit($response->body(), 300)]);
        return null;
    }

    // Old individual translate methods removed - replaced by batch call*Batch methods above

    /**
     * Get the language name for translation
     */
    private function getLanguageNameForTranslation(string $code): string
    {
        $languages = [
            'ES' => 'Español',
            'EN' => 'English',
            'PT' => 'Português',
            'FR' => 'Français',
            'DE' => 'Deutsch',
            'IT' => 'Italiano',
            'RU' => 'Русский',
            'CN' => '中文',
            'JP' => '日本語',
            'KR' => '한국어'
        ];

        return $languages[strtoupper($code)] ?? $code;
    }

    /**
     * Delete a specific translation from a tour.
     * If it's the last translation, deletes the entire tour.
     */
    public function deleteTranslation(int $id, int $languageId): JsonResponse
    {
        try {
            $tour = Tour::with('translations')->findOrFail($id);

            $translation = TourTranslation::where('tour_id', $id)
                ->where('language_id', $languageId)
                ->first();

            if (!$translation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró la traducción para ese idioma.',
                ], 404);
            }

            // If this is the only translation, delete the entire tour
            if ($tour->translations->count() <= 1) {
                $tour->delete();
                return response()->json([
                    'success' => true,
                    'message' => 'Era la última traducción. El tour completo ha sido eliminado.',
                    'tour_deleted' => true,
                ]);
            }

            // Delete just the translation
            $translation->delete();

            // If we deleted the primary language, update it to the first remaining translation
            if ($tour->primary_language_id === $languageId) {
                $remaining = TourTranslation::where('tour_id', $id)->first();
                if ($remaining) {
                    $tour->update(['primary_language_id' => $remaining->language_id]);
                }
            }

            return response()->json([
                'success' => true,
                'message' => 'Traducción eliminada correctamente.',
                'tour_deleted' => false,
            ]);
        } catch (\Exception $e) {
            Log::error("Error deleting translation", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar la traducción: ' . $e->getMessage(),
            ], 500);
        }
    }
}