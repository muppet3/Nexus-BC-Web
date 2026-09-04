<div class="py-6">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">
        
        <!-- HEADER Y MÉTRICAS -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div class="bg-gray-800 p-6 rounded-lg shadow border border-gray-700">
                <p class="text-sm font-medium text-gray-400">Total Teórico</p>
                <p class="text-3xl font-bold text-white">{{ number_format($totalTeorico) }}</p>
            </div>
            <div class="bg-gray-800 p-6 rounded-lg shadow border border-gray-700">
                <p class="text-sm font-medium text-gray-400">Total Real (Censado)</p>
                <p class="text-3xl font-bold text-emerald-400">{{ number_format($totalReal) }}</p>
            </div>
            <div class="bg-gray-800 p-6 rounded-lg shadow border border-gray-700">
                <p class="text-sm font-medium text-gray-400">Diferencia</p>
                <p class="text-3xl font-bold {{ $diferencia < 0 ? 'text-red-400' : 'text-fuchsia-400' }}">
                    {{ number_format($diferencia) }}
                </p>
            </div>
        </div>

        <!-- BARRA DE BÚSQUEDA Y ACCIONES -->
        <div class="bg-gray-800 p-4 rounded-lg shadow border border-gray-700 flex flex-col md:flex-row justify-between items-center gap-4">
            <div class="w-full md:w-1/2 relative">
                <x-text-input wire:model.live.debounce.300ms="search" placeholder="Buscar por SKU, Nombre o Código de Barras..." class="w-full pl-10 bg-gray-900 border-gray-600 text-white" />
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <svg class="h-5 w-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"></path></svg>
                </div>
            </div>
            
            <div class="flex gap-4 w-full md:w-auto">
                <select wire:model.live="filtroEstado" class="w-full md:w-auto border-gray-600 bg-gray-900 text-gray-300 focus:border-fuchsia-500 focus:ring-fuchsia-500 rounded-md shadow-sm">
                    <option value="todos">Todos los productos</option>
                    <option value="censados">Censados</option>
                    <option value="pendientes">Pendientes</option>
                </select>
                <x-primary-button wire:click="abrirMiHistorial" class="bg-fuchsia-600 hover:bg-fuchsia-500 w-full md:w-auto justify-center">
                    Mi Historial
                </x-primary-button>
                @if (auth()->user()->role === 'master')
                    <x-primary-button wire:click="abrirAdminRegistros" class="bg-red-700 hover:bg-red-600 w-full md:w-auto justify-center">
                        Administrar Registros
                    </x-primary-button>
                @endif
            </div>
        </div>

        <!-- TABLA DE PRODUCTOS -->
        <div class="bg-gray-800 rounded-lg shadow border border-gray-700 overflow-hidden">
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-700">
                    <thead class="bg-gray-900">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">SKU / Producto</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">Ubicación Actual</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-400 uppercase tracking-wider">Stock Real</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-700 bg-gray-800">
                        @forelse($productos as $producto)
                            <tr class="hover:bg-gray-700 transition-colors">
                                <td class="px-6 py-4">
                                    <div class="text-sm font-bold text-white">{{ $producto->sku }}</div>
                                    <div class="text-sm text-gray-400">{{ Str::limit($producto->name, 40) }}</div>
                                </td>
                                <td class="px-6 py-4">
                                    @if($producto->seccion)
                                        <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-900 text-emerald-300 border border-emerald-700">
                                            {{ $producto->seccion }}-{{ $producto->mueble_tipo }} {{ $producto->mueble_numero }}-E{{ $producto->entrepano }}
                                        </span>
                                    @else
                                        <span class="text-sm text-gray-500 italic">Sin ubicación</span>
                                    @endif
                                </td>
                                <td class="px-6 py-4 text-center">
                                    <span class="text-sm font-bold {{ $producto->stock_real > 0 ? 'text-white' : 'text-gray-500' }}">
                                        {{ $producto->stock_real }} {{ $producto->unit }}
                                    </span>
                                </td>
                                <td class="px-6 py-4 text-right space-x-2">
                                    <button wire:click="abrirPanel({{ $producto->id }})" class="text-fuchsia-400 hover:text-fuchsia-300 text-sm font-medium transition-colors">
                                        Censar
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="px-6 py-8 text-center text-gray-400">
                                    No se encontraron productos.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 bg-gray-900 border-t border-gray-700">
                {{ $productos->links() }}
            </div>
        </div>
    </div>

    <!-- MODAL PRINCIPAL DE CENSO (Reemplazando Slide-Over) -->
    <x-modal name="modal-censo" :show="$showSlideOver" maxWidth="md">
        <div class="p-6 bg-gray-900 text-white">
            @if($productoActivo)
                <div class="mb-4">
                    <h2 class="text-lg font-bold text-fuchsia-400">{{ $productoActivo->sku }}</h2>
                    <p class="text-sm text-gray-400">{{ $productoActivo->name }}</p>
                </div>

                @if($showAlertCensado && !$hallazgoEditandoId)
                    <div class="mb-4 bg-yellow-900/50 border border-yellow-700 p-3 rounded-md flex justify-between items-start">
                        <p class="text-sm text-yellow-300">Este producto ya tiene ubicación. Si cambias de pasillo, requerirás autorización.</p>
                        <button wire:click="cerrarAlertaCensado" class="text-yellow-500 hover:text-yellow-300">&times;</button>
                    </div>
                @endif

                <div class="space-y-4">
                    <div>
                        <x-input-label for="cantidad" value="Cantidad Encontrada" class="text-gray-300" />
                        <x-text-input wire:model="cantidad" id="cantidad" type="number" min="1" class="mt-1 block w-full bg-gray-800 text-white border-gray-600" />
                    </div>

                    <div>
                        <x-input-label value="Sección / Zona" class="text-gray-300" />
                        <select wire:model="seccionSeleccionada" class="mt-1 block w-full border-gray-600 bg-gray-800 text-white focus:border-fuchsia-500 focus:ring-fuchsia-500 rounded-md shadow-sm">
                            @foreach($secciones as $sec)
                                <option value="{{ $sec }}">{{ $sec }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <x-input-label value="Tipo de Mueble" class="text-gray-300" />
                            <div class="flex gap-2 mt-1">
                                <button wire:click="setMuebleTipo('A')" class="flex-1 py-2 rounded-md font-bold transition-colors {{ $muebleTipo === 'A' ? 'bg-fuchsia-600 text-white' : 'bg-gray-700 text-gray-300' }}">A</button>
                                <button wire:click="setMuebleTipo('RACK')" class="flex-1 py-2 rounded-md font-bold transition-colors {{ $muebleTipo === 'RACK' ? 'bg-fuchsia-600 text-white' : 'bg-gray-700 text-gray-300' }}">RACK</button>
                            </div>
                        </div>
                        <div>
                            <x-input-label for="anaquel" value="Número" class="text-gray-300" />
                            <x-text-input wire:model="anaquel" id="anaquel" type="text" class="mt-1 block w-full bg-gray-800 text-white border-gray-600" placeholder="Ej. 5" />
                        </div>
                    </div>

                    <div>
                        <x-input-label for="entrepano" value="Entrepaño / Nivel" class="text-gray-300" />
                        <div class="relative mt-1">
                            <span class="absolute inset-y-0 left-0 flex items-center pl-3 text-gray-400 font-bold">E</span>
                            <x-text-input wire:model="entrepano" id="entrepano" type="text" class="block w-full pl-8 bg-gray-800 text-white border-gray-600" placeholder="Ej. 2" />
                        </div>
                    </div>
                </div>

                <div class="mt-6 flex justify-end gap-3">
                    <x-secondary-button wire:click="cerrarPanel" class="bg-gray-800 text-gray-300 border-gray-600 hover:bg-gray-700">Cancelar</x-secondary-button>
                    <x-primary-button wire:click="validarYGuardar" class="bg-fuchsia-600 hover:bg-fuchsia-500">Guardar Censo</x-primary-button>
                </div>
            @endif
        </div>
    </x-modal>

    <!-- MODALES DE VALIDACIÓN Y FIRMA -->
    <!-- Consolidación -->
    <x-modal name="modal-consolidacion" :show="$showModalConsolidacion" maxWidth="sm">
        <div class="p-6 bg-gray-900 text-center text-white">
            <svg class="mx-auto h-12 w-12 text-yellow-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"></path></svg>
            <h3 class="text-lg font-bold mb-2">¿Consolidar Mercancía?</h3>
            <p class="text-sm text-gray-400 mb-6">El producto ya existe en la ubicación. La cantidad se sumará al stock existente.</p>
            <div class="flex justify-center gap-3">
                <x-secondary-button wire:click="$set('showModalConsolidacion', false)" class="bg-gray-800 text-gray-300 border-gray-600 hover:bg-gray-700">Cancelar</x-secondary-button>
                <x-primary-button wire:click="confirmarConsolidacion" class="bg-yellow-600 hover:bg-yellow-500">Sí, Sumar Stock</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Movimiento Físico -->
    <x-modal name="modal-movimiento" :show="$showModalMovimiento" maxWidth="sm">
        <div class="p-6 bg-gray-900 text-center text-white">
            <svg class="mx-auto h-12 w-12 text-red-400 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"></path></svg>
            <h3 class="text-lg font-bold mb-2">¡Cambio de Zona Detectado!</h3>
            <p class="text-sm text-gray-400 mb-6">Estás cambiando el producto de sección. Requerirás autorización de un compañero.</p>
            <div class="flex justify-center gap-3">
                <x-secondary-button wire:click="$set('showModalMovimiento', false)" class="bg-gray-800 text-gray-300 border-gray-600 hover:bg-gray-700">Cancelar</x-secondary-button>
                <x-primary-button wire:click="confirmarMovimientoFisico" class="bg-red-600 hover:bg-red-500">Continuar</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Autorización por PIN -->
    <x-modal name="modal-auth" :show="$showModalAuth" maxWidth="sm">
        <div class="p-6 bg-gray-900 text-white">
            <h3 class="text-lg font-bold text-center mb-2">Firma Requerida</h3>
            <p class="text-sm text-center text-fuchsia-400 mb-4">{{ $authMotivo }}</p>
            
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
                <x-primary-button wire:click="ejecutarGuardado(true)" class="bg-fuchsia-600 hover:bg-fuchsia-500">Autorizar y Guardar</x-primary-button>
            </div>
        </div>
    </x-modal>

    <!-- Mi Historial: mis registros de hoy, click para editar -->
    <x-modal name="modal-mi-historial" :show="$showModalMiHistorial" maxWidth="2xl">
        <div class="p-6 bg-gray-900 text-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Mis Registros de Hoy</h3>
                <button wire:click="$set('showModalMiHistorial', false)" class="text-gray-400 hover:text-white">✕</button>
            </div>

            <div class="max-h-[28rem] overflow-y-auto divide-y divide-gray-700">
                @forelse ($miHistorialData as $h)
                    @php $esEdicion = $h->created_at != $h->updated_at; @endphp
                    <button
                        wire:click="editarMiHistorial({{ $h->id }})"
                        wire:key="mi-hist-{{ $h->id }}"
                        class="w-full text-left py-3 flex items-center justify-between gap-3 hover:bg-gray-800/60 px-2 -mx-2 rounded transition-colors">
                        <div class="min-w-0">
                            <div class="text-sm font-bold text-white truncate">{{ $h->product->name ?? 'Producto eliminado' }}</div>
                            <div class="text-xs text-gray-400">SKU: {{ $h->product->sku ?? 'N/A' }} · {{ $h->cantidad }} Pzas en {{ $h->seccion }}-{{ $h->mueble_tipo }} {{ $h->mueble_numero }}-E{{ $h->entrepano }}</div>
                            <span class="text-[10px] font-bold {{ $esEdicion ? 'text-cyan-400' : 'text-emerald-400' }}">{{ $esEdicion ? '📝 EDICIÓN' : '🆕 NUEVO' }}</span>
                        </div>
                        <span class="shrink-0 text-xs text-gray-500">{{ $h->updated_at->format('d/m H:i') }}</span>
                    </button>
                @empty
                    <p class="text-sm text-gray-500 italic py-4 text-center">Aún no tienes registros hoy.</p>
                @endforelse
            </div>
        </div>
    </x-modal>

    <!-- Administración de Registros (solo master): ver y borrar hallazgos de cualquier usuario -->
    <x-modal name="modal-admin-registros" :show="$showModalAdminRegistros" maxWidth="3xl">
        <div class="p-6 bg-gray-900 text-white">
            <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-bold">Administrar Registros (últimos 7 días)</h3>
                <button wire:click="cerrarAdminRegistros" class="text-gray-400 hover:text-white">✕</button>
            </div>
            <p class="text-xs text-gray-500 mb-4">Borrar un registro también corrige el stock real del producto (resta la cantidad) y queda anotado en el historial de auditoría.</p>

            <div class="max-h-[28rem] overflow-y-auto divide-y divide-gray-700">
                @forelse ($adminRegistrosData as $h)
                    <div wire:key="admin-reg-{{ $h->id }}" class="py-3 flex items-center justify-between gap-3">
                        <div class="min-w-0">
                            <div class="text-sm font-bold text-white truncate">{{ $h->product->name ?? 'Producto eliminado' }}</div>
                            <div class="text-xs text-gray-400">SKU: {{ $h->product->sku ?? 'N/A' }} · {{ $h->cantidad }} Pzas en {{ $h->seccion }}-{{ $h->mueble_tipo }} {{ $h->mueble_numero }}-E{{ $h->entrepano }}</div>
                            <div class="text-xs text-gray-500">Por: {{ $h->user->name ?? 'N/A' }} · {{ $h->updated_at->format('d/m H:i') }}</div>
                        </div>
                        <button
                            wire:click="borrarHallazgo({{ $h->id }})"
                            wire:confirm="¿Borrar este registro? Esto también corrige el stock real del producto."
                            class="shrink-0 text-xs font-bold px-3 py-1.5 bg-red-500/10 border border-red-500/30 text-red-400 rounded-md hover:bg-red-500/20 transition-colors">
                            Borrar
                        </button>
                    </div>
                @empty
                    <p class="text-sm text-gray-500 italic py-4 text-center">No hay registros en los últimos 7 días.</p>
                @endforelse
            </div>
        </div>
    </x-modal>
</div>