<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cursos_codigo_padre
 * @property int $cursos_codigo_hijo
 * @property Curso $curso
 * @property Curso $curso
 */
class Prerrequisito extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'prerrequisitos';

    public $timestamps = false; 

    /**
     * @var array
     */
    protected $fillable = ['cursos_codigo_padre', 'cursos_codigo_hijo'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cursoPadre()
    {
        return $this->belongsTo('App\Curso', 'cursos_codigo_padre', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cursoHijo()
    {
        return $this->belongsTo('App\Curso', 'cursos_codigo_hijo', 'codigo');
    }
}
