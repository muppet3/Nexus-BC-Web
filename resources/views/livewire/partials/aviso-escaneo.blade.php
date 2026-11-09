{{-- Aviso: lo escaneado no es idéntico al código guardado. Recibe $aviso = ['tipo', 'escaneado', 'guardado'].
     Los espacios se muestran como ␣ para que se vean (sobre todo los del final). --}}
@php
    $visible = fn ($c) => $c === null || $c === '' ? '(sin código)' : str_replace(' ', '␣', $c);
@endphp
<div class="mb-6 p-4 rounded-lg border border-amber-500/40 bg-amber-500/10 text-amber-200 text-sm">
    <div class="font-bold mb-2">
        @if ($aviso['tipo'] === 'sufijo')
            ⚠ La etiqueta trae la letra de control Yanmar y el código guardado no
        @else
            ⚠ Lo escaneado no es exactamente igual al código guardado: cambian los espacios
        @endif
    </div>
    <div class="grid grid-cols-[auto,1fr] gap-x-3 gap-y-1 text-xs">
        <span class="text-amber-300/80">Escaneado:</span>
        <span class="font-mono text-white break-all">{{ $visible($aviso['escaneado']) }}</span>
        <span class="text-amber-300/80">Guardado:</span>
        <span class="font-mono text-zinc-300 break-all">{{ $visible($aviso['guardado']) }}</span>
    </div>
    <div class="mt-2 text-xs text-amber-300/80">
        El campo del código ya trae lo escaneado tal cual. Al guardar, el código queda corregido (con registro en auditoría).
    </div>
</div>
