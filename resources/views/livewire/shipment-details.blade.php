<div class="py-8 bg-zinc-950 min-h-screen text-zinc-100">
    <div class="max-w-[98%] mx-auto sm:px-4 lg:px-6">
        
        <div class="flex justify-between items-end mb-6">
            <div>
                <span class="text-sm font-bold text-fuchsia-500 uppercase tracking-widest">{{ $shipment->system }}</span>
                <div class="flex items-center space-x-4">
                    <h1 class="text-3xl font-bold text-white">
                        Viaje {{ $shipment->trip_number }} <span class="text-zinc-700">|</span> Ped: {{ $shipment->pedimento }}
                    </h1>
                    
                    <div class="flex items-center space-x-2 mt-1 bg-zinc-900 px-3 py-1.5 rounded-lg border border-zinc-800 shadow-inner">
                        <span class="text-[10px] text-zinc-500 font-bold uppercase">📅 Fecha:</span>
                        <input type="date" 
                            wire:change="updateArrivalDate($event.target.value)"
                            value="{{ $shipment->arrival_date ? $shipment->arrival_date->format('Y-m-d') : '' }}"
                            class="text-xs border-0 bg-transparent text-zinc-300 focus:ring-0 cursor-pointer p-0"
                        >
                    </div>
                </div>
            </div>
            
            <div class="flex space-x-4">
                <a href="{{ route('dashboard') }}" class="px-5 py-2.5 bg-zinc-900 border border-zinc-800 rounded-lg text-sm font-bold text-zinc-400 hover:text-white hover:border-zinc-600 transition-all">← Volver</a>
                
                <button wire:click="openDocuments" class="relative inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-bold text-zinc-300 bg-zinc-900 border border-zinc-800 hover:border-blue-500/50 hover:text-white transition-all shadow-[0_0_15px_rgba(59,130,246,0.1)] group">
                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-blue-500/10 to-fuchsia-500/10 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg"></span>
                    <span class="relative">📄 Documentos Globales</span>
                </button>
            </div>
        </div>

        <div class="bg-[#141414] border border-zinc-800 rounded-xl overflow-hidden shadow-2xl">
            <table class="min-w-full text-sm text-left">
                <thead class="bg-[#0a0a0a] text-[10px] uppercase font-bold text-zinc-500 border-b border-zinc-800">
                    <tr>
                        <th class="px-4 py-4 bg-[#0a0a0a] sticky left-0 z-20 w-56">Referencia / Proveedor</th>
                        
                        <th class="px-2 py-4 text-center border-l border-zinc-800 bg-blue-500/5 w-36 relative"
                            title="Arrastra aquí el archivo ÚNICO para todas las partidas"
                            x-data="{ isDropping: false, dragCounter: 0 }"
                            @dragenter.prevent="dragCounter++; isDropping = true"
                            @dragleave.prevent="dragCounter--; if(dragCounter === 0) isDropping = false"
                            @dragover.prevent.stop="isDropping = true"
                            @drop.prevent.stop="isDropping = false; dragCounter = 0; $refs.fileInputHeader.files = $event.dataTransfer.files; $refs.fileInputHeader.dispatchEvent(new Event('change'))">
                            
                            <div x-show="isDropping" class="absolute inset-0 bg-blue-500/20 border-2 border-dashed border-blue-400 z-50 flex items-center justify-center text-blue-300 opacity-90 backdrop-blur-sm">
                                <span class="text-[10px] font-bold">SOLTAR GLOBAL</span>
                            </div>

                            <div class="flex flex-col items-center justify-center">
                                <span class="text-blue-400">Ingreso MS 📥</span>
                                @if($globalMsDoc)
                                    <div class="mt-1 flex items-center text-emerald-400 bg-emerald-500/10 border border-emerald-500/20 px-1.5 py-0.5 rounded cursor-pointer" wire:click="openDocuments" title="Ver archivo global">
                                        <span class="text-sm mr-1">🌐</span>
                                        <span class="text-[9px] font-bold">ACTIVO</span>
                                    </div>
                                @else
                                    <span class="text-[9px] text-zinc-600 font-normal normal-case mt-1">(Arrastrar Global)</span>
                                @endif
                            </div>
                            
                            <div wire:loading.flex wire:target="fileDrops.header.ingreso_ms" class="absolute inset-0 bg-[#141414]/90 z-40 items-center justify-center">
                                <span class="text-xs animate-spin text-blue-400">🔄</span>
                            </div>
                            <input type="file" x-ref="fileInputHeader" wire:model="fileDrops.header.ingreso_ms" class="hidden">
                        </th>

                        <th class="px-2 py-4 text-center border-l border-zinc-800 w-36">OC Grupo 📄</th>
                        <th class="px-2 py-4 text-center border-l border-zinc-800 w-36">Factura Salida 💲</th>
                        <th class="px-2 py-4 text-center border-l border-zinc-800 bg-emerald-500/5 w-36 text-emerald-400">Ingreso GPO 📥</th>
                        <th class="px-2 py-4 text-center border-l border-zinc-800 w-20">Status</th>
                        <th class="px-2 py-4 text-center border-l border-zinc-800 w-20">Docs</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-800">
                    @foreach($items as $item)
                    @php 
                        $docs = $item->documents->groupBy('category');
                        
                        $specificDocsMs = $docs->has('ingreso_ms') ? $docs->get('ingreso_ms') : collect();
                        $hasSpecificMs  = $specificDocsMs->count() > 0;
                        $isCoveredByGlobal = !$hasSpecificMs && $globalMsDoc;

                        $hasOcGroup   = $docs->has('oc_grupo') && $docs->get('oc_grupo')->count() > 0;
                        $hasFactura   = $docs->has('factura_salida') && $docs->get('factura_salida')->count() > 0;
                        $hasIngresoGpo= $docs->has('ingreso_gpo') && $docs->get('ingreso_gpo')->count() > 0;

                        $needsGroup = false;
                        if ($item->oc_group_raw) {
                            foreach (config('nexus.invoice_triggers', []) as $trigger) {
                                if (str_contains(strtoupper($item->oc_group_raw), $trigger)) {
                                    $needsGroup = true;
                                    break;
                                }
                            }
                        }
                    @endphp

                    <tr wire:key="item-{{ $item->id }}" class="hover:bg-zinc-900/50 transition-colors group">
                        
                    <td class="px-4 py-4 bg-[#141414] sticky left-0 z-10 align-top group-hover:bg-zinc-900 transition-colors">
    
                        <div class="flex flex-wrap gap-2 mb-2">
                            @if($item->dr_raw)
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-fuchsia-500/10 text-fuchsia-400 border border-fuchsia-500/20 shadow-sm">
                                    DR: {{ $item->dr_raw }}
                                </span>
                            @endif
                            
                            @if($item->oci_ms_raw)
                                <span class="px-2 py-0.5 rounded text-[10px] font-black bg-blue-500/10 text-blue-400 border border-blue-500/20 shadow-sm">
                                    OC MS: {{ $item->oci_ms_raw }}
                                </span>
                            @endif
                        </div>
                        
                        <div class="font-bold text-blue-400 group-hover:text-blue-300 transition-colors">{{ $item->supplier_raw }}</div>
                        <div class="text-[11px] text-zinc-500 mt-1 line-clamp-2 leading-relaxed">{{ $item->description_raw }}</div>
                    </td>

                        <td class="p-0 border-l border-zinc-800 align-top relative min-h-[6rem] {{ ($hasSpecificMs || $isCoveredByGlobal) ? 'bg-emerald-500/10' : 'bg-transparent' }}">
                            <div class="h-full w-full relative flex flex-col"
                                x-data="{ isDropping: false, dragCounter: 0 }"
                                @dragenter.prevent="dragCounter++; isDropping = true"
                                @dragleave.prevent="dragCounter--; if(dragCounter === 0) isDropping = false"
                                @dragover.prevent.stop="isDropping = true"
                                @drop.prevent.stop="isDropping = false; dragCounter = 0; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))">
                                
                                <div x-show="isDropping" class="absolute inset-0 bg-blue-500/20 border-2 border-dashed border-blue-500 z-50 flex items-center justify-center opacity-90 pointer-events-none backdrop-blur-sm">
                                    <span class="text-xs font-bold text-blue-300">ACUMULAR</span>
                                </div>
                                <div wire:loading.flex wire:target="fileDrops.{{ $item->id }}.ingreso_ms" class="absolute inset-0 bg-[#141414]/90 z-40 items-center justify-center">
                                    <svg class="animate-spin h-6 w-6 text-fuchsia-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                </div>

                                <div class="flex-grow p-2 flex flex-col items-center justify-between pointer-events-none">
                                    <input type="text" value="{{ $item->entry_ms_folio }}" wire:change="updateItem({{ $item->id }}, 'entry_ms_folio', $event.target.value)" class="w-full text-[10px] text-center bg-black/40 border-zinc-800 text-zinc-100 rounded focus:ring-blue-500 pointer-events-auto h-6 placeholder-zinc-600" placeholder="Folio MS...">

                                    @if($hasSpecificMs)
                                        <div class="flex items-center space-x-1 pointer-events-auto cursor-pointer mt-1 bg-emerald-500/10 border border-emerald-500/20 px-2 py-1 rounded shadow-sm" title="{{ $specificDocsMs->last()->generated_filename }}">
                                            <span class="text-lg">📄</span>
                                            <div class="flex flex-col leading-none">
                                                <span class="text-[9px] font-bold text-emerald-400 truncate w-20">
                                                    {{ Str::limit($specificDocsMs->last()->generated_filename, 10) }}
                                                </span>
                                                @if($specificDocsMs->count() > 1)
                                                    <span class="text-[8px] text-zinc-500 font-bold">(+{{ $specificDocsMs->count() - 1 }} más)</span>
                                                @endif
                                            </div>
                                        </div>
                                    @elseif($isCoveredByGlobal)
                                        <div class="flex items-center space-x-1 pointer-events-auto cursor-pointer mt-1 opacity-75" title="Cubierto por Archivo Maestro">
                                            <span class="text-lg">🌐</span>
                                            <span class="text-[9px] font-bold text-blue-400">Global</span>
                                        </div>
                                    @endif
                                </div>
                                <input type="file" x-ref="fileInput" wire:model="fileDrops.{{ $item->id }}.ingreso_ms" class="hidden">
                            </div>
                        </td>

                        <td class="p-0 border-l border-zinc-800 align-top relative min-h-[6rem] {{ $needsGroup && $hasOcGroup ? 'bg-emerald-500/10' : '' }}">
                            @if($needsGroup)
                                <div class="h-full w-full relative flex flex-col"
                                    x-data="{ isDropping: false, dragCounter: 0 }"
                                    @dragenter.prevent="dragCounter++; isDropping = true"
                                    @dragleave.prevent="dragCounter--; if(dragCounter === 0) isDropping = false"
                                    @dragover.prevent.stop="isDropping = true"
                                    @drop.prevent.stop="isDropping = false; dragCounter = 0; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))">
                                    
                                    <div x-show="isDropping" class="absolute inset-0 bg-blue-500/20 border-2 border-dashed border-blue-500 z-50 flex items-center justify-center opacity-90 pointer-events-none backdrop-blur-sm">
                                        <span class="text-xs font-bold text-blue-300">ACUMULAR</span>
                                    </div>
                                    <div wire:loading.flex wire:target="fileDrops.{{ $item->id }}.oc_grupo" class="absolute inset-0 bg-[#141414]/90 z-40 items-center justify-center">
                                        <svg class="animate-spin h-6 w-6 text-fuchsia-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </div>

                                    <div class="flex-grow p-2 flex flex-col items-center justify-center pointer-events-none">
                                        <div class="text-xs font-bold text-center text-zinc-400 mb-1">{{ $item->oc_group_raw }}</div>
                                        @if($hasOcGroup)
                                            <div class="flex items-center bg-emerald-500/10 px-2 py-1 rounded border border-emerald-500/20">
                                                <span class="text-xs mr-1">📄</span>
                                                <div class="flex flex-col leading-none">
                                                    <span class="text-[9px] font-bold text-emerald-400">{{ Str::limit($docs->get('oc_grupo')->last()->generated_filename, 8) }}</span>
                                                    @if($docs->get('oc_grupo')->count() > 1)
                                                        <span class="text-[8px] text-zinc-500">(+{{ $docs->get('oc_grupo')->count() - 1 }})</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <input type="file" x-ref="fileInput" wire:model="fileDrops.{{ $item->id }}.oc_grupo" class="hidden">
                                </div>
                            @else
                                <div class="h-full w-full bg-[#0a0a0a] flex items-center justify-center"><span class="text-xs text-zinc-700 select-none">N/A</span></div>
                            @endif
                        </td>

                        <td class="p-0 border-l border-zinc-800 align-top relative min-h-[6rem] {{ $needsGroup && $hasFactura ? 'bg-emerald-500/10' : '' }}">
                            @if($needsGroup)
                                <div class="h-full w-full relative flex flex-col"
                                    x-data="{ isDropping: false, dragCounter: 0 }"
                                    @dragenter.prevent="dragCounter++; isDropping = true"
                                    @dragleave.prevent="dragCounter--; if(dragCounter === 0) isDropping = false"
                                    @dragover.prevent.stop="isDropping = true"
                                    @drop.prevent.stop="isDropping = false; dragCounter = 0; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))">
                                    
                                    <div x-show="isDropping" class="absolute inset-0 bg-blue-500/20 border-2 border-dashed border-blue-500 z-50 flex items-center justify-center opacity-90 pointer-events-none backdrop-blur-sm">
                                        <span class="text-xs font-bold text-blue-300">ACUMULAR</span>
                                    </div>
                                    <div wire:loading.flex wire:target="fileDrops.{{ $item->id }}.factura_salida" class="absolute inset-0 bg-[#141414]/90 z-40 items-center justify-center">
                                        <svg class="animate-spin h-6 w-6 text-fuchsia-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </div>

                                    <div class="flex-grow p-2 pointer-events-none flex flex-col justify-between">
                                        <input type="text" value="{{ $item->invoice_out_folio }}" wire:change="updateItem({{ $item->id }}, 'invoice_out_folio', $event.target.value)" class="w-full text-xs text-center bg-transparent border-0 pointer-events-auto text-zinc-200 focus:ring-0 mb-1 rounded hover:bg-zinc-800" placeholder="F-...">
                                        @if($hasFactura)
                                            <div class="flex items-center justify-center bg-emerald-500/10 px-1 rounded border border-emerald-500/20">
                                                <span class="text-xs mr-1">📎</span>
                                                <div class="flex flex-col leading-none">
                                                    <span class="text-[9px] font-bold text-emerald-400 truncate">{{ Str::limit($docs->get('factura_salida')->last()->generated_filename, 8) }}</span>
                                                    @if($docs->get('factura_salida')->count() > 1)
                                                        <span class="text-[8px] text-zinc-500">(+{{ $docs->get('factura_salida')->count() - 1 }})</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <input type="file" x-ref="fileInput" wire:model="fileDrops.{{ $item->id }}.factura_salida" class="hidden">
                                </div>
                            @else
                                <div class="h-full w-full bg-[#0a0a0a]"></div>
                            @endif
                        </td>

                        <td class="p-0 border-l border-zinc-800 align-top relative min-h-[6rem] {{ $needsGroup && $hasIngresoGpo ? 'bg-emerald-500/10' : ( $needsGroup ? 'bg-emerald-500/5' : '' ) }}">
                            @if($needsGroup)
                                <div class="h-full w-full relative flex flex-col"
                                    x-data="{ isDropping: false, dragCounter: 0 }"
                                    @dragenter.prevent="dragCounter++; isDropping = true"
                                    @dragleave.prevent="dragCounter--; if(dragCounter === 0) isDropping = false"
                                    @dragover.prevent.stop="isDropping = true"
                                    @drop.prevent.stop="isDropping = false; dragCounter = 0; $refs.fileInput.files = $event.dataTransfer.files; $refs.fileInput.dispatchEvent(new Event('change'))">
                                    
                                    <div x-show="isDropping" class="absolute inset-0 bg-emerald-500/20 border-2 border-dashed border-emerald-500 z-50 flex items-center justify-center opacity-90 pointer-events-none backdrop-blur-sm">
                                        <span class="text-xs font-bold text-emerald-300">ACUMULAR</span>
                                    </div>
                                    <div wire:loading.flex wire:target="fileDrops.{{ $item->id }}.ingreso_gpo" class="absolute inset-0 bg-[#141414]/90 z-40 items-center justify-center">
                                        <svg class="animate-spin h-6 w-6 text-fuchsia-500" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>
                                    </div>

                                    <div class="flex-grow p-2 flex flex-col items-center justify-between pointer-events-none">
                                        <input type="text" value="{{ $item->entry_gpo_folio }}" wire:change="updateItem({{ $item->id }}, 'entry_gpo_folio', $event.target.value)" class="w-full text-[10px] text-center bg-black/40 border-zinc-800 text-zinc-100 rounded focus:ring-emerald-500 pointer-events-auto h-6 placeholder-zinc-600" placeholder="Folio GPO...">
                                        
                                        @if($hasIngresoGpo)
                                            <div class="flex items-center bg-emerald-500/10 px-2 py-1 rounded border border-emerald-500/20 pointer-events-auto" title="{{ $docs->get('ingreso_gpo')->last()->generated_filename }}">
                                                <span class="text-xs mr-1">📦</span>
                                                <div class="flex flex-col leading-none">
                                                    <span class="text-[9px] font-bold text-emerald-400 truncate w-16">{{ Str::limit($docs->get('ingreso_gpo')->last()->generated_filename, 8) }}</span>
                                                    @if($docs->get('ingreso_gpo')->count() > 1)
                                                        <span class="text-[8px] text-zinc-500">(+{{ $docs->get('ingreso_gpo')->count() - 1 }})</span>
                                                    @endif
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                    <input type="file" x-ref="fileInput" wire:model="fileDrops.{{ $item->id }}.ingreso_gpo" class="hidden">
                                </div>
                            @else
                                <div class="h-full w-full bg-[#0a0a0a] flex items-center justify-center"><span class="text-xs text-zinc-700 select-none">N/A</span></div>
                            @endif
                        </td>

                        <td class="px-2 py-4 align-middle border-l border-zinc-800 text-center">
                            <button wire:click="toggleCheck({{ $item->id }}, 'is_closed_accounting')" wire:loading.attr="disabled" class="p-2 rounded-lg transition-all duration-200 shadow-sm {{ $item->is_closed_accounting ? 'bg-red-500/10 border border-red-500/30 text-red-400' : 'bg-[#0a0a0a] border border-zinc-800 text-zinc-500 hover:text-emerald-400' }}">
                                <span class="text-lg font-bold">{{ $item->is_closed_accounting ? '🔒' : '🔓' }}</span>
                            </button>
                        </td>

                        <td class="px-2 py-4 align-middle text-center border-l border-zinc-800">
                            <button wire:click="openDocuments({{ $item->id }})" class="transition relative group mt-1">
                                @if($item->documents_count > 0)
                                    <span class="text-blue-400 text-xl">📎</span>
                                    <span class="absolute -top-2 -right-2 bg-fuchsia-600 text-white text-[10px] rounded-full px-1.5 shadow-sm ring-2 ring-[#141414]">{{ $item->documents_count }}</span>
                                @else
                                    <span class="text-zinc-600 hover:text-zinc-400 text-lg">📎</span>
                                @endif
                            </button>
                        </td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>

    @if($showModal)
    <div class="fixed inset-0 z-50 overflow-y-auto" aria-labelledby="modal-title" role="dialog" aria-modal="true">
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <div class="fixed inset-0 bg-zinc-950/80 backdrop-blur-sm transition-opacity" wire:click="$set('showModal', false)"></div>
            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>
            <div class="inline-block align-bottom bg-[#141414] border border-zinc-800 rounded-xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                <div class="bg-zinc-900 border-b border-zinc-800 px-4 py-4 sm:px-6">
                    <div class="sm:flex sm:items-start">
                        <div class="mt-3 text-center sm:mt-0 sm:ml-4 sm:text-left w-full">
                            <h3 class="text-lg leading-6 font-bold text-zinc-100">
                                {{ $activeItem ? 'Documentos: ' . Str::limit($activeItem->description_raw, 30) : 'Documentos Globales del Viaje' }}
                            </h3>
                            <div class="mt-4 max-h-60 overflow-y-auto">
                                @if(count($existingDocuments) > 0)
                                    <ul class="divide-y divide-zinc-800">
                                        @foreach($existingDocuments as $doc)
                                            <li class="py-3 flex justify-between items-center text-sm">
                                                <div class="flex items-center">
                                                    <span class="text-xl mr-3">{{ $doc->category === 'ingreso_ms_global' ? '🌐' : '📄' }}</span>
                                                    <div class="flex flex-col">
                                                        <a href="{{ Storage::url($doc->file_path) }}" target="_blank" class="text-blue-400 hover:text-blue-300 font-bold truncate max-w-[200px]" title="{{ $doc->generated_filename }}">
                                                            {{ $doc->generated_filename }}
                                                        </a>
                                                        <span class="text-xs text-zinc-500">{{ $doc->category }} - {{ $doc->created_at->format('d/m H:i') }}</span>
                                                    </div>
                                                </div>
                                                <button wire:click="deleteDocument({{ $doc->id }})" class="text-red-400 hover:text-red-300 text-xs font-bold px-3 py-1 bg-red-500/10 border border-red-500/20 rounded-md transition-colors">Eliminar</button>
                                            </li>
                                        @endforeach
                                    </ul>
                                @else
                                    <p class="text-sm text-zinc-500 italic py-2">No hay documentos registrados.</p>
                                @endif
                            </div>
                            <div class="mt-6 border-t border-zinc-800 pt-4">
                                <label class="block text-sm font-bold text-zinc-400 mb-2">Subir nuevo documento (Manual)</label>
                                <input type="file" wire:model="newFile" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-bold file:bg-zinc-800 file:text-zinc-300 hover:file:bg-zinc-700 transition-colors"/>
                                @if($newFile)
                                    <button wire:click="saveDocument" class="mt-4 w-full inline-flex justify-center rounded-lg border border-transparent shadow-[0_0_15px_rgba(192,38,211,0.3)] px-4 py-2 bg-gradient-to-r from-cyan-500 to-fuchsia-600 text-base font-bold text-white hover:from-cyan-400 hover:to-fuchsia-500 sm:text-sm transition-all transform hover:scale-[1.02]">Guardar Archivo</button>
                                @endif
                                <div wire:loading wire:target="newFile, saveDocument" class="mt-2 text-xs text-fuchsia-400 font-bold">Procesando...</div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-[#0a0a0a] px-4 py-3 sm:px-6 sm:flex sm:flex-row-reverse border-t border-zinc-800">
                    <button type="button" wire:click="$set('showModal', false)" class="mt-3 w-full inline-flex justify-center rounded-lg border border-zinc-700 shadow-sm px-4 py-2 bg-zinc-800 text-base font-medium text-zinc-300 hover:bg-zinc-700 focus:outline-none sm:mt-0 sm:ml-3 sm:w-auto sm:text-sm transition-colors">Cerrar</button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>