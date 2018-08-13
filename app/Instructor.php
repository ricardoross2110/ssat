<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

/**
 * @property string $rut
 * @property string $nombres
 * @property string $apellido_paterno
 * @property string $apellido_materno
 * @property string $email
 * @property int $telefono
 * @property int $wwid
 * @property int $pid
 * @property string $foto
 * @property boolean $estado
 * @property string $nombre_foto
 * @property Trabajadore $trabajadore
 * @property CursoInstructor[] $cursoInstructors
 */
class Instructor extends Model
{
    /**
     * The table associated with the model.
     * 
     * @var string
     */
    protected $table = 'instructores';

    public $timestamps = false; 

    /**
     * The primary key for the model.
     * 
     * @var string
     */
    protected $primaryKey = 'rut';

    /**
     * Indicates if the IDs are auto-incrementing.
     * 
     * @var bool
     */
    public $incrementing = false;

    /**
     * @var array
     */
    protected $fillable = ['nombres', 'apellido_paterno', 'apellido_materno', 'email', 'telefono', 'wwid', 'pid', 'foto', 'estado', 'nombre_foto'];

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function trabajador()
    {
        return $this->belongsTo('App\Trabajador', 'rut', 'rut');
    }

    /**
     * @return \Illuminate\Database\Eloquent\Relations\HasMany
     */
    public function cursoInstructores()
    {
        return $this->hasMany('App\CursoInstructor', 'instructores_rut', 'rut');
    }
}
