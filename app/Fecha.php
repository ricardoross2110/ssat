<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $versiones_id
 * @property string $fecha
 * @property Versione $versione
 */
class Fecha extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'fechas';

    public $timestamps = false;
        
    /**
     * @var array
     */
    protected $fillable = ['versiones_id', 'fecha'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function version()
    {
        return $this->belongsTo('App\Version', 'versiones_id');
    }
}
