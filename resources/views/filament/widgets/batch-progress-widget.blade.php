<x-filament-widgets::widget>
    <x-filament::section>
        <x-slot name="heading">
            Progreso de evaluación de CVs
        </x-slot>

        <div wire:poll.5s class="space-y-4">

            @forelse ($this->getBatches() as $batch)
                <div class="p-4 rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-800">

                    {{-- Cabecera --}}
                    <div class="flex items-center justify-between mb-2">
                        <span class="text-sm font-medium text-gray-700 dark:text-gray-200">
                            {{ $batch['nombre'] }}
                        </span>

                        {{-- Badge de estado --}}
                        @if ($batch['cancelado'])
                            <span class="text-xs px-2 py-1 rounded-full bg-gray-100 text-gray-600">Cancelado</span>
                        @elseif ($batch['finalizado'])
                            <span class="text-xs px-2 py-1 rounded-full bg-green-100 text-green-700">✅ Completado</span>
                        @else
                            <span class="text-xs px-2 py-1 rounded-full bg-blue-100 text-blue-700 animate-pulse">⏳ En proceso</span>
                        @endif
                    </div>

                    {{-- Barra de progreso --}}
                    <div class="w-full bg-gray-200 dark:bg-gray-700 rounded-full h-3 mb-2">
                        <div
                            class="h-3 rounded-full transition-all duration-500 {{ $batch['fallidos'] > 0 ? 'bg-amber-500' : 'bg-green-500' }}"
                            style="width: {{ $batch['progreso'] }}%"
                        ></div>
                    </div>

                    {{-- Stats --}}
                    <div class="flex items-center justify-between text-xs text-gray-500 dark:text-gray-400">
                        <div class="flex gap-4">
                            <span>✅ {{ $batch['completados'] }} completados</span>
                            <span>⏳ {{ $batch['pendientes'] }} pendientes</span>
                            @if ($batch['fallidos'] > 0)
                                <span class="text-red-500">❌ {{ $batch['fallidos'] }} errores</span>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span>{{ $batch['progreso'] }}%</span>
                            <span class="text-gray-400">· {{ $batch['creado'] }}</span>
                        </div>
                    </div>

                </div>
            @empty
                <p class="text-sm text-gray-400 text-center py-4">
                    No hay procesos de evaluación recientes.
                </p>
            @endforelse

        </div>
    </x-filament::section>
</x-filament-widgets::widget>