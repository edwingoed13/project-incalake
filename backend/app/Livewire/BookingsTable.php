<?php

namespace App\Livewire;

use App\Models\Booking;
use Livewire\Component;
use Livewire\WithPagination;

class BookingsTable extends Component
{
    use WithPagination;

    public $search = '';
    public $status = '';
    public $payment_status = '';

    public function updatingSearch()
    {
        $this->resetPage();
    }

    public function updatingStatus()
    {
        $this->resetPage();
    }

    public function updatingPaymentStatus()
    {
        $this->resetPage();
    }

    public function deleteBooking($bookingId)
    {
        $booking = Booking::find($bookingId);
        if ($booking) {
            $booking->delete();
            session()->flash('success', 'Reserva eliminada exitosamente');
        }
    }

    public function render()
    {
        $query = Booking::with(['tour']);

        // Search
        if ($this->search != '') {
            $query->where(function($q) {
                $q->where('booking_code', 'like', "%{$this->search}%")
                  ->orWhere('customer_name', 'like', "%{$this->search}%")
                  ->orWhere('customer_email', 'like', "%{$this->search}%")
                  ->orWhere('customer_phone', 'like', "%{$this->search}%");
            });
        }

        // Filter by status
        if ($this->status !== '') {
            $query->where('status', $this->status);
        }

        // Filter by payment_status
        if ($this->payment_status !== '') {
            $query->where('payment_status', $this->payment_status);
        }

        $bookings = $query->latest()->paginate(15);

        return view('livewire.bookings-table', [
            'bookings' => $bookings
        ]);
    }
}
