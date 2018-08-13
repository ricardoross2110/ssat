<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $codigo
 * @property string $nombre
 * @property string $descripcion
 * @property boolean $vigencia
 */
class CentroCosto extends Model
{
    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $table = 'centrosCostos'; /*Agregar cuando es Nombre Compuesto*/

    public $timestamps = false;

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codigo';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = ['nombre', 'descripcion', 'vigencia'];

    // Relación
    public function trabajador()
    {
        return $this->belongsTo('App\Trabajador');
    }    

}
