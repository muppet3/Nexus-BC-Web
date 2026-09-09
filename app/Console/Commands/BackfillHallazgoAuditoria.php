<?php

namespace App\Console\Commands;

use App\Models\HallazgoCenso;
use App\Models\HistorialAuditoria;
use Illuminate\Console\Command;

/**
 * Comando de una sola corrida: liga los renglones viejos de historial_auditorias
 * (de antes de que existiera la columna hallazgo_id) con el hallazgo_censo que
 * realmente les corresponde, para que también les aparezcan los íconos de
 * editar/borrar en "Historial del producto".
 *
 * Reglas de inferencia (nunca adivina si no está seguro — deja hallazgo_id en
 * NULL si no puede determinarlo con certeza, que es el comportamiento seguro
 * que ya tenía la app: simplemente no le salen los íconos a ese renglón):
 *
 *  - Si para un producto+usuario dado solo existe UN hallazgo, todos sus
 *    renglones de auditoría (creación y ediciones) se ligan a ese hallazgo.
 *  - Si existen VARIOS hallazgos para el mismo producto+usuario (caso raro),
 *    solo se ligan con certeza los renglones "Nuevo hallazgo registrado" que
 *    coincidan exactamente en fecha/hora de creación con uno de esos hallazgos
 *    (se crean en la misma petición, así que coinciden al segundo). Las
 *    "Edición de hallazgo" de ese caso ambiguo se dejan sin ligar.
 */
class BackfillHallazgoAuditoria extends Command
{
    protected $signature = 'hallazgos:backfill-auditoria';

    protected $description = 'Liga los registros viejos de historial_auditorias con su hallazgo_censo correspondiente';

    public function handle(): int
    {
        $pendientes = HistorialAuditoria::whereNull('hallazgo_id')->get();
        $this->info("Auditorías sin ligar encontradas: {$pendientes->count()}");

        if ($pendientes->isEmpty()) {
            return self::SUCCESS;
        }

        $ligados = 0;
        $ambiguos = 0;
        $sinCandidato = 0;

        $grupos = $pendientes->groupBy(fn ($a) => $a->product_id . '-' . $a->user_id);

        foreach ($grupos as $clave => $auditoriasDelGrupo) {
            [$productId, $userId] = explode('-', $clave);

            $hallazgos = HallazgoCenso::where('product_id', $productId)
                ->where('user_id', $userId)
                ->get();

            if ($hallazgos->count() === 1) {
                $hallazgoId = $hallazgos->first()->id;
                foreach ($auditoriasDelGrupo as $aud) {
                    $aud->update(['hallazgo_id' => $hallazgoId]);
                    $ligados++;
                }

                continue;
            }

            if ($hallazgos->isEmpty()) {
                $sinCandidato += $auditoriasDelGrupo->count();

                continue;
            }

            foreach ($auditoriasDelGrupo as $aud) {
                if ($aud->accion !== 'Nuevo hallazgo registrado') {
                    $ambiguos++;

                    continue;
                }

                $match = $hallazgos->first(fn ($h) => $h->created_at->eq($aud->created_at));

                if ($match) {
                    $aud->update(['hallazgo_id' => $match->id]);
                    $ligados++;
                } else {
                    $ambiguos++;
                }
            }
        }

        $this->info("Ligados correctamente: {$ligados}");
        $this->info("Ambiguos, se dejaron sin ligar (varios hallazgos posibles): {$ambiguos}");
        $this->info("Sin ningún hallazgo candidato (probablemente ya borrado): {$sinCandidato}");

        return self::SUCCESS;
    }
}
