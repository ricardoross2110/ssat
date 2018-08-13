<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cursos_codigo
 * @property string $nombre
 * @property int $porcentaje
 * @property Curso $curso
 * @property Nota[] $notas
 * @property Repechaje[] $repechajes
 */
class Evaluacion extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'evaluaciones';

    public $timestamps = false;     

    /**
     * @var array
     */
    protected $fillable = ['cursos_codigo', 'nombre', 'porcentaje'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function curso()
    {
        return $this->belongsTo('App\Curso', 'cursos_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notas()
    {
        return $this->hasMany('App\Nota', 'evaluaciones_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function repechajes()
    {
        return $this->hasMany('App\Repechaje', 'evaluaciones_id');
    }
}
