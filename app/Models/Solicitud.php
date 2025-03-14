<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Solicitud extends Model
{
    use HasFactory;

    protected $table = 'solicitudes';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'ciudadano_id',
        'asociacion_id',
        'reciclador_id',
        'direccion',
        'ciudad',
        'referencias',
        'materiales',
        'comentarios',
        'fecha_solicitud',
        'fecha_recoleccion',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'fecha_solicitud' => 'date',
        'fecha_recoleccion' => 'date',
    ];

    /**
     * Obtener el ciudadano que creó esta solicitud
     */
    public function ciudadano()
    {
        return $this->belongsTo(Ciudadano::class, 'ciudadano_id');
    }

    /**
     * Obtener la asociación asignada a esta solicitud
     */
    public function asociacion()
    {
        return $this->belongsTo(Asociacion::class, 'asociacion_id');
    }

    /**
     * Obtener el reciclador asignado a esta solicitud
     */
    public function reciclador()
    {
        return $this->belongsTo(Reciclador::class, 'reciclador_id');
    }
}
