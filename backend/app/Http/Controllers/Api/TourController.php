<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreTourRequest;
use App\Http\Requests\UpdateTourRequest;
use App\Http\Resources\TourResource;
use App\Http\Resources\TourDetailResource;
use App\Models\Tour;
use App\Services\TourService;
use App\Services\PriceCalculatorService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Exception;

class TourController extends Controller
{
    protected TourService $tourService;
    protected PriceCalculatorService $priceCalculator;

    public function __construct(
        TourService $tourService,
        PriceCalculatorService $priceCalculator
    ) {
        $this->tourService = $tourService;
        $this->priceCalculator = $priceCalculator;
    }

    public function index(Request $request): JsonResponse
    {
        try {
            $query = Tour::query();

            $query->with([
                'translations.language',
                'city',
                'prices.ageStage',
                'mediaGallery',
                'categories',
            ]);

            if ($request->has('status')) {
                $query->where('status', $request->status);
            }

            if ($request->has('active')) {
                $query->where('active', $request->boolean('active'));
            }

            if ($request->has('service_type')) {
                $query->where('service_type', $request->service_type);
            }

            if ($request->has('difficulty')) {
                $query->where('difficulty', $request->difficulty);
            }

            if ($request->has('city_id')) {
                $query->where('city_id', $request->city_id);
            }

            if ($request->has('category_id')) {
                $query->whereHas('categories', function ($q) use ($request) {
                    $q->where('category_new_id', $request->category_id);
                });
            }

            if ($request->has('search')) {
                $search = $request->search;
                $query->whereHas('translations', function ($q) use ($search) {
                    $q->where('h1_title', 'like', "%{$search}%")
                      ->orWhere('short_description', 'like', "%{$search}%");
                });
            }

            if ($request->has('min_price')) {
                $query->whereHas('prices', function ($q) use ($request) {
                    $q->where('active', true)
                      ->where('amount', '>=', $request->min_price);
                });
            }

            if ($request->has('max_price')) {
                $query->whereHas('prices', function ($q) use ($request) {
                    $q->where('active', true)
                      ->where('amount', '<=', $request->max_price);
                });
            }

            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);
            $tours = $query->paginate($perPage);

            return response()->json([
                'success' => true,
                'data' => TourResource::collection($tours),
                'meta' => [
                    'current_page' => $tours->currentPage(),
                    'from' => $tours->firstItem(),
                    'last_page' => $tours->lastPage(),
                    'per_page' => $tours->perPage(),
                    'to' => $tours->lastItem(),
                    'total' => $tours->total(),
                ],
                'links' => [
                    'first' => $tours->url(1),
                    'last' => $tours->url($tours->lastPage()),
                    'prev' => $tours->previousPageUrl(),
                    'next' => $tours->nextPageUrl(),
                ],
            ], 200);
        } catch (Exception $e) {
            Log::error("Error fetching tours", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener los tours.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(StoreTourRequest $request): JsonResponse
    {
        try {
            $tour = $this->tourService->create($request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tour creado exitosamente.',
                'data' => new TourDetailResource($tour),
            ], 201);
        } catch (Exception $e) {
            Log::error("Error creating tour", ['error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al crear el tour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(Request $request, $id): JsonResponse
    {
        try {
            $tour = Tour::with([
                'translations.language',
                'primaryLanguage',
                'prices.ageStage',
                'prices.nationality',
                'mediaGallery',
                'mapPoints',
                'categories.translations',
                'city',
            ])->findOrFail($id);

            return response()->json([
                'success' => true,
                'data' => new TourDetailResource($tour),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tour no encontrado.',
            ], 404);
        } catch (Exception $e) {
            Log::error("Error fetching tour", ['tour_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function showBySlug(Request $request, $slug): JsonResponse
    {
        try {
            $language = $request->language ?? 'ES';

            $tour = Tour::with([
                'translations.language',
                'prices.ageStage',
                'prices.nationality',
                'mediaGallery',
                'mapPoints',
                'categories.translations',
                'city',
            ])
            ->whereHas('translations', function ($query) use ($slug, $language) {
                $query->where('slug', $slug);
            })
            ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new TourDetailResource($tour),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tour no encontrado.',
            ], 404);
        } catch (Exception $e) {
            Log::error("Error fetching tour by slug", ['slug' => $slug, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function update(UpdateTourRequest $request, $id): JsonResponse
    {
        try {
            $tour = Tour::findOrFail($id);
            $tour = $this->tourService->update($tour, $request->validated());

            return response()->json([
                'success' => true,
                'message' => 'Tour actualizado exitosamente.',
                'data' => new TourDetailResource($tour),
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tour no encontrado.',
            ], 404);
        } catch (Exception $e) {
            Log::error("Error updating tour", ['tour_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al actualizar el tour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy($id): JsonResponse
    {
        try {
            $tour = Tour::findOrFail($id);
            $this->tourService->delete($tour);

            return response()->json([
                'success' => true,
                'message' => 'Tour eliminado exitosamente.',
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tour no encontrado.',
            ], 404);
        } catch (Exception $e) {
            Log::error("Error deleting tour", ['tour_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al eliminar el tour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function calculatePrice(Request $request, $id): JsonResponse
    {
        try {
            $tour = Tour::with(['prices.ageStage'])->findOrFail($id);

            $request->validate([
                'participants' => 'required|array',
                'participants.*.age_stage_id' => 'required|integer|exists:age_stages,id',
                'participants.*.quantity' => 'required|integer|min:1',
                'coupon_code' => 'nullable|string',
                'date' => 'nullable|date',
            ]);

            $price = $this->priceCalculator->calculateTourPrice(
                $tour,
                $request->participants,
                $request->coupon_code,
                $request->date
            );

            return response()->json([
                'success' => true,
                'data' => $price,
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tour no encontrado.',
            ], 404);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error("Error calculating price", ['tour_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al calcular el precio.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function validateCoupon(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'code' => 'required|string',
                'product_id' => 'nullable|integer|exists:tours,id',
            ]);

            $result = $this->priceCalculator->validateCoupon(
                $request->code,
                $request->product_id
            );

            return response()->json([
                'success' => true,
                'data' => $result,
            ], 200);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error de validación.',
                'errors' => $e->errors(),
            ], 422);
        } catch (Exception $e) {
            Log::error("Error validating coupon", ['code' => $request->code, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al validar el cupón.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function getAvailableDates(Request $request, $id): JsonResponse
    {
        try {
            $tour = Tour::with(['availabilities', 'blockouts'])->findOrFail($id);

            $startDate = $request->get('start_date', now()->toDateString());
            $endDate = $request->get('end_date', now()->addMonths(3)->toDateString());

            $availableDates = [];

            $currentDate = \Carbon\Carbon::parse($startDate);
            $end = \Carbon\Carbon::parse($endDate);

            while ($currentDate->lte($end)) {
                $dateString = $currentDate->toDateString();
                $isBlocked = $tour->blockouts->contains(function($blockout) use ($currentDate) {
                    return $currentDate->between(
                        \Carbon\Carbon::parse($blockout->start_date),
                        \Carbon\Carbon::parse($blockout->end_date)
                    );
                });

                if (!$isBlocked) {
                    $availableDates[] = $dateString;
                }

                $currentDate->addDay();
            }

            return response()->json([
                'success' => true,
                'data' => [
                    'tour_id' => $tour->id,
                    'start_date' => $startDate,
                    'end_date' => $endDate,
                    'available_dates' => $availableDates,
                ],
            ], 200);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tour no encontrado.',
            ], 404);
        } catch (Exception $e) {
            Log::error("Error fetching available dates", ['tour_id' => $id, 'error' => $e->getMessage()]);
            return response()->json([
                'success' => false,
                'message' => 'Error al obtener fechas disponibles.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate a new tour code via API
     */
    public function generateCodeApi(Request $request): JsonResponse
    {
        try {
            $languageId = $request->query('language_id');
            if (!$languageId) {
                return response()->json([
                    'success' => false,
                    'message' => 'ID de idioma requerido.'
                ], 400);
            }

            $code = $this->tourService->generateTourCode($languageId);

            return response()->json([
                'success' => true,
                'data' => [
                    'code' => $code
                ]
            ]);
        } catch (Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error al generar el código.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Show tour by multilang URL structure: /{lang}/{city}/{slug}
     * Example: /es/puno/tour-uros-amantani-taquile-sillustani-2d1n
     */
    public function showMultilang(string $lang, string $citySlug, string $tourSlug): JsonResponse
    {
        try {
            // Normalize lang to uppercase (ES, EN, PT, etc.)
            $langCode = strtoupper($lang);

            $tour = Tour::query()
                ->with([
                    'translations.language',
                    'city',
                    'prices.ageStage',
                    'prices.nationality',
                    'mediaGallery',
                    'categories.translations',
                    'mapPoints'
                ])
                // Filter by tour slug in translation
                ->whereHas('translations', function($q) use ($tourSlug, $langCode) {
                    $q->where('slug', $tourSlug)
                      ->whereHas('language', fn($q2) => $q2->where('code', $langCode));
                })
                // Filter by city slug (using city name as fallback since slug might not exist yet)
                ->whereHas('city', function($q) use ($citySlug) {
                    // Try to match by slug first, fallback to name
                    $q->where(function($query) use ($citySlug) {
                        if (Schema::hasColumn('cities', 'slug')) {
                            $query->where('slug', $citySlug);
                        } else {
                            // Fallback: match city name (case insensitive)
                            $cityName = str_replace('-', ' ', $citySlug);
                            $query->where('name', 'like', "%{$cityName}%");
                        }
                    });
                })
                ->where('active', true)
                ->firstOrFail();

            return response()->json([
                'success' => true,
                'data' => new TourDetailResource($tour),
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Tour no encontrado con los parámetros especificados.',
                'error' => "No se encontró un tour para: idioma={$lang}, ciudad={$citySlug}, slug={$tourSlug}"
            ], 404);

        } catch (Exception $e) {
            Log::error("Error fetching tour by multilang URL", [
                'lang' => $lang,
                'citySlug' => $citySlug,
                'tourSlug' => $tourSlug,
                'error' => $e->getMessage()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al obtener el tour.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Clone a tour manually to another language
     */
    public function cloneManual(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'language_id' => 'required|integer|exists:languages,id',
            ]);

            $tour = Tour::with([
                'translations',
                'prices',
                'itineraries.itineraryTranslations',
                'multimedia',
                'pickupDetails',
                'addons',
                'requirements',
                'recommendations'
            ])->findOrFail($id);

            // Check if tour already exists in target language
            $targetLanguageId = $request->language_id;
            $existingTranslation = $tour->translations()
                ->where('language_id', $targetLanguageId)
                ->first();

            if ($existingTranslation) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tour ya existe en el idioma seleccionado.'
                ], 400);
            }

            DB::beginTransaction();

            // Generate new tour code for the language
            $language = \App\Models\Language::find($targetLanguageId);
            $newCode = $this->generateTourCodeForLanguage($language->code);

            // Clone the tour
            $newTour = $tour->replicate();
            $newTour->code = $newCode;
            $newTour->save();

            // Clone translations with empty content (manual translation)
            $sourceTranslation = $tour->translations()->first();
            if ($sourceTranslation) {
                $newTranslation = $sourceTranslation->replicate();
                $newTranslation->tour_id = $newTour->id;
                $newTranslation->language_id = $targetLanguageId;
                // Keep structure but clear content for manual translation
                $newTranslation->title = '[Traducir] ' . $sourceTranslation->title;
                $newTranslation->short_description = '[Traducir] ' . $sourceTranslation->short_description;
                $newTranslation->long_description = '[Traducir] ' . $sourceTranslation->long_description;
                $newTranslation->includes = $sourceTranslation->includes;
                $newTranslation->not_includes = $sourceTranslation->not_includes;
                $newTranslation->recommendations = $sourceTranslation->recommendations;
                $newTranslation->slug = Str::slug($newTranslation->title . '-' . $newCode);
                $newTranslation->save();
            }

            // Clone prices
            foreach ($tour->prices as $price) {
                $newPrice = $price->replicate();
                $newPrice->tour_id = $newTour->id;
                $newPrice->save();
            }

            // Clone itineraries
            foreach ($tour->itineraries as $itinerary) {
                $newItinerary = $itinerary->replicate();
                $newItinerary->tour_id = $newTour->id;
                $newItinerary->save();

                // Clone itinerary translations
                foreach ($itinerary->itineraryTranslations as $itineraryTranslation) {
                    $newItineraryTranslation = $itineraryTranslation->replicate();
                    $newItineraryTranslation->itinerary_id = $newItinerary->id;
                    $newItineraryTranslation->language_id = $targetLanguageId;
                    // Mark for translation
                    $newItineraryTranslation->title = '[Traducir] ' . $itineraryTranslation->title;
                    $newItineraryTranslation->description = '[Traducir] ' . $itineraryTranslation->description;
                    $newItineraryTranslation->save();
                }
            }

            // Clone multimedia references
            foreach ($tour->multimedia as $media) {
                $newTour->multimedia()->attach($media->id);
            }

            // Clone other related data
            foreach ($tour->pickupDetails as $pickup) {
                $newPickup = $pickup->replicate();
                $newPickup->tour_id = $newTour->id;
                $newPickup->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tour clonado exitosamente para traducción manual.',
                'data' => [
                    'id' => $newTour->id,
                    'code' => $newTour->code
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error cloning tour manually', [
                'tour_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al clonar el tour.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Clone a tour with AI translation
     */
    public function cloneWithAI(Request $request, int $id): JsonResponse
    {
        try {
            $request->validate([
                'language_id' => 'required|integer|exists:languages,id',
            ]);

            $tour = Tour::with([
                'translations',
                'prices',
                'itineraries.itineraryTranslations',
                'multimedia',
                'pickupDetails',
                'addons',
                'requirements',
                'recommendations'
            ])->findOrFail($id);

            // Check if tour already exists in target language
            $targetLanguageId = $request->language_id;
            $existingTranslation = $tour->translations()
                ->where('language_id', $targetLanguageId)
                ->first();

            if ($existingTranslation) {
                return response()->json([
                    'success' => false,
                    'message' => 'El tour ya existe en el idioma seleccionado.'
                ], 400);
            }

            DB::beginTransaction();

            // Generate new tour code for the language
            $language = \App\Models\Language::find($targetLanguageId);
            $newCode = $this->generateTourCodeForLanguage($language->code);

            // Clone the tour
            $newTour = $tour->replicate();
            $newTour->code = $newCode;
            $newTour->save();

            // Clone and translate with AI
            $sourceTranslation = $tour->translations()->first();
            if ($sourceTranslation) {
                $newTranslation = $sourceTranslation->replicate();
                $newTranslation->tour_id = $newTour->id;
                $newTranslation->language_id = $targetLanguageId;

                // AI Translation (simplified example - you would integrate with actual AI service)
                $targetLangName = $language->country;
                $newTranslation->title = $this->translateWithAI($sourceTranslation->title, $targetLangName);
                $newTranslation->short_description = $this->translateWithAI($sourceTranslation->short_description, $targetLangName);
                $newTranslation->long_description = $this->translateWithAI($sourceTranslation->long_description, $targetLangName);
                $newTranslation->includes = $this->translateWithAI($sourceTranslation->includes, $targetLangName);
                $newTranslation->not_includes = $this->translateWithAI($sourceTranslation->not_includes, $targetLangName);
                $newTranslation->recommendations = $this->translateWithAI($sourceTranslation->recommendations, $targetLangName);
                $newTranslation->slug = Str::slug($newTranslation->title . '-' . $newCode);
                $newTranslation->save();
            }

            // Clone prices (same as manual)
            foreach ($tour->prices as $price) {
                $newPrice = $price->replicate();
                $newPrice->tour_id = $newTour->id;
                $newPrice->save();
            }

            // Clone and translate itineraries
            foreach ($tour->itineraries as $itinerary) {
                $newItinerary = $itinerary->replicate();
                $newItinerary->tour_id = $newTour->id;
                $newItinerary->save();

                foreach ($itinerary->itineraryTranslations as $itineraryTranslation) {
                    $newItineraryTranslation = $itineraryTranslation->replicate();
                    $newItineraryTranslation->itinerary_id = $newItinerary->id;
                    $newItineraryTranslation->language_id = $targetLanguageId;
                    // AI Translation
                    $newItineraryTranslation->title = $this->translateWithAI($itineraryTranslation->title, $targetLangName);
                    $newItineraryTranslation->description = $this->translateWithAI($itineraryTranslation->description, $targetLangName);
                    $newItineraryTranslation->save();
                }
            }

            // Clone multimedia references
            foreach ($tour->multimedia as $media) {
                $newTour->multimedia()->attach($media->id);
            }

            // Clone other related data
            foreach ($tour->pickupDetails as $pickup) {
                $newPickup = $pickup->replicate();
                $newPickup->tour_id = $newTour->id;
                $newPickup->save();
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Tour clonado y traducido con IA exitosamente.',
                'data' => [
                    'id' => $newTour->id,
                    'code' => $newTour->code
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();

            Log::error('Error cloning tour with AI', [
                'tour_id' => $id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al clonar el tour con IA.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Helper method to translate text with AI
     * This is a placeholder - you would integrate with an actual AI translation service
     */
    private function translateWithAI(string $text, string $targetLanguage): string
    {
        // Placeholder implementation
        // In production, you would call an AI translation API like:
        // - OpenAI GPT
        // - Google Translate API
        // - DeepL API
        // - Azure Translator

        // For now, just prefix with the language to show it would be translated
        return "[AI-{$targetLanguage}] " . $text;
    }

    /**
     * Generate a unique tour code for a specific language
     */
    private function generateTourCodeForLanguage(string $languageCode): string
    {
        $prefix = strtoupper($languageCode);

        // Get the last tour code for this language
        $lastTour = Tour::where('code', 'LIKE', $prefix . '%')
            ->orderBy('code', 'desc')
            ->first();

        if ($lastTour) {
            // Extract number and increment
            preg_match('/(\d+)$/', $lastTour->code, $matches);
            $number = isset($matches[1]) ? intval($matches[1]) + 1 : 1;
        } else {
            $number = 1;
        }

        return $prefix . str_pad($number, 3, '0', STR_PAD_LEFT);
    }
}