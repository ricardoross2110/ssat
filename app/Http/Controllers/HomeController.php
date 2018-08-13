<?php

/*
 * Taken from
 * https://github.com/laravel/framework/blob/5.3/src/Illuminate/Auth/Console/stubs/make/controllers/HomeController.stub
 */

namespace App\Http\Controllers;

use App\Http\Requests;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\DB;
use Calendar;
use Carbon\Carbon;
use App\CursoTrabajador;
use App\Calendario;
use App\Version;
use App\Notificacion;
use App\Programa;
use App\Evaluacion;
use App\Log;
use App\LogErrores;
use App\Curso;
use App\Nota;
use App\Asistencia;
use App\Fecha;
use App\Instructor;
use helpers;
use Morris;
use Auth;

/**
 * Class HomeController
 * @package App\Http\Controllers
 */
class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    /**
     * Show the application dashboard.
     *
     * @return Response
     */
    public function index()
    {
        $date   = date_create();
        $instructores = Instructor::get();
        $events = [];
        $year   = date_format($date, 'Y');
        $programasdehoy = [];
        $programasdelmes = [];

        switch (Auth::user()->rol_select) {
            case 'admin':
                $data = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
                ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
                ->where('programas.estado', '=', true)
                ->get();
                break;
            
            case 'alumno':
                $data = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
                ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
                ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                ->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)
                ->where('programas.estado', '=', true)
                ->get();
                break;

            case 'jefatura':
                $data = Version::select('versiones.id As version_id', 'cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
                ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
                ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut');

                $trabajadores =  DB::table('trabajadores')->select('trabajadores.*')->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

                //$help = new helpers();
                //$colaboradores = $help->ArbolColaboradores($trabajadores, true);
                $colaboradores = ArbolColaboradores($trabajadores, true);
                $colaboradores = array_values_recursive($colaboradores);
                
                $data = $data->where(function ($q) use ($colaboradores) {
                    foreach ($colaboradores as $value) {
                        foreach ($value as $colaborador) {
                            $q = $q->orWhere('curso_trabajador.trabajadores_rut', '=', $colaborador->rut);       
                        }
                    }
                });
                $data = $data->where('programas.estado', '=', true)->groupBy('versiones.id')->groupBy('cursos.nombre')->groupBy('programas.codigo')->groupBy('fechas.id')->get();
                break;

            case 'instructor':
                $data = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
                ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
                ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                ->where('programas.estado', '=', true)
                ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                ->get();
                break;

            default:
                $data = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
                ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
                ->where('programas.estado', '=', true)
                ->get();
                break;
        }

        if($data->count()) {
            foreach ($data as $key => $value) {
                $events[] = Calendar::event(
                    $value->nombre_programa.' '.$value->nombre_curso,
                    true,
                    new \DateTime($value->fecha),
                    new \DateTime($value->fecha.' +1 day'),
                    null,
                    [
                        'color'           => '#f05050',
                        'url'             => asset('/programas/'.$value->codigo_programa),
                    ]
                );
            }
        }

        //dias no habiles para comparar
        $fechas_calendario = Calendario::all(); 
        if($fechas_calendario->count()){
            foreach ($fechas_calendario as $calendario) {
                $events[] = Calendar::event(
                    $calendario->comentario,
                    true,
                    new \DateTime($calendario->fecha),
                    new \DateTime($calendario->fecha.' +1 day'),
                    null,
                    [
                        'color'           => '#f05050',
                        'rendering'       => 'background'
                    ]
                );
            }
        }

        switch (Auth::user()->rol_select) {
            case 'admin':
                $version = Version::select('versiones.*')->groupBy('versiones.id')->orderBy('versiones.id')->get();
                break;
            
            case 'alumno':
                $version = Version::select('versiones.*')
                                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                        ->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)
                                        ->groupBy('versiones.id')
                                        ->orderBy('versiones.id')
                                        ->get();
                break;

            case 'jefatura':
                $version = Version::select('versiones.*')
                ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut');

                $trabajadores =  DB::table('trabajadores')->select('trabajadores.*')->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

                $colaboradores = ArbolColaboradores($trabajadores, true);
                $colaboradores = array_values_recursive($colaboradores);
                
                $version = $version->where(function ($q) use ($colaboradores) {
                    foreach ($colaboradores as $value) {
                        foreach ($value as $colaborador) {
                            $q = $q->orWhere('curso_trabajador.trabajadores_rut', '=', $colaborador->rut);       
                        }
                    }
                });
                $version = $version->groupBy('versiones.id')->orderBy('versiones.id')->get();

                break;

            case 'instructor':
                $version = Version::select('versiones.*')
                                        ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                        ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                        ->groupBy('versiones.id')
                                        ->orderBy('versiones.id')
                                        ->get();
                break;

            default:
                $version = Version::select('versiones.*')->groupBy('versiones.id')->orderBy('versiones.id')->get();
                break;
        }

        $calendar = Calendar::setOptions(['firstDay' => 1, 'lang' => 'es', 'buttonText'=>['today' => 'Hoy', 'month' => 'Mes', 'week' => 'Semana', 'day' => 'Día']]);
        $calendar = Calendar::addEvents($events);

        if (count($version) > 0) {
            foreach ($version as $value) {
                $versionActual = Version::find($value->id);
                $fechaInicio   = date($versionActual->fecha_inicio);
                $fechaFin      = date($versionActual->fecha_fin);
                $fecha_inicio  = Carbon::parse($versionActual->fecha_inicio);
                $fecha_fin_f   = Carbon::parse($versionActual->fecha_fin)->addDay();
                $cursosActual  = Curso::find($versionActual->cursos_codigo);

                $dias          =   5;

                $listadoFechas = [];
                for ($i = 0; $i < $dias; $i++) {
                    // sumar n dias o su equivalente en segundos
                    $preListado = date('d-m-Y', strtotime($fechaFin) + 86400*$i);

                    //detectar dia de la semana a fecha ingresada
                    $diaSemana = date('N', strtotime($preListado));
                    if($diaSemana == '6'){
                        $preListado = date('d-m-Y', strtotime($preListado) + 86400*2);
                        $fechaFin = date('d-m-Y', strtotime($fechaFin) + 86400*2);
                    }
                    if($diaSemana == '7'){
                        $preListado = date('d-m-Y', strtotime($preListado) + 86400*1);
                        $fechaFin = date('d-m-Y', strtotime($fechaFin) + 86400*1);
                    }

                    //comparar con fechas de dias no habiles, para no agregar y pasar a la siguiente
                    $mensaje = [];
                    foreach ($fechas_calendario as $fc) {
                        $fc->fecha = date('d-m-Y', strtotime($fc->fecha));
                        if ($fc->fecha <> $preListado) {
                            $mensaje[$i] = 'fecha distinta'; 
                        }else {
                            $preListado = date('d-m-Y', strtotime($preListado) + 86400*1);
                            $fechaFin = date('d-m-Y', strtotime($fechaFin) + 86400*1);
                            $mensaje[$i] = 'fecha igual';
                        }                    
                    }
                    $listadoFechas[$i] = date('Y-m-d', strtotime($preListado));
                }

                $fecha_fin = end($listadoFechas);
                $fecha_fin = Carbon::parse($fecha_fin)->addDay();

                if($fecha_fin < Carbon::now('America/Santiago')){
                    $versionActual->status = "C";
                    $statusTrabajadores = CursoTrabajador::where('versiones_id', '=', $versionActual->id)->get();
                    
                    foreach ($statusTrabajadores as $value) {
                        if ($value->status == "P" || is_null($value->status)) {
                            $notas = Nota::where('curso_trabajador_id', '=', $value->id)->get();
                            if (count($notas) == 0) {
                                $value->nota_final      = '0';
                                if ($versionActual->situacion == "Con Evaluación") {
                                    $value->status      = 'R';
                                }
                            }else{
                                $notafinal                  = 0;
                                $notasEvaluaciones          = Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'notas.resultado')
                                                ->join('notas', 'evaluaciones.id', '=', 'notas.evaluaciones_id')
                                                ->join('curso_trabajador', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')
                                                ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                                ->where('versiones.id', '=', $versionActual->id)
                                                ->where('curso_trabajador.id', '=', $value->id)
                                                ->get();

                                foreach($notasEvaluaciones as $evaluacion) {
                                    $notafinal = $notafinal + ($evaluacion->resultado * ($evaluacion->porcentaje / 100));
                                }

                                $value->nota_final      = $notafinal;
                                if ($versionActual->situacion == "Con Evaluación") {
                                    if ($value->notafinal >= $cursosActual->aprobacion_minima) {
                                        $value->status      = 'A';
                                    }else{
                                        $value->status      = 'R';
                                    }
                                }
                            }
                            $asistencias = Asistencia::where('curso_trabajador_id', '=', $value->id)->get();
                            if (count($asistencias) == 0) {
                                $value->asistencia_final        =    '0';
                                if ($versionActual->situacion == "Con Asistencia") {
                                    $value->status              =    'R';
                                }
                            }
                            $value->save();
                        }
                    }
                }else{
                    if (Carbon::now('America/Santiago') < Carbon::parse($fecha_inicio)) {
                        $versionActual->status = "A";
                    }else{
                        if((Carbon::now('America/Santiago') > $fecha_fin_f) && (Carbon::now('America/Santiago') < $fecha_fin)){
                            $versionActual->status = "P";
                        }else{
                            $versionActual->status = "D";
                        }
                    }
                }

                $versionActual->save();
            }
        }

        // para filtrar HOME a mostrar. 
        if (Auth::user()->rol_select == 'admin' ){
            $notificaciones = Notificacion::select('*')
                                                ->where('admin', '=', true)
                                                ->orderBy('visto_admin')
                                                ->orderBy('fecha', 'desc');
        }else{                
            $notificaciones = Notificacion::select('*')
                                                ->orderBy('visto')
                                                ->orderBy('fecha', 'desc');
        }

        if ( Auth::user()->rol_select == 'admin' ){
            $cursos['ENE']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '1')->whereYear('fecha_inicio', $year)->get();
            $cursos['FEB']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '2')->whereYear('fecha_inicio', $year)->get();
            $cursos['MAR']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '3')->whereYear('fecha_inicio', $year)->get();
            $cursos['ABR']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '4')->whereYear('fecha_inicio', $year)->get();
            $cursos['MAY']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '5')->whereYear('fecha_inicio', $year)->get();
            $cursos['JUN']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '6')->whereYear('fecha_inicio', $year)->get();
            $cursos['JUL']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '7')->whereYear('fecha_inicio', $year)->get();
            $cursos['AGO']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '8')->whereYear('fecha_inicio', $year)->get();
            $cursos['SEP']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '9')->whereYear('fecha_inicio', $year)->get();
            $cursos['OCT']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '10')->whereYear('fecha_inicio', $year)->get();
            $cursos['NOV']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '11')->whereYear('fecha_inicio', $year)->get();
            $cursos['DIC']    = Version::select('cursos_codigo')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '12')->whereYear('fecha_inicio', $year)->get();
            $alumnos['ENE']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '1')->whereYear('fecha_inicio', $year)->get();
            $alumnos['FEB']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '2')->whereYear('fecha_inicio', $year)->get();
            $alumnos['MAR']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '3')->whereYear('fecha_inicio', $year)->get();
            $alumnos['ABR']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '4')->whereYear('fecha_inicio', $year)->get();
            $alumnos['MAY']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '5')->whereYear('fecha_inicio', $year)->get();
            $alumnos['JUN']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '6')->whereYear('fecha_inicio', $year)->get();
            $alumnos['JUL']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '7')->whereYear('fecha_inicio', $year)->get();
            $alumnos['AGO']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '8')->whereYear('fecha_inicio', $year)->get();
            $alumnos['SEP']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '9')->whereYear('fecha_inicio', $year)->get();
            $alumnos['OCT']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '10')->whereYear('fecha_inicio', $year)->get();
            $alumnos['NOV']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '11')->whereYear('fecha_inicio', $year)->get();
            $alumnos['DIC']   = Version::select('trabajadores_rut')->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')->where('programas.estado', true)->whereMonth('fecha_inicio', '12')->whereYear('fecha_inicio', $year)->get();
            $hh = DB::table('versiones')
                            ->select('programas.codigo As codigo_programa', 'programas.nombre As nombre_programa', 'cursos.codigo As codigo_curso', 'cursos.nombre As nombre_curso', 'versiones.horas As horas_trabajadas', 'versiones.fecha_inicio', 'versiones.fecha_fin', 'sucursales.nombre As nombre_sucursal', 'curso_instructor.instructores_rut')
                            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                            ->join('instructores', 'instructores_rut', '=', 'instructores.rut')
                            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                            ->join('sucursales', 'versiones.lugar_ejecucion', '=', 'sucursales.codigo')
                            ->where('instructores.estado', '=', true)
                            ->orderBy('programas.codigo')
                            ->get();

            $instructoresActivos = DB::table('instructores')->select('rut')->where('estado', '=', true)->get();

            $instructores_hh = [];
            foreach ($instructoresActivos as $instructoresA) {
                $instructores_hh[$instructoresA->rut] = 0;
            }
            foreach ($hh as $horas_trabajadas) {
                $instructores_hh[$horas_trabajadas->instructores_rut] = $instructores_hh[$horas_trabajadas->instructores_rut] + $horas_trabajadas->horas_trabajadas;
            }
            $notificaciones = $notificaciones->get();
            $programasdehoy = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
            ->where('fechas.fecha', '=', $date)
            ->where('programas.estado', '=', true)
            ->orderBy('fechas.fecha')
            ->get();
            $programasdelmes = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
            ->whereMonth('fechas.fecha', date_format($date, 'm'))
            ->where('programas.estado', '=', true)
            ->orderBy('fechas.fecha')
            ->get();
            return view(
                            'adminlte::home_admin',
                            compact('calendar')
                        )->with('notificaciones', $notificaciones)
                         ->with('programasdehoy', $programasdehoy)
                         ->with('programasdelmes', $programasdelmes)
                         ->with('cursos', $cursos)
                         ->with('alumnos', $alumnos)
                         ->with('instructores', $instructores)
                         ->with('instructores_hh', $instructores_hh);
        }
        if ( Auth::user()->rol_select == 'jefatura' ) {
            $trabajadores =  DB::table('trabajadores')->select('trabajadores.*')->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

            $colaboradores = ArbolColaboradores($trabajadores, true);
            $colaboradores = array_values_recursive($colaboradores);

            for ($i=1; $i <= 12 ; $i++) { 
                $queryCursos[$i]           = DB::table('versiones')->select('versiones.cursos_codigo')
                                                ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                                                ->leftJoin('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')
                                                ->leftJoin('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                                                ->where('programas.estado', '=', true);

                                            $queryCursos[$i] = $queryCursos[$i]->where(function ($q) use ($colaboradores) {
                                                foreach ($colaboradores as $value) {
                                                    foreach ($value as $colaborador) {
                                                        $q = $q->orWhere('curso_trabajador.trabajadores_rut', '=', $colaborador->rut);       
                                                    }
                                                }
                                            });

                $queryAlumnos[$i]          = Version::select('trabajadores_rut')
                                            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                                            ->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')
                                            ->Join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                                            ->where('programas.estado', '=', true);

                                            $queryAlumnos[$i] = $queryAlumnos[$i]->where(function ($q) use ($colaboradores) {
                                                foreach ($colaboradores as $value) {
                                                    foreach ($value as $colaborador) {
                                                        $q = $q->orWhere('curso_trabajador.trabajadores_rut', '=', $colaborador->rut);
                                                    }
                                                }
                                            });
            }

            $cursos['ENE']    = $queryCursos[1]
                                            ->whereMonth('fecha_inicio', '1')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();

            $cursos['FEB']    = $queryCursos[2]
                                            ->whereMonth('fecha_inicio', '2')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();

            $cursos['MAR']    = $queryCursos[3]
                                            ->whereMonth('fecha_inicio', '3')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();
            $cursos['ABR']    = $queryCursos[4]
                                            ->whereMonth('fecha_inicio', '4')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();

            $cursos['MAY']    = $queryCursos[5]
                                            ->whereMonth('fecha_inicio', '5')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();
            $cursos['JUN']    = $queryCursos[6]
                                            ->whereMonth('fecha_inicio', '6')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();
            $cursos['JUL']    = $queryCursos[7]
                                            ->whereMonth('fecha_inicio', '7')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();
            $cursos['AGO']    = $queryCursos[8]
                                            ->whereMonth('fecha_inicio', '8')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();
            $cursos['SEP']    = $queryCursos[9]
                                            ->whereMonth('fecha_inicio', '9')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();
            $cursos['OCT']    = $queryCursos[10]
                                            ->whereMonth('fecha_inicio', '10')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();
            $cursos['NOV']    = $queryCursos[11]
                                            ->whereMonth('fecha_inicio', '11')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();
            $cursos['DIC']    = $queryCursos[12]
                                            ->whereMonth('fecha_inicio', '12')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('versiones.id')
                                            ->get();

            $alumnos['ENE']   = $queryAlumnos[1]
                                            ->whereMonth('fecha_inicio', '1')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();

            $alumnos['FEB']   = $queryAlumnos[2]
                                            ->whereMonth('fecha_inicio', '2')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();

            $alumnos['MAR']   = $queryAlumnos[3]
                                            ->whereMonth('fecha_inicio', '3')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();

            $alumnos['ABR']   = $queryAlumnos[4]
                                            ->whereMonth('fecha_inicio', '4')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();

            $alumnos['MAY']   = $queryAlumnos[5]
                                            ->whereMonth('fecha_inicio', '5')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();
            $alumnos['JUN']   = $queryAlumnos[6]
                                            ->whereMonth('fecha_inicio', '6')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();

            $alumnos['JUL']   = $queryAlumnos[7]
                                            ->whereMonth('fecha_inicio', '7')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();

            $alumnos['AGO']   = $queryAlumnos[8]
                                            ->whereMonth('fecha_inicio', '8')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();
            $alumnos['SEP']   = $queryAlumnos[9]
                                            ->whereMonth('fecha_inicio', '9')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();

            $alumnos['OCT']   = $queryAlumnos[10]
                                            ->whereMonth('fecha_inicio', '10')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();

            $alumnos['NOV']   = $queryAlumnos[11]
                                            ->whereMonth('fecha_inicio', '11')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();

            $alumnos['DIC']   = $queryAlumnos[12]
                                            ->whereMonth('fecha_inicio', '12')
                                            ->whereYear('fecha_inicio', $year)
                                            ->groupBy('curso_trabajador.id')
                                            ->get();


            $notificaciones = $notificaciones->where('users_id', '=', Auth::user()->id)->where('rol', '=', 'Jefatura')->get();

            $programasdehoy = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
            ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
            ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut');
            
            $programasdehoy = $programasdehoy->where(function ($q) use ($colaboradores) {
                foreach ($colaboradores as $value) {
                    foreach ($value as $colaborador) {
                        $q = $q->orWhere('curso_trabajador.trabajadores_rut', '=', $colaborador->rut);       
                    }
                }
            });

            $programasdehoy = $programasdehoy->where('fechas.fecha', '=', $date)
                                             ->where('programas.estado', '=', true)
                                             ->groupBy('cursos.nombre')
                                             ->groupBy('programas.codigo')
                                             ->groupBy('fechas.id')
                                             ->orderBy('fechas.fecha')
                                             ->get();

            $programasdelmes = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
            ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
            ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut');
            
            $programasdelmes = $programasdelmes->where(function ($q) use ($colaboradores) {
                foreach ($colaboradores as $value) {
                    foreach ($value as $colaborador) {
                        $q = $q->orWhere('curso_trabajador.trabajadores_rut', '=', $colaborador->rut);       
                    }
                }
            });
            $programasdelmes = $programasdelmes
                                            ->where('programas.estado', '=', true)
                                            ->whereMonth('fechas.fecha', date_format($date, 'm'))
                                            ->groupBy('cursos.nombre')
                                            ->groupBy('programas.codigo')
                                            ->groupBy('fechas.id')
                                            ->orderBy('fechas.fecha')
                                            ->get();

            return view('adminlte::home_jefatura', compact('calendar'), compact('notificaciones'))->with('programasdehoy', $programasdehoy)->with('programasdelmes', $programasdelmes)->with('cursos', $cursos)->with('alumnos', $alumnos);
        }
        if ( Auth::user()->rol_select == 'instructor' ) {
            $timeZone = 'America/Santiago'; 
            date_default_timezone_set($timeZone); 
            $now = date_create();
            $notas_atrasadas = CursoTrabajador::select('versiones.programas_codigo', 'notas.*')
            ->leftJoin('notas', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')
            ->join('versiones', 'curso_trabajador.versiones_id', '=', 'versiones.id')
            ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->where('programas.estado', '=', true)
            ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
            ->where('fechas.fecha', '<', date_format($now, 'Y-m-d'))
            ->whereNull('notas.id')
            ->groupBy('versiones.programas_codigo')
            ->groupBy('notas.id')
            ->get();
            $asistencias_atrasadas = CursoTrabajador::select('versiones.programas_codigo', 'asistencias.*')
            ->leftJoin('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
            ->join('versiones', 'curso_trabajador.versiones_id', '=', 'versiones.id')
            ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->where('programas.estado', '=', true)
            ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
            ->where('fechas.fecha', '<', date_format($now, 'Y-m-d'))
            ->whereNull('asistencias.id')
            ->groupBy('versiones.programas_codigo')
            ->groupBy('asistencias.id')
            ->get();
            $notificaciones = $notificaciones->where('users_id', '=', Auth::user()->id)->where('rol', '=', 'Instructor')->get();
            $programasdehoy = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
            ->where('programas.estado', '=', true)
            ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
            ->where('fechas.fecha', '=', $date)
            ->orderBy('fechas.fecha')
            ->get();
            $programasdelmes = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
            ->where('programas.estado', '=', true)
            ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
            ->whereMonth('fechas.fecha', date_format($date, 'm'))
            ->orderBy('fechas.fecha')
            ->get();
            return view('adminlte::home_instructor', compact('calendar'), compact('notificaciones'))->with('programasdehoy', $programasdehoy)->with('programasdelmes', $programasdelmes)->with('notas_atrasadas', $notas_atrasadas)->with('asistencias_atrasadas', $asistencias_atrasadas);
        }
        if ( Auth::user()->rol_select == 'alumno' ) {
            $notificaciones = $notificaciones->where('users_id', '=', Auth::user()->id)->where('rol', '=', 'Alumno')->get();
            $programasdehoy = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
                                        ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                                        ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
                                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                        ->where('programas.estado', '=', true)
                                        ->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)
                                        ->where('fechas.fecha', '=', $date)
                                        ->orderBy('fechas.fecha')
                                        ->get();

            $programasdelmes = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa', 'fechas.*')
                                        ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                                        ->join('fechas', 'fechas.versiones_id', '=', 'versiones.id')
                                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                        ->where('programas.estado', '=', true)
                                        ->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)
                                        ->whereMonth('fechas.fecha', date_format($date, 'm'))
                                        ->orderBy('fechas.fecha')
                                        ->get();

            return view('adminlte::home_alumno', compact('calendar'), compact('notificaciones'))->with('programasdehoy', $programasdehoy)->with('programasdelmes', $programasdelmes);
        }
    }

    public function errorGeneral($message)
    {
        $mensaje = \Request::get('mensaje');

        $timeZone = 'America/Santiago'; 
        date_default_timezone_set($timeZone);      

        $logError               =   new LogErrores;
        $logError->id_user      =   Auth::user()->id;
        $logError->tipo_error   =   'Error General';
        $logError->detalle      =   'Ocurrió un error general al intentar realizar una acción no permitida.';
        $logError->mensaje      =   $mensaje;
        $logError->created_at    =   date_create();
        $logError->save();

        //Guarda log al crear
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Error General",
            'details' => "Hubo un error no especifaco.",
        ]);

        return view('errors.general');
    }

    public function error400()
    {
        $timeZone = 'America/Santiago'; 
        date_default_timezone_set($timeZone);      

        $logError               =   new LogErrores;
        $logError->id_user      =   Auth::user()->id;
        $logError->tipo_error   =   'Error 400';
        $logError->detalle      =   'El usuario quiso ingresar a una página que ya no existe.';
        $logError->created_at    =   date_create();
        $logError->save();
        //Guarda log al crear
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Error 400",
            'details' => "El usuario quiso ingresar a una página que no existe.",
        ]);

        return view('errors.404');
    }
    
    public function error403()
    {
        $timeZone = 'America/Santiago'; 
        date_default_timezone_set($timeZone);      

        $logError               =   new LogErrores;
        $logError->id_user      =   Auth::user()->id;
        $logError->tipo_error   =   'Error 403';
        $logError->detalle      =   'El usuario quiso ingresar a una página la cuál no tiene el permiso.';
        $logError->created_at    =   date_create();
        $logError->save();

        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Error 403",
            'details' => "El usuario quiso ingresar a una página con acceso prohibido.",
        ]);
        return view('errors.404');
    }
    
    public function error404()
    {
        $timeZone = 'America/Santiago'; 
        date_default_timezone_set($timeZone);      

        $logError               =   new LogErrores;
        $logError->id_user      =   Auth::user()->id;
        $logError->tipo_error   =   'Error 404';
        $logError->detalle      =   'El usuario quiso ingresar a una página que no existe o no funciona.';
        $logError->created_at    =   date_create();
        $logError->save();

        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Error 404",
            'details' => "El usuario quiso ingresar a una página que no funciona o no existe.",
        ]);

        return view('errors.404');
    }
    
    public function error503()
    {
        $mensaje = \Request::get('mensaje');

        $timeZone = 'America/Santiago'; 
        date_default_timezone_set($timeZone);      

        $logError               =   new LogErrores;
        $logError->id_user      =   Auth::user()->id;
        $logError->tipo_error   =   'Error 503';
        $logError->detalle      =   'Ocurrió un error en el servidor.';
        $logError->mensaje      =   $mensaje;
        $logError->created_at    =   date_create();
        $logError->save();

        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Error 503",
            'details' => "Hay un problema con el servidor.",
        ]);

        return view('errors.404');
    }

    public function errorSql()
    {
        $mensaje = \Request::get('mensaje');

        $timeZone = 'America/Santiago'; 
        date_default_timezone_set($timeZone);

        $logError               =   new LogErrores;
        $logError->id_user      =   Auth::user()->id;
        $logError->tipo_error   =   'Error SQL';
        $logError->detalle      =   'Ocurrió un error de base de datos.';
        $logError->mensaje      =   $mensaje;
        $logError->created_at    =   date_create();
        $logError->save();

        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Error SQL",
            'details' => "El usuario ingresó un valor no valido para la base de datos.",
        ]);

        return view('errors.404');
    }

}