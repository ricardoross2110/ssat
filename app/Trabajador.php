<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $rut
 * @property int $cargos_codigo
 * @property int $sucursales_codigo
 * @property int $empresas_id
 * @property string $nombres
 * @property string $apellido_paterno
 * @property string $apellido_materno
 * @property string $email
 * @property string $fecha_nacimiento
 * @property string $genero
 * @property int $wwid
 * @property int $pid
 * @property string $fecha_ingreso
 * @property string $rut_jefatura
 * @property string $contrasena
 * @property boolean $estado
 * @property int $centrosCostos_codigo
 * @property Cargo $cargo
 * @property CentrosCosto $centrosCosto
 * @property Empresa $empresa
 * @property Sucursale $sucursale
 * @property JefeInstructor[] $jefeInstructors
 * @property Instructore $instructore
 * @property User[] $users
 * @property CursoTrabajador[] $cursoTrabajadors
 */
class Trabajador extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'trabajadores';

    public $timestamps = false;

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'rut';

    /**
     * The "type" of the auto-incrementing ID.
     * 
     * @var string
     */
    protected $keyType = 'string';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = ['cargos_codigo', 'sucursales_codigo', 'empresas_id', 'nombres', 'apellido_paterno', 'apellido_materno', 'email', 'fecha_nacimiento', 'genero', 'wwid', 'pid', 'fecha_ingreso', 'rut_jefatura', 'contrasena', 'estado', 'centrosCostos_codigo'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function empresa()
    {
        return $this->belongsTo('App\Empresa', 'empresas_id', 'id');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function sucursal()
    {
        return $this->belongsTo('App\Sucursal', 'sucursales_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function centroCosto()
    {
        return $this->belongsTo('App\CentroCosto', 'centrosCostos_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function cargo()
    {
        return $this->belongsTo('App\Cargo', 'cargos_codigo', 'codigo');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function users()
    {
        return $this->hasMany('App\User', 'trabajadores_rut', 'rut');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function instructores()
    {
        return $this->hasMany('App\Instructor', 'trabajadores_rut', 'rut');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cursoTrabajadores()
    {
        return $this->hasMany('App\CursoTrabajador', 'trabajadores_rut', 'rut');
    }
}
