<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AITranslationSetting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Validator;

class AITranslationSettingsController extends Controller
{
    /**
     * Get AI translation settings
     */
    public function index()
    {
        $settings = AITranslationSetting::first();

        if (!$settings) {
            // Return default settings if none exist
            return response()->json([
                'data' => [
                    'provider' => 'openai',
                    'api_key' => '',
                    'model' => '',
                    'custom_prompt' => '',
                    'is_active' => false,
                    'settings' => [
                        'temperature' => 0.3,
                        'max_tokens' => 4000
                    ]
                ]
            ]);
        }

        return response()->json([
            'data' => $settings
        ]);
    }

    /**
     * Save AI translation settings
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:openai,anthropic,gemini,deepseek',
            'api_key' => 'required|string',
            'model' => 'required|string',
            'custom_prompt' => 'nullable|string',
            'is_active' => 'boolean',
            'settings' => 'nullable|array',
            'settings.temperature' => 'nullable|numeric|min:0|max:1',
            'settings.max_tokens' => 'nullable|integer|min:100|max:16000'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        $settings = AITranslationSetting::first();

        $data = [
            'provider' => $request->provider,
            'api_key' => $request->api_key,
            'model' => $request->model,
            'custom_prompt' => $request->custom_prompt,
            'is_active' => $request->is_active ?? true,
            'settings' => json_encode($request->settings ?? [
                'temperature' => 0.3,
                'max_tokens' => 4000
            ])
        ];

        if ($settings) {
            $settings->update($data);
        } else {
            $settings = AITranslationSetting::create($data);
        }

        return response()->json([
            'message' => 'Settings saved successfully',
            'data' => $settings
        ]);
    }

    /**
     * Test AI provider connection
     */
    public function test(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'provider' => 'required|in:openai,anthropic,gemini,deepseek',
            'api_key' => 'required|string',
            'model' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation error',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $result = $this->testProvider(
                $request->provider,
                $request->api_key,
                $request->model
            );

            if ($result['success']) {
                return response()->json([
                    'message' => 'Connection successful',
                    'data' => $result
                ]);
            } else {
                return response()->json([
                    'message' => $result['error'] ?? 'Connection failed'
                ], 400);
            }
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Connection test failed: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Test connection to AI provider
     */
    private function testProvider(string $provider, string $apiKey, string $model): array
    {
        $testMessage = "Translate 'Hello' to Spanish";

        try {
            switch ($provider) {
                case 'openai':
                    return $this->testOpenAI($apiKey, $model, $testMessage);

                case 'anthropic':
                    return $this->testAnthropic($apiKey, $model, $testMessage);

                case 'gemini':
                    return $this->testGemini($apiKey, $model, $testMessage);

                case 'deepseek':
                    return $this->testDeepSeek($apiKey, $model, $testMessage);

                default:
                    return ['success' => false, 'error' => 'Unsupported provider'];
            }
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    private function testOpenAI(string $apiKey, string $model, string $message): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 50
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'response' => $response->json('choices.0.message.content'),
                'model' => $model
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message') ?? 'API request failed'
        ];
    }

    private function testAnthropic(string $apiKey, string $model, string $message): array
    {
        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'anthropic-version' => '2023-06-01',
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 50
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'response' => $response->json('content.0.text'),
                'model' => $model
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message') ?? 'API request failed'
        ];
    }

    private function testGemini(string $apiKey, string $model, string $message): array
    {
        // Try different API versions based on model
        $apiVersion = 'v1beta';

        // For Gemini Pro and Flash models, try v1beta first
        if (str_contains($model, 'gemini-pro') || str_contains($model, 'gemini-1.0')) {
            $apiVersion = 'v1beta';
        }

        $response = Http::timeout(30)->post(
            "https://generativelanguage.googleapis.com/{$apiVersion}/models/{$model}:generateContent?key={$apiKey}",
            [
                'contents' => [
                    [
                        'parts' => [
                            ['text' => $message]
                        ]
                    ]
                ],
                'generationConfig' => [
                    'temperature' => 0.3,
                    'maxOutputTokens' => 50
                ]
            ]
        );

        // If v1beta fails, try v1
        if (!$response->successful() && $apiVersion === 'v1beta') {
            $apiVersion = 'v1';
            $response = Http::timeout(30)->post(
                "https://generativelanguage.googleapis.com/{$apiVersion}/models/{$model}:generateContent?key={$apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $message]
                            ]
                        ]
                    ],
                    'generationConfig' => [
                        'temperature' => 0.3,
                        'maxOutputTokens' => 50
                    ]
                ]
            );
        }

        if ($response->successful()) {
            return [
                'success' => true,
                'response' => $response->json('candidates.0.content.parts.0.text'),
                'model' => $model
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message') ?? 'API request failed'
        ];
    }

    private function testDeepSeek(string $apiKey, string $model, string $message): array
    {
        $response = Http::withHeaders([
            'Authorization' => 'Bearer ' . $apiKey,
            'Content-Type' => 'application/json',
        ])->timeout(30)->post('https://api.deepseek.com/v1/chat/completions', [
            'model' => $model,
            'messages' => [
                ['role' => 'user', 'content' => $message]
            ],
            'max_tokens' => 50
        ]);

        if ($response->successful()) {
            return [
                'success' => true,
                'response' => $response->json('choices.0.message.content'),
                'model' => $model
            ];
        }

        return [
            'success' => false,
            'error' => $response->json('error.message') ?? 'API request failed'
        ];
    }
}
