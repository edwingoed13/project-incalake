<?php

namespace App\Livewire\TourWizard;

use Livewire\Component;
use App\Models\Language;
use Illuminate\Support\Str;

class TourWizardTranslations extends Component
{
    public $tourId;
    public $primary_language_id;
    public $translations = [];
    public $languages;
    public $activeTab = 1;

    protected function rules()
    {
        $rules = [];
        
        if (isset($this->primary_language_id)) {
            $rules["translations.{$this->primary_language_id}.h1_title"] = 'required|string|max:100';
            $rules["translations.{$this->primary_language_id}.meta_title"] = 'required|string|max:60';
            $rules["translations.{$this->primary_language_id}.meta_description"] = 'required|string|max:160';
            $rules["translations.{$this->primary_language_id}.slug"] = 'required|string|max:150';
        }

        return $rules;
    }

    public function mount($translations = [], $primary_language_id = null, $languageId = null)
    {
        $this->translations = $translations;
        $this->primary_language_id = $primary_language_id;
        $this->languages = Language::whereIn('code', ['ES', 'EN', 'FR', 'DE', 'PT', 'IT'])->get();
        
        if ($languageId) {
            $this->activeTab = $languageId;
        }
    }

    public function updatedTranslations($value, $key)
    {
        if (preg_match('/\.(\d+)\.h1_title/', $key, $matches)) {
            $languageId = $matches[1];
            if (!empty($value) && empty($this->translations[$languageId]['slug'])) {
                $this->translations[$languageId]['slug'] = Str::slug($value);
                $this->translations[$languageId]['meta_title'] = $value;
                $this->translations[$languageId]['slug']; 
            }
        }

        if (preg_match('/\.(\d+)\.short_description/', $key, $matches)) {
            $languageId = $matches[1];
            $trimmedValue = trim(strip_tags($value));
            if (!empty($trimmedValue) && empty($this->translations[$languageId]['meta_description'])) {
                $this->translations[$languageId]['meta_description'] = substr($trimmedValue, 0, 160);
            }
        }
    }

    public function generateSlugForLanguage($languageId)
    {
        $languageCode = $this->languages->find($languageId)?->code ?? 'EN';
        $shortTitle = array_key_exists($languageId, $this->translations) 
            ? $this->translations[$languageId]['h1_title'] 
            : '';
        
        if (empty($shortTitle)) {
            $this->translations[$languageId]['slug'] = '';
            return;
        }

        $slug = Str::slug($shortTitle);
        $languageSuffix = strtolower($languageCode);
        $this->translations[$languageId]['slug'] = $slug . '-' . $languageSuffix;
    }

    public function setLanguageTab($languageId)
    {
        $this->activeTab = $languageId;
    }

    public function render()
    {
        return view('livewire.tour-wizard.translations');
    }
}