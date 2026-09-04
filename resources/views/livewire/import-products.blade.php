<div class="py-8 bg-zinc-950 min-h-screen text-zinc-100">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8 space-y-6">

        <div class="p-6 bg-[#141414] border border-zinc-800 shadow-2xl sm:rounded-xl">
            <div class="flex items-center justify-between mb-6">
                <h2 class="text-2xl font-bold text-white">
                    📥 Importar Catálogo (Excel)
                </h2>
                <a href="{{ route('dashboard') }}" class="text-sm text-zinc-500 hover:text-zinc-300 transition-colors">← Cancelar y Volver</a>
            </div>

            <p class="text-sm text-zinc-500 mb-6">
                Sube el export de Microsip (o cualquier lista con las mismas columnas: Clave, Nombre, U.compra, Almacenable).
                Solo se crearán los productos que no existan ya en el catálogo.
            </p>

            @if ($resultado)
                <div class="mb-6 p-4 bg-emerald-500/10 border border-emerald-500/30 rounded-xl text-emerald-300 text-sm font-medium">
                    ✓ {{ $resultado }}
                </div>
            @endif

            <div class="mb-6">
                <label class="block text-xs font-bold text-zinc-500 uppercase tracking-wider mb-2">Archivo Excel (.xlsx)</label>
                <input type="file" wire:model="file" accept=".xlsx,.xls"
                    class="block w-full text-sm text-zinc-300 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-zinc-800 file:text-zinc-200 hover:file:bg-zinc-700">
                @error('file') <span class="text-red-400 text-xs mt-1 block">{{ $message }}</span> @enderror
                <div wire:loading wire:target="file" class="text-xs text-fuchsia-400 mt-2">Subiendo archivo...</div>
            </div>

            <div class="flex justify-end border-t border-zinc-800 pt-6">
                <button
                    wire:click="previsualizar"
                    wire:loading.attr="disabled"
                    wire:target="previsualizar"
                    class="relative inline-flex items-center px-6 py-2.5 rounded-lg text-sm font-bold text-zinc-300 bg-zinc-900 border border-zinc-800 hover:border-fuchsia-500/50 hover:text-white transition-all shadow-[0_0_15px_rgba(192,38,211,0.1)] group disabled:opacity-50">
                    <span class="absolute inset-0 w-full h-full bg-gradient-to-r from-blue-500/10 to-fuchsia-500/10 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg"></span>
                    <span class="relative" wire:loading.remove wire:target="previsualizar">🔍 Previsualizar</span>
                    <span class="relative" wire:loading wire:target="previsualizar">Leyendo archivo...</span>
                </button>
            </div>
        </div>

        @if ($showPreview)
            <div class="p-6 bg-[#141414] border border-zinc-800 shadow-2xl sm:rounded-xl">
                <div class="grid grid-cols-2 md:grid-cols-5 gap-4 mb-6">
                    <div class="bg-[#0a0a0a] border border-zinc-800 rounded-lg p-4">
                        <div class="text-2xl font-bold text-emerald-400">{{ count($nuevos) }}</div>
                        <div class="text-[10px] uppercase tracking-wider text-zinc-500 mt-1">Nuevos por crear</div>
                    </div>
                    <div class="bg-[#0a0a0a] border border-zinc-800 rounded-lg p-4">
                        <div class="text-2xl font-bold text-zinc-400">{{ $existentesCount }}</div>
                        <div class="text-[10px] uppercase tracking-wider text-zinc-500 mt-1">Ya existían (omitidos)</div>
                    </div>
                    <div class="bg-[#0a0a0a] border border-zinc-800 rounded-lg p-4">
                        <div class="text-2xl font-bold text-orange-400">{{ count($sinClave) }}</div>
                        <div class="text-[10px] uppercase tracking-wider text-zinc-500 mt-1">Sin clave (revisión manual)</div>
                    </div>
                    <div class="bg-[#0a0a0a] border border-zinc-800 rounded-lg p-4">
                        <div class="text-2xl font-bold text-orange-400">{{ count($duplicadosInternos) }}</div>
                        <div class="text-[10px] uppercase tracking-wider text-zinc-500 mt-1">Duplicados en el archivo</div>
                    </div>
                    <div class="bg-[#0a0a0a] border border-zinc-800 rounded-lg p-4">
                        <div class="text-2xl font-bold text-zinc-500">{{ $excluidosServicio + $excluidosNoAlmacenable }}</div>
                        <div class="text-[10px] uppercase tracking-wider text-zinc-500 mt-1">Excluidos (servicio / no almacenable)</div>
                    </div>
                </div>

                @if (count($sinClave) > 0)
                    <div class="mb-6 p-4 bg-orange-500/10 border border-orange-500/30 rounded-xl">
                        <p class="text-xs font-bold text-orange-300 uppercase tracking-wider mb-2">⚠ Requieren revisión manual (sin clave, no se importan automáticamente)</p>
                        <ul class="text-xs text-zinc-400 space-y-1">
                            @foreach ($sinClave as $s)
                                <li>— {{ $s['nombre'] ?: '(sin nombre)' }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <div class="flex justify-between items-center mb-4">
                    <h3 class="text-lg font-bold text-zinc-100 flex items-center">
                        <span class="text-fuchsia-500 text-2xl mr-2">✓</span> Productos nuevos a crear ({{ count($nuevos) }})
                    </h3>
                </div>

                <div class="overflow-x-auto border border-zinc-800 rounded-lg rounded-t-none max-h-[28rem] overflow-y-auto">
                    <table class="min-w-full divide-y divide-zinc-800 text-sm">
                        <thead class="bg-[#0a0a0a] text-[10px] uppercase font-bold text-zinc-500 sticky top-0">
                            <tr>
                                <th class="px-3 py-3 text-center w-10"></th>
                                <th class="px-2 py-3 text-left">SKU</th>
                                <th class="px-2 py-3 text-left">Nombre</th>
                                <th class="px-2 py-3 text-left">Unidad</th>
                                <th class="px-2 py-3 text-left">Grupo</th>
                                <th class="px-2 py-3 text-left">Línea</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-800 bg-[#141414]">
                            @foreach ($nuevos as $index => $row)
                                <tr wire:key="nuevo-{{ $index }}" class="hover:bg-zinc-900/50 transition-colors">
                                    <td class="px-3 py-2 text-center border-r border-zinc-800">
                                        <button wire:click="quitarNuevo({{ $index }})" class="text-zinc-600 hover:text-red-400 transition-colors" title="No importar este renglón">🗑️</button>
                                    </td>
                                    <td class="px-2 py-2 text-xs font-bold text-fuchsia-400">{{ $row['sku'] }}</td>
                                    <td class="px-2 py-2 text-xs text-zinc-300">{{ $row['name'] }}</td>
                                    <td class="px-2 py-2 text-xs text-zinc-500">{{ $row['unit'] }}</td>
                                    <td class="px-2 py-2 text-xs text-zinc-500">{{ $row['grupo'] ?? '—' }}</td>
                                    <td class="px-2 py-2 text-xs text-zinc-500">{{ $row['linea'] ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="mt-6 flex justify-end border-t border-zinc-800 pt-6">
                    <button
                        wire:click="confirmarImportacion"
                        wire:loading.attr="disabled"
                        wire:target="confirmarImportacion"
                        wire:confirm="¿Confirmas crear {{ count($nuevos) }} productos nuevos en el catálogo?"
                        class="relative inline-flex items-center px-8 py-3 rounded-lg text-lg font-bold text-white bg-gradient-to-r from-cyan-500 via-blue-600 to-fuchsia-600 hover:from-cyan-400 hover:to-fuchsia-500 shadow-[0_0_20px_rgba(192,38,211,0.3)] transition-all transform hover:scale-[1.02] active:scale-[0.98] disabled:opacity-50">
                        💾 Confirmar e Importar
                    </button>
                </div>
            </div>
        @endif
    </div>
</div>
