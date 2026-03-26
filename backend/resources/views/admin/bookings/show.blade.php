@extends('admin.layout')

@section('title', 'Detalle de Reserva')
@section('page-title', 'Detalle de Reserva #' . $booking->booking_code)

@section('header-actions')
    <div class="flex space-x-3">
        <a href="{{ route('admin.bookings.edit', $booking) }}" class="inline-flex items-center px-4 py-2 bg-yellow-600 hover:bg-yellow-700 text-white font-semibold rounded-lg shadow-md transition duration-150 ease-in-out">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
            </svg>
            Editar Reserva
        </a>
        <a href="{{ route('admin.bookings.index') }}" class="inline-flex items-center px-4 py-2 bg-gray-600 hover:bg-gray-700 text-white font-semibold rounded-lg shadow-md transition duration-150 ease-in-out">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            Volver a Lista
        </a>
    </div>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <!-- Main Info -->
        <div class="lg:col-span-2 space-y-6">
            <!-- Booking Status Card -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Estado de Reserva</h2>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado Reserva</label>
                        @php
                            $statusColors = [
                                'pending' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                'confirmed' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                'cancelled' => 'bg-red-100 dark:bg-red-900 text-red-800 dark:text-red-200',
                                'completed' => 'bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200',
                            ];
                            $statusLabels = [
                                'pending' => 'Pendiente',
                                'confirmed' => 'Confirmada',
                                'cancelled' => 'Cancelada',
                                'completed' => 'Completada',
                            ];
                        @endphp
                        <div class="mt-1">
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full {{ $statusColors[$booking->status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$booking->status] ?? $booking->status }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Estado Pago</label>
                        @php
                            $paymentColors = [
                                'pending' => 'bg-yellow-100 dark:bg-yellow-900 text-yellow-800 dark:text-yellow-200',
                                'paid' => 'bg-green-100 dark:bg-green-900 text-green-800 dark:text-green-200',
                                'refunded' => 'bg-gray-100 dark:bg-gray-900 text-gray-800 dark:text-gray-200',
                            ];
                            $paymentLabels = [
                                'pending' => 'Pendiente',
                                'paid' => 'Pagado',
                                'refunded' => 'Reembolsado',
                            ];
                        @endphp
                        <div class="mt-1">
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full {{ $paymentColors[$booking->payment_status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $paymentLabels[$booking->payment_status] ?? $booking->payment_status }}
                            </span>
                        </div>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Método de Pago</label>
                        <div class="mt-1">
                            <span class="inline-flex px-3 py-1 text-sm font-semibold rounded-full bg-blue-100 dark:bg-blue-900 text-blue-800 dark:text-blue-200">
                                {{ strtoupper($booking->payment_method) }}
                            </span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Customer Info -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información del Cliente</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Nombre</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->customer_name }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Email</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->customer_email }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Teléfono</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->customer_phone ?? 'N/A' }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">País</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->customer_country ?? 'N/A' }}</p>
                    </div>
                    @if($booking->customer_notes)
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Notas del Cliente</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->customer_notes }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Tour Info -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Información del Tour</h2>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div class="md:col-span-2">
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Tour</label>
                        <p class="mt-1 text-gray-900 dark:text-white font-medium">{{ $booking->tour_title }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha del Tour</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->tour_date?->format('d/m/Y') }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Hora del Tour</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->tour_time?->format('H:i') }}</p>
                    </div>
                    @if($booking->pickup_location)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Lugar de Recojo</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->pickup_location }}</p>
                    </div>
                    @endif
                    @if($booking->pickup_time)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Hora de Recojo</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->pickup_time }}</p>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Participants Info -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Participantes</h2>
                <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Adultos</label>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $booking->adults }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Niños</label>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $booking->children }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Infantes</label>
                        <p class="mt-1 text-2xl font-bold text-gray-900 dark:text-white">{{ $booking->infants }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Total</label>
                        <p class="mt-1 text-2xl font-bold text-indigo-600 dark:text-indigo-400">{{ $booking->total_participants }}</p>
                    </div>
                </div>
            </div>

            @if($booking->admin_notes)
            <!-- Admin Notes -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Notas del Administrador</h2>
                <p class="text-gray-900 dark:text-white">{{ $booking->admin_notes }}</p>
            </div>
            @endif
        </div>

        <!-- Sidebar -->
        <div class="lg:col-span-1 space-y-6">
            <!-- Payment Summary -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Resumen de Pago</h2>
                <div class="space-y-3">
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Subtotal</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $booking->currency }} {{ number_format($booking->subtotal, 2) }}</span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600 dark:text-gray-400">Descuento</span>
                        <span class="text-gray-900 dark:text-white font-medium">{{ $booking->currency }} {{ number_format($booking->discount, 2) }}</span>
                    </div>
                    <div class="border-t border-gray-200 dark:border-gray-700 pt-3">
                        <div class="flex justify-between">
                            <span class="text-lg font-semibold text-gray-900 dark:text-white">Total</span>
                            <span class="text-lg font-bold text-indigo-600 dark:text-indigo-400">{{ $booking->currency }} {{ number_format($booking->total, 2) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Booking Details -->
            <div class="bg-white dark:bg-gray-800 shadow rounded-lg p-6">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-4">Detalles de Reserva</h2>
                <div class="space-y-3">
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Código</label>
                        <p class="mt-1 text-gray-900 dark:text-white font-mono">{{ $booking->booking_code }}</p>
                    </div>
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Creación</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->created_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @if($booking->paid_at)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Pago</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->paid_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                    @if($booking->payment_id)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">ID de Pago</label>
                        <p class="mt-1 text-gray-900 dark:text-white font-mono text-xs break-all">{{ $booking->payment_id }}</p>
                    </div>
                    @endif
                    @if($booking->cancelled_at)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Fecha de Cancelación</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->cancelled_at->format('d/m/Y H:i') }}</p>
                    </div>
                    @endif
                    @if($booking->cancellation_reason)
                    <div>
                        <label class="text-sm font-medium text-gray-500 dark:text-gray-400">Razón de Cancelación</label>
                        <p class="mt-1 text-gray-900 dark:text-white">{{ $booking->cancellation_reason }}</p>
                    </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
