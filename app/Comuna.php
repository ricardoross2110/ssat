<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $codigo
 * @property int $codigo_provincia
 * @property string $nombre
 * @property Provincia $provincia
 * @property Sucursale[] $sucursales
 */
class Comuna extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'comunas';

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
    protected $fillable = ['codigo_provincia', 'nombre'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function provincia()
    {
        return $this->belongsTo('App\Provincia', 'codigo_provincia', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function sucursales()
    {
        return $this->hasMany('App\Sucursal', 'comunas_codigo', 'codigo');
    }
}
