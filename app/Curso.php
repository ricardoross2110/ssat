<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $codigo
 * @property int $sucursales_codigo
 * @property string $nombre
 * @property int $horas
 * @property float $aprobacion_minima
 * @property boolean $convalidable
 * @property boolean $repechaje
 * @property boolean $estado
 * @property string $categoria
 * @property int $tipoCursos_codigo
 * @property int $unidadesNegocio_codigo
 * @property int $tipoMotores_codigo
 * @property Sucursale $sucursale
 * @property TipoCurso $tipoCurso
 * @property TipoMotore $tipoMotore
 * @property UnidadesNegocio $unidadesNegocio
 * @property Documento[] $documentos
 * @property Evaluacione[] $evaluaciones
 * @property Nivele[] $niveles
 * @property Prerrequisito[] $prerrequisitos
 * @property Prerrequisito[] $prerrequisitos
 * @property CursoInstructor[] $cursoInstructors
 * @property CursoTrabajador[] $cursoTrabajadors
 * @property Versione[] $versiones
 */
class Curso extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'cursos';

    public $timestamps = false; 

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'codigo';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;    // revisar bien esto    

    /**
     * @var array
     */
    protected $fillable = ['sucursales_codigo', 'nombre', 'horas', 'aprobacion_minima', 'convalidable', 'repechaje', 'estado', 'categoria', 'tipoCursos_codigo', 'tipoMotores_codigo', 'unidadesNegocio_codigo'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tipoCurso()
    {
        return $this->belongsTo('App\TipoCurso', 'tipoCursos_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function tipoMotor()
    {
        return $this->belongsTo('App\TipoMotor', 'tipoMotores_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function unidadNegocio()
    {
        return $this->belongsTo('App\UnidadNegocio', 'unidadesNegocio_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sucursal()
    {
        return $this->belongsTo('App\Sucursal', 'sucursales_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function versiones()
    {
        return $this->hasMany('App\Version', 'cursos_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cursoInstructores()
    {
        return $this->hasMany('App\CursoInstructor', 'cursos_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function niveles()
    {
        return $this->hasMany('App\Nivel', 'cursos_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prerrequisitosPadre()
    {
        return $this->hasMany('App\Prerrequisito', 'cursos_codigo_padre', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function prerrequisitosHijo()
    {
        return $this->hasMany('App\Prerrequisito', 'cursos_codigo_hijo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function documentos()
    {
        return $this->hasMany('App\Documento', 'cursos_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cursoTrabajadores()
    {
        return $this->hasMany('App\CursoTrabajador', 'cursos_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function evaluaciones()
    {
        return $this->hasMany('App\Evaluacion', 'cursos_codigo', 'codigo');
    }
}
