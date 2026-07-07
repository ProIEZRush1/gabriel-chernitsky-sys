<?php

namespace App\Console\Commands;

use App\Models\Renta;
use Illuminate\Console\Command;

class GenerarMensualidadesRentas extends Command
{
    protected $signature = 'rentas:generar-mensualidades {--periodo= : Mes límite a generar en formato YYYY-MM (por defecto, el mes actual)}';

    protected $description = 'Genera las mensualidades pendientes (cuentas por cobrar) de todas las rentas';

    public function handle(): int
    {
        $periodo = $this->option('periodo');
        $rentas = Renta::all();
        $creadas = 0;

        foreach ($rentas as $renta) {
            $creadas += $renta->generarMensualidadesPendientes($periodo);
        }

        $this->info("Mensualidades generadas: {$creadas} (sobre {$rentas->count()} renta(s)).");

        return self::SUCCESS;
    }
}
