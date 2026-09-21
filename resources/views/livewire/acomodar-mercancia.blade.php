<div
    class="py-10 bg-zinc-950 min-h-screen text-zinc-100"
    x-on:producto-seleccionado.window="$nextTick(() => $refs.anaquelInput.focus())"
>
    <div class="max-w-5xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="p-8 bg-[#141414] border border-zinc-800 shadow-2xl sm:rounded-2xl">
            <div class="flex items-center justify-between mb-8">
                <div>
                    <h2 class="text-3xl font-bold text-white">📦 Acomodar Mercancía</h2>
                    <p class="text-sm text-zinc-500 mt-1">Solo ubicación — no se registra cantidad, esto no es un conteo.</p>
                </div>
                <a href="{{ route('dashboard') }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">← Volver</a>
            </div>

            @if (session('success'))
                <div class="mb-8 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-300 text-sm font-medium">
                    ✓ {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-8 p-4 bg-red-500/10 border border-red-500/30 rounded-xl text-red-300 text-sm font-medium">
                    ✕ {{ session('error') }}
                </div>
            @endif

            @if (! $productoActivo)
                {{-- ===================== BUSCADOR ===================== --}}
                <p class="text-sm text-zinc-500 mb-4">
                    Escanea el código de barras o teclea el <strong>SKU</strong> / nombre del producto que vas a reubicar.
                </p>

                <div class="relative">
                    <input
                        type="text"
                        x-ref="searchInput"
                        wire:model.live.debounce.200ms="search"
                        autofocus
                        placeholder="Código de barras, SKU o nombre..."
                        class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-xl text-zinc-200 focus:ring-emerald-500 focus:border-emerald-500 text-lg py-4 pl-5 pr-14"
                    >
                    @if ($search !== '')
                        <button
                            type="button"
                            @click="$refs.searchInput.value = ''; $refs.searchInput.dispatchEvent(new Event('input')); $refs.searchInput.focus()"
                            title="Limpiar"
                            class="absolute inset-y-0 right-0 px-5 flex items-center text-zinc-500 hover:text-white transition-colors text-lg">
                            ✕
                        </button>
                    @endif
                </div>

                @if (count($resultados) > 0)
                    <div class="mt-6 border border-zinc-800 rounded-xl divide-y divide-zinc-800 max-h-[32rem] overflow-y-auto">
                        @foreach ($resultados as $p)
                            <button
                                wire:click="seleccionar({{ $p['id'] }})"
                                class="w-full text-left px-6 py-4 hover:bg-zinc-900 transition-colors flex items-center justify-between gap-4">
                                <div class="min-w-0">
                                    <div class="text-base font-bold text-emerald-400">{{ $p['sku'] }}</div>
                                    <div class="text-sm text-zinc-400 truncate">{{ $p['name'] }}</div>
                                    <div class="text-xs text-zinc-500 mt-1">
                                        📍 {{ $p['ubicacion'] ?? 'Sin ubicación' }}
                                        &middot; Existencia: <span class="font-semibold text-zinc-300">{{ $p['stock_real'] }}</span>
                                    </div>
                                </div>
                                @if (empty($p['codigo_barras']))
                                    <span class="shrink-0 text-[11px] font-bold px-2.5 py-1.5 rounded bg-orange-500/10 text-orange-400 border border-orange-500/30">SIN CÓDIGO</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @elseif (strlen($search) >= 2)
                    <p class="mt-6 text-sm text-zinc-500 italic text-center">Sin resultados para "{{ $search }}".</p>
                @endif
            @else
                {{-- ===================== PRODUCTO SELECCIONADO ===================== --}}
                <div class="p-6 bg-[#0a0a0a] border border-zinc-800 rounded-xl mb-8">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <div class="text-xl font-bold text-white">{{ $productoActivo->name }}</div>
                            <div class="text-base text-emerald-400 font-bold mt-1">{{ $productoActivo->sku }}</div>
                        </div>
                        <div class="text-right shrink-0">
                            <div class="text-[11px] uppercase tracking-wider text-zinc-500 font-bold">Ubicación actual</div>
                            <div class="text-sm text-zinc-300 mt-1">{{ $productoActivo->ubicacion_completa ?? 'Sin ubicación' }}</div>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                    {{-- Columna izquierda: ubicación --}}
                    <div>
                        <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Ubicación nueva</h3>

                        <div class="space-y-5">
                            <div>
                                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Sección</label>
                                <select wire:model="seccionSeleccionada" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-xl text-zinc-200 focus:ring-emerald-500 focus:border-emerald-500 text-base py-3">
                                    @foreach ($secciones as $s)
                                        <option value="{{ $s }}">{{ trim($s) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Tipo de mueble</label>
                                <div class="flex gap-3">
                                    <button type="button" wire:click="setMuebleTipo('A')"
                                        class="flex-1 py-3 rounded-xl text-sm font-bold border transition-colors {{ $muebleTipo === 'A' ? 'bg-emerald-600 border-emerald-500 text-white' : 'bg-[#0a0a0a] border-zinc-800 text-zinc-400 hover:border-zinc-600' }}">
                                        A
                                    </button>
                                    <button type="button" wire:click="setMuebleTipo('RACK')"
                                        class="flex-1 py-3 rounded-xl text-sm font-bold border transition-colors {{ $muebleTipo === 'RACK' ? 'bg-emerald-600 border-emerald-500 text-white' : 'bg-[#0a0a0a] border-zinc-800 text-zinc-400 hover:border-zinc-600' }}">
                                        RACK
                                    </button>
                                </div>
                            </div>

                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">No.</label>
                                    <input type="text" x-ref="anaquelInput" wire:model="anaquel" autofocus
                                        class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-xl text-zinc-200 focus:ring-emerald-500 focus:border-emerald-500 text-base py-3">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Entrepaño</label>
                                    <input type="text" wire:model="entrepano"
                                        class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-xl text-zinc-200 focus:ring-emerald-500 focus:border-emerald-500 text-base py-3">
                                </div>
                            </div>
                        </div>
                    </div>

                    {{-- Columna derecha: código de barras opcional --}}
                    <div>
                        <h3 class="text-xs font-bold text-zinc-500 uppercase tracking-wider mb-4">Código de barras (opcional)</h3>
                        <p class="text-xs text-zinc-500 mb-3">Solo si vas a asignar o corregir uno de paso, mientras acomodas.</p>
                        <input type="text" wire:model="codigoBarras" placeholder="Escanea aquí si aplica..."
                            class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-xl text-zinc-200 focus:ring-emerald-500 focus:border-emerald-500 text-base py-3">
                        @error('codigoBarras') <span class="text-red-400 text-xs mt-2 block">{{ $message }}</span> @enderror

                        @if ($productoActivo->codigo_barras)
                            <p class="text-xs text-zinc-600 mt-3">Actual: <span class="text-zinc-400">{{ $productoActivo->codigo_barras }}</span></p>
                        @endif
                    </div>
                </div>

                <div class="mt-10 flex gap-4">
                    <button wire:click="cambiarProducto" class="px-6 py-3.5 bg-zinc-900 border border-zinc-800 rounded-xl text-sm font-bold text-zinc-400 hover:text-white transition-all">
                        ← Cambiar producto
                    </button>
                    <button
                        wire:click="guardarUbicacion"
                        wire:loading.attr="disabled"
                        wire:target="guardarUbicacion"
                        class="flex-1 inline-flex items-center justify-center px-6 py-3.5 rounded-xl text-base font-bold text-white bg-gradient-to-r from-emerald-500 to-teal-500 hover:from-emerald-400 hover:to-teal-400 shadow-[0_0_20px_rgba(16,185,129,0.3)] transition-all disabled:opacity-50">
                        💾 Guardar Ubicación
                    </button>
                </div>
            @endif
        </div>
    </div>

    {{-- Autorización de compañero (cambio de ubicación ya establecida) --}}
    <x-modal name="modal-auth-acomodo" :show="$showModalAuth" maxWidth="sm">
        <div class="p-6 bg-gray-900 text-white">
            <h3 class="text-lg font-bold text-center mb-2">🔐 Firma Requerida</h3>
            <p class="text-sm text-center text-emerald-400 mb-4">{{ $authMotivo }}</p>

            <div class="space-y-4">
                <div>
                    <x-input-label value="Usuario del Compañero" class="text-gray-300" />
                    <x-text-input wire:model="supervisorUsername" type="text" class="mt-1 block w-full bg-gray-800 border-gray-600 text-white" />
                </div>
                <div>
                    <x-input-label value="PIN de Autorización" class="text-gray-300" />
                    <x-text-input wire:model="supervisorPin" type="password" class="mt-1 block w-full bg-gray-800 border-gray-600 text-white" />
                </div>

                @if (session()->has('auth_error'))
                    <p class="text-sm text-red-400 text-center">{{ session('auth_error') }}</p>
                @endif
            </div>

            <div class="mt-6 flex justify-end gap-3">
                <x-secondary-button wire:click="$set('showModalAuth', false)" class="bg-gray-800 text-gray-300 border-gray-600 hover:bg-gray-700">Cancelar</x-secondary-button>
                <x-primary-button wire:click="guardarUbicacion(true)" class="bg-emerald-600 hover:bg-emerald-500">Autorizar y Guardar</x-primary-button>
            </div>
        </div>
    </x-modal>
</div>
