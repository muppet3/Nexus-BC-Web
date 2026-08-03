<div class="py-8 bg-zinc-950 min-h-screen text-zinc-100">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        
        <div class="md:flex md:items-center md:justify-between mb-6">
            <div class="flex-1 min-w-0">
                <h2 class="text-3xl font-bold leading-7 text-white sm:truncate tracking-tight">📡 Rastreo Global</h2>
                <p class="mt-1 text-sm text-zinc-500 italic">Consulta histórica de partidas, folios y evidencias.</p>
            </div>
        </div>

        <div class="bg-[#141414] border border-zinc-800 rounded-xl p-5 mb-8 shadow-lg">
            <div class="grid grid-cols-1 md:grid-cols-12 gap-6 items-center">
                <div class="md:col-span-6 relative">
                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" /></svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" class="block w-full pl-11 pr-4 py-2.5 bg-[#0a0a0a] border-zinc-800 rounded-lg text-zinc-200 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm" placeholder="Buscar SKU, Proveedor, Folio MS, Factura..." />
                </div>
                <div class="md:col-span-2">
                    <select wire:model.live="selectedYear" class="block w-full py-2.5 px-4 bg-[#0a0a0a] border-zinc-800 rounded-lg text-zinc-300 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                        <option value="">📅 Todos los Años</option>
                        @foreach($years as $year) <option value="{{ $year }}">{{ $year }}</option> @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <select wire:model.live="statusFilter" class="block w-full py-2.5 px-4 bg-[#0a0a0a] border-zinc-800 rounded-lg text-zinc-300 focus:ring-fuchsia-500 focus:border-fuchsia-500 sm:text-sm">
                        <option value="">🌪️ Todos los Estatus</option>
                        <option value="pending_ms">⚠️ Pendiente Entrada MS</option>
                        <option value="pending_invoice">💲 Pendiente Factura</option>
                        <option value="closed">🔒 Cerrados Contabilidad</option>
                    </select>
                </div>
                <div class="md:col-span-1 flex justify-end">
                    <button wire:click="export" class="p-3 bg-emerald-500/10 border border-emerald-500/20 text-emerald-400 hover:bg-emerald-500/20 rounded-lg transition-all shadow-[0_0_15px_rgba(16,185,129,0.15)] group">
                        <svg class="h-6 w-6 group-hover:scale-110 transition-transform" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" /></svg>
                    </button>
                </div>
            </div>
        </div>

        <div class="bg-[#141414] border border-zinc-800 rounded-xl overflow-hidden shadow-2xl">
            <table class="min-w-full divide-y divide-zinc-800 text-sm">
                <thead class="bg-[#0a0a0a] text-[10px] uppercase font-bold text-zinc-500 border-b border-zinc-800">
                    <tr>
                        <th class="px-4 py-4 text-left">Viaje / Fecha</th>
                        <th class="px-4 py-4 text-left w-1/4">Descripción / Prov.</th>
                        <th class="px-4 py-4 text-left">Referencias</th>
                        <th class="px-4 py-4 text-center">Entradas</th>
                        <th class="px-4 py-4 text-center">Situación</th>
                        <th class="px-4 py-4 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    @forelse($items as $item)
                    <tr class="hover:bg-zinc-900/40 transition-colors group">
                        <td class="px-4 py-5 align-top">
                            <div class="text-xs font-bold text-blue-400 uppercase tracking-tighter">{{ $item->shipment->system }}</div>
                            <div class="text-sm font-bold text-zinc-100">Viaje {{ $item->shipment->trip_number }}</div>
                            <div class="text-[10px] text-zinc-500 italic">{{ $item->shipment->arrival_date ? $item->shipment->arrival_date->format('d/M/Y') : '-' }}</div>
                        </td>
                        <td class="px-4 py-5 align-top">
                            <div class="text-xs font-black text-zinc-300 uppercase leading-none mb-1">{{ $item->supplier_raw }}</div>
                            <div class="text-xs text-zinc-500 leading-snug line-clamp-2">{{ $item->description_raw }}</div>
                        </td>
                        <td class="px-4 py-5 align-top">
                            @if($item->oc_group_raw) <div class="text-[10px] text-fuchsia-400 font-bold mb-1">OC GPO: {{ $item->oc_group_raw }}</div> @endif
                            @if($item->invoice_provider_raw) <div class="text-[9px] bg-[#0a0a0a] text-zinc-400 px-2 py-0.5 rounded border border-zinc-800 w-fit">F.PROV: {{ $item->invoice_provider_raw }}</div> @endif
                        </td>
                        <td class="px-4 py-5 align-top text-center">
                            @if($item->entry_ms_folio) <span class="block text-[10px] text-blue-400 font-bold mb-1 bg-blue-500/10 border border-blue-500/20 rounded py-0.5">MS: {{ $item->entry_ms_folio }}</span> @endif
                            @if($item->entry_gpo_folio) <span class="block text-[10px] text-emerald-400 font-bold bg-emerald-500/10 border border-emerald-500/20 rounded py-0.5">GPO: {{ $item->entry_gpo_folio }}</span> @endif
                        </td>
                        <td class="px-4 py-5 align-top">
                             <div class="flex flex-col space-y-1.5">
                                <div class="flex items-center text-[10px] font-bold {{ $item->is_closed_accounting ? 'text-red-500' : 'text-zinc-600' }}">● <span class="ml-2 uppercase text-zinc-400">Conta: <span class="{{ $item->is_closed_accounting ? 'text-red-400' : 'text-blue-400' }}">{{ $item->is_closed_accounting ? 'Cerrado' : 'Abierto' }}</span></span></div>
                                <div class="flex items-center text-[10px] font-bold {{ $item->is_entered_ms ? 'text-emerald-400' : 'text-orange-500' }}">● <span class="ml-2 uppercase text-zinc-400">Físico: <span class="{{ $item->is_entered_ms ? 'text-emerald-400' : 'text-orange-400' }}">{{ $item->is_entered_ms ? 'Recibido' : 'Pendiente' }}</span></span></div>
                             </div>
                        </td>
                        <td class="px-4 py-5 align-middle text-right">
                             <a href="{{ route('shipments.show', $item->shipment_id) }}" class="text-zinc-600 group-hover:text-fuchsia-500 text-2xl font-bold transition-colors">→</a>
                        </td>
                    </tr>
                    @empty
                    <tr><td colspan="6" class="px-6 py-12 text-center text-zinc-600 italic">No se encontraron registros.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        <div class="mt-6">{{ $items->links() }}</div>
    </div>
</div>