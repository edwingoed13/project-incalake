<div x-data="{ activeTab: {{ $activeTab }} }">
    <div class="border border-gray-200 dark:border-gray-700 rounded-lg p-6 mb-6">
        <h3 class="text-lg font-semibold mb-4">Traducciones & SEO</h3>

        <div class="flex flex-wrap gap-2 mb-4">
            @foreach($this->languages as $language)
                <button 
                    type="button"
                    @click="activeTab = {{ $language['id'] }}"
                    :class="activeTab === {{ $language['id'] }} 
                        ? 'bg-indigo-600 text-white' 
                        : 'bg-gray-200 dark:bg-gray-700 text-gray-700 dark:text-gray-300'"
                    class="px-4 py-2 rounded-lg transition-colors"
                >
                    {{ $language['name'] }} ({{ $language['code'] }})
                    @if($primary_language_id == $language['id']) <span class="ml-1 text-yellow-300">*</span> @endif
                </button>
            @endforeach
        </div>

        @foreach($this->languages as $language)
            <div x-show="activeTab === {{ $language['id'] }}" x-transition>
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            H1 Title
                            @if($primary_language_id == $language['id']) <span class="text-red-500">*</span> @endif
                        </label>
                        <input type="text" 
                            wire:model="translations.{{ $language['id'] }}.h1_title" 
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" 
                            placeholder="Ej: Tour a Machu Picchu">
                        @error("translations.{$language['id']}.h1_title") <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Slug / URL
                            @if($primary_language_id == $language['id']) <span class="text-red-500">*</span> @endif
                        </label>
                        <div class="flex">
                            <input type="text" 
                                wire:model="translations.{{ $language['id'] }}.slug" 
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-l-lg p-2.5 dark:bg-gray-700 dark:text-white" 
                                placeholder="Ej: tour-a-machu-picchu-es">
                            <button type="button" wire:click="generateSlugForLanguage({{ $language['id'] }})" class="px-3 bg-gray-500 text-white rounded-r-lg hover:bg-gray-600">
                                Auto
                            </button>
                        </div>
                        @error("translations.{$language['id']}.slug") <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Meta Title (Máx 60 caracteres)
                            @if($primary_language_id == $language['id']) <span class="text-red-500">*</span> @endif
                        </label>
                        <input type="text" 
                            wire:model="translations.{{ $language['id'] }}.meta_title" 
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" 
                            placeholder="Ej: Tour a Machu Picchu - Incalake">
                        <p class="text-xs text-gray-500 mt-1">
                            <span x-text="{{ $translations[$language['id']]['meta_title'] ?? '' }}.length">0</span> / 60
                        </p>
                        @error("translations.{$language['id']}.meta_title") <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">
                            Meta Description (Máx 160 caracteres)
                            @if($primary_language_id == $language['id']) <span class="text-red-500">*</span> @endif
                        </label>
                        <textarea wire:model="translations.{{ $language['id'] }}.meta_description" rows="3"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" 
                            placeholder="Ej: Descubre el misterio y la belleza de Machu Picchu"></textarea>
                        <p class="text-xs text-gray-500 mt-1">
                            <span x-text="{{ $translations[$language['id']]['meta_description'] ?? '' }}.length">0</span> / 160
                        </p>
                        @error("translations.{$language['id']}.meta_description") <p class="text-red-500 text-sm mt-1">{{ $message }}</p> @enderror
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Short Description</label>
                        <textarea wire:model="translations.{{ $language['id'] }}.short_description" rows="2"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" 
                            placeholder="Breve descripción para resumen"></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">Long Description</label>
                        <textarea wire:model="translations.{{ $language['id'] }}.long_description" rows="5"
                            class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" 
                            placeholder="Descripción completa del tour..."></textarea>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300 mb-2">CTA Text</label>
                            <input type="text" 
                                wire:model="translations.{{ $language['id'] }}.cta_text" 
                                class="w-full border border-gray-300 dark:border-gray-600 rounded-lg p-2.5 dark:bg-gray-700 dark:text-white" 
                                placeholder="Ej: Reservar Ahora">
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    </div>
</div>