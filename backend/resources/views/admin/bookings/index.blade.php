@extends('admin.layout')

@section('title', 'Reservas')
@section('page-title', 'Gestión de Reservas - Clientes (Web/OTAs)')

@section('header-actions')
    <a href="{{ route('admin.bookings.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition duration-150 ease-in-out">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva Reserva
    </a>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    <livewire:bookings-table />
</div>
@endsection
