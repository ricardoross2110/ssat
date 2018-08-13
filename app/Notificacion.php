<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $users_id
 * @property string $texto
 * @property string $fecha
 * @property string $titulo
 * @property string $url
 * @property boolean $visto
 * @property string $titulo_admin
 * @property string $texto_admin
 * @property boolean $visto_admin
 * @property boolean $admin
 * @property string $rol
 * @property User $user
 */
class Notificacion extends Model
{
     /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'notificaciones';

    public $timestamps = false;    

    /**
     * @var array
     */
    protected $fillable = ['users_id', 'texto', 'fecha', 'titulo', 'url', 'visto', 'titulo_admin', 'texto_admin', 'visto_admin', 'admin', 'rol'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */

    public function user()
    {
        return $this->belongsTo('App\User', 'id_user');
    }
}
