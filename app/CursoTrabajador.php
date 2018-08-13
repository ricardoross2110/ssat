<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $cursos_codigo
 * @property string $trabajadores_rut
 * @property int $versiones_id
 * @property string $status
 * @property float $nota_final
 * @property float $nota_final_repechaje
 * @property string $status_repechaje
 * @property boolean $repechaje
 * @property float $asistencia_final
 * @property Curso $curso
 * @property Trabajadore $trabajadore
 * @property Versione $versione
 * @property Mensaje[] $mensajes
 * @property Nota[] $notas
 * @property Asistencia[] $asistencias
 * @property Repechaje[] $repechajes
 */
class CursoTrabajador extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'curso_trabajador';

    public $timestamps = false;    

    /**
     * @var array
     */
    protected $fillable = ['cursos_codigo', 'trabajadores_rut', 'versiones_id', 'status', 'nota_final', 'nota_final_repechaje', 'status_repechaje', 'repechaje', 'asistencia_final'];

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
    public function trabajador()
    {
        return $this->belongsTo('App\Trabajador', 'trabajadores_rut', 'rut');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function version()
    {
        return $this->belongsTo('App\Version', 'versiones_id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function notas()
    {
        return $this->hasMany('App\Nota');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function asistencias()
    {
        return $this->hasMany('App\Asistencia');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function mensajes()
    {
        return $this->hasMany('App\Mensaje');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function repechajes()
    {
        return $this->hasMany('App\Repechaje');
    }
}
