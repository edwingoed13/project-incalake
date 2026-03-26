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

            // Copy other SEO and marketing fields
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
     * Add a new translation to an existing tour with AI translation
     */
    public function cloneWithAI(Request $request, int $id): JsonResponse
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

            // Get the source translation
            $sourceTranslation = $tour->translations()
                ->where('language_id', 1) // Spanish
                ->first();

            if (!$sourceTranslation) {
                $sourceTranslation = $tour->translations()->first();
            }

            if (!$sourceTranslation) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontró una traducción base para traducir.'
                ], 400);
            }

            // Get the target language details
            $language = Language::find($targetLanguageId);
            $targetLangName = $this->getLanguageNameForTranslation($language->code);

            // Create new translation with AI-translated content
            $newTranslation = new TourTranslation();
            $newTranslation->tour_id = $tour->id;
            $newTranslation->language_id = $targetLanguageId;

            // Translate content using AI (placeholder implementation) with field limits
            $translatedTitle = $this->translateWithAI($sourceTranslation->h1_title, $targetLangName);

            // Generate unique slug to avoid duplicates
            $baseSlug = Str::slug(Str::limit($translatedTitle, 80));
            $slug = $baseSlug;
            $counter = 1;

            // Check if slug exists and make it unique
            while (TourTranslation::where('slug', $slug)->exists()) {
                $slug = $baseSlug . '-' . $language->code . '-' . $counter;
                $counter++;
            }

            $newTranslation->slug = $slug;
            $newTranslation->h1_title = Str::limit($translatedTitle, 255);
            $newTranslation->meta_title = Str::limit($this->translateWithAI($sourceTranslation->meta_title, $targetLangName), 60);
            $newTranslation->meta_description = Str::limit($this->translateWithAI($sourceTranslation->meta_description, $targetLangName), 160);
            $newTranslation->short_description = $this->translateWithAI($sourceTranslation->short_description, $targetLangName);
            $newTranslation->long_description = $this->translateWithAI($sourceTranslation->long_description, $targetLangName);
            $newTranslation->what_includes = $this->translateWithAI($sourceTranslation->what_includes, $targetLangName);
            $newTranslation->what_not_includes = $this->translateWithAI($sourceTranslation->what_not_includes, $targetLangName);
            $newTranslation->itinerary = $this->translateWithAI($sourceTranslation->itinerary, $targetLangName);
            $newTranslation->recommendations = $this->translateWithAI($sourceTranslation->recommendations, $targetLangName);
            $newTranslation->what_to_bring = $this->translateWithAI($sourceTranslation->what_to_bring, $targetLangName);
            $newTranslation->policies = $this->translateWithAI($sourceTranslation->policies, $targetLangName);
            $newTranslation->cancellation_policy = $this->translateWithAI($sourceTranslation->cancellation_policy, $targetLangName);

            // Translate marketing fields
            $newTranslation->cta_text = $this->translateWithAI($sourceTranslation->cta_text ?? 'Reservar Ahora', $targetLangName);
            $newTranslation->price_from_label = $this->translateWithAI($sourceTranslation->price_from_label ?? 'Desde', $targetLangName);

            // Copy non-translatable fields
            $newTranslation->canonical_url = $sourceTranslation->canonical_url;
            $newTranslation->og_title = $this->translateWithAI($sourceTranslation->og_title, $targetLangName);
            $newTranslation->og_description = $this->translateWithAI($sourceTranslation->og_description, $targetLangName);
            $newTranslation->og_image_path = $sourceTranslation->og_image_path;
            $newTranslation->twitter_title = $this->translateWithAI($sourceTranslation->twitter_title, $targetLangName);
            $newTranslation->twitter_description = $this->translateWithAI($sourceTranslation->twitter_description, $targetLangName);
            $newTranslation->twitter_image_path = $sourceTranslation->twitter_image_path;
            $newTranslation->ads_headline = $this->translateWithAI($sourceTranslation->ads_headline, $targetLangName);
            $newTranslation->ads_description = $this->translateWithAI($sourceTranslation->ads_description, $targetLangName);

            $newTranslation->save();

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Traducción creada y traducida con IA exitosamente.',
                'data' => [
                    'tour_id' => $tour->id,
                    'translation_id' => $newTranslation->id,
                    'language' => $language->country,
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
     * Translate text using AI service
     */
    private function translateWithAI(string $text, string $targetLanguage): string
    {
        if (empty($text)) {
            return $text;
        }

        try {
            // Get AI translation settings
            $settings = AITranslationSetting::where('is_active', true)->first();

            if (!$settings || empty($settings->api_key) || empty($settings->model)) {
                Log::warning('AI translation settings not configured');
                return $text; // Return original text if not configured
            }

            // Build the translation prompt
            $prompt = $settings->custom_prompt ??
                "You are a professional tourism translator. Translate the following tour content to {$targetLanguage}. " .
                "Maintain the tone, SEO optimization, and tourism-specific terminology. Preserve HTML tags and formatting.";

            $prompt = str_replace('{target_language}', $targetLanguage, $prompt);
            $prompt = str_replace('{source_language}', 'Spanish', $prompt);
            $prompt = str_replace('{content}', $text, $prompt);

            // Full prompt
            $fullPrompt = $prompt . "\n\nContent to translate:\n" . $text;

            // Call AI provider based on configuration
            switch ($settings->provider) {
                case 'gemini':
                    return $this->translateWithGemini($fullPrompt, $settings);
                case 'openai':
                    return $this->translateWithOpenAI($fullPrompt, $settings);
                case 'anthropic':
                    return $this->translateWithAnthropic($fullPrompt, $settings);
                case 'deepseek':
                    return $this->translateWithDeepSeek($fullPrompt, $settings);
                default:
                    Log::warning('Unknown AI provider: ' . $settings->provider);
                    return $text;
            }
        } catch (\Exception $e) {
            Log::error('Error translating with AI', [
                'error' => $e->getMessage(),
                'text_preview' => Str::limit($text, 100)
            ]);
            return $text; // Return original text on error
        }
    }

    private function translateWithGemini(string $prompt, $settings): string
    {
        $apiVersion = 'v1beta';

        $response = Http::timeout(60)->post(
            "https://generativelanguage.googleapis.com/{$apiVersion}/models/{$settings->model}:generateContent?key={$settings->api_key}",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $prompt]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => $settings->settings['temperature'] ?? 0.3,
                    'maxOutputTokens' => $settings->settings['max_tokens'] ?? 4000
                ]
            ]
        );

        if ($response->successful()) {
            $translatedText = $response->json('candidates.0.content.parts.0.text');
            // Return translated text if available, otherwise return empty string to avoid returning the prompt
            return $translatedText ?: '';
        }

        Log::error('Gemini API error', ['response' => $response->body()]);
        // Return empty string instead of prompt when error occurs
        return '';
    }

    private function translateWithOpenAI(string $prompt, $settings): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->api_key,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $settings->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $settings->settings['temperature'] ?? 0.3,
            'max_tokens' => $settings->settings['max_tokens'] ?? 4000
        ]);

        if ($response->successful()) {
            $translatedText = $response->json('choices.0.message.content');
            return $translatedText ?: '';
        }

        Log::error('OpenAI API error', ['response' => $response->body()]);
        return '';
    }

    private function translateWithAnthropic(string $prompt, $settings): string
    {
        $response = Http::withHeaders([
            'x-api-key' => $settings->api_key,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $settings->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'max_tokens' => $settings->settings['max_tokens'] ?? 4000
        ]);

        if ($response->successful()) {
            $translatedText = $response->json('content.0.text');
            return $translatedText ?: '';
        }

        Log::error('Anthropic API error', ['response' => $response->body()]);
        return '';
    }

    private function translateWithDeepSeek(string $prompt, $settings): string
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $settings->api_key,
            'Content-Type' => 'application/json',
        ])->timeout(60)->post('https://api.deepseek.com/v1/chat/completions', [
            'model' => $settings->model,
            'messages' => [
                ['role' => 'user', 'content' => $prompt]
            ],
            'temperature' => $settings->settings['temperature'] ?? 0.3,
            'max_tokens' => $settings->settings['max_tokens'] ?? 4000
        ]);

        if ($response->successful()) {
            $translatedText = $response->json('choices.0.message.content');
            return $translatedText ?: '';
        }

        Log::error('DeepSeek API error', ['response' => $response->body()]);
        return '';
    }

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
}