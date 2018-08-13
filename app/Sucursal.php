<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $codigo
 * @property int $comunas_codigo
 * @property string $nombre
 * @property int $tipo
 * @property boolean $vigencia
 * @property string $descripcion
 * @property Comuna $comuna
 * @property Curso[] $cursos
 * @property Trabajadore[] $trabajadores
 */
class Sucursal extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'sucursales';

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
    protected $fillable = ['comunas_codigo', 'nombre', 'tipo', 'vigencia', 'descripcion'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function comuna()
    {
        return $this->belongsTo('App\Comuna', 'comunas_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function trabajadores()
    {
        return $this->hasMany('App\Trabajador', 'sucursales_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cursos()
    {
        return $this->hasMany('App\Curso', 'sucursales_codigo', 'codigo');
    }
}
