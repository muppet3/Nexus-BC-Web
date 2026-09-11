<div class="py-8 bg-zinc-950 min-h-screen text-zinc-100">
    <div class="max-w-2xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="p-6 bg-[#141414] border border-zinc-800 shadow-2xl sm:rounded-xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white">
                    🏷️ Asignar Código de Barras
                </h2>
                <a href="{{ route('dashboard') }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">← Volver</a>
            </div>

            @if (session('success'))
                <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-300 text-sm font-medium">
                    ✓ {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-6 p-4 bg-red-500/10 border border-red-500/30 rounded-xl text-red-300 text-sm font-medium">
                    ✕ {{ session('error') }}
                </div>
            @endif

            @if (! $productoActivo)
                <p class="text-sm text-zinc-500 mb-4">
                    Escanea el código de barras o teclea el <strong>SKU</strong> / nombre del producto.
                </p>

                <div class="relative">
                    <input
                        type="text"
                        x-ref="searchInput"
                        wire:model.live.debounce.200ms="search"
                        autofocus
                        placeholder="Código de barras, SKU o nombre..."
                        class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-base py-3 pr-12"
                    >
                    @if ($search !== '')
                        <button
                            type="button"
                            @click="$wire.limpiarBusqueda().then(() => $nextTick(() => $refs.searchInput.focus()))"
                            title="Limpiar"
                            class="absolute inset-y-0 right-0 px-4 flex items-center text-zinc-500 hover:text-white transition-colors">
                            ✕
                        </button>
                    @endif
                </div>

                @if (count($resultados) > 0)
                    <div class="mt-4 border border-zinc-800 rounded-lg divide-y divide-zinc-800 max-h-96 overflow-y-auto">
                        @foreach ($resultados as $p)
                            <button
                                wire:click="seleccionar({{ $p['id'] }})"
                                class="w-full text-left px-4 py-3 hover:bg-zinc-900 transition-colors flex items-center justify-between gap-3">
                                <div class="min-w-0">
                                    <div class="text-sm font-bold text-fuchsia-400">{{ $p['sku'] }}</div>
                                    <div class="text-xs text-zinc-400 truncate">{{ $p['name'] }}</div>
                                </div>
                                @if (empty($p['codigo_barras']))
                                    <span class="shrink-0 text-[10px] font-bold px-2 py-1 rounded bg-orange-500/10 text-orange-400 border border-orange-500/30">SIN CÓDIGO</span>
                                @else
                                    <span class="shrink-0 text-[10px] font-bold px-2 py-1 rounded bg-zinc-800 text-zinc-400">YA TIENE: {{ $p['codigo_barras'] }}</span>
                                @endif
                            </button>
                        @endforeach
                    </div>
                @elseif (strlen($search) >= 2)
                    <p class="mt-4 text-sm text-zinc-500 italic text-center">Sin resultados para "{{ $search }}".</p>
                @endif
            @else
                <div class="p-4 bg-[#0a0a0a] border border-zinc-800 rounded-lg mb-6">
                    <div class="text-lg font-bold text-white">{{ $productoActivo->name }}</div>
                    <div class="text-sm text-fuchsia-400 font-bold">{{ $productoActivo->sku }}</div>
                    <div class="mt-2 text-xs text-zinc-500">
                        Código actual:
                        @if ($productoActivo->codigo_barras)
                            <span class="text-zinc-300">{{ $productoActivo->codigo_barras }}</span>
                        @else
                            <span class="text-orange-400 font-bold">SIN CÓDIGO</span>
                        @endif
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3 mb-6">
                    <button
                        wire:click="imprimirCodigo"
                        wire:loading.attr="disabled"
                        wire:target="imprimirCodigo"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold text-blue-300 bg-blue-500/10 border border-blue-500/30 hover:bg-blue-500/20 transition-colors disabled:opacity-50">
                        🖨️ Imprimir Código de Barras
                    </button>
                    <button
                        wire:click="imprimirDescripcion"
                        wire:loading.attr="disabled"
                        wire:target="imprimirDescripcion"
                        class="inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-lg text-xs font-bold text-cyan-300 bg-cyan-500/10 border border-cyan-500/30 hover:bg-cyan-500/20 transition-colors disabled:opacity-50">
                        🏷️ Imprimir Descripción
                    </button>
                </div>

                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Nuevo Código de Barras</label>
                <div class="relative">
                    <input
                        type="text"
                        x-ref="codigoInput"
                        wire:model="codigoNuevo"
                        wire:keydown.enter="guardarCodigo"
                        autofocus
                        placeholder="Escanea aquí..."
                        class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-base py-3 pr-12"
                    >
                    @if ($codigoNuevo !== '')
                        <button
                            type="button"
                            @click="$wire.limpiarCodigoNuevo().then(() => $nextTick(() => $refs.codigoInput.focus()))"
                            title="Limpiar"
                            class="absolute inset-y-0 right-0 px-4 flex items-center text-zinc-500 hover:text-white transition-colors">
                            ✕
                        </button>
                    @endif
                </div>
                @error('codigoNuevo') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror

                <div class="mt-6 flex gap-3">
                    <button wire:click="cambiarProducto" class="px-5 py-2.5 bg-zinc-900 border border-zinc-800 rounded-lg text-sm font-bold text-zinc-400 hover:text-white transition-all">
                        ← Cambiar producto
                    </button>
                    <button
                        wire:click="guardarCodigo"
                        class="flex-1 inline-flex items-center justify-center px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 shadow-[0_0_15px_rgba(192,38,211,0.3)] transition-all">
                        💾 Guardar Código
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
