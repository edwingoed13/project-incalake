<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Models\Tour;
use App\Models\City;
use App\Models\Language;
use App\Models\AgeStage;
use App\Models\CategoryNew;
use App\Models\Nationality;
use Illuminate\Support\Str;

class TourWizard extends Component
{
    use WithFileUploads;

    public $currentStep = 1;
    public $totalSteps = 8;
    public $tourId = null;

    // Step 1: Basic Information
    public $code, $city_id, $city_name, $service_type = 'tour', $status = 'draft';
    public $difficulty = 'moderate', $target_audience = 'all';
    public $capacity = 999, $cupos, $duration_days = 1, $duration_hours;
    public $start_time, $booking_anticipation_hours = 24, $data_requirement = 1, $data_requirement_type = 'leader';
    public $primary_language_id = null;
    public $departure_time = null, $departure_period = 'AM';
    public $duration_quantity = 1, $duration_unit = 'days';
    public $timezone = 'America/Lima';
    public $payment_method = 'all';
    public $policy_type = 'standard', $policy_description = null, $policy_description_custom = null;
    public $booking_anticipation_quantity = 24, $booking_anticipation_unit = 'hours';
    public $operational_info_required = [], $personal_info_required = [];
    public $guide_type = 'live_guide', $guide_languages = [];
    public $require_availability = false;
    public $availability_data = [], $blocks_data = [], $offers_data = [];
    public $images = [], $uploadedImages = [], $tempImages = [];
    public $youtube_url = null;
    public $mapPoints = [];
    public $index_status = 'index', $follow_status = 'follow', $active = true;

    // Step 6: Pickup & Logistics
    public $enable_meeting_point = false;
    public $enable_hotel_pickup = false;
    public $meeting_point_description = null;
    public $meeting_point_lat = null;
    public $meeting_point_lng = null;
    public $pickup_location_description = null;
    public $pickup_center_lat = null;
    public $pickup_center_lng = null;
    public $pickup_radius_km = null;
    public $dropoff_location_description = null;

    // Step 2: Translations & SEO
    public $translations = [];

    // Step 3: Prices
    public $tax_percentage = 7.00;
    public $advance_payment_percentage = 100;
    public $prices = [];

    // Step 5: Categories
    public $selectedCategories = [];

    // Collections
    public $cities, $languages, $ageStages, $categories, $nationalities;

    protected function rules()
    {
        $rules = [];

        if ($this->currentStep == 1) {
            $rules = [
                'code' => 'required|string|max:100|unique:tours,code,' . $this->tourId,
                'city_name' => 'required|string|max:255',
                'primary_language_id' => 'required|exists:languages,id',
                'service_type' => 'required|in:tour,package,experience,transport',
                'difficulty' => 'required|in:easy,moderate,hard',
                'capacity' => 'required|integer|min:1',
                'departure_time' => 'required',
                'duration_days' => 'nullable|integer|min:0',
            ];
        }

        if ($this->currentStep == 2) {
            // Solo validar el idioma primario como requerido
            if ($this->primary_language_id) {
                $rules["translations.{$this->primary_language_id}.h1_title"] = 'required|string|max:100';
                $rules["translations.{$this->primary_language_id}.meta_title"] = 'required|string|max:60';
                $rules["translations.{$this->primary_language_id}.meta_description"] = 'required|string|max:160';
                $rules["translations.{$this->primary_language_id}.slug"] = 'required|string|max:150';
            }

            // Los demás idiomas son opcionales
            foreach ($this->languages as $language) {
                if ($language->id != $this->primary_language_id) {
                    $rules["translations.{$language->id}.h1_title"] = 'nullable|string|max:100';
                    $rules["translations.{$language->id}.meta_title"] = 'nullable|string|max:60';
                    $rules["translations.{$language->id}.meta_description"] = 'nullable|string|max:160';
                    $rules["translations.{$language->id}.slug"] = 'nullable|string|max:150';
                }
            }
        }

        if ($this->currentStep == 3) {
            foreach ($this->ageStages as $ageStage) {
                $rules["prices.{$ageStage->id}.amount"] = 'required|numeric|min:0';
            }
        }

        // Para los demás pasos (4-8), permitir sin validación estricta
        // o agregar validaciones específicas según sea necesario
        if ($this->currentStep >= 4) {
            $rules['currentStep'] = 'required|integer|min:1|max:8';
        }

        return $rules;
    }

    public function mount($tourId = null)
    {
        $this->tourId = $tourId;

        $this->cities = City::active()->orderBy('name')->get();
        $this->languages = Language::whereIn('code', ['ES', 'EN', 'FR', 'DE', 'PT', 'IT'])->get();
        $this->ageStages = AgeStage::orderBy('min_age')->get();
        $this->categories = CategoryNew::where('active', true)->get();
        $this->nationalities = Nationality::orderBy('description')->get();

        foreach ($this->languages as $language) {
            $this->translations[$language->id] = [
                'h1_title' => '', 'meta_title' => '', 'meta_description' => '', 'slug' => '',
                'short_description' => '', 'long_description' => '',
                'og_title' => '', 'og_description' => '',
                'twitter_title' => '', 'twitter_description' => '',
                'ads_headline' => '', 'ads_description' => '',
                'cta_text' => $language->code == 'ES' ? 'Reservar Ahora' : 'Book Now',
                'itinerary' => '', 'what_includes' => '', 'what_not_includes' => '',
                'recommendations' => '', 'what_to_bring' => '', 'policies' => '',
                'cancellation_policy' => '',
            ];
        }

        foreach ($this->ageStages as $ageStage) {
            $this->prices[$ageStage->id] = [
                'amount' => 0, 'active' => true, 'price_type' => 'per_person',
                'min_quantity' => 1, 'max_quantity' => null,
            ];
        }

        if ($this->tourId) {
            $this->loadTour();
        } else {
            // Establecer políticas predeterminadas para nuevos tours
            $this->setDefaultPolicyDescription();

            // Establecer idioma primario predeterminado y generar código automáticamente
            if (!$this->primary_language_id) {
                $defaultLanguage = $this->languages->where('code', 'ES')->first();
                if ($defaultLanguage) {
                    $this->primary_language_id = $defaultLanguage->id;
                    $this->generateTourCode();
                }
            }
        }
    }

    protected function setDefaultPolicyDescription()
    {
        if ($this->policy_type === 'standard' && empty($this->policy_description)) {
            $this->policy_description = '<h3>Políticas de Cancelación</h3>
<table>
  <thead>
    <tr>
      <th>Periodo de Anticipación para Anulación</th>
      <th>Penalidad</th>
      <th>Detalles</th>
    </tr>
  </thead>
  <tbody>
    <tr>
      <td>Hasta 48 horas antes del inicio del tour</td>
      <td>20% del total</td>
      <td>Gastos administrativos, comisiones de tarjeta de crédito/débito y otros relacionados.</td>
    </tr>
    <tr>
      <td>Dentro de las 48 horas antes del inicio del tour</td>
      <td>100% del total</td>
      <td>Monto total acordado del servicio.</td>
    </tr>
  </tbody>
</table>';
        }
    }

    protected function loadTour()
    {
        $tour = Tour::with(['translations', 'prices', 'categories', 'mediaGallery', 'mapPoints'])->findOrFail($this->tourId);

        $this->fill($tour->only(['code', 'city_id', 'city_name', 'primary_language_id', 'service_type', 'status', 'difficulty', 'target_audience',
            'capacity', 'cupos', 'duration_days', 'duration_hours', 'departure_time', 'departure_period',
            'duration_quantity', 'duration_unit', 'timezone', 'start_time',
            'booking_anticipation_hours', 'tax_percentage', 'advance_payment_percentage', 'payment_method', 'policy_type', 'policy_description', 'policy_description_custom',
            'booking_anticipation_quantity', 'booking_anticipation_unit', 'operational_info_required', 'personal_info_required',
            'enable_meeting_point', 'enable_hotel_pickup', 'meeting_point_description', 'meeting_point_lat', 'meeting_point_lng',
            'pickup_location_description', 'pickup_center_lat', 'pickup_center_lng', 'pickup_radius_km', 'dropoff_location_description',
            'guide_type', 'guide_languages',
            'require_availability', 'availability_data', 'blocks_data', 'offers_data',
            'data_requirement', 'data_requirement_type', 'index_status', 'follow_status', 'active', 'youtube_url']));

        foreach ($tour->translations as $translation) {
            $this->translations[$translation->language_id] = $translation->only([
                'h1_title', 'meta_title', 'meta_description', 'slug', 'short_description',
                'long_description', 'og_title', 'og_description', 'twitter_title',
                'twitter_description', 'ads_headline', 'ads_description', 'cta_text',
                'itinerary', 'what_includes', 'what_not_includes', 'recommendations',
                'what_to_bring', 'policies', 'cancellation_policy'
            ]);
        }

        // Reorganizar los precios en la estructura anidada requerida por Step 4
        foreach ($tour->prices as $price) {
            $ageStageId = $price->age_stage_id;

            // Inicializar estructura si no existe
            if (!isset($this->prices[$ageStageId])) {
                $this->prices[$ageStageId] = [
                    'amount' => 0,
                    'active' => false,
                    'price_type' => 'per_person',
                    'min_quantity' => 1,
                    'max_quantity' => null,
                    'nationalities' => []
                ];
            }

            // Marcar como activo si hay al menos un precio
            $this->prices[$ageStageId]['active'] = true;

            // Si no tiene nacionalidad, es un precio simple
            if (!$price->nationality_id) {
                $this->prices[$ageStageId]['amount'] = $price->amount;
                $this->prices[$ageStageId]['price_type'] = $price->price_type;
                $this->prices[$ageStageId]['min_quantity'] = $price->min_quantity;
                $this->prices[$ageStageId]['max_quantity'] = $price->max_quantity;
            } else {
                // Asegurar que exista el array de nacionalidades
                if (!isset($this->prices[$ageStageId]['nationalities'])) {
                    $this->prices[$ageStageId]['nationalities'] = [];
                }

                // Buscar o crear la nacionalidad en el array
                $nationalityIndex = null;
                foreach ($this->prices[$ageStageId]['nationalities'] as $index => $nat) {
                    if ($nat['nationality_id'] == $price->nationality_id) {
                        $nationalityIndex = $index;
                        break;
                    }
                }

                if ($nationalityIndex === null) {
                    // Crear nueva entrada de nacionalidad
                    $this->prices[$ageStageId]['nationalities'][] = [
                        'nationality_id' => $price->nationality_id,
                        'age_min' => null,
                        'age_max' => null,
                        'ranges' => []
                    ];
                    $nationalityIndex = count($this->prices[$ageStageId]['nationalities']) - 1;
                }

                // Agregar el rango de precio
                $this->prices[$ageStageId]['nationalities'][$nationalityIndex]['ranges'][] = [
                    'from' => $price->min_quantity ?? 1,
                    'to' => $price->max_quantity,
                    'price' => $price->amount
                ];
            }
        }

        $this->selectedCategories = $tour->categories->pluck('id')->toArray();

        // Cargar imágenes existentes
        foreach ($tour->mediaGallery as $media) {
            $this->tempImages[] = [
                'id' => $media->id,
                'filename' => basename($media->image_path),
                'path' => $media->image_path,
                'url' => \Storage::url($media->image_path),
                'size' => 0, // No se almacena en la BD
                'mime' => 'image/jpeg', // Se asume JPEG
                'is_temp' => false,
                'alt_text' => $media->alt_text ?? '',
                'title_text' => $media->title_text ?? '',
                'description' => $media->description ?? '',
            ];
        }

        // Cargar puntos del mapa existentes
        foreach ($tour->mapPoints as $mapPoint) {
            $this->mapPoints[] = [
                'name' => $mapPoint->name,
                'description' => $mapPoint->description ?? '',
                'coordinates' => $mapPoint->coordinates,
                'type' => $mapPoint->type,
                'order' => $mapPoint->order,
            ];
        }
    }

    public function nextStep()
    {
        $this->validate();
        if ($this->currentStep < $this->totalSteps) {
            $this->currentStep++;
        }
    }

    public function previousStep()
    {
        if ($this->currentStep > 1) {
            $this->currentStep--;
        }
    }

    public function goToStep($step)
    {
        if ($step >= 1 && $step <= $this->totalSteps) {
            $this->currentStep = $step;
        }
    }

    public function updatedPrimaryLanguageId($value)
    {
        if ($value && !$this->tourId) {
            $this->generateTourCode();
        }
    }


    protected function generateTourCode()
    {
        try {
            $languageCode = $this->languages->find($this->primary_language_id)?->code ?? 'EN';
            $prefixLength = 2;
            $prefix = strtoupper(substr($languageCode, 0, $prefixLength));

            // Obtener el último tour con este prefijo
            $lastTour = Tour::where('code', 'LIKE', $prefix . '%')
                ->orderBy('code', 'desc')
                ->first();

            if ($lastTour) {
                // Extraer el número del último código (formato: ES001, ES002, etc.)
                preg_match('/\d+$/', $lastTour->code, $matches);
                $lastNumber = isset($matches[0]) ? intval($matches[0]) : 0;
                $nextNumber = $lastNumber + 1;
            } else {
                // Si no hay tours con este prefijo, empezar desde 1
                $nextNumber = 1;
            }

            // Formatear el número con ceros a la izquierda (3 dígitos)
            $this->code = $prefix . str_pad($nextNumber, 3, '0', STR_PAD_LEFT);

        } catch (\Exception $e) {
            \Log::error("Error generating tour code", ['error' => $e->getMessage()]);
            // Fallback: usar contador total
            $this->code = 'TOUR' . str_pad(Tour::count() + 1, 3, '0', STR_PAD_LEFT);
        }
    }

    public function addNationality($ageStageId)
    {
        if (!isset($this->prices[$ageStageId]['nationalities'])) {
            $this->prices[$ageStageId]['nationalities'] = [];
        }

        $this->prices[$ageStageId]['nationalities'][] = [
            'nationality_id' => null,
            'price_ranges' => []
        ];
    }

    public function removeNationality($ageStageId, $nationalityIndex)
    {
        if (isset($this->prices[$ageStageId]['nationalities'][$nationalityIndex])) {
            unset($this->prices[$ageStageId]['nationalities'][$nationalityIndex]);
            // Reindexar el array
            $this->prices[$ageStageId]['nationalities'] = array_values($this->prices[$ageStageId]['nationalities']);
        }
    }

    public function addPriceRange($ageStageId, $nationalityIndex)
    {
        if (!isset($this->prices[$ageStageId]['nationalities'][$nationalityIndex]['ranges'])) {
            $this->prices[$ageStageId]['nationalities'][$nationalityIndex]['ranges'] = [];
        }

        $this->prices[$ageStageId]['nationalities'][$nationalityIndex]['ranges'][] = [
            'from' => 1,
            'to' => null,
            'price' => 0
        ];
    }

    public function removePriceRange($ageStageId, $nationalityIndex, $rangeIndex)
    {
        if (isset($this->prices[$ageStageId]['nationalities'][$nationalityIndex]['ranges'][$rangeIndex])) {
            unset($this->prices[$ageStageId]['nationalities'][$nationalityIndex]['ranges'][$rangeIndex]);
            // Reindexar el array
            $this->prices[$ageStageId]['nationalities'][$nationalityIndex]['ranges'] =
                array_values($this->prices[$ageStageId]['nationalities'][$nationalityIndex]['ranges']);
        }
    }

    public function updatedImages()
    {
        $this->validate([
            'images.*' => 'image|max:5120',
        ]);

        foreach ($this->images as $image) {
            $tempPath = $image->store('temp/tours', 'public');

            $imageData = [
                'filename' => $image->getClientOriginalName(),
                'path' => $tempPath,
                'url' => \Storage::url($tempPath),
                'size' => $image->getSize(),
                'mime' => $image->getMimeType(),
                'is_temp' => true,
                'alt_text' => '',
                'title_text' => '',
                'description' => '',
            ];

            $this->tempImages[] = $imageData;

            // Disparar evento para abrir el cropper automáticamente
            $this->dispatch('imageUploaded', [
                'index' => count($this->tempImages) - 1,
                'imageUrl' => \Storage::url($tempPath)
            ]);
        }

        $this->reset('images');
    }

    public function updateCroppedImage($index, $croppedImageData)
    {
        if (!isset($this->tempImages[$index])) {
            return;
        }

        // Eliminar imagen anterior si es temporal
        $oldImage = $this->tempImages[$index];
        if (isset($oldImage['is_temp']) && $oldImage['is_temp'] && \Storage::disk('public')->exists($oldImage['path'])) {
            \Storage::disk('public')->delete($oldImage['path']);
        }

        // Decodificar base64 y guardar la nueva imagen recortada
        $imageData = substr($croppedImageData, strpos($croppedImageData, ',') + 1);
        $imageData = base64_decode($imageData);

        // Generar nombre único para la imagen recortada
        $filename = 'cropped_' . time() . '_' . uniqid() . '.jpg';
        $path = 'temp/tours/' . $filename;

        // Guardar la imagen recortada
        \Storage::disk('public')->put($path, $imageData);

        // Actualizar la información de la imagen
        $this->tempImages[$index] = [
            'filename' => $this->tempImages[$index]['filename'],
            'path' => $path,
            'url' => \Storage::url($path),
            'size' => strlen($imageData),
            'mime' => 'image/jpeg',
            'is_temp' => true,
            'is_cropped' => true,
            'alt_text' => $this->tempImages[$index]['alt_text'] ?? '',
            'title_text' => $this->tempImages[$index]['title_text'] ?? '',
            'description' => $this->tempImages[$index]['description'] ?? '',
        ];

        session()->flash('message', 'Imagen editada correctamente');
    }

    public function removeImage($index)
    {
        if (isset($this->tempImages[$index])) {
            $image = $this->tempImages[$index];
            if (isset($image['is_temp']) && $image['is_temp'] && \Storage::disk('public')->exists($image['path'])) {
                \Storage::disk('public')->delete($image['path']);
            }
            array_splice($this->tempImages, $index, 1);
        }
    }

    public function generateSlug($languageId)
    {
        if (!empty($this->translations[$languageId]['h1_title'])) {
            $this->translations[$languageId]['slug'] = Str::slug($this->translations[$languageId]['h1_title']);
        }
    }

    public function autoFillSeoFields($languageId)
    {
        $t = &$this->translations[$languageId];

        if (empty($t['meta_title']) && !empty($t['h1_title'])) {
            $t['meta_title'] = Str::limit($t['h1_title'], 60, '');
        }
        if (empty($t['meta_description']) && !empty($t['short_description'])) {
            $t['meta_description'] = Str::limit($t['short_description'], 160, '');
        }
        if (empty($t['og_title'])) $t['og_title'] = $t['meta_title'];
        if (empty($t['og_description'])) $t['og_description'] = $t['meta_description'];
        if (empty($t['twitter_title'])) $t['twitter_title'] = $t['meta_title'];
        if (empty($t['twitter_description'])) $t['twitter_description'] = $t['meta_description'];
    }

    protected function convertDuration()
    {
        // Convert duration_quantity + duration_unit to duration_days and duration_hours
        if ($this->duration_quantity && $this->duration_unit) {
            switch ($this->duration_unit) {
                case 'minutes':
                    $this->duration_days = 0;
                    $this->duration_hours = 0;
                    break;
                case 'hours':
                    $this->duration_days = 0;
                    $this->duration_hours = (int) $this->duration_quantity;
                    break;
                case 'days':
                    $this->duration_days = (int) $this->duration_quantity;
                    $this->duration_hours = 0;
                    break;
            }
        }
    }

    public function save()
    {
        // No validation needed on save - validation happens on each step
        \DB::beginTransaction();

        try {
            // Convert duration_quantity + duration_unit to duration_days and duration_hours
            $this->convertDuration();

            // Obtener o crear city_id basado en city_name
            if ($this->city_name && !$this->city_id) {
                $city = City::firstOrCreate(
                    ['name' => $this->city_name],
                    ['active' => true]
                );
                $this->city_id = $city->id;
            }

            $tour = Tour::updateOrCreate(
                ['id' => $this->tourId],
                [
                    'code' => $this->code,
                    'primary_language_id' => $this->primary_language_id,
                    'city_id' => $this->city_id,
                    'city_name' => $this->city_name,
                    'service_type' => $this->service_type,
                    'status' => $this->status,
                    'difficulty' => $this->difficulty,
                    'target_audience' => $this->target_audience,
                    'capacity' => $this->capacity,
                    'cupos' => $this->cupos,
                    'duration_days' => $this->duration_days,
                    'duration_hours' => $this->duration_hours,
                    'departure_time' => $this->departure_time,
                    'departure_period' => $this->departure_period,
                    'duration_quantity' => $this->duration_quantity,
                    'duration_unit' => $this->duration_unit,
                    'timezone' => $this->timezone,
                    'start_time' => $this->start_time,
                    'booking_anticipation_hours' => $this->booking_anticipation_hours,
                    'tax_percentage' => $this->tax_percentage,
                    'advance_payment_percentage' => $this->advance_payment_percentage,
                    'payment_method' => $this->payment_method,
                    'policy_type' => $this->policy_type,
                    'policy_description' => $this->policy_description,
                    'policy_description_custom' => $this->policy_description_custom,
                    'booking_anticipation_quantity' => $this->booking_anticipation_quantity,
                    'booking_anticipation_unit' => $this->booking_anticipation_unit,
                    'operational_info_required' => $this->operational_info_required,
                    'personal_info_required' => $this->personal_info_required,
                    'enable_meeting_point' => $this->enable_meeting_point,
                    'enable_hotel_pickup' => $this->enable_hotel_pickup,
                    'meeting_point_description' => $this->meeting_point_description,
                    'meeting_point_lat' => $this->meeting_point_lat,
                    'meeting_point_lng' => $this->meeting_point_lng,
                    'pickup_location_description' => $this->pickup_location_description,
                    'pickup_center_lat' => $this->pickup_center_lat,
                    'pickup_center_lng' => $this->pickup_center_lng,
                    'pickup_radius_km' => $this->pickup_radius_km,
                    'dropoff_location_description' => $this->dropoff_location_description,
                    'guide_type' => $this->guide_type,
                    'guide_languages' => $this->guide_languages,
                    'require_availability' => $this->require_availability,
                    'availability_data' => $this->availability_data,
                    'blocks_data' => $this->blocks_data,
                    'offers_data' => $this->offers_data,
                    'data_requirement' => $this->data_requirement,
                    'data_requirement_type' => $this->data_requirement_type,
                    'index_status' => $this->index_status,
                    'follow_status' => $this->follow_status,
                    'youtube_url' => $this->youtube_url,
                    'active' => $this->active,
                ]
            );

            foreach ($this->translations as $languageId => $translationData) {
                if (!empty($translationData['h1_title'])) {
                    $tour->translations()->updateOrCreate(
                        ['language_id' => $languageId],
                        $translationData
                    );
                }
            }

            // Eliminar todos los precios existentes para este tour
            $tour->prices()->delete();

            // Guardar precios desde la estructura anidada
            foreach ($this->prices as $ageStageId => $priceData) {
                // Verificar si esta etapa de edad está activa
                if (isset($priceData['active']) && $priceData['active']) {
                    // Si tiene nacionalidades configuradas, usar estructura compleja
                    if (isset($priceData['nationalities']) && !empty($priceData['nationalities'])) {
                        foreach ($priceData['nationalities'] as $nationalityData) {
                            if (isset($nationalityData['nationality_id']) && !empty($nationalityData['nationality_id'])) {
                                // Guardar cada rango de precio como un registro separado
                                if (isset($nationalityData['ranges']) && !empty($nationalityData['ranges'])) {
                                    foreach ($nationalityData['ranges'] as $range) {
                                        if (isset($range['price']) && $range['price'] > 0) {
                                            $tour->prices()->create([
                                                'tour_id' => $tour->id,
                                                'age_stage_id' => $ageStageId,
                                                'nationality_id' => $nationalityData['nationality_id'],
                                                'price_type' => 'per_person',
                                                'amount' => $range['price'],
                                                'min_quantity' => $range['from'] ?? 1,
                                                'max_quantity' => $range['to'] ?? null,
                                                'active' => true,
                                            ]);
                                        }
                                    }
                                }
                            }
                        }
                    } else {
                        // Precio simple sin nacionalidad (fallback al sistema anterior)
                        if (isset($priceData['amount']) && $priceData['amount'] > 0) {
                            $tour->prices()->create([
                                'tour_id' => $tour->id,
                                'age_stage_id' => $ageStageId,
                                'nationality_id' => null,
                                'price_type' => $priceData['price_type'] ?? 'per_person',
                                'amount' => $priceData['amount'],
                                'min_quantity' => $priceData['min_quantity'] ?? 1,
                                'max_quantity' => $priceData['max_quantity'] ?? null,
                                'active' => true,
                            ]);
                        }
                    }
                }
            }

            if (!empty($this->selectedCategories)) {
                $tour->categories()->sync($this->selectedCategories);
            }

            // Guardar imágenes de la galería
            if (!empty($this->tempImages)) {
                // Eliminar imágenes existentes que ya no están en tempImages
                $existingImageIds = collect($this->tempImages)->pluck('id')->filter()->toArray();
                $tour->mediaGallery()->whereNotIn('id', $existingImageIds)->delete();

                // Guardar/actualizar cada imagen
                foreach ($this->tempImages as $index => $imageData) {
                    $mediaData = [
                        'tour_id' => $tour->id,
                        'language_id' => $this->primary_language_id,
                        'image_path' => $imageData['path'],
                        'alt_text' => $imageData['alt_text'] ?? '',
                        'title_text' => $imageData['title_text'] ?? '',
                        'description' => $imageData['description'] ?? '',
                        'order' => $index + 1,
                    ];

                    if (isset($imageData['id'])) {
                        // Actualizar imagen existente
                        $tour->mediaGallery()->where('id', $imageData['id'])->update($mediaData);
                    } else {
                        // Crear nueva imagen
                        $tour->mediaGallery()->create($mediaData);
                    }
                }
            }

            // Guardar puntos del mapa
            if (!empty($this->mapPoints)) {
                // Eliminar puntos existentes
                $tour->mapPoints()->delete();

                // Guardar nuevos puntos
                foreach ($this->mapPoints as $index => $point) {
                    $tour->mapPoints()->create([
                        'tour_id' => $tour->id,
                        'name' => $point['name'],
                        'description' => $point['description'] ?? '',
                        'coordinates' => $point['coordinates'], // Ya viene en formato "lat,lng" desde JS
                        'type' => $point['type'] ?? 'punto_parada',
                        'order' => $point['order'] ?? ($index + 1),
                    ]);
                }
            }

            \DB::commit();

            // Si estamos en el último paso o es un tour nuevo, redirigir al índice
            if ($this->currentStep == $this->totalSteps || !$this->tourId) {
                session()->flash('success', 'Tour guardado exitosamente');
                return redirect()->route('admin.tours.index');
            }

            // Si estamos editando y no es el último paso, solo mostrar mensaje y actualizar el tourId
            $this->tourId = $tour->id;
            session()->flash('message', '✓ Cambios guardados exitosamente');

        } catch (\Exception $e) {
            \DB::rollBack();
            session()->flash('error', 'Error: ' . $e->getMessage());
        }
    }

    public function render()
    {
        return view('livewire.tour-wizard');
    }
}
