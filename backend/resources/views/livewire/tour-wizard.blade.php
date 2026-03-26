<div class="max-w-7xl mx-auto">
    {{-- Mensajes de sesión --}}
    @if (session()->has('message'))
        <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('message') }}</span>
        </div>
    @endif

    @if (session()->has('error'))
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <span class="block sm:inline">{{ session('error') }}</span>
        </div>
    @endif

    {{-- Errores de validación de Livewire --}}
    @if ($errors->any())
        <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative mb-4" role="alert">
            <strong class="font-bold">¡Error!</strong>
            <span class="block sm:inline">Por favor corrige los siguientes errores:</span>
            <ul class="list-disc list-inside mt-2">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="mb-8">
        <div class="flex items-center justify-between mb-4">
            @for ($i = 1; $i <= $totalSteps; $i++)
                <div class="flex-1 {{ $i < $totalSteps ? 'pr-2' : '' }}">
                    <button type="button" wire:click="goToStep({{ $i }})" class="w-full text-center {{ $currentStep >= $i ? 'text-indigo-600 dark:text-indigo-400' : 'text-gray-400 dark:text-gray-600' }}">
                        <div class="relative">
                            <div class="w-full h-2 {{ $currentStep >= $i ? 'bg-indigo-600' : 'bg-gray-200 dark:bg-gray-700' }} rounded"></div>
                            <div class="absolute top-0 left-1/2 transform -translate-x-1/2 -translate-y-2 w-8 h-8 rounded-full border-2 flex items-center justify-center text-sm font-semibold {{ $currentStep >= $i ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-gray-800 border-gray-300 dark:border-gray-600 text-gray-500' }}">{{ $i }}</div>
                        </div>
                        <div class="mt-6 text-xs font-medium">
                            @if ($i == 1) Información Básica
                            @elseif ($i == 2) Traducciones & SEO
                            @elseif ($i == 3) Contenido
                            @elseif ($i == 4) Precios
                            @elseif ($i == 5) Multimedia
                            @elseif ($i == 6) Opciones de Reserva
                            @elseif ($i == 7) Categorías
                            @elseif ($i == 8) Disponibilidad
                            @endif
                        </div>
                    </button>
                </div>
            @endfor
        </div>
    </div>

    <form wire:submit.prevent="save" id="tourWizardForm">
        <div class="bg-white dark:bg-gray-800 shadow-md rounded-lg p-6 mb-6">
            @include('livewire.tour-wizard.step' . $currentStep)
        </div>

        <div class="flex justify-between items-center">
            <button type="button" wire:click="previousStep" @if ($currentStep == 1) disabled @endif class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700 disabled:opacity-50 disabled:cursor-not-allowed">← Atrás</button>

            <div class="flex items-center gap-4">
                <div class="text-sm text-gray-600 dark:text-gray-400">Paso {{ $currentStep }} de {{ $totalSteps }}</div>

                @if($tourId)
                    {{-- Mostrar botón "Guardar" en modo edición --}}
                    <button type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                        <span wire:loading.remove>Guardar</span>
                        <span wire:loading>Guardando...</span>
                    </button>
                @endif
            </div>

            @if ($currentStep < $totalSteps)
                <button type="button" wire:click="nextStep" class="px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700">Siguiente →</button>
            @else
                <button type="submit" wire:loading.attr="disabled" wire:loading.class="opacity-50 cursor-not-allowed" class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700">
                    <span wire:loading.remove>Guardar Tour</span>
                    <span wire:loading>Guardando...</span>
                </button>
            @endif
        </div>
    </form>

    <script>
        // Sincronizar todos los editores TipTap con Livewire
        function syncAllTiptapEditors() {
            if (!window.tiptapEditors) {
                return;
            }

            Object.keys(window.tiptapEditors).forEach(editorId => {
                const editor = window.tiptapEditors[editorId];
                const hiddenInput = document.getElementById(editorId + '-hidden');

                if (editor && hiddenInput) {
                    const content = editor.getHTML();
                    hiddenInput.value = content;

                    // Disparar evento input/change para que Livewire lo capture
                    hiddenInput.dispatchEvent(new Event('input', { bubbles: true }));
                    hiddenInput.dispatchEvent(new Event('change', { bubbles: true }));
                }
            });
        }

        // Escuchar eventos de Livewire para sincronizar antes de guardar
        document.addEventListener('livewire:init', () => {
            Livewire.hook('commit', ({ component, commit, respond, succeed, fail }) => {
                syncAllTiptapEditors();
            });
        });
    </script>
</div>

@push('scripts')
<script>
    document.addEventListener('livewire:initialized', () => {
        Livewire.on('tour-saved', (event) => {
            // Mostrar mensaje de éxito
            const Toast = Swal.mixin({
                toast: true,
                position: 'top-end',
                showConfirmButton: false,
                timer: 3000,
                timerProgressBar: true
            });

            Toast.fire({
                icon: 'success',
                title: event.message || 'Tour guardado exitosamente'
            });

            // Redirigir después de 2 segundos
            setTimeout(() => {
                window.location.href = '{{ route('admin.tours.index') }}';
            }, 2000);
        });
    });
</script>
@endpush
