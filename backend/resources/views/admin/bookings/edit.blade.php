@extends('admin.layout')

@section('title', 'Editar Reserva')
@section('page-title', 'Editar Reserva #' . $booking->booking_code)

@section('header-actions')
    <a href="{{ route('admin.bookings.show', $booking) }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-150 ease-in-out">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver a Detalle
    </a>
@endsection

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
        @if ($errors->any())
            <div class="mb-6 px-4 py-3 rounded-lg bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200 border border-red-200 dark:border-red-800">
                <ul class="list-disc list-inside">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('admin.bookings.update', $booking) }}" method="POST">
            @csrf
            @method('PUT')

            <!-- Booking Status -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Estado de Reserva</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estado Reserva *</label>
                        <select name="status" id="status" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="pending" {{ $booking->status == 'pending' ? 'selected' : '' }}>Pendiente</option>
                            <option value="confirmed" {{ $booking->status == 'confirmed' ? 'selected' : '' }}>Confirmada</option>
                            <option value="cancelled" {{ $booking->status == 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                            <option value="completed" {{ $booking->status == 'completed' ? 'selected' : '' }}>Completada</option>
                        </select>
                    </div>
                    <div>
                        <label for="payment_status" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Estado Pago *</label>
                        <select name="payment_status" id="payment_status" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="pending" {{ $booking->payment_status == 'pending' ? 'selected' : '' }}>Pendiente</option>
                            <option value="paid" {{ $booking->payment_status == 'paid' ? 'selected' : '' }}>Pagado</option>
                            <option value="refunded" {{ $booking->payment_status == 'refunded' ? 'selected' : '' }}>Reembolsado</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información del Cliente</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="customer_name" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Nombre *</label>
                        <input type="text" name="customer_name" id="customer_name" value="{{ old('customer_name', $booking->customer_name) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="customer_email" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Email *</label>
                        <input type="email" name="customer_email" id="customer_email" value="{{ old('customer_email', $booking->customer_email) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="customer_phone" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Teléfono</label>
                        <input type="text" name="customer_phone" id="customer_phone" value="{{ old('customer_phone', $booking->customer_phone) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="customer_country" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">País</label>
                        <input type="text" name="customer_country" id="customer_country" value="{{ old('customer_country', $booking->customer_country) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label for="customer_notes" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Notas del Cliente</label>
                        <textarea name="customer_notes" id="customer_notes" rows="3" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('customer_notes', $booking->customer_notes) }}</textarea>
                    </div>
                </div>
            </div>

            <!-- Tour Info -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información del Tour</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label for="tour_id" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Tour *</label>
                        <select name="tour_id" id="tour_id" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            @foreach($tours as $tour)
                                @php
                                    $translation = $tour->translations->where('language_id', 1)->first();
                                @endphp
                                <option value="{{ $tour->id }}" {{ $booking->tour_id == $tour->id ? 'selected' : '' }}>
                                    {{ $translation?->h1_title ?? 'Sin título' }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label for="tour_date" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Fecha del Tour *</label>
                        <input type="date" name="tour_date" id="tour_date" value="{{ old('tour_date', $booking->tour_date?->format('Y-m-d')) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="tour_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hora del Tour</label>
                        <input type="time" name="tour_time" id="tour_time" value="{{ old('tour_time', $booking->tour_time?->format('H:i')) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="pickup_location" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Lugar de Recojo</label>
                        <input type="text" name="pickup_location" id="pickup_location" value="{{ old('pickup_location', $booking->pickup_location) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="pickup_time" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Hora de Recojo</label>
                        <input type="text" name="pickup_time" id="pickup_time" value="{{ old('pickup_time', $booking->pickup_time) }}" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Participants -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Participantes</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label for="adults" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Adultos *</label>
                        <input type="number" name="adults" id="adults" min="0" value="{{ old('adults', $booking->adults) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="children" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Niños *</label>
                        <input type="number" name="children" id="children" min="0" value="{{ old('children', $booking->children) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="infants" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Infantes *</label>
                        <input type="number" name="infants" id="infants" min="0" value="{{ old('infants', $booking->infants) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                </div>
            </div>

            <!-- Payment -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información de Pago</h3>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="subtotal" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Subtotal *</label>
                        <input type="number" name="subtotal" id="subtotal" step="0.01" min="0" value="{{ old('subtotal', $booking->subtotal) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="discount" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Descuento *</label>
                        <input type="number" name="discount" id="discount" step="0.01" min="0" value="{{ old('discount', $booking->discount) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="total" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Total *</label>
                        <input type="number" name="total" id="total" step="0.01" min="0" value="{{ old('total', $booking->total) }}" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                    </div>
                    <div>
                        <label for="currency" class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Moneda *</label>
                        <select name="currency" id="currency" required class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">
                            <option value="USD" {{ $booking->currency == 'USD' ? 'selected' : '' }}>USD</option>
                            <option value="PEN" {{ $booking->currency == 'PEN' ? 'selected' : '' }}>PEN</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- Admin Notes -->
            <div class="mb-6">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notas del Administrador</h3>
                <textarea name="admin_notes" id="admin_notes" rows="4" class="w-full rounded-md border-gray-300 dark:border-gray-600 dark:bg-gray-700 dark:text-white shadow-sm focus:border-indigo-500 focus:ring-indigo-500">{{ old('admin_notes', $booking->admin_notes) }}</textarea>
            </div>

            <!-- Actions -->
            <div class="flex justify-end space-x-3">
                <a href="{{ route('admin.bookings.show', $booking) }}" class="px-6 py-2 bg-gray-300 dark:bg-gray-600 hover:bg-gray-400 dark:hover:bg-gray-500 text-gray-700 dark:text-gray-200 rounded-lg font-semibold transition duration-150 ease-in-out">
                    Cancelar
                </a>
                <button type="submit" class="px-6 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg font-semibold transition duration-150 ease-in-out">
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
