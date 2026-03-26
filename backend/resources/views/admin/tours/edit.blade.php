@extends('admin.layout')

@section('title', 'Editar Tour')
@section('page-title', 'Editar Tour: ' . $tour->code)

@section('header-actions')
    <a href="{{ route('admin.tours.index') }}" class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-md shadow-sm text-sm font-medium text-gray-700 dark:text-gray-200 bg-white dark:bg-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
        </svg>
        Volver
    </a>
@endsection

@section('content')
<div class="max-w-7xl mx-auto">
    {{-- Usar el TourWizard en modo edición pasando el ID del tour --}}
    @livewire('tour-wizard', ['tourId' => $tour->id])
</div>
@endsection