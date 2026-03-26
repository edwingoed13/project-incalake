<?php

namespace App\Livewire\TourWizard;

use Livewire\Component;
use App\Models\AgeStage;

class TourWizardPrices extends Component
{
    public $tourId;
    public $payment_method = 'all';
    public $prices = [];
    public $ageStages;

    protected function rules()
    {
        $rules = [];
        $hasActivePrice = false;

        $rules['payment_method'] = 'required|in:paypal,culqi,all';

        foreach ($this->prices as $ageStageId => $priceData) {
            if (isset($priceData['active']) && $priceData['active']) {
                $hasActivePrice = true;
                if (isset($priceData['ranges']) && is_array($priceData['ranges'])) {
                    foreach ($priceData['ranges'] as $rangeIndex => $range) {
                        $rules["prices.{$ageStageId}.ranges.{$rangeIndex}.from"] = 'required|integer|min:1';
                        $rules["prices.{$ageStageId}.ranges.{$rangeIndex}.to"] = 'required|integer|min:1';
                        $rules["prices.{$ageStageId}.ranges.{$rangeIndex}.price"] = 'required|numeric|min:0.01';
                    }
                }
            }
        }

        if (!$hasActivePrice) {
            $rules['prices'] = ['required', function ($attribute, $value, $fail) {
                $fail('Debe activar al menos una etapa de edad con precios configurados.');
            }];
        }

        return $rules;
    }

    public function mount($prices = [], $payment_method = null)
    {
        $this->prices = $prices;
        $this->payment_method = $payment_method ?? 'all';
        $this->ageStages = AgeStage::orderBy('min_age')->get();

        if (empty($this->prices)) {
            foreach ($this->ageStages as $ageStage) {
                $this->prices[$ageStage->id] = [
                    'active' => true,
                    'ranges' => [
                        ['from' => 1, 'to' => 1, 'price' => 0]
                    ]
                ];
            }
        }
    }

    public function togglePriceRange($ageStageId)
    {
        $this->prices[$ageStageId]['active'] = !$this->prices[$ageStageId]['active'];
    }

    public function addPriceRange($ageStageId)
    {
        $lastRange = end($this->prices[$ageStageId]['ranges']);
        $from = $lastRange['to'] + 1;

        $this->prices[$ageStageId]['ranges'][] = [
            'from' => $from,
            'to' => $from,
            'price' => 0
        ];
    }

    public function removePriceRange($ageStageId, $rangeIndex)
    {
        unset($this->prices[$ageStageId]['ranges'][$rangeIndex]);
        $this->prices[$ageStageId]['ranges'] = array_values($this->prices[$ageStageId]['ranges']);
    }

    public function render()
    {
        return view('livewire.tour-wizard.prices');
    }
}