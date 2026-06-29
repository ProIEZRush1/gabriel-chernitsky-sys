<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Propiedad extends Model
{
    use HasFactory;

    protected $table = 'propiedades';

    protected $fillable = [
        'nombre',
        'tipo',
        'direccion',
        'ciudad',
        'valor_comercial',
        'estado',
        'notas',
    ];

    protected $casts = [
        'valor_comercial' => 'decimal:2',
    ];

    public function rentas()
    {
        return $this->hasMany(Renta::class);
    }

    public function seguros()
    {
        return $this->hasMany(Seguro::class);
    }
}
