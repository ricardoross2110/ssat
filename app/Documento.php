<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cursos_codigo
 * @property string $nombre
 * @property string $extension
 * @property string $nombre_unico
 * @property string $ruta
 * @property Curso $curso
 */
class Documento extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'documentos';
    
    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['cursos_codigo', 'nombre', 'extension', 'nombre_unico', 'ruta'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function curso()
    {
        return $this->belongsTo('App\Curso', 'cursos_codigo', 'codigo');
    }
}
