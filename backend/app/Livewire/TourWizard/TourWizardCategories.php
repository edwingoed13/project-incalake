<?php

namespace App\Livewire\TourWizard;

use Livewire\Component;
use App\Models\CategoryNew;

class TourWizardCategories extends Component
{
    public $tourId;
    public $selectedCategories = [];
    public $categories;

    public function mount($selectedCategories = [])
    {
        $this->selectedCategories = $selectedCategories;
        $this->categories = CategoryNew::where('active', true)
            ->with(['translations' => function($q) {
                $q->whereHas('language', function($query) {
                    $query->where('code', 'ES');
                });
            }])
            ->get();
    }

    public function toggleCategory($categoryId)
    {
        if (in_array($categoryId, $this->selectedCategories)) {
            $this->selectedCategories = array_diff($this->selectedCategories, [$categoryId]);
        } else {
            $this->selectedCategories[] = $categoryId;
        }
    }

    public function isSelected($categoryId)
    {
        return in_array($categoryId, $this->selectedCategories);
    }

    public function getCategoryName($category)
    {
        $translation = $category->translations->first();
        return $translation ? $translation->name : $category->name ?? 'Sin nombre';
    }

    public function render()
    {
        return view('livewire.tour-wizard.categories');
    }
}