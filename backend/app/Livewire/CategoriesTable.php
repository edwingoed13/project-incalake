<?php

namespace App\Livewire;

use App\Models\CategoryNew;
use App\Models\CategoryTranslation;
use App\Models\Language;
use Livewire\Component;
use Livewire\WithPagination;

class CategoriesTable extends Component
{
    use WithPagination;

    public $search = '';
    public $showEditModal = false;
    public $editingCategory = null;
    public $translations = [];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function editCategory($categoryId)
    {
        $this->editingCategory = CategoryNew::with(['translations.language'])->find($categoryId);

        if ($this->editingCategory) {
            // Cargar todas las traducciones en un array indexado por código de idioma
            $this->translations = [];
            foreach ($this->editingCategory->translations as $translation) {
                $langCode = $translation->language->code;
                $this->translations[$langCode] = [
                    'id' => $translation->id,
                    'name' => $translation->name,
                    'description' => $translation->description ?? '',
                    'slug' => $translation->slug,
                ];
            }

            $this->showEditModal = true;
        }
    }

    public function updateCategory()
    {
        // Validar todas las traducciones
        $rules = [];
        foreach ($this->translations as $langCode => $trans) {
            $rules["translations.{$langCode}.name"] = 'required|string|max:255';
            $rules["translations.{$langCode}.description"] = 'nullable|string';
            $rules["translations.{$langCode}.slug"] = 'required|string|max:150';
        }

        $this->validate($rules);

        // Actualizar cada traducción
        foreach ($this->translations as $langCode => $trans) {
            if (isset($trans['id'])) {
                CategoryTranslation::where('id', $trans['id'])->update([
                    'name' => $trans['name'],
                    'description' => $trans['description'],
                    'slug' => $trans['slug'],
                ]);
            }
        }

        session()->flash('success', 'Categoría actualizada exitosamente');
        $this->closeModal();
    }

    public function closeModal()
    {
        $this->showEditModal = false;
        $this->editingCategory = null;
        $this->translations = [];
        $this->resetValidation();
    }

    public function deleteCategory($categoryId)
    {
        $category = CategoryNew::find($categoryId);
        if ($category) {
            $category->delete(); // Las traducciones se eliminan en cascada
            session()->flash('success', 'Categoría eliminada exitosamente');
        }
    }

    public function render()
    {
        $query = CategoryNew::with(['translations.language']);

        if ($this->search != '') {
            $query->whereHas('translations', function($q) {
                $q->where('name', 'like', "%{$this->search}%")
                  ->orWhere('slug', 'like', "%{$this->search}%");
            });
        }

        $categories = $query->latest()->paginate(15);

        return view('livewire.categories-table', [
            'categories' => $categories
        ]);
    }
}
