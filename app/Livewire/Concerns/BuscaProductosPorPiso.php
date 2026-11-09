<?php

namespace App\Livewire\Concerns;

use App\Models\Product;
use App\Services\ProductMatcher;

/**
 * Buscador compartido entre las herramientas de piso (Asignar Código de
 * Barras, Acomodar Mercancía): mismo criterio de búsqueda (código de barras
 * exacto, sku/nombre parcial), mismos datos en los resultados, y el mismo
 * truco de "escanear la etiqueta LIMPIAR para borrar todo".
 *
 * La clase que usa este trait debe declarar sus propias propiedades públicas
 * $search, $resultados y $productoActivo.
 */
trait BuscaProductosPorPiso
{
    // Código "comando" para el lector: sin importar qué haya escrito ya en el campo,
    // al escanear esta etiqueta se limpia todo. Como el lector solo escribe texto,
    // esta palabra queda pegada a lo que hubiera antes (ej. "119574-33150LIMPIAR"),
    // por eso se busca como subcadena y no como coincidencia exacta.
    private const CODIGO_LIMPIAR = 'LIMPIAR';

    public function updatedSearch()
    {
        $this->productoActivo = null;

        if (str_contains(strtoupper($this->search), self::CODIGO_LIMPIAR)) {
            $this->search = '';
            $this->resultados = [];

            return;
        }

        if (strlen($this->search) < 2) {
            $this->resultados = [];

            return;
        }

        // Mismo criterio y orden que la app: lo más parecido primero (ver Product::scopeBuscarEnPiso).
        $this->resultados = Product::buscarEnPiso($this->search)
            ->limit(20)
            ->get(['id', 'sku', 'name', 'codigo_barras', 'sin_codigo_fisico', 'stock_real', 'seccion', 'mueble_tipo', 'mueble_numero', 'entrepano'])
            ->map(fn (Product $p) => [
                'id' => $p->id,
                'sku' => $p->sku,
                'name' => $p->name,
                'codigo_barras' => $p->codigo_barras,
                'sin_codigo_fisico' => $p->sin_codigo_fisico,
                'stock_real' => $p->stock_real,
                'ubicacion' => $p->ubicacion_completa,
                // 'espacios' / 'sufijo' si lo escaneado no es idéntico al código guardado.
                'aviso' => ProductMatcher::diferenciaConEscaneado($p->codigo_barras, $p->sku, $this->search),
            ])
            ->all();
    }

    /**
     * Al elegir un producto de la lista: si lo escaneado no era idéntico a su código
     * guardado, deja el aviso listo para la pantalla (lo escaneado vs lo guardado).
     */
    protected function avisoAlSeleccionar(int $productId): ?array
    {
        foreach ($this->resultados as $r) {
            if ($r['id'] === $productId && $r['aviso'] !== null) {
                return ['tipo' => $r['aviso'], 'escaneado' => ProductMatcher::codigoEscaneado($this->search), 'guardado' => $r['codigo_barras']];
            }
        }

        return null;
    }

    public function limpiarBusqueda()
    {
        $this->search = '';
        $this->resultados = [];
    }
}
