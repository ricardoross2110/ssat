<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $id_user
 * @property string $tipo_error
 * @property string $detalle
 * @property string $mensaje
 * @property string $created_at
 * @property string $updated_at
 */

class LogErrores extends Model
{
    
     /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'log_errores';


    /**
     * @var array
     */
    protected $fillable = ['id_user', 'tipo_error', 'detalle', 'mensaje','created_at', 'updated_at'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function user()
    {
        return $this->belongsTo('App\User', 'id_user');
    }
}
