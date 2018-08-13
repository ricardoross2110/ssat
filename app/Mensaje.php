<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $curso_trabajador_id
 * @property string $titulo
 * @property string $texto
 * @property string $respuesta
 * @property string $created_at
 * @property string $updated_at
 * @property int $curso_instructor_id
 * @property boolean $estado
 * @property CursoTrabajador $cursoTrabajador
 */
class Mensaje extends Model
{   
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'mensajes';
    
    /**
     * @var array
     */
    protected $fillable = ['curso_trabajador_id', 'titulo', 'texto', 'respuesta', 'created_at', 'updated_at', 'curso_instructor_id', 'estado'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function curso_trabajador()
    {
        return $this->belongsTo('App\CursoTrabajador', 'curso_trabajador_id', 'id');
    }

    public function curso_instructor()
    {
        return $this->belongsTo('App\CursoInstructor', 'curso_instructor_id', 'id');
    }
}
