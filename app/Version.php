<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cursos_codigo
 * @property int $programas_codigo
 * @property int $calificaciones_codigo
 * @property int $empresas_id
 * @property int $curso_instructor_id
 * @property int $horas
 * @property string $situacion
 * @property string $cod_sence
 * @property int $lugar_ejecucion
 * @property string $status
 * @property string $fecha_inicio
 * @property string $fecha_fin
 * @property CursoInstructor $cursoInstructor
 * @property Curso $curso
 * @property Empresa $empresa
 * @property Programa $programa
 * @property Calificacione $calificacione
 * @property Fecha[] $fechas
 * @property CursoTrabajador[] $cursoTrabajadors
 */
class Version extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'versiones';

    public $timestamps = false;    

    /**
     * @var array
     */
    protected $fillable = ['cursos_codigo', 'programas_codigo', 'calificaciones_codigo', 'empresas_id', 'curso_instructor_id', 'horas', 'situacion', 'cod_sence', 'lugar_ejecucion', 'status', 'fecha_inicio', 'fecha_fin'];

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
    public function programa()
    {
        return $this->belongsTo('App\Programa', 'programas_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function empresa()
    {
        return $this->belongsTo('App\Empresa', 'empresas_id');
    }

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
    public function cursoInstructor()
    {
        return $this->belongsTo('App\CursoInstructor');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function fechas()
    {
        return $this->hasMany('App\Fecha', 'versiones_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cursoTrabajadores()
    {
        return $this->hasMany('App\CursoTrabajador', 'versiones_id');
    }
}
