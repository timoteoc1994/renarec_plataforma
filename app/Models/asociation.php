<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Sanctum\HasApiTokens;


class asociation extends Model
{
    use HasApiTokens;
    protected $fillable = ['name', 'email', 'photo', 'password', 'number_phone', 'city', 'estado'];
}
