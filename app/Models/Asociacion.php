<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Asociacion extends Model
{
    use HasFactory;

    protected $table = 'asociaciones';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'number_phone',
        'direccion',
        'city',
        'descripcion',
        'logo_url',
        'verified',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'verified' => 'boolean',
    ];

    /**
     * Obtener los recicladores de esta asociación
     */
    public function recicladores()
    {
        return $this->hasMany(Reciclador::class, 'asociacion_id');
    }

    /**
     * Obtener las solicitudes asignadas a esta asociación
     */
    public function solicitudes()
    {
        return $this->hasMany(Solicitud::class, 'asociacion_id');
    }

    /**
     * Obtener la cuenta de usuario asociada
     */
    public function authUser()
    {
        return $this->hasOne(AuthUser::class, 'profile_id')->where('role', 'asociacion');
    }
}
