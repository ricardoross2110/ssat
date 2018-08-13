<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $codigo
 * @property string $nombre
 * @property Versione[] $versiones
 * @property Nivele[] $niveles
 */
class Calificacion extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'calificaciones';

    public $timestamps = false;    

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codigo';

    /**
     * The "type" of the auto-incrementing ID.
     * 
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = ['nombre'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versiones()
    {
        return $this->hasMany('App\Version', 'calificaciones_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function niveles()
    {
        return $this->hasMany('App\Nivel', 'calificaciones_codigo', 'codigo');
    }
}
