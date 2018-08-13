<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $codigo
 * @property string $nombre
 * @property boolean $vigencia
 * @property string $descripcion
 * @property Trabajadore[] $trabajadores
 */
class Cargo extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'cargos';

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
    protected $fillable = ['nombre', 'vigencia', 'descripcion'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function trabajadores()
    {
        return $this->hasMany('App\Trabajador', 'cargos_codigo', 'codigo');
    }
}
