<div class="py-8 bg-zinc-950 min-h-screen text-zinc-100">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <div class="p-6 bg-[#141414] border border-zinc-800 shadow-2xl sm:rounded-xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white">
                    📦 Nuevo Aviso de Llegada
                </h2>
                <a href="{{ route('dashboard') }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">← Cancelar y Volver</a>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-4 gap-6 mb-8">
                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Sistema</label>
                    <select wire:model="system" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                        <option value="MARINA">Marina Ganadora</option>
                        <option value="MARA">Mara Marlin</option>
                    </select>
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Viaje</label>
                    <input type="text" wire:model="trip_number" placeholder="Ej: 794" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm placeholder-zinc-700">
                    @error('trip_number') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Pedimento</label>
                    <input type="text" wire:model="pedimento" placeholder="Ej: 5000823" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm placeholder-zinc-700">
                    @error('pedimento') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>

                <div>
                    <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Fecha Estimada / Llegada</label>
                    <input type="date" wire:model="arrival_date" class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm custom-date-picker">
                    @error('arrival_date') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="mb-6">
                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Pegar Datos del Excel</label>
                <textarea 
                    wire:model="rawText" 
                    rows="5" 
                    class="block w-full bg-[#0a0a0a] border border-zinc-800 rounded-lg text-zinc-300 font-mono text-xs focus:ring-fuchsia-500 focus:border-fuchsia-500 placeholder-zinc-700" 
                    placeholder="Pega aquí las columnas desde Excel (DR, Proveedor, Descripción, etc...)"></textarea>
                @error('rawText') <span class="text-red-400 text-xs mt-1">{{ $message }}</span> @enderror
                @error('parsedItems') <span class="text-red-400 text-xs mt-1">Debes previsualizar datos válidos antes de guardar.</span> @enderror
            </div>

            <div class="flex justify-end border-t border-zinc-800 pt-6">
                <button 
                    wire:click="parse" 
                    class="relative inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-bold text-zinc-300 bg-zinc-900 border border-zinc-800 hover:border-fuchsia-500/50 hover:text-white transition-all shadow-[0_0_15px_rgba(192,38,211,0.1)] group">
                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-blue-500/10 to-fuchsia-500/10 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg"></span>
                    <span class="relative">🔍 Previsualizar Datos</span>
                </button>
            </div>
        </div>

        @if($showPreview && count($parsedItems) > 0)
        <div class="p-6 bg-[#141414] border border-zinc-800 shadow-2xl sm:rounded-xl">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold text-zinc-100 flex items-center">
                    <span class="text-fuchsia-500 text-2xl mr-2">✓</span> Datos Detectados ({{ count($parsedItems) }} filas)
                </h3>
                <span class="text-xs text-zinc-500 italic">Revisa y edita los datos si es necesario antes de guardar.</span>
            </div>

            <div class="overflow-x-auto border border-zinc-800 rounded-lg rounded-t-none">
                <table class="min-w-full divide-y divide-zinc-800 text-sm">
                    <thead class="bg-[#0a0a0a] text-[10px] uppercase font-bold text-zinc-500">
                        <tr>
                            <th class="px-3 py-3 text-center w-10"></th>
                            <th class="px-2 py-3 text-left">DR</th>
                            <th class="px-2 py-3 text-left">PROV</th>
                            <th class="px-2 py-3 text-left">OC GPO</th>
                            <th class="px-2 py-3 text-left">REFERENCIA</th>
                            <th class="px-2 py-3 text-left">FACT. PROV</th>
                            <th class="px-2 py-3 text-right">VALOR $</th>
                            <th class="px-2 py-3 text-right">FLETE</th>
                            <th class="px-2 py-3 text-right">TOTAL</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-800 bg-[#141414]">
                        @foreach($parsedItems as $index => $item)
                        <tr wire:key="row-{{ $index }}" class="hover:bg-zinc-900/50 transition-colors group">
                            <td class="px-3 py-2 text-center align-middle border-r border-zinc-800">
                                <button wire:click="removeRow({{ $index }})" class="text-zinc-600 hover:text-red-400 transition-colors" title="Eliminar fila">
                                    🗑️
                                </button>
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" wire:model="parsedItems.{{ $index }}.dr_raw" class="w-full text-xs font-bold text-blue-400 border-0 bg-transparent focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" wire:model="parsedItems.{{ $index }}.supplier_raw" class="w-full text-xs text-zinc-200 border-0 bg-transparent focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" wire:model="parsedItems.{{ $index }}.oc_group_raw" class="w-20 text-xs text-fuchsia-400 border-0 bg-transparent focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" wire:model="parsedItems.{{ $index }}.description_raw" class="w-full text-xs text-zinc-400 border-0 bg-transparent focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" wire:model="parsedItems.{{ $index }}.invoice_provider_raw" class="w-24 text-[10px] text-zinc-500 border-0 bg-transparent focus:ring-0 px-1 hover:bg-zinc-800 rounded uppercase">
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" wire:model="parsedItems.{{ $index }}.value_raw" class="w-20 text-xs text-zinc-300 border-0 bg-transparent text-right focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" wire:model="parsedItems.{{ $index }}.freight_raw" class="w-16 text-xs text-zinc-300 border-0 bg-transparent text-right focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                            </td>
                            <td class="px-1 py-1">
                                <input type="text" wire:model="parsedItems.{{ $index }}.total_raw" class="w-20 text-xs font-bold text-emerald-400 border-0 bg-transparent text-right focus:ring-0 px-1 hover:bg-zinc-800 rounded">
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="mt-6 flex justify-end border-t border-zinc-800 pt-6">
                <button 
                    wire:click="save" 
                    class="relative inline-flex items-center px-8 py-3 rounded-lg text-lg font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 shadow-[0_0_20px_rgba(192,38,211,0.3)] transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    💾 Guardar Viaje Definitivo
                </button>
            </div>
        </div>
        @endif
    </div>

    <style>
        /* Estilizar el icono del calendario en modo oscuro */
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