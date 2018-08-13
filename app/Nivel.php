<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string $calificaciones_codigo
 * @property int $cursos_codigo
 * @property int $nivel_num
 * @property Calificacione $calificacione
 * @property Curso $curso
 */
class Nivel extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'niveles';

    public $timestamps = false;    

    /**
     * @var array
     */
    protected $fillable = ['calificaciones_codigo', 'cursos_codigo', 'nivel_num'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function calificacion()
    {
        return $this->belongsTo('App\Calificacion', 'calificaciones_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function curso()
    {
        return $this->belongsTo('App\Curso', 'cursos_codigo', 'codigo');
    }
}
