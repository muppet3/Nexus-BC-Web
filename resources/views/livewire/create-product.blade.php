<div class="py-8 bg-zinc-950 min-h-screen text-zinc-100">
    <div class="max-w-3xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="p-6 bg-[#141414] border border-zinc-800 shadow-2xl sm:rounded-xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white">
                    ➕ Nuevo Producto
                </h2>
                <a href="{{ route('dashboard') }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">← Cancelar y Volver</a>
            </div>

            @if ($creado)
                <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-300 text-sm font-medium">
                    ✓ Producto creado correctamente. Puedes registrar otro.
                </div>
            @endif

            @if ($productoExistente)
                <div class="mb-6 p-4 bg-orange-500/10 border border-orange-500/30 rounded-xl">
                    <p class="text-sm font-bold text-orange-300 mb-1">⚠ Este producto ya existe, no se creó de nuevo:</p>
                    <p class="text-xs text-zinc-400">
                        <span class="font-bold text-zinc-200">{{ $productoExistente->sku }}</span>
                        — {{ $productoExistente->name }}
                        @if ($productoExistente->codigo_barras)
                            <span class="text-zinc-500">(código de barras: {{ $productoExistente->codigo_barras }})</span>
                        @endif
                    </p>
                </div>
            @endif

            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">SKU *</label>
                    <input type="text" wire:model="sku" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                    @error('sku') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Código de Barras (opcional)</label>
                    <input type="text" wire:model="codigo_barras" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                </div>

                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Nombre / Descripción *</label>
                    <input type="text" wire:model="name" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                    @error('name') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Unidad</label>
                    <select wire:model="unit" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                        <option value="pieza">pieza</option>
                        <option value="kit">kit</option>
                        <option value="metro">metro</option>
                        <option value="pie">pie</option>
                        <option value="centímetro">centímetro</option>
                        <option value="caja">caja</option>
                        <option value="paquete">paquete</option>
                        <option value="kilogramo">kilogramo</option>
                        <option value="galón">galón</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Marca</label>
                    <input type="text" wire:model="marca" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Grupo</label>
                    <input type="text" wire:model="grupo" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Línea</label>
                    <input type="text" wire:model="linea" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                </div>
            </div>

            <div class="flex justify-end border-t border-zinc-800 pt-6 mt-6">
                <button
                    wire:click="guardar"
                    class="relative inline-flex items-center px-8 py-3 rounded-lg text-lg font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 shadow-[0_0_20px_rgba(192,38,211,0.3)] transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    💾 Guardar Producto
                </button>
            </div>
        </div>
    </div>
</div>
