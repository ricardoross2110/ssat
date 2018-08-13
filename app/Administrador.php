<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $rut
 * @property string $nombres
 * @property string $apellido_paterno
 * @property string $apellido_materno
 * @property string $email
 * @property boolean $estado
 */
class Administrador extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'administradores';

    public $timestamps = false; 

    /**
     * @var array
     */
    protected $fillable = ['rut', 'nombres', 'apellido_paterno', 'apellido_materno', 'email', 'estado'];

}
