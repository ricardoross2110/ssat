<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Facades\Mail;
use App\Programa;
use App\Log;
use App\Curso;
use App\Documento;
use App\Calificacion;
use App\Evaluacion;
use App\Prerrequisito;
use App\Sucursal;
use App\Nivel;
use App\Empresa;
use App\Notificacion;
use App\Version;
use App\Fecha;
use App\CursoInstructor;
use App\CursoTrabajador;
use App\Trabajador;
use App\Asistencia;
use App\Nota;
use App\Repechaje;
use App\Mensaje;
use App\User;
use App\Instructor;
use App\ProgramasActivos;
use App\CursoPrograma;
use App\TrabajadorCurso;
use App\Calendario;
use Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use Illuminate\Support\Facades\Hash;
use Session;

class ProgramaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $codigo = \Request::get('codigo');
        $nombre = \Request::get('nombre');
        $estado = \Request::get('estado');

        if (Auth::user()->rol_select == 'jefatura' || Auth::user()->rol_select == 'instructor' || Auth::user()->rol_select == 'alumno'){
            if(\Request::get('browser') == 0){
                $fechadesde = \Request::get('fechadesde');
                $fechahasta = \Request::get('fechahasta');
            }else if(\Request::get('browser') == 1){
                $fechadesde = \Request::get('fechadesdeChrome');
                $fechahasta = \Request::get('fechahastaChrome');
            }
        }

        $modalidad = \Request::get('modalidad');
        
        if (Auth::user()->rol_select == 'instructor' || Auth::user()->rol_select == 'jefatura' || Auth::user()->rol_select == 'alumno') {
            $programas = DB::table('versiones')->select('programas.codigo','programas.nombre', 'programas.modalidad', 'programas.estado', DB::raw('min(versiones.fecha_inicio) as fecha_inicio'), DB::raw('max(versiones.fecha_fin) as fecha_fin'));
        } else {
            $programas = DB::table('versiones')->select('programas.codigo','programas.nombre', 'programas.modalidad', 'programas.estado');
        }
        
        $programas = $programas->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo');
        if (Auth::user()->rol_select == 'instructor') {
            $programas = $programas
                            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id');
        }elseif(Auth::user()->rol_select == 'jefatura') {
            $programas = $programas
                            ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                            ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut');
        }elseif (Auth::user()->rol_select == 'alumno') {
            $programas = $programas
                            ->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id');
        }

        if (!is_null($codigo)) {
            $programas = $programas
                                ->where('codigo','=',$codigo);
        }

        if (!is_null($nombre)){
            $programas = $programas
                                ->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($estado) && $estado <> 2){
            $programas = $programas
                                ->where('estado','=',$estado);
        }

        if (!is_null($modalidad)) {
            if ($modalidad != 'Todos') {
                $programas = $programas
                                ->where('modalidad','=',$modalidad);
            }
        }

        if (Auth::user()->rol_select == 'instructor' || Auth::user()->rol_select == 'jefatura' || Auth::user()->rol_select == 'alumno') {
            if (!is_null($fechadesde)) {
                $programas = $programas
                                ->where('versiones.fecha_inicio', '>=', $fechadesde);
            }
            if(!is_null($fechahasta)){
                $programas = $programas
                                ->where('versiones.fecha_fin', '<=', $fechahasta);
            }
        }

        if (Auth::user()->rol_select == 'instructor' ){
            $programas = $programas->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut);
        }elseif(Auth::user()->rol_select == 'alumno'){
            $programas = $programas->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut);
        }elseif (Auth::user()->rol_select == 'jefatura') {
            $trabajadores =  DB::table('trabajadores')->select('trabajadores.*')->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

            $colaboradores = ArbolColaboradores($trabajadores, true);
            $colaboradores = array_values_recursive($colaboradores);
            
            $programas = $programas->where(function ($q) use ($colaboradores) {
                foreach ($colaboradores as $value) {
                    foreach ($value as $colaborador) {
                        $q = $q->orWhere('curso_trabajador.trabajadores_rut', '=', $colaborador->rut);       
                    }
                }
            });
        }

        if (Auth::user()->rol_select == 'instructor' || Auth::user()->rol_select == 'jefatura' || Auth::user()->rol_select == 'alumno') {
            $programas = $programas
                            ->where('programas.estado', '=', true)
                            ->groupBy('programas.codigo');
        } else {
            $programas = $programas->groupBy('programas.codigo');
        }

        $programas = $programas->orderBy('codigo', 'asc')->get();


        foreach ($programas as $programa) {
            if ($programa->modalidad == "Por Curso") {
                $programa->modalidad = "Por curso";
            }elseif ($programa->modalidad == "Por Calificación") {
                $programa->modalidad = "Por calificación";
            }

            if ( $programa->estado == 1 ){
                $programa->estado = "Activo";
            }else{
                $programa->estado = "Cancelado";
            }
        }

        if (Auth::user()->rol_select == "admin") {   
            return view('programas.index')->with('programas', $programas)->with('estado', $estado);
        }elseif (Auth::user()->rol_select == "alumno") {
            return view('programas.index_alumno')->with('programas', $programas)->with('estado', $estado);
        }else {
            return view('programas.index_jefatura')->with('programas', $programas)->with('estado', $estado);
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $programas = Programa::all();
        $cursos = Curso::where('estado', '=', 'true')
                            ->orderBy('codigo')->get(); 
        $calificaciones = Calificacion::all();
        $empresas = Empresa::all();

        return view('programas.create')->with('programas',$programas)
                                        ->with('cursos',$cursos)
                                        ->with('calificaciones',$calificaciones)
                                        ->with('empresas',$empresas);
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if(empty($request->input('codigo'))){
            return redirect()->route('programas.create')->withInput()->with('error','Debe ingresar un código');
        }
        if(empty($request->input('nombre'))){
            return redirect()->route('programas.create')->withInput()->with('error','Debe ingresar un nombre');
        }
        if(($request->input('modalidad')) == '0'  ){
            return redirect()->route('programas.create')->withInput()->with('error','Debe seleccionar una modalidad');
        }

        $rules = ['codigo' => 'unique:programas,codigo',$request->input('codigo')];
        $this->validate($request,$rules);        

        $programa = new Programa;
        $programa->codigo = $request->input('codigo');
        $programa->nombre = $request->input('nombre');
        $programa->estado  = $request->input('estado');
        $programa->modalidad = $request->input('modalidad');

        $cursos = $request->input('cursos');
        $validador = true;
      
        if (count($cursos) > 0) {
            $fechaExiste = 1;
            $fechasOcupadas = [];

            foreach ($cursos as $curso) {
                $id = strval($curso);

                if(count($request->input('fechas'.$id)) == 0){
                    return redirect()->route('programas.create')->withInput()->with('error','Debe agregar al menos una fecha');
                }

                //fechas ingresadas
                $fechas = $request->input('fechas'.$id);

                foreach ($fechas as $fecha) {

                    if(count($fechasOcupadas)==0){
                        array_push($fechasOcupadas, $fecha);
                    }else{
                        foreach ($fechasOcupadas as $value) {
                            if ($value != $fecha) {
                                $fechaExiste = 0;
                            }else{
                                $fechaExiste = 1;
                                return redirect()->route('programas.create')->withInput()->with('error','Existe más de un calificación programada para la misma fecha');
                           }
                        }
                    }

                    if ($fechaExiste == 0) {
                        array_push($fechasOcupadas, $fecha);
                    }
                }
            }

            foreach ($cursos as $curso) {
                $id = strval($curso);

                if (empty($request->input('horas'.$id))) {
                    $validador = false;
                }
                if (empty($request->input('situacion'.$id))) {
                    $validador = false;
                }
                if (empty($request->input('lugar'.$id))) {
                    $validador = false;
                }
                if (empty($request->input('instructor'.$id))) {
                    $validador = false;
                }
                if ($programa->modalidad == "Por Curso") {
                    if (is_null($request->input('empresa'.$id))) {
                        $validador = false;
                    }
                }
                if(count($request->input('fechas'.$id)) == 0){
                    $validador = false;
                }

                //fechas ingresadas
                $fechas = $request->input('fechas'.$id);
     
                //validar instructor con fecha para no cargar mismo dia
                $rutInst = DB::table('curso_instructor')->select('instructores_rut')->where('id','=',$request->input('instructor'.$id))->groupBy('instructores_rut')->get();

                $idCursoInst = DB::table('curso_instructor')->select('id')->where('instructores_rut','=',$rutInst[0]->instructores_rut)->groupBy('id')->get();

                $versionesInstructor = [];
                foreach($idCursoInst as $ici) { 
                    $versionesInstructor[$ici->id] = DB::table('versiones')->select('id')->where('curso_instructor_id','=',$ici->id)->where('status','<>','C')->groupBy('id')->get();
                }

                $fechasInstructor = [];
                foreach($versionesInstructor as $vi) { 
                    for ($i=0; $i < sizeof($vi); $i++) {
                        $fechasInstructor[$vi[$i]->id] = DB::table('fechas')->select('fecha')->where('versiones_id','=',$vi[$i]->id)->groupBy('fecha')->get();
                    }
                }

                $fechasInstructor2 = [];
                foreach($fechasInstructor as $fi) {
                    for ($i=0; $i < sizeof($fi); $i++) {
                        array_push($fechasInstructor2, $fi[$i]);
                    }
                }

                if( count($fechasInstructor2) <> 0 ) {
                    for ($j=0; $j < sizeof($fechasInstructor2); $j++) { 
                        $fechasNI[$j] = date("d/m/Y", strtotime($fechasInstructor2[$j]->fecha));
                    }

                    //comparacion fechas ingresadas y las que estan activas para dictar algun curso
                    foreach($fechas as $value) {  
                        $flag = false;
                        for ($i=0; $i < sizeof($fechasNI); $i++) {    
                            if ($value == $fechasNI[$i]) {
                                $flag = true;
                            }
                        }
                        if ($flag <> false) { 
                            return redirect()->route('programas.create')->withInput()->with('error','Instructor ya tiene asignado un curso en fechas ingresadas');
                        }
                    } 
                } 

            }

            if ($validador) {
                $programa->save(); 
                foreach ($cursos as $curso) {
                    $timeZone = 'America/Santiago'; 
                    date_default_timezone_set($timeZone); 
                    $now = date_create();

                    $id = strval($curso);

                    $version    = new Version;
                    $version->horas = $request->input('horas'.$id);
                    $version->situacion  = $request->input('situacion'.$id);
                    $version->cod_sence = $request->input('cod_sence'.$id);
                    $version->lugar_ejecucion = $request->input('lugar'.$id);
                    $version->cursos_codigo = $id;
                    $version->programas_codigo = $programa->codigo;
                    $version->curso_instructor_id = $request->input('instructor'.$id);

                    $nombre_curso = Curso::select('nombre')->where('codigo', '=', $id)->get();
                    $trabajadores_rut = CursoInstructor::select('instructores_rut')->where('id', '=', $request->input('instructor'.$id))->get();
                    $instructor      = User::select('id', 'name', 'email')->where('trabajadores_rut', '=', $trabajadores_rut[0]->instructores_rut)->get();

                    if(count($instructor)>0){
                        $notificacion        = new Notificacion;
                        $notificacion->texto = 'Se te ha designado como instructor del curso '.$nombre_curso[0]->nombre.' , del programa '. $programa->nombre.'.';
                        $notificacion->texto_admin = 'Se designó al instructor '.$instructor[0]->name.', al curso '.$nombre_curso[0]->nombre.' , del programa '. $programa->nombre.'.';
                        $notificacion->fecha = date_format($now, 'Y-m-d H:i:s');
                        $notificacion->titulo = 'Se ha asigando un curso nuevo.';
                        $notificacion->titulo_admin = 'Se ha creado un curso nuevo.';
                        $notificacion->url = '/programas/'.$programa->codigo;
                        $notificacion->visto = false;
                        $notificacion->visto_admin = false;
                        $notificacion->admin = true;
                        $notificacion->users_id = $instructor[0]->id;
                        $notificacion->rol = 'Instructor';
                        $notificacion->save();
                    }

                    if ($programa->modalidad == "Por Curso") {
                        $version->calificaciones_codigo = null;
                        //validar al ingresar empresa Abierto = 0
                        if ($request->input('empresa'.$id) == '0') {
                            $version->empresas_id = null;
                        }else {
                            $version->empresas_id = $request->input('empresa'.$id);
                        }
                    }else{
                        $version->calificaciones_codigo = $request->input('calificacion');
                        //validar al ingresar empresa Abierto = 0
                        if ($request->input('empresa') == '0') {
                            $version->empresas_id = null;
                        }else {
                            $version->empresas_id = $request->input('empresa');
                        }
                    }

                    $version->status = 'A';
                    $version->save();       

                    $fechas = $request->input('fechas'.$id);
                    foreach ($fechas as $fecha) {
                        $fechaNueva = new Fecha;
                        $fechaNueva->fecha = $fecha;
                        $fechaNueva->versiones_id = $version->id;
                        $fechaNueva->save();
                    }

                    //obtengo fechas inicio y final de programas para guardar en registro de version 
                    $fechas_prog = Fecha::select(DB::raw('min(fecha) as fecha_min'), DB::raw('max(fecha) as fecha_max'))
                                    ->where('versiones_id', '=', $version->id)->get();   

                    foreach ($fechas_prog as $fp) {
                        $version = Version::find($version->id);
                        $version->fecha_inicio = $fp->fecha_min;
                        $version->fecha_fin = $fp->fecha_max;
                        $version->save();
                    }

                    $data = array(
                        'programa'                  => $programa->nombre,
                        'programa_codigo'           => $programa->codigo,
                        'curso'                     => $nombre_curso[0]->nombre,
                        'instructor'                => $instructor[0]->name,
                        'fecha_inicio'              => $version->fecha_inicio,
                        'fecha_termino'             => $version->fecha_fin,
                    );

                    //envio mail
                    $emailInst = $instructor[0]->email;
                    $variableValor = getenv('VAR_CORREO');
                    if ($variableValor == 'true') {
                        Mail::send('email.instructorPrograma', $data , function($message) use ($emailInst) {
                                $message->to($emailInst, 'Instructor')
                                    ->from('cumminssgc@gmail.com', 'Administrador SGC')
                                    ->subject('Creación programa');
                        });
                    }
                }
            }else{
               return redirect()->route('programas.create')->withInput()->with('error','Debe configurar todos los parámetros del curso');
            }

            //Enviar notificación del curso

            //Guardar log al crear programa
            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Agregar Programa",
                'details' => "Se crea Programa: " . $request->input('nombre'),
            ]);

            return redirect()->route('programas.index')->with('success','Programa creado correctamente'); 
        }else{
            return redirect()->route('programas.create')->withInput()->with('error','Debe seleccionar al menos un curso.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $codigo
     * @return \Illuminate\Http\Response
     */
    public function show($codigo)
    {

        Session::flash('codigoPrograma', $codigo);

        $programa = Programa::find($codigo);
    
        if ( $programa->estado == 1 ){
            $programa->estado = "Activo";
        }else{
            $programa->estado = "Cancelado";
        }

        if(Auth::user()->rol_select == "alumno"){
            $rut = Auth::user()->trabajadores_rut;
            $trabajador = Trabajador::find($rut);

            if ( $trabajador->estado == 1 ){
                $trabajador->estado = "Activo";
            }else{
                $trabajador->estado = "Inactivo";
            }

            if ( $trabajador->genero == 'M' ){
                $trabajador->genero = "Masculino";
            }else{
                $trabajador->genero = "Femenino";
            }

            //Para mostrar los cursos del trabajador
            $cursos             =   CursoTrabajador::where('trabajadores_rut','=', $rut)->get();

            $programas          =   Version::select('programas.*')
                                        ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                        ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                                        ->where('programas_codigo', '=', $codigo)
                                        ->where('programas.estado', '=', 'true')
                                        ->groupBy('programas.codigo')
                                        ->get();

            $cursos             = [];
            $asistencias        = [];
            $evaluaciones       = [];
            $repechaje          = [];
            $notas              = [];
            $notafinal          = [];
            $diasasistidos      = [];
            $promedioaistencias = [];

            foreach ($programas as $key => $value) {
                $cursos[$value->codigo] = Version::select('versiones.situacion','cursos.codigo', 'cursos.nombre', 'cursos.aprobacion_minima', 'curso_trabajador.*', 'instructores.*')
                                            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                                            ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                            ->join('instructores', 'curso_instructor.instructores_rut', '=', 'instructores.rut')
                                            ->where('programas.codigo', '=', $value->codigo)
                                            ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                                            ->groupBy('cursos.codigo', 'versiones.situacion', 'curso_trabajador.id', 'instructores.rut')
                                            ->get();

                if ($value->modalidad == "Por Curso") {
                    $value->modalidad = "Por curso";
                }elseif ($value->modalidad == "Por Calificación") {
                    $value->modalidad = "Por calificación";
                }
            }

            foreach ($programas as $key => $value) {
                $notaprograma = 0;
                foreach ($cursos[$value->codigo] as $key2 => $value2) {
                    $asistencias[$value2->codigo]       = Version::select('asistencias.id', 'asistencias.fecha', 'asistencias.estado')
                    ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->join('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                    ->where('versiones.programas_codigo', '=', $value->codigo)
                    ->where('versiones.cursos_codigo', '=', $value2->codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                    ->get();

                    $diasasistidos[$value2->codigo]     = Version::select('asistencias.id', 'asistencias.fecha', 'asistencias.estado')
                    ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->join('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                    ->where('estado', '=', true)
                    ->where('versiones.programas_codigo', '=', $value->codigo)
                    ->where('versiones.cursos_codigo', '=', $value2->codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                    ->get();

                    $evaluaciones[$value2->codigo]      = Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'notas.resultado')
                    ->join('notas', 'evaluaciones.id', '=', 'notas.evaluaciones_id')
                    ->join('curso_trabajador', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')
                    ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->where('versiones.programas_codigo', '=', $value->codigo)
                    ->where('versiones.cursos_codigo', '=', $value2->codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                    ->orderBy('evaluaciones.id')
                    ->get();

                    $repechaje[$value2->codigo]         = Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'repechaje.resultado')
                    ->join('repechaje', 'evaluaciones.id', '=', 'repechaje.evaluaciones_id')
                    ->join('curso_trabajador', 'curso_trabajador.id', '=', 'repechaje.curso_trabajador_id')
                    ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->where('versiones.programas_codigo', '=', $value->codigo)
                    ->where('versiones.cursos_codigo', '=', $value2->codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                    ->orderBy('evaluaciones.id')
                    ->get();

                    $notafinal[$value2->codigo] = 0;
                    if(count($evaluaciones[$value2->codigo]) > 0){
                        $notafinal[$value2->codigo] = 0;
                        foreach ($evaluaciones[$value2->codigo] as $key3 => $value3) {
                            $notafinal[$value2->codigo] = $notafinal[$value2->codigo] + $value3->resultado * ($value3->porcentaje / 100);
                        }
                        $notaprograma = $notaprograma + $notafinal[$value2->codigo];
                    }

                    if (count($asistencias[$value2->codigo]) > 0) {
                         $promedioaistencias[$value2->codigo] = (count($diasasistidos[$value2->codigo]) * 100) / count($asistencias[$value2->codigo]);             
                    } else {
                        $promedioaistencias[$value2->codigo] = -1;
                    }


                    if ($value2->situacion == "Con Evaluación") {
                        $value2->situacion = "Con evaluación";
                    }elseif ($value2->situacion == "Con Asistencia") {
                        $value2->situacion = "Con asistencia";
                    }

                }
            }

            return view('programas.show')->with('trabajador', $trabajador)
                                                      ->with('cursos', $cursos)
                                                      ->with('programa', $programa)
                                                      ->with('programas', $programas)
                                                      ->with('asistencias', $asistencias)
                                                      ->with('evaluaciones', $evaluaciones)
                                                      ->with('repechaje', $repechaje)
                                                      ->with('notafinal', $notafinal)
                                                      ->with('promedioaistencias', $promedioaistencias);
        }else{
            if (Auth::user()->rol_select == "instructor") {
                $versiones = Version::join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                ->where('programas_codigo','=',$codigo)->get();

                $cs = DB::table('versiones')
                                ->select('calificaciones_codigo')
                                ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                ->where('programas_codigo','=',$codigo)
                                ->groupBy('calificaciones_codigo')->get();
            }else{
                $versiones  = Version::join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                ->where('programas_codigo','=',$codigo)->get();
                $cs         = DB::table('versiones')
                                ->select('calificaciones_codigo')
                                ->where('programas_codigo','=',$codigo)
                                ->groupBy('calificaciones_codigo')->get();
            }

            foreach($cs as $vdvd){
                /*$nivelesPre = Nivel::join('calificaciones', 'niveles.calificaciones_codigo', '=', 'calificaciones.codigo')->join('versiones', 'versiones.calificaciones_codigo', '=', 'calificaciones.codigo')->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();*/

                if (Auth::user()->rol_select == "instructor") {
                    $nivelesPre = Nivel::select("niveles.*", "versiones.*", "cursos.*")
                                        ->join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('versiones', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                        ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                        ->where('programas_codigo','=',$codigo)
                                        ->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();
                }else{
                    $nivelesPre = Nivel::join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('versiones', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->where('programas_codigo','=',$codigo)
                                        ->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();
                }
                $nivelCursos = $nivelesPre->groupBy('nivel_num');
            }

            foreach ($nivelesPre as $nivelPre) {
                if($nivelPre->situacion == "Con Evaluación"){
                    $nivelPre->situacion = "Con evaluación";
                }elseif($nivelPre->situacion == "Con Asistencia"){
                    $nivelPre->situacion = "Con asistencia";
                }
            }

            //instructor del curso
            $instructorCurso = [];
            foreach($versiones as $key => $value){
                if ($value->situacion == "Con Evaluación") {
                    $value->situacion = "Con evaluación";
                }elseif ($value->situacion == "Con Asistencia") {
                    $value->situacion = "Con asistencia";
                }

                $instructorCurso[$value->curso_instructor_id] = CursoInstructor::select('curso_instructor.*', 'instructores.nombres', 'instructores.apellido_paterno', 'instructores.apellido_materno')
                                        ->join('instructores', 'curso_instructor.instructores_rut', '=', 'instructores.rut')
                                        ->where('curso_instructor.id', '=', $value->curso_instructor_id)->get();
            }

            // trabajadores de cursos del programa
            $filtrar = DB::table('versiones')->select('id')->where('programas_codigo','=', $codigo)->groupBy('id')->get();

            if (Auth::user()->rol_select == "jefatura") {
                $alumnos      = DB::table('trabajadores')->select('trabajadores.rut')->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

                $trabajador   = DB::table('curso_trabajador')
                                ->select('curso_trabajador.*', 'trabajadores.*', 'empresas.nombre As nombre_empresa', 'sucursales.nombre As nombre_sucursal')
                                ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                                ->join('empresas', 'trabajadores.empresas_id', '=', 'empresas.id')
                                ->join('sucursales', 'trabajadores.sucursales_codigo', '=', 'sucursales.codigo');

                $trabajador = $trabajador->where(function ($q) use ($filtrar) {
                    foreach($filtrar as $vf_id){
                        $q->orWhere('versiones_id', '=', $vf_id->id);
                    }
                });

                $trabajador = $trabajador->where(function ($q) use ($alumnos) {
                    foreach ($alumnos as $alumno) {
                        $q->orWhere('trabajadores_rut', '=', $alumno->rut);
                    }
                });

                $trabajador       = $trabajador->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

                $colaboradores = ArbolColaboradoresJefatura($trabajador, $filtrar, true);
                $colaboradores = array_values_recursive($colaboradores);
                $colaboradores2 = [];

                foreach ($colaboradores as $value) {
                    foreach ($value as $colaborador) {
                        if (!isset($colaboradores2[$colaborador->id])) {
                            $colaboradores2[$colaborador->id] = $colaborador;
                        }
                    }
                }
            }else{
                $query   = DB::table('curso_trabajador')
                                ->select('curso_trabajador.*', 'trabajadores.*', 'empresas.nombre As nombre_empresa', 'sucursales.nombre As nombre_sucursal')
                                ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                                ->join('empresas', 'trabajadores.empresas_id', '=', 'empresas.id')
                                ->join('sucursales', 'trabajadores.sucursales_codigo', '=', 'sucursales.codigo');
        
                foreach($filtrar as $vf_id){
                    $query->orWhere('versiones_id', '=', $vf_id->id);
                }

                $trabajadores       = $query->get();
            }


            $asistencias        = [];
            $evaluaciones       = [];
            $repechaje          = [];
            $notas              = [];
            $notafinal          = [];
            $diasasistidos      = [];
            $promedioaistencias = [];
            $notaprograma       = 0;
    
            if (Auth::user()->rol_select == "jefatura") {
                foreach ($colaboradores as $value) {
                    foreach ($value as $trabajador) {
                        $asistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Version::select('asistencias.id', 'asistencias.fecha', 'asistencias.estado')
                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                        ->join('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                        ->where('versiones.programas_codigo', '=', $codigo)
                        ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                        ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                        ->get();
            
                        $diasasistidos[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Version::select('asistencias.id', 'asistencias.fecha', 'asistencias.estado')
                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                        ->join('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                        ->where('estado', '=', true)
                        ->where('versiones.programas_codigo', '=', $codigo)
                        ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                        ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                        ->get();
            
                        $evaluaciones[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'notas.resultado')
                        ->join('notas', 'evaluaciones.id', '=', 'notas.evaluaciones_id')
                        ->join('curso_trabajador', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')
                        ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                        ->where('versiones.programas_codigo', '=', $codigo)
                        ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                        ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                        ->orderBy('evaluaciones.id')
                        ->get();

                        $repechaje[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'repechaje.resultado')
                        ->join('repechaje', 'evaluaciones.id', '=', 'repechaje.evaluaciones_id')
                        ->join('curso_trabajador', 'curso_trabajador.id', '=', 'repechaje.curso_trabajador_id')
                        ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                        ->where('versiones.programas_codigo', '=', $codigo)
                        ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                        ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                        ->orderBy('evaluaciones.id')
                        ->get();
            
                        $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = 0;
                        if(count($evaluaciones[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]) > 0){
                            $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = 0;
                            foreach ($evaluaciones[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] as $key3 => $value3) {
                                $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] + $value3->resultado * ($value3->porcentaje / 100);
                            }
                            $notaprograma = $notaprograma + $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut];
                        }
            
                        if (count($asistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]) > 0) {
                            $promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = (count($diasasistidos[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]) * 100) / count($asistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]);
                        } else {
                            $promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = -1;
                        }

                        $versionTrabajador = Version::find($trabajador->versiones_id);
                        $curso_trabajador   = CursoTrabajador::find($trabajador->id);
                        //dd($versionTrabajador, $curso_trabajador, $promedioaistencias);
                        if ($promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] >= 0) {
                            $curso_trabajador->asistencia_final = $promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut];
                        }

                        if($versionTrabajador->situacion == "Con Asistencia"){
                            if ($promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] >= 70) {
                                $curso_trabajador->status = "A";
                            }else{
                                if ($promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] >= 0) {
                                    $curso_trabajador->status = "R";
                                }else{
                                    $curso_trabajador->status = "P";
                                }
                            }
                        }
                        $curso_trabajador->save();
                    }
                }

                $trabajadores = $colaboradores2;
            }else{
                foreach ($trabajadores as $trabajador) {
                    $asistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Version::select('asistencias.id', 'asistencias.fecha', 'asistencias.estado')
                    ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->join('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                    ->where('versiones.programas_codigo', '=', $codigo)
                    ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                    ->get();
        
                    $diasasistidos[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Version::select('asistencias.id', 'asistencias.fecha', 'asistencias.estado')
                    ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->join('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                    ->where('estado', '=', true)
                    ->where('versiones.programas_codigo', '=', $codigo)
                    ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                    ->get();
        
                    $evaluaciones[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'notas.resultado')
                    ->join('notas', 'evaluaciones.id', '=', 'notas.evaluaciones_id')
                    ->join('curso_trabajador', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')
                    ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->where('versiones.programas_codigo', '=', $codigo)
                    ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                    ->orderBy('evaluaciones.id')
                    ->get();

                    $repechaje[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'repechaje.resultado')
                    ->join('repechaje', 'evaluaciones.id', '=', 'repechaje.evaluaciones_id')
                    ->join('curso_trabajador', 'curso_trabajador.id', '=', 'repechaje.curso_trabajador_id')
                    ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->where('versiones.programas_codigo', '=', $codigo)
                    ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                    ->orderBy('evaluaciones.id')
                    ->get();
        
                    $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = 0;
                    if(count($evaluaciones[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]) > 0){
                        $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = 0;
                        foreach ($evaluaciones[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] as $key3 => $value3) {
                            $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] + $value3->resultado * ($value3->porcentaje / 100);
                        }
                        $notaprograma = $notaprograma + $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut];
                    }
        
                    if (count($asistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]) > 0) {
                        $promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = (count($diasasistidos[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]) * 100) / count($asistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]);
                    } else {
                        $promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = -1;
                    }

                    $versionTrabajador = Version::find($trabajador->versiones_id);
                    $curso_trabajador   = CursoTrabajador::find($trabajador->id);
                    //dd($versionTrabajador, $curso_trabajador, $promedioaistencias);
                    if ($promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] >= 0) {
                        $curso_trabajador->asistencia_final = $promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut];
                    }

                    if($versionTrabajador->situacion == "Con Asistencia"){
                        if ($promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] >= 70) {
                            $curso_trabajador->status = "A";
                        }else{
                            if ($promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] >= 0) {
                                $curso_trabajador->status = "R";
                            }else{
                                $curso_trabajador->status = "P";
                            }
                        }
                    }
                    $curso_trabajador->save();
                }
            }


            return view('programas.show')->with('programa',$programa)
                                         ->with('versiones',$versiones)
                                         ->with('trabajadores',$trabajadores)
                                         ->with('nivelCursos',$nivelCursos)
                                         ->with('instructorCurso',$instructorCurso)
                                         ->with('asistencias', $asistencias)
                                         ->with('evaluaciones', $evaluaciones)
                                         ->with('repechaje', $repechaje)
                                         ->with('notafinal', $notafinal)
                                         ->with('promedioaistencias', $promedioaistencias);
        }  
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $codigo
     * @return \Illuminate\Http\Response
     */
    public function edit($codigo)
    {

        $programa = Programa::find($codigo);

        //validar que programa no se este dictanto o este cerrado
        $counter = Version::where('programas_codigo','=',$codigo)
                        ->where('status','<>','A')->count();   

        if ($counter <>'0' || $programa->estado == false) {        
            return redirect()->route('programas.index')->with('error','No se puede editar programa');
        }else {         

            $versiones = Version::where('programas_codigo','=', $codigo)
                        ->select('versiones.*', 'cursos.horas as horas_curso', 'cursos.categoria', 'cursos.nombre')
                        ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')->get();

            // nivel-cursos del programa (por ser tipo calificacion)
            $cs = DB::table('versiones')->select('calificaciones_codigo')->where('programas_codigo','=',$codigo)->groupBy('calificaciones_codigo')->get();
            foreach($cs as $cn){
                $nivelesPre = Nivel::where('calificaciones_codigo','=', $cn->calificaciones_codigo)->get();
                $nivelCursos = $nivelesPre->groupBy('nivel_num');
            }                          

            $infoNivelCursos = DB::table('versiones')
                            ->join('niveles', function($join){
                                $join->on('versiones.calificaciones_codigo', '=', 'niveles.calificaciones_codigo')
                                     ->on('versiones.cursos_codigo', '=', 'niveles.cursos_codigo');
                            })
                            ->select('niveles.*', 'versiones.*')
                            ->where('versiones.programas_codigo','=',$codigo)->get();

            //fechas de cursos del programa
            $fechas = [];
            foreach ($versiones as $key => $value) {
                $fechas[$value->id] = Fecha::where('versiones_id','=',$value->id)->get();
            }

            $empresas = Empresa::all();      

            foreach ($versiones as $v) {
                if ($v->empresas_id == null){
                    $v->empresas_id = "0";
                }else{
                    $v->empresas_id = $v->empresas_id;
                }
            }

            $cursos = Curso::where('estado', '=', 'true')
                            ->orderBy('codigo')->get(); 

            $calificaciones = Calificacion::all(); 

            //sucursales de cursos del programa
            $sucursales = [];
            foreach ($versiones as $key => $value) {
                if ($value->categoria == 'I'){
                     
                     $sucursales[$value->cursos_codigo] = Sucursal::where('sucursales.vigencia','=',true)->get();
                    
                }else{
                    
                    $sucursales[$value->cursos_codigo] = DB::table('cursos')
                                ->join('sucursales', 'cursos.sucursales_codigo', '=', 'sucursales.codigo')
                                ->select('sucursales.codigo', 'sucursales.nombre', 'cursos.codigo as cod_curso')
                                ->where('cursos.codigo','=',$value->cursos_codigo)
                                ->where('sucursales.vigencia','=',true)
                                ->groupBy('sucursales.codigo', 'sucursales.nombre', 'cursos.codigo')->get();
                }
            }

            // instructores de cursos del programa
            $filtrarC = DB::table('versiones')
                            ->select('cursos_codigo')
                            ->where('programas_codigo','=',$codigo)->get();
            $query = DB::table('curso_instructor')
                    ->join('instructores', 'instructores.rut','=','curso_instructor.instructores_rut');
            foreach($filtrarC as $cf_codigo){
                $query->orWhere('cursos_codigo', '=', $cf_codigo->cursos_codigo);
            }

            $instructores = $query->get();


            return view('programas.edit')->with('programa',$programa)
                                         ->with('cursos',$cursos)
                                         ->with('calificaciones',$calificaciones)
                                         ->with('versiones',$versiones)
                                         ->with('infoNivelCursos',$infoNivelCursos)
                                         ->with('nivelCursos',$nivelCursos)
                                         ->with('empresas',$empresas)
                                         ->with('fechas',$fechas)
                                         ->with('sucursales',$sucursales)
                                         ->with('instructores',$instructores);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $codigo
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $codigo)
    {

        $programa = Programa::find($codigo);    
        $nombreAntiguo = $programa->nombre;
        $programa->nombre = $request->input('nombre');
        
        $cursos = $request->input('cursos');

        $fechaExiste = 1;
        $fechasOcupadas = [];

        foreach ($cursos as $curso) {
            $id = strval($curso);

            if(count($request->input('fechas'.$id)) == 0){
                return redirect()->route('programas.edit', $codigo)->withInput()->with('error','Debe agregar al menos una fecha');
            }

            //fechas ingresadas
            $fechas = $request->input('fechas'.$id);

            foreach ($fechas as $fecha) {

                if(count($fechasOcupadas)==0){
                    array_push($fechasOcupadas, $fecha);
                }else{
                    foreach ($fechasOcupadas as $value) {
                        if ($value != $fecha) {
                            $fechaExiste = 0;
                        }else{
                            $fechaExiste = 1;
                            return redirect()->route('programas.edit', $codigo)->withInput()->with('error','Existe más de un calificación programada para la misma fecha');
                        }
                    }
                }

                if ($fechaExiste == 0) {
                    array_push($fechasOcupadas, $fecha);
                }
            }
        }

        $validador = true;
        foreach ($cursos as $curso) {

            $id = strval($curso);

            if (empty($request->input('horas'.$id))) {
                $validador = false;
            }
            if(count($request->input('fechas'.$id)) == 0){
                $validador = false;
            }

            //fechas ingresadas
            $fechas = $request->input('fechas'.$id);
     
            //validar instructor con fecha para no cargar mismo dia
            $rutInst = DB::table('curso_instructor')->select('instructores_rut')->where('id','=',$request->input('instructor'.$id))->groupBy('instructores_rut')->get();

            $idCursoInst = DB::table('curso_instructor')->select('id')->where('instructores_rut','=',$rutInst[0]->instructores_rut)->groupBy('id')->get();

            $versionesInstructor = [];
            foreach($idCursoInst as $ici) { 
                $versionesInstructor[$ici->id] = DB::table('versiones')->select('id')->where('curso_instructor_id','=',$ici->id)->where('status','<>','C')->groupBy('id')->get();
            }

            $fechasInstructor = [];
            foreach($versionesInstructor as $vi) { 
                for ($i=0; $i < sizeof($vi); $i++) {
                    $fechasInstructor[$vi[$i]->id] = DB::table('fechas')->select('fecha')->where('versiones_id','=',$vi[$i]->id)->groupBy('fecha')->get();
                }
            }

            $fechasInstructor2 = [];
            foreach($fechasInstructor as $fi) {
                for ($i=0; $i < sizeof($fi); $i++) {
                    array_push($fechasInstructor2, $fi[$i]);
                }
            }

            if( count($fechasInstructor2) <> 0 ) {
                for ($j=0; $j < sizeof($fechasInstructor2); $j++) { 
                    $fechasNI[$j] = date("d/m/Y", strtotime($fechasInstructor2[$j]->fecha));
                }

                //comparacion fechas ingresadas y las que estan activas para dictar algun curso
                foreach($fechas as $value) {  
                    $flag = false;
                    for ($i=0; $i < sizeof($fechasNI); $i++) {    
                        if ($value == $fechasNI[$i]) {
                            $flag = true;
                        }
                    }
                    if ($flag <> false) { 
                        return redirect()->route('programas.edit', $codigo)->withInput()->with('error','Instructor ya tiene asignado un curso en fechas ingresadas');
                    }
                } 
            } 

        }

        if ($validador) {
            $programa->save(); 

            //eliminar fechas del programa editado
            $versionVigente = Version::where('programas_codigo','=',$codigo)->get();
            foreach($versionVigente as $value) {
                $fechasEliminar = Fecha::where('versiones_id','=',$value->id)->get();
                foreach($fechasEliminar as $valueF) {
                    $valueF->delete();
                }      
            } 

            foreach ($cursos as $curso) {
                $id = strval($curso);

                $versiones = Version::where('programas_codigo','=',$codigo)
                                    ->where('cursos_codigo','=',$curso)->get();
     
                foreach ($versiones as $version) {
                    $version->horas = $request->input('horas'.$id);
                    $version->situacion  = $request->input('situacion'.$id);
                    $version->cod_sence = $request->input('cod_sence'.$id);
                    $version->lugar_ejecucion = $request->input('lugar'.$id);
                    $version->curso_instructor_id = $request->input('instructor'.$id);
       
                    if ($programa->modalidad == "Por Curso") {
                        //validar al ingresar empresa Abierto = 0
                        if ($request->input('empresa'.$id) == '0') {
                            $version->empresas_id = null;
                        }else {
                            $version->empresas_id = $request->input('empresa'.$id);
                        }
                    }else{
                        //validar al ingresar empresa Abierto = 0
                        if ($request->input('empresa') == '0') {
                            $version->empresas_id = null;
                        }else {
                            $version->empresas_id = $request->input('empresa');
                        }
                    }

                    $version->save();   

                    $fechas = $request->input('fechas'.$id);
                    foreach ($fechas as $fecha) {
                        $fechaNueva = new Fecha;
                        $fechaNueva->fecha = $fecha;
                        $fechaNueva->versiones_id = $version->id;
                        $fechaNueva->save();
                    }

                    //obtengo fechas inicio y final de programas para guardar en registro de version 
                    $fechas_prog = Fecha::select(DB::raw('min(fecha) as fecha_min'), DB::raw('max(fecha) as fecha_max'))
                                    ->where('versiones_id', '=', $version->id)->get();   

                    foreach ($fechas_prog as $fp) {
                        $version = Version::find($version->id);
                        $version->fecha_inicio = $fp->fecha_min;
                        $version->fecha_fin = $fp->fecha_max;
                        $version->save();
                    }
                }
            }
        }else{
            return redirect()->route('programas.edit', $codigo)->withInput()->with('error','Debe configurar todos los parámetros del curso');
        }

        Log::create([
            'id_user' => Auth::user()->id,
            'action'  => "Editar Programa",
            'details' => "Se edita Programa: " . $nombreAntiguo . " por: " . $request->input('nombre'),
        ]);

        return redirect()->route('programas.index')->with('success','Programa editado correctamente');        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $codigo
     * @return \Illuminate\Http\Response
     */
    public function destroy($codigo)
    {
        $programa = Programa::find($codigo);
        $programa->delete();

        //Guardar log al crear usuario
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Eliminar Programa",
            'details' => "Se elimina Programa: " . $codigo,
        ]);

        return redirect()->route('programas.index');
    }  

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $codigo         = $request->input('codigoExcel');
        $nombre         = $request->input('nombreExcel');
        $modalidad      = $request->input('modalidadExcel'); 
        $estado         = $request->input('estadoExcel');   

        if (Auth::user()->rol_select == 'jefatura' || Auth::user()->rol_select == 'instructor' || Auth::user()->rol_select == 'alumno') {
            if($request->input('browserExcel') == 0){
                $fechadesde = $request->input('fechadesdeExcel');
                $fechahasta = $request->input('fechahastaExcel');
            }else if($request->input('browserExcel') == 1){
                $fechadesde = $request->input('fechadesdeChromeExcel');
                $fechahasta = $request->input('fechahastaChromeExcel');
            }
        }

        if (Auth::user()->rol_select == 'instructor' || Auth::user()->rol_select == 'jefatura' || Auth::user()->rol_select == 'alumno') {
            $programas = DB::table('versiones')->select('programas.codigo','programas.nombre', 'programas.modalidad', 'programas.estado', DB::raw('min(versiones.fecha_inicio) as fecha_inicio'), DB::raw('max(versiones.fecha_fin) as fecha_fin'));
        } else {
            $programas = DB::table('versiones')->select('programas.codigo','programas.nombre', 'programas.modalidad', 'programas.estado');
        }
        
        $programas = $programas->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo');
        if (Auth::user()->rol_select == 'instructor') {
            $programas = $programas
                            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id');
        }elseif(Auth::user()->rol_select == 'jefatura') {
            $programas = $programas
                            ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                            ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut');
        }elseif (Auth::user()->rol_select == 'alumno') {
            $programas = $programas
                            ->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id');
        }

        if (!is_null($codigo)) {
            $programas = $programas->where('codigo','=',$codigo);
        }

        if (!is_null($nombre)){
            $programas = $programas->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($estado) && $estado <> 2){
            $programas = $programas->where('estado','=',$estado);
        }

        if (!is_null($modalidad)) {
            if ($modalidad != 'Todos') {
                $programas = $programas->where('modalidad','=',$modalidad);
            }
        }

        if (Auth::user()->rol_select == 'instructor' || Auth::user()->rol_select == 'jefatura' || Auth::user()->rol_select == 'alumno') {
            if (!is_null($fechadesde)) {
                $programas = $programas->where('versiones.fecha_inicio', '>=', $fechadesde);
            }
            if(!is_null($fechahasta)){
                $programas = $programas->where('versiones.fecha_fin', '<=', $fechahasta);
            }
        }

        if (Auth::user()->rol_select == 'instructor' ){
            $programas = $programas->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut);
        }elseif(Auth::user()->rol_select == 'alumno'){
            $programas = $programas->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut);
        }elseif (Auth::user()->rol_select == 'jefatura') {
            $trabajadores =  DB::table('trabajadores')->select('trabajadores.*')->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

            $colaboradores = ArbolColaboradores($trabajadores, true);
            $colaboradores = array_values_recursive($colaboradores);
            
            $programas = $programas->where(function ($q) use ($colaboradores) {
                foreach ($colaboradores as $value) {
                    foreach ($value as $colaborador) {
                        $q = $q->orWhere('curso_trabajador.trabajadores_rut', '=', $colaborador->rut);       
                    }
                }
            });
        }

        if (Auth::user()->rol_select == 'instructor' || Auth::user()->rol_select == 'jefatura' || Auth::user()->rol_select == 'alumno') {
            $programas = $programas
                                    ->where('programas.estado', '=', true)
                                    ->groupBy('programas.codigo')
                                    ->orderBy('fecha_inicio', 'asc')
                                    ->orderBy('fecha_fin', 'asc');
        } else {
            $programas = $programas->groupBy('programas.codigo')->orderBy('codigo', 'asc');
        }

        $programas = $programas->get();


        if (Auth::user()->rol_select == 'admin' ){
            return Excel::create('ListadoProgramas', function($excel) use ($programas) {
                $excel->sheet('Programa', function($sheet) use ($programas)
                {
                    $count = 2;
                    
                    $sheet->row(1, ['Código', 'Nombre', 'Modalidad', 'Estado']);
                    foreach ($programas as $key => $value) {
                        if ( $value->estado == 1 ){
                            $value->estado = "Activo";
                        }else{
                            $value->estado = "Cancelado";
                        }
                        $sheet->row($count, [$value->codigo, $value->nombre, $value->modalidad, $value->estado]);
                        $count = $count +1;
                    }
                });
            })->download('xlsx');
        }else{
            return Excel::create('ListadoProgramas', function($excel) use ($programas) {
                $excel->sheet('Programa', function($sheet) use ($programas)
                {
                    $count = 2;

                    $sheet->setColumnFormat(array(
                        'D' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                        'E' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                    ));
                    
                    $sheet->row(1, ['Código', 'Nombre', 'Modalidad', 'Fecha inicio', 'Fecha fin', 'Estado']);
                    foreach ($programas as $key => $value) {
                        if ( $value->estado == 1 ){
                            $value->estado = "Activo";
                        }else{
                            $value->estado = "Cancelado";
                        }

                        $fechaInicio = strtotime($value->fecha_inicio);
                        $fechaInicio = \PHPExcel_Shared_Date::PHPToExcel($fechaInicio);
                        $fechaFin    = strtotime($value->fecha_fin);
                        $fechaFin    = \PHPExcel_Shared_Date::PHPToExcel($fechaFin);

                        $sheet->appendRow(array($value->codigo, $value->nombre, $value->modalidad, $fechaInicio, $fechaFin, $value->estado));

                        $count = $count +1;
                    }
                });
            })->download('xlsx');
        }

    }

    // metodo exportr a excel datos mantenedor
    public function exportExcelHist(Request $request)
    {
        $codigo         = $request->input('codigoExcel'); 

        $programa = Programa::find($codigo);

        $historialPrograma = Version::select('versiones.programas_codigo', 'instructores.rut', 'instructores.nombres','instructores.apellido_paterno','instructores.apellido_materno', DB::raw('count(distinct versiones.cursos_codigo) as cantidad_cursos'), DB::raw("(select count(distinct a.cursos_codigo)
                                            from versiones a
                                            where a.programas_codigo = ".$codigo.") as total_curso"),
                    DB::raw("(select min(a.fecha_inicio)
                                from versiones a
                                where a.programas_codigo = ".$codigo.") as fecha_inicio"),
                    DB::raw("(select max(a.fecha_fin)
                                from versiones a
                                where a.programas_codigo = ".$codigo.") as fecha_fin"))
                ->join('curso_instructor', 'curso_instructor.id', '=', 'versiones.curso_instructor_id')
                ->join('instructores', 'instructores.rut', '=', 'curso_instructor.instructores_rut')
                ->where('versiones.programas_codigo', '=', $codigo)
                ->groupBy('versiones.programas_codigo', 'instructores.rut', 'instructores.nombres','instructores.apellido_paterno','instructores.apellido_materno')
                ->orderBy('fecha_inicio', 'desc')
                ->get();

        return Excel::create('HistorialProgramas', function($excel) use ($historialPrograma) {
            $excel->sheet('Programas', function($sheet) use ($historialPrograma)
            {
                $count = 2;

                $sheet->setColumnFormat(array(
                    'A' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                    'B' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                ));
                
                $sheet->row(1, ['Fecha inicio', 'Fecha fin', 'Instructor', 'Cursos asociados']);
                foreach ($historialPrograma as $key => $value) {

                    $fechaInicio = strtotime($value->fecha_inicio);
                    $fechaInicio = \PHPExcel_Shared_Date::PHPToExcel($fechaInicio);
                    $fechaFin    = strtotime($value->fecha_fin);
                    $fechaFin    = \PHPExcel_Shared_Date::PHPToExcel($fechaFin);

                    $sheet->appendRow(array($fechaInicio, $fechaFin, $value->nombres.' '.$value->apellido_paterno.' '.$value->apellido_materno, $value->cantidad_cursos.' de '.$value->total_curso));
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

    //metodo para cambiar estado programa, segun corresponda
    public function cambioEstado($codigo)
    {
        $programa = Programa::find($codigo);

        //validar que programa no se este dictanto
        $counter = Version::where('programas_codigo','=',$codigo)
                        ->where('status','=','D')->count();   

        if ($counter <>'0') {                          
            return redirect()->route('programas.index')->with('error','No se puede cambiar estado del programa');   
        }else {        

            if ( $programa->estado  == 1){
                $programa->estado  = 0;
            }else{
                $programa->estado  = 1;
            }

            $programa->save();

            //envio mail cuando paso a estado es cancelado
            $variableValor = getenv('VAR_CORREO');
            if ($variableValor == 'true') {

                if ($programa->estado == 0) {
                    
                    $versiones = Version::select('id', 'cursos_codigo', 'curso_instructor_id', 'fecha_inicio', 'fecha_fin', 'cursos.nombre')
                                    ->join('cursos', 'cursos.codigo', '=', 'versiones.cursos_codigo')
                                    ->where('programas_codigo', '=', $codigo)->get();

                    //instructores
                    foreach ($versiones as $version) { 
                        $instructor = CursoInstructor::select('instructores.rut', 'users.email')
                                    ->join('instructores', 'instructores.rut', '=', 'curso_instructor.instructores_rut')
                                    ->join('users', 'users.trabajadores_rut', '=', 'instructores.rut')
                                    ->where('curso_instructor.id', '=', $version->curso_instructor_id)->get();
          
                        $data = array(
                                'programa_codigo'           => $programa->codigo,
                                'programa_nombre'           => $programa->nombre,
                                'curso'                     => $version->nombre,
                                'fecha_inicio'              => $version->fecha_inicio,
                                'fecha_termino'             => $version->fecha_fin,
                        );

                        $emailInst = $instructor[0]->email;                        
                        Mail::send('email.cancelacionInstPrograma', $data , function($message) use ($emailInst) {
                            $message->to($emailInst, 'Instructor')
                                    ->from('cumminssgc@gmail.com', 'Administrador SGC')
                                    ->subject('Cancelación Programa');
                        });
                        
                    }

                    //trabajadores
                    foreach ($versiones as $version) { 

                        $trabajadores = CursoTrabajador::select('curso_trabajador.trabajadores_rut', 'users.email')
                                    ->join('users', 'users.trabajadores_rut', '=', 'curso_trabajador.trabajadores_rut')
                                    ->where('curso_trabajador.versiones_id', '=', $version->id)->get();

                        $data = array(
                                'programa_codigo'           => $programa->codigo,
                                'programa_nombre'           => $programa->nombre,
                                'curso'                     => $version->nombre,
                                'fecha_inicio'              => $version->fecha_inicio,
                                'fecha_termino'             => $version->fecha_fin,
                        );

                        foreach ($trabajadores as $trabajador) { 
                            $emailTrabajador = $trabajador->email;
                            Mail::send('email.cancelacionTrabPrograma', $data , function($message) use ($emailTrabajador) {
                                $message->to($emailTrabajador, 'Trabajador')
                                    ->from('cumminssgc@gmail.com', 'Administrador SGC')
                                        ->subject('Cancelación Programa');
                            });
                        }
                    }
        
                }
            }

            return redirect()->route('programas.index')->with('success','Cambio estado realizado correctamente');
        }
    }


    public function asistencias($codigo)
    {
        $programa = Programa::find($codigo);

        if ( $programa->estado == 1 ){
            $programa->estado = "Activo";
        }else{
            $programa->estado = "Cancelado";
        }

        if (Auth::user()->rol_select == 'alumno') {
            $versiones = Version::select('versiones.*')->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                    ->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)
                                    ->where('programas_codigo','=',$codigo)
                                    ->get(); 
            // nivel-cursos del programa (por ser tipo calificacion)
            $cs = DB::table('versiones')
                        ->select('calificaciones_codigo')
                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                        ->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)
                        ->where('programas_codigo','=',$codigo)
                        ->groupBy('calificaciones_codigo')
                        ->get();

            foreach($cs as $vdvd){
                $nivelesPre = Nivel::join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                                    ->join('versiones', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                    ->where('programas_codigo','=',$codigo)
                                    ->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)
                                    ->get();

                $nivelCursos = $nivelesPre->groupBy('nivel_num');
            }        

            // trabajadores de cursos del programa
            $filtrar = DB::table('versiones')
                        ->select('versiones.id')
                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                        ->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)
                        ->where('programas_codigo','=',$codigo)
                        ->groupBy('versiones.id')
                        ->get();

            $query = DB::table('curso_trabajador')
                        ->select('curso_trabajador.cursos_codigo', 'trabajadores.*')
                        ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                        ->groupBy('cursos_codigo', 'trabajadores.rut');

            $query = $query->where(function ($q) use ($filtrar) {
                foreach($filtrar as $vf_id){
                    $q = $q->orWhere('versiones_id', '=', $vf_id->id);       
                }
            });
            $query = $query->where('trabajadores.rut', '=', Auth::user()->trabajadores_rut);

            $trabajadores = $query->get();
        }else{
            if (Auth::user()->rol_select == "instructor") {
                $versiones  =   Version::select("versiones.*")
                                    ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                    ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                    ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                    ->where('programas_codigo','=',$codigo)
                                    ->get();
                $cs         =   DB::table('versiones')
                                    ->select('calificaciones_codigo')
                                    ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                    ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                    ->where('programas_codigo','=',$codigo)
                                    ->groupBy('calificaciones_codigo')->get();
            }else{
                $versiones  =   Version::join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                    ->where('programas_codigo','=',$codigo)->get();
                $cs         =   DB::table('versiones')
                                    ->select('calificaciones_codigo')
                                    ->where('programas_codigo','=',$codigo)
                                    ->groupBy('calificaciones_codigo')->get();
            }
            
            foreach($cs as $vdvd){
                /*$nivelesPre = Nivel::join('calificaciones', 'niveles.calificaciones_codigo', '=', 'calificaciones.codigo')->join('versiones', 'versiones.calificaciones_codigo', '=', 'calificaciones.codigo')->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();*/

                if (Auth::user()->rol_select == "instructor") {
                    $nivelesPre = Nivel::select("niveles.*", "versiones.*", "cursos.*")
                                        ->join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('versiones', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                        ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                        ->where('programas_codigo','=',$codigo)
                                        ->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();
                }else{
                    $nivelesPre = Nivel::join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('versiones', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->where('programas_codigo','=',$codigo)
                                        ->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();
                }
                $nivelCursos = $nivelesPre->groupBy('nivel_num');
            }

            // trabajadores de cursos del programa
            $filtrar = DB::table('versiones')
                            ->select('id')
                            ->where('programas_codigo','=',$codigo)
                            ->groupBy('id')
                            ->get();

            if (Auth::user()->rol_select == "jefatura") {
                $alumnos      = DB::table('trabajadores')->select('trabajadores.rut')->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

                $trabajador   = DB::table('curso_trabajador')
                                ->select('curso_trabajador.*', 'trabajadores.*', 'empresas.nombre As nombre_empresa', 'sucursales.nombre As nombre_sucursal')
                                ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                                ->join('empresas', 'trabajadores.empresas_id', '=', 'empresas.id')
                                ->join('sucursales', 'trabajadores.sucursales_codigo', '=', 'sucursales.codigo');

                $trabajador = $trabajador->where(function ($q) use ($filtrar) {
                    foreach($filtrar as $vf_id){
                        $q->orWhere('versiones_id', '=', $vf_id->id);
                    }
                });

                $trabajador = $trabajador->where(function ($q) use ($alumnos) {
                    foreach ($alumnos as $alumno) {
                        $q->orWhere('trabajadores_rut', '=', $alumno->rut);
                    }
                });

                $trabajador       = $trabajador->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

                $colaboradores = ArbolColaboradoresJefatura($trabajador, $filtrar, true);
                $colaboradores = array_values_recursive($colaboradores);
                $colaboradores2 = [];

                foreach ($colaboradores as $value) {
                    foreach ($value as $colaborador) {
                        if (!isset($colaboradores2[$colaborador->id])) {
                            $colaboradores2[$colaborador->id] = $colaborador;
                        }
                    }
                }

                $trabajadores = $colaboradores2;
            }else{
                $query   = DB::table('curso_trabajador')
                                ->select('curso_trabajador.*', 'trabajadores.*', 'empresas.nombre As nombre_empresa', 'sucursales.nombre As nombre_sucursal')
                                ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                                ->join('empresas', 'trabajadores.empresas_id', '=', 'empresas.id')
                                ->join('sucursales', 'trabajadores.sucursales_codigo', '=', 'sucursales.codigo');
        
                $query = $query->where(function ($q) use ($filtrar) {
                    foreach($filtrar as $vf_id){
                        $q = $q->orWhere('versiones_id', '=', $vf_id->id);       
                    }
                });

                $trabajadores       = $query->get();
            }
        }

        $fechas = [];

        foreach ($versiones as $version) {
            $fechas[$version->id] = Fecha::select('fecha')->where('versiones_id', '=', $version->id)->get();
        }

        if (Auth::user()->rol_select == 'jefatura' || Auth::user()->rol_select == 'instructor') {
            //asistencia por trabajador
            $asistencias =[];
            foreach ($versiones as $version) {
                foreach ($fechas[$version->id] as $fecha) {
                    $asistencias[$version->id][$fecha->fecha] = Asistencia::select('asistencias.*')->join('curso_trabajador', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')->where('fecha', '=', $fecha->fecha)->where('versiones.id', '=', $version->id)->get();
                }
            }
        }elseif (Auth::user()->rol_select == 'alumno') {
            $asistencias =[];
            foreach ($versiones as $version) {
                foreach ($fechas[$version->id] as $fecha) {
                    $asistencias[$version->id][$fecha->fecha] = Asistencia::select('asistencias.*')->join('curso_trabajador', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)->where('fecha', '=', $fecha->fecha)->where('versiones.id', '=', $version->id)->get();
                }
            }
        }else{
            $asistencias =[];
            foreach ($versiones as $version) {
                foreach ($fechas[$version->id] as $fecha) {
                    $asistencias[$version->id][$fecha->fecha] = Asistencia::select('asistencias.*')->join('curso_trabajador', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')->where('fecha', '=', $fecha->fecha)->where('versiones.id', '=', $version->id)->get();
                }
            }
        }

        return view('programas.asistencia')->with('programa',$programa)
                                           ->with('versiones',$versiones)
                                           ->with('trabajadores',$trabajadores)
                                           ->with('nivelCursos',$nivelCursos)
                                           ->with('fechas', $fechas)
                                           ->with('asistencias',$asistencias);
    }


    public function evaluaciones($codigo)
    {

        $programa = Programa::find($codigo);

        if ( $programa->estado == 1 ){
            $programa->estado = "Activo";
        }else{
            $programa->estado = "Cancelado";
        }


        if (Auth::user()->rol_select == 'alumno') {
            $versiones = Version::select('versiones.*')->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                    ->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)
                                    ->where('programas_codigo','=',$codigo)
                                    ->get(); 
            // nivel-cursos del programa (por ser tipo calificacion)
            $cs = DB::table('versiones')
                        ->select('calificaciones_codigo')
                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                        ->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)
                        ->where('programas_codigo','=',$codigo)
                        ->groupBy('calificaciones_codigo')
                        ->get();

            foreach($cs as $vdvd){
                $nivelesPre = Nivel::join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                                    ->join('versiones', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                    ->where('programas_codigo','=',$codigo)
                                    ->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)
                                    ->get();

                $nivelCursos = $nivelesPre->groupBy('nivel_num');
            }        

            // trabajadores de cursos del programa
            $filtrar = DB::table('versiones')
                        ->select('versiones.id')
                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                        ->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)
                        ->where('programas_codigo','=',$codigo)
                        ->groupBy('versiones.id')
                        ->get();

            $query = DB::table('curso_trabajador')
                        ->select('curso_trabajador.cursos_codigo', 'trabajadores.*')
                        ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                        ->groupBy('cursos_codigo', 'trabajadores.rut');

            $query = $query->where(function ($q) use ($filtrar) {
                foreach($filtrar as $vf_id){
                    $q = $q->orWhere('versiones_id', '=', $vf_id->id);       
                }
            });
            $query = $query->where('trabajadores.rut', '=', Auth::user()->trabajadores_rut);
            
            $trabajadores = $query->get();
        }else{
            if (Auth::user()->rol_select == "instructor") {
                $versiones = Version::select("versiones.*")
                                        ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                        ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                        ->where('programas_codigo','=',$codigo)->get();

                $cs = DB::table('versiones')
                                ->select('calificaciones_codigo')
                                ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                ->where('programas_codigo','=',$codigo)
                                ->groupBy('calificaciones_codigo')->get();
            }else{
                $versiones = Version::join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                ->where('programas_codigo','=',$codigo)->get();
                $cs = DB::table('versiones')
                                ->select('calificaciones_codigo')
                                ->where('programas_codigo','=',$codigo)
                                ->groupBy('calificaciones_codigo')->get();
            }

            foreach($cs as $vdvd){
                if (Auth::user()->rol_select == "instructor") {
                    $nivelesPre = Nivel::select("niveles.*", "versiones.*", "cursos.*")
                                        ->join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('versiones', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                        ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                        ->where('programas_codigo','=',$codigo)
                                        ->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();
                }else{
                    $nivelesPre = Nivel::join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('versiones', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->where('programas_codigo','=',$codigo)
                                        ->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();
                }
                $nivelCursos = $nivelesPre->groupBy('nivel_num');
            }     

            // trabajadores de cursos del programa
            $filtrar = DB::table('versiones')->select('id')->where('programas_codigo','=',$codigo)->groupBy('id')->get();
            if (Auth::user()->rol_select == "jefatura") {
                $alumnos      = DB::table('trabajadores')->select('trabajadores.rut')->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

                $trabajador   = DB::table('curso_trabajador')
                                ->select('curso_trabajador.*', 'trabajadores.*', 'empresas.nombre As nombre_empresa', 'sucursales.nombre As nombre_sucursal')
                                ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                                ->join('empresas', 'trabajadores.empresas_id', '=', 'empresas.id')
                                ->join('sucursales', 'trabajadores.sucursales_codigo', '=', 'sucursales.codigo');

                $trabajador = $trabajador->where(function ($q) use ($filtrar) {
                    foreach($filtrar as $vf_id){
                        $q->orWhere('versiones_id', '=', $vf_id->id);
                    }
                });

                $trabajador = $trabajador->where(function ($q) use ($alumnos) {
                    foreach ($alumnos as $alumno) {
                        $q->orWhere('trabajadores_rut', '=', $alumno->rut);
                    }
                });

                $trabajador       = $trabajador->where('rut_jefatura','=', Auth::user()->trabajadores_rut)->get();

                $colaboradores = ArbolColaboradoresJefatura($trabajador, $filtrar, true);
                $colaboradores = array_values_recursive($colaboradores);
                $colaboradores2 = [];

                foreach ($colaboradores as $value) {
                    foreach ($value as $colaborador) {
                        if (!isset($colaboradores2[$colaborador->id])) {
                            $colaboradores2[$colaborador->id] = $colaborador;
                        }
                    }
                }

                $trabajadores = $colaboradores2;
            }else{
                $query   = DB::table('curso_trabajador')
                                ->select('curso_trabajador.*', 'trabajadores.*', 'empresas.nombre As nombre_empresa', 'sucursales.nombre As nombre_sucursal')
                                ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                                ->join('empresas', 'trabajadores.empresas_id', '=', 'empresas.id')
                                ->join('sucursales', 'trabajadores.sucursales_codigo', '=', 'sucursales.codigo');
        
                $query = $query->where(function ($q) use ($filtrar) {
                    foreach($filtrar as $vf_id){
                        $q = $q->orWhere('versiones_id', '=', $vf_id->id);       
                    }
                });

                $trabajadores       = $query->get();
            }
        }

        //evaluaciones por trabajador (falta incluir lo de repechaje)
        $evaluaciones       = [];
        $evaluacionesCurso  = [];
        if (Auth::user()->rol_select == 'jefatura' || Auth::user()->rol_select == 'instructor') {
            foreach ($versiones as $version) {
                $evaluacionesCurso[$version->id] = DB::table('evaluaciones')->select('*')->where('cursos_codigo','=', $version->cursos_codigo)->get();
                foreach ($evaluacionesCurso[$version->id] as $evaluacionCurso) {
                    $evaluaciones[$version->id][$evaluacionCurso->id] = Nota::select('notas.*')->join('curso_trabajador', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')->where('evaluaciones_id', '=', $evaluacionCurso->id)->where('programas_codigo', '=', $codigo)->get();
                }
            }
        }elseif(Auth::user()->rol_select == 'alumno') {
            foreach ($versiones as $version) {
                $evaluacionesCurso[$version->id] = DB::table('evaluaciones')->select('*')->where('cursos_codigo','=', $version->cursos_codigo)->get();
                foreach ($evaluacionesCurso[$version->id] as $evaluacionCurso) {
                    $evaluaciones[$version->id][$evaluacionCurso->id] = Nota::select('notas.*')->join('curso_trabajador', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')->where('curso_trabajador.trabajadores_rut', '=', Auth::user()->trabajadores_rut)->where('evaluaciones_id', '=', $evaluacionCurso->id)->where('programas_codigo', '=', $codigo)->get();
                }
            }
        }else{
            $evaluaciones = DB::table('versiones')
                        ->select('versiones.cursos_codigo', 'versiones.programas_codigo', 'versiones.calificaciones_codigo', 'versiones.empresas_id', 'curso_trabajador.trabajadores_rut', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'notas.resultado')
                        ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                        ->join('notas', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')
                        ->join('evaluaciones', 'notas.evaluaciones_id', '=', 'evaluaciones.id')
                        ->where('versiones.programas_codigo','=',$codigo)
                        ->groupBy('versiones.cursos_codigo', 'versiones.programas_codigo', 'versiones.calificaciones_codigo', 'versiones.empresas_id', 'curso_trabajador.trabajadores_rut', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'notas.resultado')->get();        
        }

        return view('programas.evaluacion')->with('programa',$programa)
                                          ->with('versiones',$versiones)
                                          ->with('trabajadores',$trabajadores)
                                          ->with('nivelCursos',$nivelCursos)
                                          ->with('evaluacionesCurso', $evaluacionesCurso)
                                          ->with('evaluaciones',$evaluaciones);
    }

    public function repechaje($codigo)
    {
        $programa = Programa::find($codigo);
    
        if ( $programa->estado == 1 ){
            $programa->estado = "Activo";
        }else{
            $programa->estado = "Cancelado";
        }

            if (Auth::user()->rol_select == "instructor") {
                $versiones = Version::join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                ->where('programas_codigo','=',$codigo)->get();

                $cs = DB::table('versiones')
                                ->select('calificaciones_codigo')
                                ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                ->where('programas_codigo','=',$codigo)
                                ->groupBy('calificaciones_codigo')->get();
            }else{
                $versiones = Version::join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                ->where('programas_codigo','=',$codigo)->get();
                $cs = DB::table('versiones')
                                ->select('calificaciones_codigo')
                                ->where('programas_codigo','=',$codigo)
                                ->groupBy('calificaciones_codigo')->get();
            }

            foreach($cs as $vdvd){
                /*$nivelesPre = Nivel::join('calificaciones', 'niveles.calificaciones_codigo', '=', 'calificaciones.codigo')->join('versiones', 'versiones.calificaciones_codigo', '=', 'calificaciones.codigo')->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();*/

                if (Auth::user()->rol_select == "instructor") {
                    $nivelesPre = Nivel::select("niveles.*", "versiones.*", "cursos.*")
                                        ->join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('versiones', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                        ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                        ->where('programas_codigo','=',$codigo)
                                        ->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();
                }else{
                    $nivelesPre = Nivel::join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                                        ->join('versiones', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                        ->where('programas_codigo','=',$codigo)
                                        ->where('niveles.calificaciones_codigo','=', $vdvd->calificaciones_codigo)->get();
                }
                $nivelCursos = $nivelesPre->groupBy('nivel_num');
            }
    
            // trabajadores de cursos del programa
            $filtrar = DB::table('versiones')->select('id')->where('programas_codigo','=',$codigo)->groupBy('id')->get();
            $query   = DB::table('curso_trabajador')
                            ->select('curso_trabajador.*', 'trabajadores.*', 'empresas.nombre As nombre_empresa', 'sucursales.nombre As nombre_sucursal')
                            ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                            ->join('empresas', 'trabajadores.empresas_id', '=', 'empresas.id')
                            ->join('sucursales', 'trabajadores.sucursales_codigo', '=', 'sucursales.codigo')
                            ->groupBy();
    
            foreach($filtrar as $vf_id){
                $query->orWhere('versiones_id', '=', $vf_id->id);
            }
    
    
            $trabajadores       = $query->get();  
            $asistencias        = [];
            $evaluaciones       = [];
            $notas              = [];
            $notafinal          = [];
            $diasasistidos      = [];
            $promedioaistencias = [];
            $notaprograma       = 0;
    
    
            foreach ($trabajadores as $trabajador) {
                $asistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Version::select('asistencias.id', 'asistencias.fecha', 'asistencias.estado')
                ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                ->join('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                ->where('versiones.programas_codigo', '=', $codigo)
                ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                ->get();
    
                $diasasistidos[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Version::select('asistencias.id', 'asistencias.fecha', 'asistencias.estado')
                ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                ->join('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                ->where('estado', '=', true)
                ->where('versiones.programas_codigo', '=', $codigo)
                ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                ->get();
    
    
                $evaluaciones[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje',  'evaluaciones.cursos_codigo','notas.resultado', 'notas.curso_trabajador_id')
                ->join('notas', 'evaluaciones.id', '=', 'notas.evaluaciones_id')
                ->join('curso_trabajador', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')
                ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                ->where('versiones.programas_codigo', '=', $codigo)
                ->where('versiones.cursos_codigo', '=', $trabajador->cursos_codigo)
                ->where('curso_trabajador.trabajadores_rut', '=', $trabajador->trabajadores_rut)
                ->get();
    
                $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = 0;
                if(count($evaluaciones[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]) > 0){
                    $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = 0;
                    foreach ($evaluaciones[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] as $key3 => $value3) {
                        $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] + $value3->resultado * ($value3->porcentaje / 100);
                    }
                    $notaprograma = $notaprograma + $notafinal[$trabajador->cursos_codigo][$trabajador->trabajadores_rut];
                }
    
                if (count($asistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]) > 0) {
                    $promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = (count($diasasistidos[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]) * 100) / count($asistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut]);
                } else {
                    $promedioaistencias[$trabajador->cursos_codigo][$trabajador->trabajadores_rut] = 0;
                }
            }
  
            return view("programas.repechaje")->with('programa',$programa)
                                         ->with('versiones',$versiones)
                                         ->with('trabajadores',$trabajadores)
                                         ->with('nivelCursos',$nivelCursos)
                                         ->with('asistencias', $asistencias)
                                         ->with('evaluaciones', $evaluaciones)
                                         ->with('notafinal', $notafinal)
                                         ->with('promedioaistencias', $promedioaistencias);
    } 

    public function historial($codigo)
    {
        $programa = Programa::find($codigo);

        $historialPrograma = Version::select('versiones.programas_codigo', 'instructores.rut', 'instructores.nombres','instructores.apellido_paterno','instructores.apellido_materno', DB::raw('count(distinct versiones.cursos_codigo) as cantidad_cursos'), DB::raw("(select count(distinct a.cursos_codigo)
                                            from versiones a
                                            where a.programas_codigo = ".$codigo.") as total_curso"),
                    DB::raw("(select min(a.fecha_inicio)
                                from versiones a
                                where a.programas_codigo = ".$codigo.") as fecha_inicio"),
                    DB::raw("(select max(a.fecha_fin)
                                from versiones a
                                where a.programas_codigo = ".$codigo.") as fecha_fin"))
                ->join('curso_instructor', 'curso_instructor.id', '=', 'versiones.curso_instructor_id')
                ->join('instructores', 'instructores.rut', '=', 'curso_instructor.instructores_rut')
                ->where('versiones.programas_codigo', '=', $codigo)
                ->groupBy('versiones.programas_codigo', 'instructores.rut', 'instructores.nombres','instructores.apellido_paterno','instructores.apellido_materno')->get();        

//dd($programa, $historialPrograma);
        return view('programas.historial')->with('programa',$programa)
                                          ->with('historialPrograma',$historialPrograma);
    }        

    // se llama a vista de asignaciones
    public function asignaciones($codigo)
    {
        $programa = Programa::find($codigo);

        //validar que programa no se este dictanto o este cerrado
        $counterProg = Version::where('programas_codigo','=',$codigo)->count();
        $counterCerr = Version::where('programas_codigo','=',$codigo)
                        ->where('status','=','C')->count();
        $resta = $counterProg - $counterCerr;
        
        /*
        $counter = Version::where('programas_codigo','=',$codigo)
                        ->where('status','<>','A')->count();
        */

        //if ($counter <>'0' || $programa->estado == false) {
        if ($resta == '0' || $programa->estado == false) {
            return redirect()->route('programas.index')->with('error','No se puede asignar trabajadores');   
        }else{
            
            if (Auth::user()->rol_select == 'instructor'){
                $versiones = DB::table('versiones')->select('versiones.*', 'programas.*')
                            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                            ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                            ->where('programas.estado', '=', 'true')
                            ->where('versiones.status', '<>', 'C')
                            ->where('versiones.programas_codigo','=',$codigo)->get();  
            }else{
                $versiones = Version::
                            join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                            ->where('programas.estado', '=', 'true')
                            ->where('versiones.status', '<>', 'C')
                            ->where('versiones.programas_codigo','=',$codigo)->get();  
            }


            //instructores asignados a versiones
            $rut_instructores = [];
            foreach ($versiones as $value) {
                $rut_ins = CursoInstructor::find($value->curso_instructor_id)->instructores_rut;
                if(count($rut_instructores) == 0){
                    array_push($rut_instructores,$rut_ins);
                }
                else{
                    foreach ($rut_instructores as $valor){
                        if($valor != $rut_ins){
                            array_push($rut_instructores,$rut_ins);                        
                        }
                    }
                }
            }

            // empresas donde buscar trabajadores
            $empresaFiltrar = DB::table('versiones')->select('empresas_id')->where('programas_codigo','=',$codigo)->groupBy('empresas_id')->get();

            foreach($empresaFiltrar as $ef){
                $filtrar = $ef->empresas_id;
            }
            
            //trabajadores segun empresa del programa
            $trabajadores = DB::table('trabajadores')
                        ->select('trabajadores.rut','trabajadores.nombres','trabajadores.apellido_paterno','trabajadores.apellido_materno', 'empresas.*')
                        ->join('empresas', 'trabajadores.empresas_id', '=', 'empresas.id')
                        ->where('trabajadores.estado','=','true')
                        ->whereNotIn('trabajadores.rut',$rut_instructores);

            if (!is_null($filtrar)) {
                $trabajadores = $trabajadores->where('empresas.id','=',$filtrar)->get();
            }else {
                $trabajadores = $trabajadores->get();
            }

            $trabajadoresActuales = [];
            //trabajadores ya asignados por version
            foreach($versiones as $key => $version){
                $trabajadoresActuales[$key] = CursoTrabajador::select('curso_trabajador.trabajadores_rut', 'trabajadores.rut','trabajadores.nombres','trabajadores.apellido_paterno','trabajadores.apellido_materno')
                        ->where('versiones_id','=',$version->id)
                        ->join('trabajadores', 'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                        ->distinct()
                        ->get();
            }

            $todos = [];
            foreach ($trabajadoresActuales as $key => $value) {
                foreach ($value as $key => $value1) {
                    array_push($todos, $value1);
                }
            }

            //trabajadores ya asignados sin repetir
            $trabajadores_actuales = [];
            $contador = 0;
            foreach ($todos as $key => $value) {
                if(count($trabajadores_actuales) == 0){
                    array_push($trabajadores_actuales,$value);
                }
                else{
                    foreach ($trabajadores_actuales as $valor){
                        if($valor->trabajadores_rut == $value->trabajadores_rut){
                            $contador = $contador + 1;
                        }
                    }
                    if($contador == 0){
                        array_push($trabajadores_actuales,$value);                        
                    }                            
                }
            }

            //dd($todos,$trabajadores_actuales);

            return view('programas.asignacion')->with('programa',$programa)
                                                ->with('trabajadores',$trabajadores)
                                                ->with('trabajadores_actuales',$trabajadores_actuales)
                                                ->with('versiones',$versiones);
        }
    } 

    public function verCurso($codigo)
    {

        $codigoPrograma = Session::get('codigoPrograma');

        $curso_info = Curso::select('cursos.*', 'tipoMotores.nombre as motor', 'unidadesNegocio.nombre As unidad_negocio', 'tipoCursos.nombre As tipo')
                        ->leftJoin('tipoMotores', 'tipoMotores.codigo', '=', 'cursos.tipoMotores_codigo')
                        ->leftJoin('unidadesNegocio', 'unidadesNegocio.codigo', '=', 'cursos.unidadesNegocio_codigo')
                        ->leftJoin('tipoCursos', 'tipoCursos.codigo', '=', 'cursos.tipoCursos_codigo')
                        ->where('cursos.codigo','=', $codigo)->get();

        $documentos = Documento::where('cursos_codigo','=',$codigo)->get();

        $evaluaciones = Evaluacion::where('cursos_codigo','=',$codigo)->get();

        $prerrequisitos = Prerrequisito::where('cursos_codigo_hijo','=',$codigo)->get();    

        return view('programas.ver_curso')->with('curso_info',$curso_info)
                                   ->with('documentos',$documentos)
                                   ->with('evaluaciones',$evaluaciones)
                                   ->with('codigoPrograma',$codigoPrograma)
                                   ->with('prerrequisitos',$prerrequisitos);
    }

    // metodo para guardar la asignacion de trabajadores-curso
    public function storeAsignacion(Request $request)
    {

        if(empty($request->input('to'))){
            return redirect()->back()->withInput()->with('error','Debe seleccionar al menos un trabajador');
        }

        $versionId  = $_POST['versiones'];
        $cursoId    = $_POST['cursos'];
        $trabajadorAgregado = 0;
        
        //asignar trabajador a cursos
        for ($i=0; $i < sizeof($versionId); $i++) { 
            $trabajadores = $request->input('to');
            foreach ($trabajadores as $trabajador) { 
                $Reprobado  = 0;
                $noCursado  = 0;

                $Prerrequisitos = DB::table('prerrequisitos')
                ->select('prerrequisitos.cursos_codigo_padre')
                ->where('cursos_codigo_hijo', '=', $cursoId[$i])
                ->get();
                
                foreach ($Prerrequisitos as $key => $value) {
                    $EstadoCursos = DB::table('curso_trabajador')
                    ->select('curso_trabajador.cursos_codigo','curso_trabajador.status')
                    ->where('cursos_codigo', '=', $value->cursos_codigo_padre)
                    ->where('trabajadores_rut', '=', $trabajador)
                    ->get();

                    if (count($EstadoCursos) == 0) {
                        $noCursado = $noCursado + 1;
                    }else{
                        foreach ($EstadoCursos as $key => $value) {
                            if($value->status == 'R' || $value->status == 'P'){
                                $Reprobado = $Reprobado + 1;
                            }
                        }
                    }
                }

                $statusVersion = Version::find($versionId[$i])->status;            

                if($Reprobado == 0 && $noCursado == 0 && ($statusVersion <> 'P' || $statusVersion <> 'C')){

                    //Comprobar si existe trabajador con esos datos
                    $CursoTrabajadorId = DB::table('curso_trabajador')
                    ->select('curso_trabajador.id')
                    ->where('cursos_codigo', '=', $cursoId[$i])
                    ->where('trabajadores_rut', '=', $trabajador)
                    //->where('versiones_id', '=', $versionId[$i])
                    ->where('status', '<>', 'R')
                    ->get();

                    if(count($CursoTrabajadorId) == 0){
                        $ct = new CursoTrabajador;
                        $ct->cursos_codigo      = $cursoId[$i];       
                        $ct->trabajadores_rut   = $trabajador;
                        $ct->versiones_id       = $versionId[$i];
                        $ct->status             = 'P';
                        $ct->save();

                        if($ct->save() == true){
                            $trabajadorAgregado = $trabajadorAgregado + 1;
                        }

                        $user_id      = User::select('id', 'email')->where('trabajadores_rut', '=', $trabajador)->get();

                        if(count($user_id)>0){
                            //Guardar Notificación
                            $timeZone = 'America/Santiago'; 
                            date_default_timezone_set($timeZone); 
                            $now = date_create();
                            $notificacion           = new Notificacion;
                            $notificacion->texto    = 'Se te asignó un curso nuevo, revisa los programas.';
                            $notificacion->fecha    = date_format($now, 'Y-m-d H:i:s');
                            $notificacion->titulo   = 'Se te ha asignado un curso nuevo.';
                            $notificacion->url      = '/programas';
                            $notificacion->visto    = false;
                            $notificacion->admin    = false;
                            $notificacion->rol      = 'Alumno';
                            $notificacion->users_id = $user_id[0]->id;
                            $notificacion->save();


                            //envio mail
                            //nombre curso
                            $nombre_curso = Curso::select('nombre as nombre_curso')->where('codigo', '=', $cursoId[$i])->get();

                            //fechas inicio y fin del curso
                            $version      = Version::select('cursos_codigo', 'programas_codigo', 'fecha_inicio', 'fecha_fin', 'nombre as nombre_programa')
                                                    ->join('programas', 'programas.codigo', '=', 'versiones.programas_codigo')
                                                    ->where('id', '=', $versionId[$i])->get();

                            $data = array(
                                'programa_codigo'           => $version[0]->programas_codigo,
                                'programa_nombre'           => $version[0]->nombre_programa,
                                'curso'                     => $nombre_curso[0]->nombre_curso,
                                'fecha_inicio'              => $version[0]->fecha_inicio,
                                'fecha_termino'             => $version[0]->fecha_fin,
                            );

                            $emailTrabajador = $user_id[0]->email;
                            $variableValor = getenv('VAR_CORREO');
                            if ($variableValor == 'true') {
                                Mail::send('email.trabajadorPrograma', $data , function($message) use ($emailTrabajador) {
                                        $message->to($emailTrabajador, 'Trabajador')
                                    ->from('cumminssgc@gmail.com', 'Administrador SGC')
                                            ->subject('Asignación Curso');
                                });
                            }
                        }
                    }
                }
            }
        }
        //quitar trabajadores de cursos
        for ($i=0; $i < sizeof($versionId); $i++) {

            $trabajadoresActuales = CursoTrabajador::select('*')
                                                    ->where('versiones_id','=',$versionId[$i])->get();
            
            foreach($trabajadoresActuales as $value) { 
                $flag = false;
                for ($j=0; $j < sizeof($trabajadores); $j++) {  
                    if ($value->trabajadores_rut == $trabajadores[$j]) {
                        $flag = true;
                    }else{
                        $trabajador_nota = Nota::where('curso_trabajador_id', '=', $value->id)->count();
                        if ($trabajador_nota > 0) {
                            $flag = true;
                        }
                    }
                }
                if ($flag == false) {    
                    $value->delete();
                }
            } 
        }
	
        //Guardar log al asociar trabajadores al programa
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Trabajadores a Programas",
            'details' => "Se agregan trabajadores a Programa: " . $request->input('nombrePrograma'),
        ]);

        if ($trabajadorAgregado == 0) {
            return redirect()->route('programas.index')->with('error','Trabajadores no asignados');
        }else{
            return redirect()->route('programas.index')->with('success','Trabajadores asignados correctamente'); 
        }
    }    


    // metodo para cargar datos niveles a calificacion seleccionada
    public function cargaNivel(Request $request)
    {
        if ($request->ajax()) {

            $calificacionSelecionada = $request->input('calificacion');
        
            $nivelesPre = Nivel::where('calificaciones_codigo','=', $calificacionSelecionada)->get();
            $nivelesGroup = $nivelesPre->groupBy('nivel_num');
        

            // cursos a los cuales buscar informacion
            $filtrarC = DB::table('niveles')
                            ->select('nivel_num','cursos_codigo')
                            ->where('calificaciones_codigo','=',$calificacionSelecionada)->get();
            $query = DB::table('cursos');
            foreach($filtrarC as $cf_codigo){
                $query->orWhere('codigo', '=', $cf_codigo->cursos_codigo);
            }
            $cursoInfo = $query->get();

            // instructores de los cursos de calificacion seleccionada
            $instructores = DB::table('niveles')
                            ->join('cursos', 'niveles.cursos_codigo', '=', 'cursos.codigo')
                            ->join('curso_instructor', 'cursos.codigo', '=', 'curso_instructor.cursos_codigo')
                            ->join('instructores', 'instructores.rut', '=', 'curso_instructor.instructores_rut')
                            ->select('cursos.codigo as cod_curso', 'curso_instructor.id', 'curso_instructor.instructores_rut', 'instructores.nombres', 'instructores.apellido_paterno', 'instructores.apellido_materno')
                            ->where('calificaciones_codigo','=',$calificacionSelecionada)
                            ->where('instructores.estado','=',true)
                            ->groupBy('cursos.codigo', 'curso_instructor.id', 'curso_instructor.instructores_rut', 'instructores.nombres', 'instructores.apellido_paterno', 'instructores.apellido_materno')->get();

            //sucursales de cursos de la calificacion seleccionada
            $sucursales = [];
            foreach ($cursoInfo as $key => $value) {
                if ($value->categoria == 'I'){
                     
                     $sucursales[$value->codigo] = Sucursal::where('sucursales.vigencia','=',true)
                                                            ->orderBy('sucursales.nombre', 'asc')->get();
                    
                }else{
                    
                    $sucursales[$value->codigo] = DB::table('cursos')
                                ->join('sucursales', 'cursos.sucursales_codigo', '=', 'sucursales.codigo')
                                ->select('sucursales.codigo', 'sucursales.nombre', 'cursos.codigo as cod_curso')
                                ->where('cursos.codigo','=',$value->codigo)
                                ->where('sucursales.vigencia','=',true)
                                ->groupBy('sucursales.codigo', 'sucursales.nombre', 'cursos.codigo')
                                ->orderBy('sucursales.nombre', 'asc')->get();
                }
            }

            return response()->json([
                "cursoInfo" => $cursoInfo,
                "instructores" => $instructores,
                "sucursales" => $sucursales,
                "nivelesGroup" => $nivelesGroup
            ]);
        }     
    }


    // metodo para devolver informacion para curso seleccionado
    public function infoCurso(Request $request)
    {
        if ($request->ajax()) {

            $cursoSelecionado = $request->input('curso');
            $cursoInfo = Curso::where('codigo','=', $cursoSelecionado)->get();   

            // sucursales de curso ingresado
            // verifico categoria de curso (interno(I) no tiene sucursal asociada, externo(E) si)
            if($cursoInfo[0]->categoria == 'I'){
                $sucursales = Sucursal::where('sucursales.vigencia','=',true)
                                        ->orderBy('sucursales.nombre', 'asc')->get();
            }else {
                $sucursales = DB::table('cursos')
                                ->join('sucursales', 'cursos.sucursales_codigo', '=', 'sucursales.codigo')
                                ->select('sucursales.codigo', 'sucursales.nombre')
                                ->where('cursos.codigo','=',$cursoSelecionado)
                                ->where('sucursales.vigencia','=',true)
                                ->groupBy('sucursales.codigo', 'sucursales.nombre')
                                ->orderBy('sucursales.nombre', 'asc')->get();
            }

            // instructores de curso ingresado
            $instructores = DB::table('curso_instructor')
                            ->join('instructores', 'instructores.rut', '=', 'curso_instructor.instructores_rut')
                            ->select('curso_instructor.id', 'curso_instructor.cursos_codigo', 'instructores.rut', 'instructores.nombres', 'instructores.apellido_paterno', 'instructores.apellido_materno')
                            ->where('curso_instructor.cursos_codigo','=',$cursoSelecionado)
                            ->where('instructores.estado','=',true)
                            ->groupBy('curso_instructor.id', 'curso_instructor.cursos_codigo', 'instructores.rut', 'instructores.nombres', 'instructores.apellido_paterno', 'instructores.apellido_materno')->get();
            
            $cantidadInstructores =  $instructores->count();

            $cursoEvaluaciones = Evaluacion::select(DB::raw('SUM(porcentaje) as total_porcentaje'))
                                        ->where('cursos_codigo','=', $cursoSelecionado)->get();


            return response()->json([
                "respHoras" => $cursoInfo[0]->horas,
                "respNomCurso" => $cursoInfo[0]->nombre,
                "instructores" => $instructores,
                "cantidadInstructores" => $cantidadInstructores,
                "cursoEvaluaciones" => $cursoEvaluaciones,
                "sucursales" => $sucursales
            ]);
        }     
    }

    public function mensaje($codigo)
    {
        $programa = Programa::find($codigo);

        $id_user        = Auth::user()->id;
        $rut_trab       = User::find($id_user)->trabajadores_rut;
        
        $cursos = DB::table('versiones')
            ->select('versiones.cursos_codigo', 'cursos.codigo', 'cursos.nombre')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
            ->where('versiones.programas_codigo','=',$codigo)
            ->where('curso_trabajador.trabajadores_rut','=',$rut_trab)
            ->get(); 

        $mensajes = DB::table('mensajes')
            ->select('curso_trabajador.cursos_codigo','mensajes.titulo', 'mensajes.texto', 'mensajes.respuesta', 'mensajes.updated_at', 'mensajes.estado')
            ->join('curso_trabajador', 'mensajes.curso_trabajador_id', '=', 'curso_trabajador.id')            
            ->join('versiones', 'curso_trabajador.versiones_id', '=', 'versiones.id')
            ->where('versiones.programas_codigo','=',$codigo)
            ->where('curso_trabajador.trabajadores_rut','=',$rut_trab)
            ->orderBy('mensajes.updated_at','desc')
            ->get(); 

        return view('programas.mensaje')->with('programa',$programa)
        ->with('cursos', $cursos)
        ->with('mensajes', $mensajes);
    }

    public function storeMensaje(Request $request)
    {

        if(empty($request->input('codigocurso'))){
            return redirect()->back()->withInput()->with('error','Debe seleccionar un curso');
        }
        if(empty($request->input('titulonuevo'))){
            return redirect()->back()->withInput()->with('error','Debe ingresar un título');
        }
        if(empty($request->input('textonuevo'))){
            return redirect()->back()->withInput()->with('error','Debe ingresar una consulta');
        }

        /*Validacion de largos*/
        $countT = mb_strlen($request->input('titulonuevo'), 'UTF-8');
        if($countT > 50){
            return back()->with('error', 'Título demasiado largo, el máximo permitido es 50 caracteres');
        }

        $countC = mb_strlen($request->input('textonuevo'), 'UTF-8');
        if($countC > 200){
            return back()->with('error', 'Consulta demasiado largo, el máximo permitido es 200 caracteres');
        }

        $codigoprog     = $request->input('codigo');
        $curso          = $request->input('codigocurso');
        $id_user        = Auth::user()->id;
        $rut_trab       = User::find($id_user)->trabajadores_rut;

        $results = DB::table('curso_trabajador')
            ->select('curso_trabajador.id as idcurso', 'versiones.id as idversion')           
            ->join('versiones', 'curso_trabajador.versiones_id', '=', 'versiones.id')
            ->where('versiones.programas_codigo','=',$codigoprog)
            ->where('curso_trabajador.cursos_codigo','=',$curso)
            ->where('curso_trabajador.trabajadores_rut','=',$rut_trab)
            ->get(); 
      
        foreach ($results as $result) {
            $curso_trabajador_id = $result->idcurso;
            $versiones_id = $result->idversion;            
        }

        $resultados = Version::where('id','=', $versiones_id)->get();

        foreach ($resultados as $resultado) {
            $curso_instructor_id = $resultado->curso_instructor_id;            
        }

        $rut_ins    = CursoInstructor::find($curso_instructor_id)->instructores_rut;

        $emailInst  = Instructor::find($rut_ins)->email;

        $mensaje                        = new Mensaje;
        $mensaje->titulo                = $request->input('titulonuevo');
        $mensaje->texto                 = $request->input('textonuevo');        
        $mensaje->curso_trabajador_id   = $curso_trabajador_id;
        $mensaje->curso_instructor_id   = $curso_instructor_id;
        $mensaje->estado                = 0;
        $mensaje->save();             

        $trabajadores           = User::select('id', 'name')->where('trabajadores_rut', '=', $rut_ins)->get();
        if(count($trabajadores) > 0){
            //Guardar Notificación
            $timeZone = 'America/Santiago'; 
            date_default_timezone_set($timeZone); 
            $now = date_create();
            $notificacion           = new Notificacion;
            $notificacion->texto    = 'Tienes una nueva consulta.';
            $notificacion->fecha    = date_format($now, 'Y-m-d H:i:s');
            $notificacion->titulo   = 'Tienes una nueva consulta.';
            $notificacion->url      = '/programas/respuesta/'.$codigoprog;
            $notificacion->visto    = false;
            $notificacion->admin    = false;
            $notificacion->rol      = 'Instructor';
            $notificacion->users_id = $trabajadores[0]->id;
            $notificacion->save();
        }

        //Guardar log al crear consulta
        Log::create([
            'id_user'                   => Auth::user()->id,
            'action'                    => "Agregar Mensaje",
            'details'                   => "Se agrega mensaje a programa: " . $request->input('nombre'),
        ]);

        $data = array(
            'programa'                  => $codigoprog,
            'curso'                     => $curso,
            'titulo'                    => $mensaje->titulo,
            'texto'                     => $mensaje->texto,
        );

        //envio mail
        $variableValor = getenv('VAR_CORREO');
        if ($variableValor == 'true') {
            Mail::send('email.mensajeconsulta', $data , function($message) use ($emailInst) {
                    $message->to($emailInst, 'Instructor')
                                    ->from('cumminssgc@gmail.com', 'Administrador SGC')
                        ->subject('Creación consulta');
            });
        }

        return redirect()->route('programas.index')
        ->with('success','Consulta creada correctamente'); 

    }

    public function respuesta($codigo)
    {
        $programa = Programa::find($codigo);

        $id_user        = Auth::user()->id;
        $rut_ins        = User::find($id_user)->trabajadores_rut;
        
        $cursos = DB::table('versiones')
            ->select('versiones.cursos_codigo', 'cursos.codigo', 'cursos.nombre')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
            ->where('versiones.programas_codigo','=',$codigo)
            ->where('curso_instructor.instructores_rut','=',$rut_ins)
            ->get(); 
        
        foreach ($cursos as $curso) {
            $codigocurso = $curso->codigo;
        }

        $mensajes = DB::table('mensajes')
            ->select('curso_instructor.cursos_codigo', 'mensajes.id', 'mensajes.titulo', 'mensajes.texto', 'mensajes.respuesta', 'mensajes.updated_at', 'mensajes.estado')
            ->join('curso_trabajador', 'mensajes.curso_trabajador_id', '=', 'curso_trabajador.id')            
            ->join('versiones', 'curso_trabajador.versiones_id', '=', 'versiones.id')
            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
            ->where('versiones.programas_codigo','=',$codigo)
            ->where('curso_instructor.instructores_rut','=',$rut_ins)            
            ->orderBy('mensajes.updated_at','desc')
            ->get();       

        return view('programas.respuesta')->with('programa',$programa)
        ->with('cursos', $cursos)
        ->with('mensajes', $mensajes);
    }

    public function storeRespuesta(Request $request)
    {
        if(empty($request->input('mensajenuevo'))){
            return redirect()->back()->withInput()->with('error','Debe ingresar una respuesta');
        }
        $codigoprog                     = $request->input('codigo');
        $curso                          = $request->input('codigocurso');
        
        $mensaje                        = $request->input('idmensaje');

        $mensaje                        = Mensaje::find($mensaje);
        $mensaje->respuesta             = $request->input('mensajenuevo');
        $mensaje->estado                = 1;
        $mensaje->save();

        $curso_trabajador_id            = Mensaje::find($request->input('idmensaje'))->curso_trabajador_id;
        $trabajadores_rut               = CursoTrabajador::find($curso_trabajador_id)->trabajadores_rut;
        $trabajador_id                  = User::select('id', 'email')->where('trabajadores_rut', '=', $trabajadores_rut)->get();

        $emailAlumno                    = $trabajador_id[0]->email;

        if(count($trabajador_id) > 0){
            //Guardar Notificación
            $timeZone = 'America/Santiago'; 
            date_default_timezone_set($timeZone); 
            $now = date_create();
            $notificacion           = new Notificacion;
            $notificacion->texto    = 'Tienes una nueva respuesta.';
            $notificacion->fecha    = date_format($now, 'Y-m-d H:i:s');
            $notificacion->titulo   = 'Tienes una nueva respuesta.';
            $notificacion->url      = '/programas/mensaje/'.$codigoprog;
            $notificacion->visto    = false;
            $notificacion->admin    = false;
            $notificacion->rol      = 'Alumno';
            $notificacion->users_id = $trabajador_id[0]->id;
            $notificacion->save();
        }

        //Guardar log al crear consulta
        Log::create([
            'id_user'                   => Auth::user()->id,
            'action'                    => "Responder Mensaje",
            'details'                   => "Se responde mensaje de programa: " . $request->input('nombre'),
        ]);

        $data = array(
            'programa'                  => $codigoprog,
            'curso'                     => $curso,
            'titulo'                    => $mensaje->titulo,
            'texto'                     => $mensaje->texto,
            'respuesta'                 => $mensaje->respuesta,
        );

        //envio mail
        Mail::send('email.respuestaconsulta', $data , function($message) use ($emailAlumno) {
                $message->to($emailAlumno, 'Alumno')
                                    ->from('cumminssgc@gmail.com', 'Administrador SGC')
                    ->subject('Respuesta consulta');
        });

        return redirect()->route('programas.index')
        ->with('success','Respuesta guardada correctamente'); 

    }

    //metodo para llevar al index del alumno
    public function indexAlumno()
    {

        $rut_alumno       = User::find(Auth::user()->id)->trabajadores_rut;

        $programasencurso = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa')//, 'fechas.*')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')
            ->where('curso_trabajador.trabajadores_rut', '=', $rut_alumno)
            ->where('versiones.status', '=', 'D') //D=Dictando
            ->where('cursos.estado', '=', true)
            ->groupBy('programas.codigo', 'cursos.nombre')
            ->get();

        $programascursados = Version::select('cursos.nombre as nombre_curso', 'programas.codigo as codigo_programa','programas.nombre as nombre_programa')//, 'fechas.*')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')
            ->where('curso_trabajador.trabajadores_rut', '=', $rut_alumno)
            ->where('versiones.status', 'C')
            ->where('cursos.estado', '=', true)
            ->groupBy('programas.codigo', 'cursos.nombre')
            ->get();

        $historialprogramas = Version::select(DB::raw("TO_CHAR(fecha_inicio,'yyyy-mm') as fecha_cursos"), DB::raw('count(*) as cursos_mes'))
            //->select(DB::raw('count(*) as user_count, status'))
        ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
            ->join('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->where('curso_trabajador.trabajadores_rut', '=', $rut_alumno)
            ->where('cursos.estado', '=', true)
        ->where('programas.estado', '=', true)
            ->groupBy(DB::raw("TO_CHAR(fecha_inicio,'yyyy-mm')"))
            ->orderBy(DB::raw("TO_CHAR(fecha_inicio,'yyyy-mm')"))
            ->get();

        return view('programas.dashboard_alumno')->with('programasencurso', $programasencurso)->with('programascursados', $programascursados)->with('historialprogramas', $historialprogramas);
    }
    
    public function agregarAsistencia(Request $request)
    {
        try {
            if ($request->ajax()) {
                $asistencias = json_decode($request->input('asistencias'));
                foreach ($asistencias as $asistencia) {
                    $agregarAsistencia = new Asistencia;
                    $agregarAsistencia->curso_trabajador_id = $asistencia->rut;
                    $agregarAsistencia->fecha = $asistencia->fecha;
                    $agregarAsistencia->estado = $asistencia->asistencia;
                    $agregarAsistencia->save(); 
                }
                return response()->json([
                    "asistencias" => $asistencias,
                    "message" => "ok"
                ]);
            } 
        } catch (Exception $e) {
            return response()->json([
                "notas"   => $notas,
                "message" => "wrong",
                "e"      => $e
            ]);
        }
    }

    public function agregarRepechaje(Request $request)
    {
        $timeZone = 'America/Santiago'; 
        date_default_timezone_set($timeZone); 
        $now = date_create();

        try {
            if ($request->ajax()) {
                $notas_repechaje = json_decode($request->notas_repechaje);

                $i = 0;

                foreach ($notas_repechaje as $nota_repechaje) {
                    $nuevaNotaRepechaje                      = new Repechaje;
                    $nuevaNotaRepechaje->resultado           = number_format($nota_repechaje->nota, 0, ',', '');
                    $nuevaNotaRepechaje->fecha               = date_format($now, 'Y-m-d H:i:s');
                    $nuevaNotaRepechaje->evaluaciones_id     = $nota_repechaje->evaluacionid;
                    $nuevaNotaRepechaje->curso_trabajador_id = $request->curso_trabajador_id;
                    $nuevaNotaRepechaje->save();
                    $i++; 
                }

                $curso_trabajador                             = CursoTrabajador::find($request->curso_trabajador_id);
                $curso_trabajador->nota_final_repechaje       = number_format($request->nota_final_repechaje, 0, ',', '');
                $curso_trabajador->status_repechaje           = $request->status;
                $curso_trabajador->save();

                return response()->json([
                    "response" => "ok"
                ]);
            }    
        } catch (Exception $e) {
            
        }
    }

    public function asignarRepechaje(Request $request)
    {
        $id         =   $request->id;
        $status     =   $request->status;

        $cursoTrabajador            = CursoTrabajador::find($id);
        $cursoTrabajador->repechaje = $status;
        $cursoTrabajador->save();

        return response()->json([
            "id"      => $cursoTrabajador,
            "message" => "ok"
        ]);
    }

    public function agregarNota(Request $request)
    {
        $timeZone = 'America/Santiago'; 
        date_default_timezone_set($timeZone); 
        $now = date_create();
        try {
            if ($request->ajax()) {
                
                $notas = json_decode($request->input('notas'));

                foreach ($notas as $nota) {
                    $notaActual = "";
                    if (!is_null($nota->nota)) {
                        $notaActual = str_replace(',', '.', $nota->nota);
                    }

                    if ($nota->accion == "agregar") {
                        $nuevaNota                      = new Nota;
                        $nuevaNota->resultado           = $notaActual;
                        $nuevaNota->fecha               = date_format($now, 'Y-m-d H:i:s');
                        $nuevaNota->evaluaciones_id     = $nota->evaluacionid;
                        $nuevaNota->curso_trabajador_id = $nota->trabajadorid;
                        $nuevaNota->save(); 

                        $trabajadores_rut   = CursoTrabajador::select('trabajadores_rut')->where('id', '=', $nota->trabajadorid)->get();
                        $trabajadores           = User::select('id', 'name')->where('trabajadores_rut', '=', $trabajadores_rut[0]->trabajadores_rut)->get();

                        if(count($trabajadores) > 0){
                            $notificacion           = new Notificacion;
                            $notificacion->texto    = 'Revisa las sección de programas y revisa tus notas.';
                            $notificacion->fecha    = date_format($now, 'Y-m-d H:i:s');
                            $notificacion->titulo   = 'Tienes notas nuevas.';
                            $notificacion->url      = '/programas';
                            $notificacion->visto    = false;
                            $notificacion->admin    = false;
                            $notificacion->rol      = 'Alumno';
                            $notificacion->users_id = $trabajadores[0]->id;
                            $notificacion->save();
                        }
                    }else{
                        $nuevaNota                      = Nota::find($nota->accion);;
                        $nuevaNota->resultado           = $notaActual;
                        $nuevaNota->save(); 

                        $trabajadores_rut   = CursoTrabajador::select('trabajadores_rut')->where('id', '=', $nota->trabajadorid)->get();
                        $trabajadores           = User::select('id', 'name')->where('trabajadores_rut', '=', $trabajadores_rut[0]->trabajadores_rut)->get();

                        if(count($trabajadores) > 0){
                            $notificacion           = new Notificacion;
                            $notificacion->texto    = 'Revisa las sección de programas y revisa tus notas.';
                            $notificacion->fecha    = date_format($now, 'Y-m-d H:i:s');
                            $notificacion->titulo   = 'Te actualizaron una nota.';
                            $notificacion->url      = '/programas';
                            $notificacion->rol      = 'Alumno';
                            $notificacion->visto    = false;
                            $notificacion->admin    = false;
                            $notificacion->users_id = $trabajadores[0]->id;
                            $notificacion->save();
                        }
                    }

                    $curso_trabajador           = CursoTrabajador::find($nota->trabajadorid);
                    $version                    = Version::find($curso_trabajador->versiones_id);
                    $curso                      = Curso::find($curso_trabajador->cursos_codigo);
                    $evaluaciones               = Evaluacion::where('cursos_codigo', '=', $curso_trabajador->cursos_codigo)->count();
                    $nota_trabajador            = Nota::where('curso_trabajador_id', '=', $nota->trabajadorid)->count();
                    $notasEvaluaciones          = Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'notas.resultado')
                                                ->join('notas', 'evaluaciones.id', '=', 'notas.evaluaciones_id')
                                                ->join('curso_trabajador', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')
                                                ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                                ->where('versiones.id', '=', $version->id)
                                                ->where('curso_trabajador.id', '=', $curso_trabajador->id)
                                                ->get();

                    if($version->situacion == "Con Evaluación"){
                        $notafinal    = 0;
                        $notaprograma = 0;
                        if ($nota_trabajador == $evaluaciones) {
                            foreach ($notasEvaluaciones as $evaluacion) {
                                $notafinal = $notafinal + ($evaluacion->resultado * ($evaluacion->porcentaje / 100));
                            }
                            
                            $curso_trabajador->nota_final = number_format($notafinal, 0, ',', '');

                            if ($curso->aprobacion_minima <= $notafinal) {
                                $curso_trabajador->status = 'A';
                            }else{
                                $curso_trabajador->status = 'R';
                            }
                        }else{
                            $curso_trabajador->status = 'P';
                        }

                        $curso_trabajador->save();
                    }
                }
                return response()->json([
                    "notas"   => $notas,
                    "evaluaciones" => $evaluaciones,
                    "nota_trabajador" => $nota_trabajador,
                    "message" => "ok"
                ]);
            } 
        } catch (Exception $e) {
            return response()->json([
                "notas"   => $notas,
                "message" => "wrong",
                "e"      => $e
            ]);
        }
    }

    // se generar fechas
    public function generaFechas(Request $request)
    {
        if ($request->ajax()) {

            $horas = $request->input('horas');
            $fechaIngresada = $request->input('fechaIngresada'); 

            $dias = ceil($horas/8);

            $fechaMod = str_replace('/', '-', $fechaIngresada);

            //dias no habiles para comparar
            $fechas_calendario = Calendario::all(); 
            
            $listadoFechas = [];
            for ($i = 0; $i < $dias; $i++) {
                // sumar n dias o su equivalente en segundos
                $preListado = date('d-m-Y', strtotime($fechaMod) + 86400*$i);

                //detectar dia de la semana a fecha ingresada
                $diaSemana = date('N', strtotime($preListado));
                if($diaSemana == '6'){
                    $preListado = date('d-m-Y', strtotime($preListado) + 86400*2);
                    $fechaMod = date('d-m-Y', strtotime($fechaMod) + 86400*2);
                }
                if($diaSemana == '7'){
                    $preListado = date('d-m-Y', strtotime($preListado) + 86400*1);
                    $fechaMod = date('d-m-Y', strtotime($fechaMod) + 86400*1);
                }

                //comparar con fechas de dias no habiles, para no agregar y pasar a la siguiente
                foreach ($fechas_calendario as $fc) {
                    $fc->fecha = date('d-m-Y', strtotime($fc->fecha));
                    if ($fc->fecha <> $preListado) {
                    }else {
                        $preListado = date('d-m-Y', strtotime($preListado) + 86400*1);
                        $fechaMod = date('d-m-Y', strtotime($fechaMod) + 86400*1);
                    }                    
                }
                $listadoFechas[$i] = date('d/m/Y', strtotime($preListado));
            }
 
            return response()->json([
                    "dias" => $dias,
                    "fechaIngresada" => $fechaIngresada,
                    "listadoFechas" => $listadoFechas
            ]);
        }
    }    

/*
    public function ListarProgramasActivos(){
        $programasActivos = Programa::all();
        return response()->json($programasActivos);
    }
*/

    public function ListarProgramasActivos($User,$Pass,$FechaInicio,$FechaTermino,$Sucursal,$Instructor){
        
        $nombreUsuario = User::find(1)->name;
        $passUsuario = User::find(1)->password;

        if($User == $nombreUsuario && Hash::check($Pass, $passUsuario)){

            $trabajadores = [];
            $programasActivos = DB::table('versiones')
            ->select('programas.codigo', 'programas.nombre as NombrePrograma', 'cursos.nombre as NombreCurso', 'versiones.fecha_inicio as FechaInicio', 'versiones.fecha_fin as FechaFin', 'instructores.nombres as NombreInstructor', 'sucursales.nombre as NombreSucursal', 'versiones.cod_sence as CodigoSence')
            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo' )
            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
            ->join('sucursales', 'versiones.lugar_ejecucion', '=', 'sucursales.codigo')
            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
            ->join('instructores', 'curso_instructor.instructores_rut', '=', 'instructores.rut')  
            ->where('programas.estado', '=', 'true')
            ->where('versiones.status', '=', 'A');

            if ($FechaInicio != "''") {
                $programasActivos = $programasActivos->where('versiones.fecha_inicio', '=', $FechaInicio);
            }

            if ($FechaTermino != "''") {
                $programasActivos = $programasActivos->where('versiones.fecha_fin', '=', $FechaTermino);
            }

            if ($Sucursal != "''") {
                $programasActivos = $programasActivos->where('versiones.lugar_ejecucion', '=', $Sucursal);
            }

            if ($Instructor != "''") {
                $programasActivos = $programasActivos->where('instructores.rut', '=', $Instructor);
            }

            $programasActivos = $programasActivos->get();

            foreach ($programasActivos as $key => $value) {
                $trabajadores[$value->codigo][$value->NombrePrograma][$value->NombreCurso][$value->FechaInicio][$value->FechaFin][$value->NombreInstructor][$value->NombreSucursal][$value->CodigoSence] = DB::table('versiones')
                ->select('trabajadores.rut as Rut trabajador', 'trabajadores.nombres as Nombre trabajador')
                ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo' )
                ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut') 
                ->orderBy('trabajadores.rut', 'asc')
                ->where('programas.estado', '=', 'true')
                ->where('versiones.status', '=', 'A')
                ->where('versiones.programas_codigo', '=', $value->codigo)
                ->distinct()
                ->get();
            }
            
            //dd($trabajadores);
            if (count($trabajadores) == 0) {
                return response()->json([0,'Error','No hay registros en ese programa']);
            }else{
                return response()->json([$trabajadores,1,'Exito']);
            }

            /*
            if(count($programasActivos) > 0){   
                return response()->json([$programasActivos,1,'Exito']);
            }else{
                return response()->json([$programasActivos,0,'Error','No se encontraron resultados.']);
            }
            */

        }else{
            return response()->json([0,'Error','Usuario o contraseña incorrectos.']);
        }
    }

    public function RegistrarTrabajadorPrograma($User,$Pass,$Programa,$Trabajador){

        $nombreUsuario = User::find(1)->name;
        $passUsuario = User::find(1)->password;

        $versionId = []; //arreglo de versiones
        $cursoId = []; //arreglo de cursos
        $trabajadorNoExiste = []; //Trabajadores no existe en bd
        $trabajadorAgregado = 0; //contador de Trabajadores agregados
        $registroExiste = []; //Trabajador ya existe en curso
        $distintaEmpresa = []; //Trabajador es de distinta empresa de la que se dicta el programa

        if($User == $nombreUsuario && Hash::check($Pass, $passUsuario)){
            if (is_numeric($Programa)) {  //comprobar que sea numérico
                $programa = Programa::find($Programa);
                if (count($programa) != 0) {
                    $versiones = DB::table('versiones')
                    ->select('versiones.id', 'versiones.cursos_codigo', 'fechas.fecha' )
                    ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                    ->join('fechas', 'versiones.id', '=', 'fechas.versiones_id' )
                    ->where('programas.estado', '=', 'true')
                    ->where('versiones.status', '=', 'A')
                    ->where('versiones.programas_codigo', '=', $Programa)
                    ->get();

                    $versionCurso = 0;
                    $curso = 0;
                    $fechaExiste = 1;
                    $fechasOcupadas = [];
                    $cursoErroneo = 0;
                    $a = 0;
                    $EstadoCursos = [];
                    $cursosReprobados = [];
                    $cursoReprobadoId = [];

                    foreach ($versiones as $version) {
                        $a = $a + 1;

                        if($versionCurso != $version->id){
                            if ($fechaExiste == 0 && $versionCurso != 0) {
                                array_push($versionId, $versionCurso);
                                array_push($cursoId, $curso);
                            }

                            $versionCurso = $version->id;
                            $curso = $version->cursos_codigo;

                            if(count($fechasOcupadas)==0){
                                array_push($fechasOcupadas, $version->fecha);
                            }else{
                                foreach ($fechasOcupadas as $value) {
                                    if ($value != $version->fecha) {
                                        $fechaExiste = 0;
                                    }else{
                                        $fechaExiste = 1;
                                        $cursoErroneo = 1;
                                        break;
                                    }
                                }
                            }

                            if ($fechaExiste == 0) {
                                array_push($fechasOcupadas, $version->fecha);
                            }
                        }
                        else{
                            foreach ($fechasOcupadas as $value) {
                                if ($value != $version->fecha && $cursoErroneo == 0) {
                                    $fechaExiste = 0;
                                }else{
                                    //dd('2');
                                    $fechaExiste = 1;
                                    $cursoErroneo = 1;
                                    break;
                                }
                            }

                            if ($fechaExiste == 0 && $cursoErroneo == 0) {
                                array_push($fechasOcupadas, $version->fecha);
                            }
                        }
                    }
                    
                    if ($fechaExiste == 0 && $versionCurso != 0 && $cursoErroneo == 0) {
                        array_push($versionId, $versionCurso);
                        array_push($cursoId, $curso);
                    }

                    if(count($fechasOcupadas)==1){
                        array_push($versionId, $versionCurso);
                        array_push($cursoId, $curso);
                    }

                    if(count($versionId) == 0 || count($cursoId) == 0){
                        return response()->json([0,'Error','No hay cursos activos para ese programa.']);
                    }
                    
                    for ($i=0; $i < sizeof($versionId); $i++) {
                        $cadenadividida = explode(',', $Trabajador);

                        foreach ($cadenadividida as $trabajador) {
                            $trab = Trabajador::find($trabajador);
                            $Reprobado = 0;

                            if (count($trab) != 0) {

                                $empresaProg = Version::find($versionId[$i])->empresas_id;
                                $empresaTrab = Trabajador::find($trabajador)->empresas_id;

                                if($empresaProg == null || $empresaProg == $empresaTrab)
                                {
                                    $Prerrequisitos = DB::table('prerrequisitos')
                                    ->select('prerrequisitos.cursos_codigo_padre')
                                    ->where('cursos_codigo_hijo', '=', $cursoId[$i])
                                    ->get();
                                    
                                    foreach ($Prerrequisitos as $key => $value) {
                                        $EstadoCursos = DB::table('curso_trabajador')
                                        ->select('curso_trabajador.cursos_codigo','curso_trabajador.status')
                                        ->where('cursos_codigo', '=', $value->cursos_codigo_padre)
                                        ->where('trabajadores_rut', '=', $trabajador)
                                        ->get();

                                        foreach ($EstadoCursos as $key => $value) {
                                            if($value->status == 'R' || $value->status == 'P'){
                                                $Reprobado = $Reprobado + 1;
                                                array_push($cursoReprobadoId,$value->cursos_codigo);
                                            }
                                        }
                                    }                        

                                    $statusVersion = Version::find($versionId[$i])->status;            

                                    if($Reprobado == 0 && ($statusVersion <> 'P' || $statusVersion <> 'C')){

                                        //Comprobra si existe trabajador con esos datos
                                        $CursoTrabajadorId = DB::table('curso_trabajador')
                                        ->select('curso_trabajador.id')
                                        ->where('cursos_codigo', '=', $cursoId[$i])
                                        ->where('trabajadores_rut', '=', $trabajador)
                                        //->where('versiones_id', '=', $versionId[$i])
                                        ->where('status', '<>', 'R')
                                        ->get();

                                        if(count($CursoTrabajadorId) == 0){
                                            $ct = new CursoTrabajador;
                                            $ct->cursos_codigo      = $cursoId[$i];       
                                            $ct->trabajadores_rut   = $trabajador;
                                            $ct->versiones_id       = $versionId[$i];
                                            $ct->status             = 'P';
                                            $ct->save();
                                            $user_id                = User::select('id')->where('trabajadores_rut', '=', $trabajador)->get();

                                            if(count($user_id)>0){
                                                //Guardar Notificación
                                                $timeZone = 'America/Santiago'; 
                                                date_default_timezone_set($timeZone); 
                                                $now = date_create();
                                                $notificacion           = new Notificacion;
                                                $notificacion->texto    = 'Se te asignó un curso nuevo, revisa los programas.';
                                                $notificacion->fecha    = date_format($now, 'Y-m-d H:i:s');
                                                $notificacion->titulo   = 'Se te ha asignado un curso nuevo.';
                                                $notificacion->url      = '/programas';
                                                $notificacion->visto    = false;
                                                $notificacion->rol      = 'Alumno';
                                                $notificacion->users_id = $user_id[0]->id;
                                                $notificacion->save();
                                            }
                                            if($ct->save() == true){
                                                $trabajadorAgregado = $trabajadorAgregado + 1;
                                            }
                                        }else{
                                            array_push($registroExiste, $cursoId[$i]);
                                        }
                                    }else{
                                        array_push($cursosReprobados, $trabajador, $cursoReprobadoId);
                                    }
                                }else{
                                    array_push($distintaEmpresa, $trabajador);
                                }
                            }else{
                                array_push($trabajadorNoExiste, $trabajador);
                            }
                        }
                    }
                   
                    //Guardar log al asociar trabajadores al programa
                    $nombreProg = Programa::find($Programa)->nombre;
                    Log::create([
                        'id_user' => 1, //1 = WebServices
                        'action' => "Agregar Trabajadores a Programa",
                        'details' => "Se agregan trabajadores a Programa: " . $nombreProg,
                    ]);

                    if ($trabajadorAgregado == 0) {
                        return response()->json([0,'Error','Trabajadores registrados: ',$trabajadorAgregado,'Trabajadores no existentes, no registrados: ',$trabajadorNoExiste, 'Trabajador existe en curso(s): ', $registroExiste, 'Cursos trabajadores reprobados', $cursosReprobados, 'Trabajadores de distinta empresa', $distintaEmpresa ]);
                    }else{
                        return response()->json([1,'Exito','Trabajadores registrados:',$trabajadorAgregado,'Trabajadores no existentes, no registrados:',$trabajadorNoExiste, 'Trabajador existe en curso(s): ', $registroExiste, 'Cursos trabajadores reprobados', $cursosReprobados, 'Trabajadores de distinta empresa', $distintaEmpresa ]);
                    }
                }else{
                    return response()->json([0,'Error','No existe el programa ingresado.']);
                }
            }else{
                return response()->json([0,'Error','Código programa incorrecto.']);
            }
        }else{
            return response()->json([0,'Error','Usuario o contraseña incorrectos.']);
        }
    }

    public function EliminarTrabajadorPrograma($User,$Pass,$Programa,$Curso,$Trabajador){
        
        $nombreUsuario = User::find(1)->name;
        $passUsuario = User::find(1)->password;

        $versionId = [];
        $cursoId = [];
        $trabajadorNoExiste = [];
        $trabajadorEliminado = 0;

        if($User == $nombreUsuario && Hash::check($Pass, $passUsuario)){
            if (is_numeric($Programa)) { //comprobar que sea numérico
                $programa = Programa::find($Programa);
                if (count($programa) != 0) { //comprobar que exista programa
                    $versiones = DB::table('versiones')
                    ->select('versiones.id', 'versiones.cursos_codigo')
                    ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo' )
                    ->where('programas.estado', '=', 'true')
                    ->where('versiones.status', '=', 'A')
                    ->where('versiones.programas_codigo', '=', $Programa);

                    if ($Curso != "''") {
                        if (is_numeric($Curso)) {
                            $versiones = $versiones->where('versiones.cursos_codigo', '=', $Curso);
                        }else{
                            return response()->json([0,'Error','Código curso incorrecto.']);
                        }
                    }

                    $versiones = $versiones->get();

                    foreach ($versiones as $version) {
                        array_push($versionId, $version->id);
                        array_push($cursoId, $version->cursos_codigo);
                    }

                    if(count($versionId) == 0 || count($cursoId) == 0){
                        return response()->json([0,'Error','No hay cursos activos para ese programa.']);
                    }
                    
                    for ($i=0; $i < sizeof($versionId); $i++) {
                        $cadenadividida = explode(',', $Trabajador);            
                        //dd($cadenadividida);
                        foreach ($cadenadividida as $trabajador) {
                            $trab = Trabajador::find($trabajador);
                            if (count($trab) != 0) {
                                $CursoTrabajadorId = DB::table('curso_trabajador')
                                ->select('curso_trabajador.id')
                                ->where('cursos_codigo', '=', $cursoId[$i])
                                ->where('trabajadores_rut', '=', $trabajador)
                                ->where('versiones_id', '=', $versionId[$i])
                                ->get();
                                
                                foreach($CursoTrabajadorId as $value) {
                                    $eliminar = CursoTrabajador::find($value->id);
                                    $eliminar->delete();
                                    if($eliminar->delete() == null){
                                        $trabajadorEliminado = $trabajadorEliminado + 1;
                                    }
                                }
                                
                            }else{
                                array_push($trabajadorNoExiste, $trabajador);
                            }
                        }
                    }
                   
                    //Guardar log al asociar trabajadores al programa
                    $nombreProg = Programa::find($Programa)->nombre;
                    Log::create([
                        'id_user' => 1, //1 = WebServices
                        'action' => "Eliminar Trabajadores de Programa",
                        'details' => "Se eliminan trabajadores de Programa: " . $nombreProg,
                    ]); 

                    if ($trabajadorEliminado == 0) {
                        return response()->json([0,'Error','Trabajadores eliminados:',$trabajadorEliminado,'Trabajadores no existentes, no eliminados:',$trabajadorNoExiste ]);
                    }else{
                        return response()->json([1,'Exito','Trabajadores eliminados:',$trabajadorEliminado,'Trabajadores no existentes, no eliminados:',$trabajadorNoExiste ]);
                    }
                }else{
                    return response()->json([0,'Error','No existe el programa ingresado.']);
                }
            }else{
                return response()->json([0,'Error','Código programa incorrecto.']);
            }
        }else{
            return response()->json([0,'Error','Usuario o contraseña incorrectos.']);
        }

    }

    public function ListarTrabajadoresProgramas($User,$Pass,$Programa){
        
        $nombreUsuario = User::find(1)->name;
        $passUsuario = User::find(1)->password;

        if($User == $nombreUsuario && Hash::check($Pass, $passUsuario)){
            if (is_numeric($Programa)) {            
                $programa = Programa::find($Programa);
                $trabajadores = [];
                if (count($programa) != 0) {
                    $estadoPrograma = Programa::find($Programa)->estado;
                    if($estadoPrograma == true){
                        $programasActivos = Programa::select('programas.codigo', 'programas.nombre')
                        ->where('programas.estado', '=', 'true');

                        if ($Programa != "''" ) {
                            $programasActivos = $programasActivos->where('programas.codigo', '=', $Programa);
                        }

                        $programasActivos = $programasActivos->get();

                        foreach ($programasActivos as $key => $value) {
                            $trabajadores[$value->nombre] = DB::table('versiones')
                            ->select('trabajadores.rut as Rut trabajador', 'trabajadores.nombres as Nombre trabajador')
                            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo' )
                            ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                            ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut') 
                            ->orderBy('trabajadores.rut', 'asc')
                            ->where('programas.estado', '=', 'true')
                            ->where('versiones.programas_codigo', '=', $value->codigo)
                            ->distinct()
                            ->get();
                        }
                        
                        if (count($trabajadores) == 0) {
                            return response()->json([0,'Error','No hay registros en ese programa']);
                        }else{
                            return response()->json([$trabajadores,1,'Exito']);
                        }  
                    }else{
                        return response()->json([0,'Error','El programa se encuentra inactivo.']);
                    }                  
                }else{
                    return response()->json([0,'Error','No existe el programa ingresado.']);
                }
            }else{
                return response()->json([0,'Error','Código programa incorrecto.']);
            }
        }else{
            return response()->json([0,'Error','Usuario o contraseña incorrectos.']);
        }
    }

    public function getPrograma(Request $request)
    {
        if ($request->ajax()) {
            $programa = Programa::find($request->input('valor'));

            $counter = count($programa);

            if($counter > 0){
                return response()->json([
                    "programa" => $programa,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "programa" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }

    public function verificarSuc(Request $request)
    {

        if ($request->ajax()) {
            $distinto = 0;

            $sucursales = json_decode($request->input('sucursales'));
            $rut        = $request->input('rut');

            $suc_trabajador = Trabajador::find($rut)->sucursales_codigo;

            foreach ($sucursales as $value) {
                if($value->sucursales != $suc_trabajador){
                    $distinto = $distinto + 1;
                }
            }

            if($distinto > 0){
                return response()->json([
                    "count" => $distinto
                ]);
            }else{
                return response()->json([
                    "count" => '-1'
                ]);
            }
        }
    } 

    public function verificarSucTodos(Request $request)
    {

        if ($request->ajax()) {
            $distinto = 0;

            $trabajadores   = json_decode($request->input('trabajadores'));
            $sucursales     = json_decode($request->input('sucursales'));

            foreach ($trabajadores as $value) {
                $suc_trabajador = Trabajador::find($value->rut)->sucursales_codigo;
                foreach ($sucursales as $valor) {
                    if($valor->sucursales != $suc_trabajador){
                        $distinto = $distinto + 1;
                    }
                }
            }

            if($distinto > 0){
                return response()->json([
                    "count" => $distinto
                ]);
            }else{
                return response()->json([
                    "count" => '-1'
                ]);
            }
        }
    }    
}
