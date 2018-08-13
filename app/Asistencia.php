<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $curso_trabajador_id
 * @property string $fecha
 * @property boolean $estado
 * @property CursoTrabajador $cursoTrabajador
 */
class Asistencia extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'asistencias';

    public $timestamps = false;

    /**
     * @var array
     */
    protected $fillable = ['curso_trabajador_id', 'fecha', 'estado'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cursoTrabajador()
    {
        return $this->belongsTo('App\CursoTrabajador');
    }
}
