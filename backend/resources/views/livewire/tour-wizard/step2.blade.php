@php
    // Obtener solo el idioma principal seleccionado en Step 1
    $primaryLanguage = collect($this->languages)->firstWhere('id', $this->primary_language_id);
@endphp

@if($primaryLanguage)
    <h3 class="text-lg font-semibold text-gray-900 dark:text-gray-100 mb-6">
        Traducción y SEO - {{ $primaryLanguage['country'] }} ({{ $primaryLanguage['code'] }})
    </h3>

    <div class="border-l-4 border-indigo-500 pl-6 py-4 bg-gray-50 dark:bg-gray-900">
        <h4 class="text-md font-bold text-gray-800 dark:text-gray-200 mb-4 flex items-center">
            <span class="inline-block w-8 h-8 rounded-full bg-indigo-600 text-white text-center leading-8 mr-3">{{ $primaryLanguage['code'] }}</span>
            {{ $primaryLanguage['country'] }}
        </h4>

        <div class="grid grid-cols-1 gap-4">
            {{-- H1 Title --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Título H1 * (máx. 100)</label>
                <input type="text" wire:model.blur="translations.{{ $primaryLanguage['id'] }}.h1_title" wire:blur="generateSlug({{ $primaryLanguage['id'] }})" maxlength="100" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <span class="text-xs text-gray-500">{{ strlen($translations[$primaryLanguage['id']]['h1_title'] ?? '') }}/100</span>
                @error('translations.' . $primaryLanguage['id'] . '.h1_title') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            {{-- Slug --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Slug URL * (auto-generado)</label>
                <input type="text" wire:model="translations.{{ $primaryLanguage['id'] }}.slug" readonly class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-gray-100 dark:bg-gray-900 text-gray-900 dark:text-gray-100">
            </div>

            {{-- Meta Title --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meta Title * (máx. 60)</label>
                <input type="text" wire:model="translations.{{ $primaryLanguage['id'] }}.meta_title" maxlength="60" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100">
                <span class="text-xs {{ strlen($translations[$primaryLanguage['id']]['meta_title'] ?? '') > 60 ? 'text-red-600' : 'text-green-600' }}">
                    {{ strlen($translations[$primaryLanguage['id']]['meta_title'] ?? '') }}/60
                </span>
                @error('translations.' . $primaryLanguage['id'] . '.meta_title') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            {{-- Meta Description --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Meta Description * (máx. 160)</label>
                <textarea wire:model="translations.{{ $primaryLanguage['id'] }}.meta_description" maxlength="160" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
                <span class="text-xs {{ strlen($translations[$primaryLanguage['id']]['meta_description'] ?? '') > 160 ? 'text-red-600' : 'text-green-600' }}">
                    {{ strlen($translations[$primaryLanguage['id']]['meta_description'] ?? '') }}/160
                </span>
                @error('translations.' . $primaryLanguage['id'] . '.meta_description') <span class="text-red-600 text-xs">{{ $message }}</span> @enderror
            </div>

            {{-- Short Description --}}
            <div>
                <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Descripción Corta</label>
                <textarea wire:model="translations.{{ $primaryLanguage['id'] }}.short_description" rows="3" class="w-full px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg bg-white dark:bg-gray-700 text-gray-900 dark:text-gray-100"></textarea>
            </div>


        </div>
    </div>
@else
    <div class="text-center py-12">
        <svg class="mx-auto h-12 w-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <h3 class="mt-4 text-lg font-medium text-gray-900 dark:text-gray-100">Idioma no seleccionado</h3>
        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">
            Por favor, selecciona un idioma principal en el Paso 1 antes de continuar.
        </p>
        <div class="mt-6">
            <button type="button" wire:click="goToStep(1)" class="px-4 py-2 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">
                Volver al Paso 1
            </button>
        </div>
    </div>
@endif
