<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cursos_codigo
 * @property string $instructores_rut
 * @property Curso $curso
 * @property Instructore $instructore
 * @property Versione[] $versiones
 */
class CursoInstructor extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'curso_instructor';

    public $timestamps = false;    

    /**
     * @var array
     */
    protected $fillable = ['cursos_codigo', 'instructores_id'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function curso()
    {
        return $this->belongsTo('App\Curso', 'cursos_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instructor()
    {
        return $this->belongsTo('App\Instructor', 'instructores_rut', 'rut');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versiones()
    {
        return $this->hasMany('App\Version');
    }
}
