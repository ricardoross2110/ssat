<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $fecha
 * @property string $hora_inicio
 * @property string $hora_final
 * @property string $comentario
 */
class Calendario extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'calendarios';

    public $timestamps = false;
    	
    /**
     * @var array
     */
    protected $fillable = ['fecha', 'hora_inicio', 'hora_final', 'comentario'];

}
