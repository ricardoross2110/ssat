<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Nota extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'notas';

    public $timestamps = false;     

    /**
     * @var array
     */
    protected $fillable = ['evaluaciones_id', 'curso_trabajador_id', 'resultado', 'fecha'];

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

