<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $evaluaciones_id
 * @property int $curso_trabajador_id
 * @property int $resultado
 * @property string $fecha
 * @property Evaluacione $evaluacione
 * @property CursoTrabajador $cursoTrabajador
 */
class Repechaje extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'repechaje';

    public $timestamps = false;     

    /**
     * @var array
     */
    protected $fillable = ['evaluaciones_id','curso_trabajador_id','resultado', 'fecha'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function evaluacion()
    {
        return $this->belongsTo('App\Evaluacion', 'evaluaciones_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function CursoTrabajador()
    {
        return $this->belongsTo('App\CursoTrabajador', 'curso_trabajador_id', 'id');
    }
}

