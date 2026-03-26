@extends('admin.layout')

@section('title', 'Tours')
@section('page-title', 'Gestión de Tours')

@section('header-actions')
    <a href="{{ route('admin.tours.create') }}" class="inline-flex items-center px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow-md transition duration-150 ease-in-out">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
        </svg>
        Nueva Tour
    </a>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Mostrar mensaje de éxito si existe --}}
    @if(session('success'))
        <div id="success-message" class="bg-green-100 border-l-4 border-green-500 text-green-700 p-4 mb-4 rounded transition-opacity duration-500" role="alert">
            <p class="font-bold">✓ Éxito</p>
            <p>{{ session('success') }}</p>
        </div>
    @endif

    <livewire:tours-table />
</div>
@endsection

@push('scripts')
<script>
    // Auto-ocultar mensaje de éxito después de 3 segundos
    document.addEventListener('DOMContentLoaded', function() {
        const successMessage = document.getElementById('success-message');
        if (successMessage) {
            setTimeout(function() {
                successMessage.style.opacity = '0';
                setTimeout(function() {
                    successMessage.remove();
                }, 500); // Esperar a que termine la transición
            }, 3000); // Mostrar por 3 segundos
        }
    });
</script>
@endpush
