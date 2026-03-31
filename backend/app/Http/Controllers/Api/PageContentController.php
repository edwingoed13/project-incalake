<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PageContent;
use App\Models\Language;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class PageContentController extends Controller
{
    /**
     * Get page content by page name and language.
     * Public endpoint for the frontend.
     * GET /api/pages/{page}?language=ES
     */
    public function show(string $page, Request $request): JsonResponse
    {
        $langCode = strtoupper($request->language ?? 'ES');
        $language = Language::where('code', $langCode)->first();

        if (!$language) {
            return response()->json(['success' => false, 'message' => 'Language not found.'], 404);
        }

        $content = PageContent::where('page', $page)
            ->where('language_id', $language->id)
            ->where('published', true)
            ->first();

        // Fallback to Spanish if not found
        if (!$content && $langCode !== 'ES') {
            $esLang = Language::where('code', 'ES')->first();
            if ($esLang) {
                $content = PageContent::where('page', $page)
                    ->where('language_id', $esLang->id)
                    ->where('published', true)
                    ->first();
            }
        }

        return response()->json([
            'success' => true,
            'data' => $content ? [
                'id' => $content->id,
                'page' => $content->page,
                'language_id' => $content->language_id,
                'language_code' => $language->code,
                'content' => $content->content,
                'published' => $content->published,
            ] : null,
        ]);
    }

    /**
     * Get all translations for a page (admin).
     * GET /api/admin/pages/{page}
     */
    public function index(string $page): JsonResponse
    {
        $contents = PageContent::where('page', $page)
            ->with('language')
            ->get()
            ->map(function ($item) {
                return [
                    'id' => $item->id,
                    'language_id' => $item->language_id,
                    'language_code' => $item->language->code ?? null,
                    'language_country' => $item->language->country ?? null,
                    'content' => $item->content,
                    'published' => $item->published,
                    'updated_at' => $item->updated_at,
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $contents,
        ]);
    }

    /**
     * Save/update page content for a specific language.
     * PUT /api/admin/pages/{page}
     */
    public function update(string $page, Request $request): JsonResponse
    {
        $request->validate([
            'language_id' => 'required|integer|exists:languages,id',
            'content' => 'required|array',
            'published' => 'boolean',
        ]);

        try {
            $content = PageContent::updateOrCreate(
                ['page' => $page, 'language_id' => $request->language_id],
                [
                    'content' => $request->content,
                    'published' => $request->published ?? true,
                ]
            );

            return response()->json([
                'success' => true,
                'message' => 'Content saved successfully.',
                'data' => $content,
            ]);
        } catch (\Exception $e) {
            Log::error("Error saving page content", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error saving content: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Translate page content to a new language using AI.
     * POST /api/admin/pages/{page}/translate
     */
    public function translate(string $page, Request $request): JsonResponse
    {
        $request->validate([
            'source_language_id' => 'required|integer|exists:languages,id',
            'target_language_id' => 'required|integer|exists:languages,id',
        ]);

        $source = PageContent::where('page', $page)
            ->where('language_id', $request->source_language_id)
            ->first();

        if (!$source) {
            return response()->json([
                'success' => false,
                'message' => 'Source content not found.',
            ], 404);
        }

        $targetLang = Language::find($request->target_language_id);

        // Use the AI translation from TourCloneController pattern
        try {
            $aiSettings = \App\Models\AITranslationSetting::where('is_active', true)->first();

            if (!$aiSettings) {
                // Fallback: copy with prefix markers
                $translated = $this->copyWithMarkers($source->content, $targetLang->code);
            } else {
                $translated = $this->translateContentWithAI($source->content, $targetLang, $aiSettings);
            }

            $content = PageContent::updateOrCreate(
                ['page' => $page, 'language_id' => $request->target_language_id],
                [
                    'content' => $translated,
                    'published' => false, // Not published until reviewed
                ]
            );

            return response()->json([
                'success' => true,
                'message' => "Content translated to {$targetLang->country}. Please review before publishing.",
                'data' => $content,
            ]);
        } catch (\Exception $e) {
            Log::error("Error translating page content", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error translating: ' . $e->getMessage(),
            ], 500);
        }
    }

    private function copyWithMarkers(array $content, string $langCode): array
    {
        $result = [];
        foreach ($content as $key => $value) {
            if (is_string($value) && !empty($value)) {
                $result[$key] = "[{$langCode}] {$value}";
            } elseif (is_array($value)) {
                $result[$key] = $this->copyWithMarkers($value, $langCode);
            } else {
                $result[$key] = $value;
            }
        }
        return $result;
    }

    private function translateContentWithAI(array $content, Language $targetLang, $aiSettings): array
    {
        // Collect all translatable strings
        $textsToTranslate = [];
        $this->flattenTexts($content, '', $textsToTranslate);

        if (empty($textsToTranslate)) {
            return $content;
        }

        $targetLangName = $targetLang->country;
        $prompt = "Translate the following JSON key-value pairs to {$targetLangName}. Return ONLY valid JSON with the same keys. Translate values only, keep HTML tags intact.\n\n" . json_encode($textsToTranslate, JSON_UNESCAPED_UNICODE);

        try {
            $translated = $this->callAI($prompt, $aiSettings);
            $translatedTexts = json_decode($translated, true);

            if ($translatedTexts) {
                return $this->unflattenTexts($content, $translatedTexts);
            }
        } catch (\Exception $e) {
            Log::warning("AI translation failed, using markers", ['error' => $e->getMessage()]);
        }

        return $this->copyWithMarkers($content, $targetLang->code);
    }

    private function flattenTexts(array $data, string $prefix, array &$result): void
    {
        foreach ($data as $key => $value) {
            $fullKey = $prefix ? "{$prefix}.{$key}" : $key;
            if (is_string($value) && !empty($value)) {
                $result[$fullKey] = $value;
            } elseif (is_array($value) && !isset($value[0])) {
                $this->flattenTexts($value, $fullKey, $result);
            }
        }
    }

    private function unflattenTexts(array $original, array $translated): array
    {
        $result = $original;
        foreach ($translated as $flatKey => $value) {
            $keys = explode('.', $flatKey);
            $ref = &$result;
            foreach ($keys as $k) {
                if (!isset($ref[$k])) break;
                $ref = &$ref[$k];
            }
            if (is_string($ref)) {
                $ref = $value;
            }
        }
        return $result;
    }

    private function callAI(string $prompt, $settings): string
    {
        $provider = $settings->provider;
        $apiKey = $settings->api_key;
        $model = $settings->model;

        if ($provider === 'openai') {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'Authorization' => "Bearer {$apiKey}",
            ])->post('https://api.openai.com/v1/chat/completions', [
                'model' => $model ?: 'gpt-4o-mini',
                'messages' => [['role' => 'user', 'content' => $prompt]],
                'temperature' => 0.3,
            ]);
            return $response->json('choices.0.message.content', '{}');
        }

        if ($provider === 'anthropic') {
            $response = \Illuminate\Support\Facades\Http::withHeaders([
                'x-api-key' => $apiKey,
                'anthropic-version' => '2023-06-01',
            ])->post('https://api.anthropic.com/v1/messages', [
                'model' => $model ?: 'claude-sonnet-4-20250514',
                'max_tokens' => 4096,
                'messages' => [['role' => 'user', 'content' => $prompt]],
            ]);
            return $response->json('content.0.text', '{}');
        }

        throw new \Exception("Unsupported AI provider: {$provider}");
    }
}
