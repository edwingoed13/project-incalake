<?php

namespace App\Livewire\TourWizard;

use Livewire\Component;
use App\Models\City;
use App\Models\Language;
use App\Exceptions\TourCodeGenerationException;
use Illuminate\Support\Facades\Log;

class TourWizardBasicInfo extends Component
{
    public $tourId;
    public $primary_language_id;
    public $code, $city_id, $city_name, $service_type = 'tour', $status = 'draft';
    public $difficulty = 'moderate', $target_audience = 'all';
    public $capacity = 99, $cupos;

    public $departure_time;
    public $departure_period = "AM";
    public $duration_quantity;
    public $duration_unit = "hours";
    public $timezone = "America/Lima";

    public $duration_days = 0, $duration_hours;
    public $start_time, $booking_anticipation_hours = 24, $data_requirement = 1;
    public $index_status = 'index', $follow_status = 'follow', $active = true;

    public $cities, $languages;

    protected function rules()
    {
        return [
            'code' => 'required|string|max:100|unique:tours,code,' . $this->tourId,
            'city_name' => 'required|string|max:255',
            'service_type' => 'required|in:tour,package,experience,transport',
            'difficulty' => 'required|in:easy,moderate,hard',
            'capacity' => 'required|integer|min:1',
            'primary_language_id' => 'required|exists:languages,id',
            'departure_time' => 'required',
            'departure_period' => 'required|in:AM,PM',
            'duration_quantity' => 'required|integer|min:1',
            'duration_unit' => 'required|in:days,hours,minutes',
            'timezone' => 'required',
        ];
    }

    public function mount($tourId = null)
    {
        $this->tourId = $tourId;
        $this->cities = City::active()->orderBy('name')->get();
        $this->languages = Language::whereIn('code', ['ES', 'EN', 'FR', 'DE', 'PT', 'IT'])->get();

        // Si es un tour nuevo (no estamos editando), establecer idioma primario predeterminado y generar código
        if (!$this->tourId && !$this->primary_language_id) {
            // Establecer español como idioma predeterminado
            $defaultLanguage = $this->languages->where('code', 'ES')->first();
            if ($defaultLanguage) {
                $this->primary_language_id = $defaultLanguage->id;
                $this->generateCode();
            }
        }
    }

    public function updatedPrimaryLanguageId($value)
    {
        if ($value) {
            $this->generateCode();
        }
    }

    protected function generateCode()
    {
        try {
            $languageCode = $this->languages->find($this->primary_language_id)?->code ?? 'EN';
            $prefixLength = 2;
            $prefix = strtoupper(substr($languageCode, 0, $prefixLength));

            // Obtener el último tour con este prefijo
            $lastTour = \App\Models\Tour::where('code', 'LIKE', $prefix . '%')
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
            Log::error("Error generating tour code", ['error' => $e->getMessage()]);
            // Fallback: usar contador total
            $this->code = 'TOUR' . str_pad(\App\Models\Tour::count() + 1, 3, '0', STR_PAD_LEFT);
        }
    }

    public function render()
    {
        return view('livewire.tour-wizard.basic-info');
    }
}