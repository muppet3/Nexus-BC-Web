<div class="py-8 bg-zinc-950 min-h-screen text-zinc-100">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="p-6 bg-[#141414] border border-zinc-800 shadow-2xl sm:rounded-xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white">
                    🧾 Nueva Orden de Compra (PDF)
                </h2>
                <a href="{{ route('dashboard') }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">← Cancelar y Volver</a>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">PDF de la Orden de Compra</label>
                <input type="file" wire:model="file" accept="application/pdf"
                    class="block w-full text-sm text-zinc-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-zinc-800 file:text-zinc-200 hover:file:bg-zinc-700">
                @error('file') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                <div wire:loading wire:target="file" class="text-xs text-fuchsia-400 mt-2">Subiendo archivo...</div>
            </div>

            <div class="flex justify-end border-t border-zinc-800 pt-6">
                <button
                    wire:click="leerPdf"
                    wire:loading.attr="disabled"
                    wire:target="leerPdf"
                    class="relative inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-bold text-zinc-300 bg-zinc-900 border border-zinc-800 hover:border-fuchsia-500/50 hover:text-white transition-all shadow-[0_0_15px_rgba(192,38,211,0.1)] group disabled:opacity-50">
                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-blue-500/10 to-fuchsia-500/10 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg"></span>
                    <span class="relative" wire:loading.remove wire:target="leerPdf">🔍 Leer PDF</span>
                    <span class="relative" wire:loading wire:target="leerPdf">Leyendo PDF...</span>
                </button>
            </div>
        </div>

        @if ($showPreview)
            <div class="p-6 bg-[#141414] border border-zinc-800 shadow-2xl sm:rounded-xl">
                <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Folio (Orden de Compra)</label>
                        <input type="text" wire:model="po_number" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                        @error('po_number') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Fecha</label>
                        <input type="date" wire:model="order_date" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm custom-date-picker">
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Proveedor</label>
                        <input type="text" wire:model="supplier_name" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                    </div>
                </div>

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-zinc-100 flex items-center">
                        <span class="text-fuchsia-500 text-2xl mr-2">✓</span> Renglones detectados ({{ count($items) }})
                    </h3>
                    <span class="text-xs text-zinc-500 italic">Revisa y edita antes de guardar. "Nuevo" = se creará en el catálogo.</span>
                </div>

                <div class="overflow-x-auto border border-zinc-800 rounded-lg rounded-t-none">
                    <table class="min-w-full divide-y divide-zinc-800 text-sm">
                        <thead class="bg-[#0a0a0a] text-[10px] uppercase font-bold text-zinc-500">
                            <tr>
                                <th class="px-3 py-3 text-center w-10"></th>
                                <th class="px-2 py-3 text-left">Estado</th>
                                <th class="px-2 py-3 text-left">SKU</th>
                                <th class="px-2 py-3 text-left">Nombre</th>
                                <th class="px-2 py-3 text-left">Unidad</th>
                                <th class="px-2 py-3 text-right">Cantidad</th>
                                <th class="px-2 py-3 text-right">Precio Unit.</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800 bg-[#141414]">
                            @foreach ($items as $index => $item)
                                <tr wire:key="item-{{ $index }}" class="hover:bg-zinc-900/50 transition-colors">
                                    <td class="px-3 py-2 text-center border-r border-zinc-800">
                                        <button wire:click="removeItem({{ $index }})" class="text-zinc-600 hover:text-red-400 transition-colors" title="Eliminar renglón">🗑️</button>
                                    </td>
                                    <td class="px-2 py-2 text-[10px]">
                                        @if ($item['existe'])
                                            <span class="px-2 py-1 rounded bg-zinc-800 text-zinc-400 font-bold" title="{{ $item['existing_name'] }}">YA EXISTE</span>
                                        @else
                                            <span class="px-2 py-1 rounded bg-emerald-500/20 text-emerald-400 font-bold">NUEVO</span>
                                        @endif
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" wire:model="items.{{ $index }}.raw_sku" class="w-full text-xs font-bold text-fuchsia-400 border-0 bg-transparent focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" wire:model="items.{{ $index }}.raw_description" class="w-full text-xs text-zinc-200 border-0 bg-transparent focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" wire:model="items.{{ $index }}.unit" class="w-20 text-xs text-zinc-400 border-0 bg-transparent focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="number" wire:model="items.{{ $index }}.quantity_ordered" class="w-20 text-xs text-zinc-300 border-0 bg-transparent text-right focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                                    </td>
                                    <td class="px-1 py-1">
                                        <input type="text" wire:model="items.{{ $index }}.unit_price" class="w-24 text-xs font-bold text-emerald-400 border-0 bg-transparent text-right focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end border-t border-zinc-800 pt-6">
                    <button
                        wire:click="guardar"
                        class="relative inline-flex items-center px-8 py-3 rounded-lg text-lg font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 shadow-[0_0_20px_rgba(192,38,211,0.3)] transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                        💾 Guardar Orden de Compra
                    </button>
                </div>
            </div>
        @endif
    </div>

    <style>
        .custom-date-picker::-webkit-calendar-picker-indicator {
            filter: invert(1);
            cursor: pointer;
            opacity: 0.6;
        }
        .custom-date-picker::-webkit-calendar-picker-indicator:hover {
            opacity: 1;
        }
    </style>
</div>
