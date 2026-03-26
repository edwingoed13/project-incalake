<?php

namespace App\Livewire;

use App\Models\Language;
use Livewire\Component;
use Livewire\WithPagination;

class LanguagesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $showEditModal = false;
    public $editingLanguage = null;
    public $country = '';
    public $code = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function editLanguage($languageId)
    {
        $this->editingLanguage = Language::find($languageId);
        if ($this->editingLanguage) {
            $this->country = $this->editingLanguage->country;
            $this->code = $this->editingLanguage->code;
            $this->showEditModal = true;
        }
    }

    public function updateLanguage()
    {
        $this->validate([
            'country' => 'required|string|max:128',
            'code' => 'required|string|max:2',
        ]);

        if ($this->editingLanguage) {
            $this->editingLanguage->update([
                'country' => $this->country,
                'code' => strtoupper($this->code),
            ]);

            session()->flash('success', 'Idioma actualizado exitosamente');
            $this->closeModal();
        }
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->editingLanguage = null;
        $this->country = '';
        $this->code = '';
        $this->resetValidation();
    }

    public function deleteLanguage($languageId)
    {
        $language = Language::find($languageId);
        if ($language) {
            $language->delete();
            session()->flash('success', 'Idioma eliminado exitosamente');
        }
    }

    public function render()
    {
        $query = Language::query();

        if ($this->search != '') {
            $query->where(function($q) {
                $q->where('country', 'like', "%{$this->search}%")
                  ->orWhere('code', 'like', "%{$this->search}%");
            });
        }

        $languages = $query->latest()->paginate(15);

        return view('livewire.languages-table', [
            'languages' => $languages
        ]);
    }
}
