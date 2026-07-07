<?php

namespace App\Console\Commands;

use App\Models\Renta;
use Illuminate\Console\Command;

class GenerarMensualidadesRentas extends Command
{
    protected $signature = 'rentas:generar-mensualidades';

    protected $description = 'Genera las mensualidades pendientes (cuentas por cobrar) de todas las rentas';

    public function handle(): int
    {
        $rentas = Renta::all();
        $creadas = 0;

        foreach ($rentas as $renta) {
            $creadas += $renta->generarMensualidadesPendientes();
        }

        $this->info("Mensualidades generadas: {$creadas} (sobre {$rentas->count()} renta(s)).");

        return self::SUCCESS;
    }
}
