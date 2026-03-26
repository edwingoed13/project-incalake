<?php

namespace App\Livewire;

use App\Models\Tour;
use App\Models\City;
use App\Models\Language;
use Livewire\Component;
use Livewire\WithPagination;

class ToursTable extends Component
{
    use WithPagination;

    // Filters
    public $search = '';
    public $filterCity = '';
    public $filterStatus = '';
    public $filterServiceType = '';

    // Modal state
    public $showDeleteModal = false;
    public $tourToDelete = null;

    protected $queryString = [
        'search' => ['except' => ''],
        'filterCity' => ['except' => ''],
        'filterStatus' => ['except' => ''],
    ];

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function render()
    {
        $query = Tour::with(['city', 'translations.language', 'categories', 'prices']);

        // Search filter
        if ($this->search) {
            $query->where(function($q) {
                $q->where('code', 'like', "%{$this->search}%")
                  ->orWhereHas('translations', function($subQ) {
                      $subQ->where('h1_title', 'like', "%{$this->search}%")
                           ->orWhere('slug', 'like', "%{$this->search}%");
                  })
                  ->orWhereHas('city', function($subQ) {
                      $subQ->where('name', 'like', "%{$this->search}%");
                  });
            });
        }

        // City filter
        if ($this->filterCity) {
            $query->where('city_id', $this->filterCity);
        }

        // Status filter
        if ($this->filterStatus) {
            $query->where('status', $this->filterStatus);
        }

        // Service type filter
        if ($this->filterServiceType) {
            $query->where('service_type', $this->filterServiceType);
        }

        $tours = $query->latest()->paginate(15);
        $cities = City::active()->orderBy('name')->get();

        return view('livewire.tours-table', [
            'tours' => $tours,
            'cities' => $cities,
        ]);
    }

    public function confirmDelete($tourId)
    {
        $this->tourToDelete = $tourId;
        $this->showDeleteModal = true;
    }

    public function deleteTour()
    {
        if ($this->tourToDelete) {
            $tour = Tour::find($this->tourToDelete);
            if ($tour) {
                $tour->delete();
                session()->flash('success', 'Tour eliminado exitosamente');
            }
        }
        
        $this->showDeleteModal = false;
        $this->tourToDelete = null;
    }

    public function cancelDelete()
    {
        $this->showDeleteModal = false;
        $this->tourToDelete = null;
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->filterCity = '';
        $this->filterStatus = '';
        $this->filterServiceType = '';
        $this->resetPage();
    }
}
