<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $codigo
 * @property string $nombre
 * @property string $modalidad
 * @property boolean $estado
 * @property Versione[] $versiones
 */
class Programa extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'programas';

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
    protected $fillable = ['nombre', 'modalidad', 'estado'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versiones()
    {
        return $this->hasMany('App\Version', 'programas_codigo', 'codigo');
    }
}
