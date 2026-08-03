<div class="py-8 bg-zinc-950 min-h-screen text-zinc-100">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        
        <div class="mb-8">
            <h2 class="text-3xl font-bold text-zinc-100 mb-6">Avisos de llegada ✈️</h2>
            
            <div class="grid grid-cols-1 gap-5 sm:grid-cols-3">
                <div class="bg-[#141414] overflow-hidden rounded-xl border border-zinc-800 shadow-sm">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-zinc-400 truncate">Pendientes Entrada MS</dt>
                        <dd class="mt-1 text-3xl font-semibold text-orange-400">{{ $stats['pending_ms'] }}</dd>
                        <div class="mt-2 text-xs text-zinc-500">Partidas esperando recepción</div>
                    </div>
                </div>

                <div class="bg-[#141414] overflow-hidden rounded-xl border border-zinc-800 shadow-sm">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-zinc-400 truncate">Por Facturar</dt>
                        <dd class="mt-1 text-3xl font-semibold text-blue-400">{{ $stats['pending_invoice'] }}</dd>
                        <div class="mt-2 text-xs text-zinc-500">Partidas sin folio de salida</div>
                    </div>
                </div>

                <div class="bg-[#141414] overflow-hidden rounded-xl border border-zinc-800 shadow-sm">
                    <div class="px-4 py-5 sm:p-6">
                        <dt class="text-sm font-medium text-zinc-400 truncate">Viajes Este Año</dt>
                        <dd class="mt-1 text-3xl font-semibold text-emerald-400">{{ $stats['active_trips'] }}</dd>
                        <div class="mt-2 text-xs text-zinc-500">Operaciones en curso</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="md:flex md:items-center md:justify-between mb-8 gap-4">
            <div class="flex-1 min-w-0 md:mr-4">
                <div class="relative rounded-md shadow-sm">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <svg class="h-5 w-5 text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                        </svg>
                    </div>
                    <input wire:model.live.debounce.300ms="search" type="text" 
                        class="bg-[#0a0a0a] text-zinc-200 placeholder-zinc-500 focus:ring-fuchsia-500 focus:border-fuchsia-500 block w-full pl-10 sm:text-sm border-zinc-800 rounded-lg py-3" 
                        placeholder="Buscar viaje, pedimento, sistema...">
                </div>
            </div>

            <div class="mt-4 flex md:mt-0 md:ml-4 space-x-4">
                <a href="{{ route('global-search') }}" class="relative inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-bold text-zinc-300 bg-zinc-900 border border-zinc-800 hover:border-blue-500/50 hover:text-white transition-all shadow-[0_0_15px_rgba(59,130,246,0.1)] overflow-hidden group">
                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-blue-500/10 to-fuchsia-500/10 opacity-0 group-hover:opacity-100 transition-opacity"></span>
                    <span class="relative flex items-center">📡 Ir al Radar</span>
                </a>
                
                <a href="{{ route('shipments.create') }}" class="relative inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 shadow-[0_0_20px_rgba(192,38,211,0.3)] transition-all transform hover:scale-[1.02] active:scale-[0.98]">
                    🚀 Nuevo Viaje
                </a>
            </div>
        </div>

        <div class="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @forelse($shipments as $shipment)
            <div class="bg-[#141414] border border-zinc-800 rounded-xl relative group hover:border-zinc-600 transition-all duration-300 flex flex-col overflow-hidden shadow-lg">
                
                <a href="{{ route('shipments.show', $shipment) }}" class="absolute inset-0 z-0"></a>

                <div class="px-5 py-4 relative pointer-events-none"> 
                    
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex-shrink-0 font-bold text-sm text-zinc-200 bg-zinc-800 border border-zinc-700 px-3 py-1 rounded-md shadow-sm">
                            ✈️ {{ $shipment->trip_number }}
                        </div>

                        <div class="flex items-center space-x-2 pointer-events-auto z-20">
                            <span class="px-2 inline-flex text-[10px] leading-5 font-semibold rounded-full border {{ $shipment->status === 'CERRADO' ? 'bg-red-500/10 text-red-400 border-red-500/20' : 'bg-emerald-500/10 text-emerald-400 border-emerald-500/20' }}">
                                {{ $shipment->status }}
                            </span>

                            <button wire:click.prevent="openNotes({{ $shipment->id }})" 
                                    class="relative p-1 text-zinc-500 hover:text-fuchsia-400 hover:bg-zinc-800 rounded-full transition outline-none focus:outline-none" 
                                    title="Bitácora de Notas">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z" />
                                </svg>
                                @if($shipment->notes_count > 0)
                                <span class="absolute top-0 right-0 block h-4 w-4 transform -translate-y-1/3 translate-x-1/3 rounded-full ring-2 ring-zinc-900 bg-fuchsia-500 text-white text-[10px] font-bold flex items-center justify-center shadow-sm">
                                    {{ $shipment->notes_count }}
                                </span>
                                @endif
                            </button>

                            <button wire:click.prevent="openQuickView({{ $shipment->id }})" 
                                    class="relative p-1 text-zinc-500 hover:text-fuchsia-400 hover:bg-zinc-800 rounded-full transition outline-none focus:outline-none" 
                                    title="Vista Rápida">
                                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    <div class="mb-3 mt-4">
                        <div class="flex items-center justify-between">
                             <p class="text-sm font-bold text-zinc-100 truncate w-3/4" title="{{ $shipment->pedimento }}">
                                <span class="text-zinc-500 font-normal mr-1">Ped:</span>{{ $shipment->pedimento }}
                            </p>
                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold border 
                                {{ $shipment->system == 'MARINA' ? 'bg-blue-500/10 text-blue-400 border-blue-500/20' : 'bg-orange-500/10 text-orange-400 border-orange-500/20' }}">
                                {{ $shipment->system }}
                            </span>
                        </div>
                    </div>

                    <div class="flex justify-between items-center text-xs text-zinc-500 mb-3">
                        <div class="flex items-center">
                            📅 {{ $shipment->arrival_date ? $shipment->arrival_date->format('d/M/Y') : '---' }}
                        </div>
                        <div class="font-medium text-zinc-400">
                            📦 {{ $shipment->items_count }} Items
                        </div>
                    </div>
                    
                    <div>
                        <div class="w-full bg-zinc-800 rounded-full h-1.5 mb-1">
                            <div class="bg-gradient-to-r from-cyan-500 to-fuchsia-600 h-1.5 rounded-full transition-all duration-500" 
                                 style="width: {{ $shipment->progress }}%"></div>
                        </div>
                        <div class="text-right">
                             <span class="text-[10px] font-bold text-fuchsia-400">{{ $shipment->progress }}% Doc</span>
                        </div>
                    </div>

                </div>
            </div>
            @empty
            <div class="col-span-full text-center py-12 bg-[#141414] rounded-xl border border-dashed border-zinc-800">
                <svg class="mx-auto h-12 w-12 text-zinc-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 13V6a2 2 0 00-2-2H6a2 2 0 00-2 2v7m16 0v5a2 2 0 01-2 2H6a2 2 0 01-2-2v-5m16 0h-2.586a1 1 0 00-.707.293l-2.414 2.414a1 1 0 01-.707.293h-3.172a1 1 0 01-.707-.293l-2.414-2.414A1 1 0 006.586 13H4" />
                </svg>
                <h3 class="mt-2 text-sm font-medium text-zinc-300">No hay viajes</h3>
                <p class="mt-1 text-sm text-zinc-500">Empieza creando un nuevo viaje o ajusta la búsqueda.</p>
                <div class="mt-6">
                    <a href="{{ route('shipments.create') }}" class="inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 transition-colors">
                        🚀 Nuevo Viaje
                    </a>
                </div>
            </div>
            @endforelse
        </div>

        <div class="mt-8">
            {{ $shipments->links() }}
        </div>
    </div>

    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-zinc-950/80 backdrop-blur-sm transition-opacity" wire:click="$set('showModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-[#141414] border border-zinc-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-5xl sm:w-full relative min-h-[300px]">
                
                <div wire:loading.flex class="absolute inset-0 bg-[#141414]/90 z-20 justify-center items-center">
                    <div class="flex flex-col items-center">
                        <svg class="animate-spin h-10 w-10 text-fuchsia-500 mb-3" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                        <span class="text-sm text-zinc-400 font-medium">Consultando expediente...</span>
                    </div>
                </div>

                @if($selectedShipment)
                <div class="bg-zinc-900 border-b border-zinc-800 px-4 py-4 sm:px-6 flex justify-between items-center">
                    <div class="flex items-center">
                        <h3 class="text-xl leading-6 font-bold text-zinc-100 mr-3">
                            ✈️ Viaje {{ $selectedShipment->trip_number }}
                        </h3>
                        <span class="px-2 py-0.5 rounded text-xs font-bold bg-blue-500/10 text-blue-400 border border-blue-500/20">
                            {{ $selectedShipment->system }}
                        </span>
                    </div>
                    <button wire:click="$set('showModal', false)" class="text-zinc-500 hover:text-zinc-300 text-3xl leading-none transition-colors">&times;</button>
                </div>

                <div class="bg-[#0a0a0a] px-4 py-5 sm:px-6 border-b border-zinc-800">
                    <dl class="grid grid-cols-1 gap-x-4 gap-y-4 sm:grid-cols-4">
                        <div class="sm:col-span-1">
                            <dt class="text-xs font-medium text-zinc-500 uppercase">Pedimento</dt>
                            <dd class="mt-1 text-sm font-bold text-zinc-200">{{ $selectedShipment->pedimento }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-xs font-medium text-zinc-500 uppercase">Fecha Llegada</dt>
                            <dd class="mt-1 text-sm text-zinc-300">{{ $selectedShipment->arrival_date ? $selectedShipment->arrival_date->format('d/M/Y') : 'N/A' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-xs font-medium text-zinc-500 uppercase">Tipo de Cambio</dt>
                            <dd class="mt-1 text-sm text-emerald-400 font-bold">$ {{ $selectedShipment->exchange_rate ?? '---' }}</dd>
                        </div>
                        <div class="sm:col-span-1">
                            <dt class="text-xs font-medium text-zinc-500 uppercase">Total Partidas</dt>
                            <dd class="mt-1 text-sm text-zinc-300">{{ $selectedShipment->items->count() }} Items</dd>
                        </div>
                    </dl>
                </div>

                <div class="bg-[#141414] px-4 py-4 sm:px-6">
                    <h4 class="text-sm font-bold text-zinc-300 mb-3">📦 Detalle de Partidas</h4>
                    <div class="max-h-80 overflow-y-auto border border-zinc-800 rounded-lg">
                        <table class="min-w-full divide-y divide-zinc-800">
                            <thead class="bg-[#0a0a0a] sticky top-0 z-10">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-zinc-500 uppercase tracking-wider w-24">DR</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-zinc-500 uppercase tracking-wider">Proveedor / Factura</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-zinc-500 uppercase tracking-wider">Descripción</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-zinc-500 uppercase tracking-wider">Referencias</th>
                                    <th class="px-4 py-3 text-left text-xs font-bold text-zinc-500 uppercase tracking-wider">Travesía (Estatus)</th>
                                </tr>
                            </thead>
                            <tbody class="bg-[#141414] divide-y divide-zinc-800">
                                @foreach($selectedShipment->items as $item)
                                    @php
                                        $groupOcRaw = trim($item->oc_group_raw);
                                        $isRealGroupOc = $groupOcRaw !== '' && strtolower($groupOcRaw) !== 'x' && strtolower($groupOcRaw) !== 'n/a';
                                    @endphp

                                <tr class="hover:bg-zinc-900 transition duration-150">
                                    <td class="px-4 py-4 whitespace-nowrap align-middle">
                                        <div class="text-sm font-bold text-fuchsia-400">{{ $item->dr_raw ?? '-' }}</div>
                                    </td>

                                    <td class="px-4 py-4 whitespace-nowrap align-middle">
                                        <div class="text-sm font-bold text-zinc-200">{{ Str::limit($item->supplier_raw, 20) }}</div>
                                        @if($item->invoice_provider_raw)
                                            <div class="text-xs text-zinc-500 mt-0.5">Fact: {{ $item->invoice_provider_raw }}</div>
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 align-middle">
                                        <div class="text-sm text-zinc-400 leading-snug">{{ Str::limit($item->description_raw, 45) }}</div>
                                    </td>

                                    <td class="px-4 py-4 whitespace-nowrap align-middle">
                                        @if($item->oci_ms_raw) 
                                            <span class="block text-xs text-zinc-400 font-medium">OC MS: {{ $item->oci_ms_raw }}</span> 
                                        @endif
                                        
                                        @if($isRealGroupOc) 
                                            <span class="block text-xs text-zinc-400 font-medium mt-1">OC GPO: {{ $item->oc_group_raw }}</span> 
                                        @endif
                                    </td>

                                    <td class="px-4 py-4 align-middle">
                                        <div class="flex flex-col space-y-1.5">
                                            <div class="flex items-center text-xs">
                                                <span class="mr-2 text-[10px] {{ $item->is_entered_ms ? 'text-emerald-500' : 'text-zinc-700' }}">●</span>
                                                <span class="{{ $item->is_entered_ms ? 'text-zinc-200 font-bold' : 'text-zinc-500' }}">
                                                    {{ $item->is_entered_ms ? 'Ya ingresado MS' : 'Pendiente MS' }}
                                                </span>
                                            </div>

                                            @if($item->invoice_out_folio || $isRealGroupOc)
                                            <div class="flex items-center text-xs">
                                                <span class="mr-2 text-[10px] {{ $item->invoice_out_folio ? 'text-blue-500' : 'text-zinc-700' }}">●</span>
                                                <span class="{{ $item->invoice_out_folio ? 'text-zinc-200 font-bold' : 'text-zinc-500' }}">
                                                    {{ $item->invoice_out_folio ? 'Ya facturado' : 'Sin Facturar' }}
                                                </span>
                                            </div>
                                            @endif

                                            @if($isRealGroupOc || $item->is_entered_group)
                                            <div class="flex items-center text-xs">
                                                <span class="mr-2 text-[10px] {{ $item->is_entered_group ? 'text-purple-500' : 'text-zinc-700' }}">●</span>
                                                <span class="{{ $item->is_entered_group ? 'text-zinc-200 font-bold' : 'text-zinc-500' }}">
                                                    {{ $item->is_entered_group ? 'Ya ingresó Grupo' : 'Pendiente Grupo' }}
                                                </span>
                                            </div>
                                            @endif

                                            <div class="flex items-center text-xs">
                                                <span class="mr-2 text-[10px] {{ $item->is_closed_accounting ? 'text-red-500' : 'text-zinc-700' }}">●</span>
                                                <span class="{{ $item->is_closed_accounting ? 'text-zinc-200 font-bold' : 'text-zinc-500' }}">
                                                    {{ $item->is_closed_accounting ? 'Ya enviado (Conta)' : 'Abierto' }}
                                                </span>
                                            </div>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="bg-zinc-900 border-t border-zinc-800 px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse">
                    <a href="{{ route('shipments.show', $selectedShipment->id) }}" class="w-full inline-flex justify-center rounded-md border border-transparent shadow-[0_0_15px_rgba(192,38,211,0.3)] px-4 py-2 bg-gradient-to-r from-cyan-500 to-fuchsia-600 text-base font-bold text-white hover:from-cyan-400 hover:to-fuchsia-500 sm:ml-3 sm:w-auto sm:text-sm transition-all transform hover:scale-[1.02]">
                        Ir al Detalle Completo →
                    </a>
                    <button type="button" wire:click="$set('showModal', false)" class="mt-3 w-full inline-flex justify-center rounded-md border border-zinc-700 shadow-sm px-4 py-2 bg-zinc-800 text-base font-medium text-zinc-300 hover:bg-zinc-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">
                        Cerrar
                    </button>
                </div>
                @endif
            </div>
        </div>
    </div>
    @endif

    @if($showNotesModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-zinc-950/80 backdrop-blur-sm transition-opacity" wire:click="$set('showNotesModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <div class="inline-block align-bottom bg-[#141414] rounded-xl border border-zinc-800 text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <div class="bg-zinc-900 border-b border-zinc-800 px-4 py-4 sm:px-6 flex justify-between items-center">
                    <h3 class="text-lg leading-6 font-bold text-zinc-100 flex items-center">
                        💬 Bitácora: Viaje {{ $noteShipmentTrip }}
                    </h3>
                    <button wire:click="$set('showNotesModal', false)" class="text-zinc-500 hover:text-zinc-300 text-2xl transition-colors">&times;</button>
                </div>

                <div class="bg-[#0a0a0a] px-4 py-4 sm:p-6 h-64 overflow-y-auto border-b border-zinc-800">
                    @forelse($currentNotes as $note)
                        <div class="mb-3 bg-zinc-900 p-3 rounded-lg shadow-sm border border-zinc-800 relative group">
                            <p class="text-sm text-zinc-300 whitespace-pre-line">{{ $note->note }}</p>
                            <div class="mt-2 flex justify-between items-center">
                                <span class="text-xs text-zinc-500">
                                    {{ $note->created_at->format('d/m/Y h:i A') }} • {{ $note->user->name ?? 'Sistema' }}
                                </span>
                                <button wire:click="deleteNote({{ $note->id }})" class="text-red-500 hover:text-red-400 text-xs opacity-0 group-hover:opacity-100 transition">
                                    Eliminar
                                </button>
                            </div>
                        </div>
                    @empty
                        <div class="text-center text-zinc-600 mt-10">
                            <p class="mb-2 text-2xl">📭</p>
                            No hay notas registradas en este viaje.
                        </div>
                    @endforelse
                </div>

                <div class="bg-zinc-900 px-4 py-4 sm:px-6">
                    <div class="flex space-x-2">
                        <input wire:model="newNoteText" wire:keydown.enter="saveNote" type="text" 
                            class="flex-1 bg-[#0a0a0a] border border-zinc-800 text-zinc-200 placeholder-zinc-600 focus:ring-fuchsia-500 focus:border-fuchsia-500 block w-full sm:text-sm rounded-md" 
                            placeholder="Escribe una nota rápida...">
                        
                        <button wire:click="saveNote" class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-md text-white bg-gradient-to-r from-cyan-500 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 transition-colors">
                            Enviar
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>