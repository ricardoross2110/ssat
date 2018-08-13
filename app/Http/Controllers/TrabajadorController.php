<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use App\Trabajador;
use App\Sucursal;
use App\Fecha;
use App\Version;
use App\Evaluacion;
use App\Empresa;
use App\CentroCosto;
use App\Cargo;
use App\User;
use App\Log;
Use App\Notificacion;
Use App\CursoTrabajador;
Use App\Instructor;
use Auth;
use PDF;
use Illuminate\Validation\Rule;
use Storage;
use Malahierba\ChileRut\ChileRut;
use Illuminate\Support\Facades\DB;

class TrabajadorController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $rut = \Request::get('rutB');
        $apellido_paterno = \Request::get('apellido_paternoB');

        if (Auth::user()->rol_select == 'jefatura') {
            $rut_jefatura = Auth::user()->trabajadores_rut;
        }else{
            $rut_jefatura = \Request::get('rut_jefaturaB');
        }

        $wwid        = \Request::get('wwidB');
        $pid         = \Request::get('pidB');
        $cargo       = \Request::get('cargoB');
        $centroCosto = \Request::get('centroCostoB');
        $sucursal    = \Request::get('sucursalB');
        $empresa     = \Request::get('empresaB');
        $estado      = \Request::get('estadoB');
        
        if (!is_null($rut)) {
            $rut = str_replace('k', 'K', $rut);
        }
        
        if (!is_null($rut_jefatura)) {
            $rut_jefatura = str_replace('k', 'K', $rut_jefatura);
        }
        //dd('empresa:'.$empresa);

        $trabajador = DB::table('trabajadores')->select('trabajadores.*');

        if (Auth::user()->rol_select == 'instructor') {
            $trabajador = $trabajador
                            ->join('curso_trabajador', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                            ->join('versiones', 'curso_trabajador.versiones_id', '=', 'versiones.id')
                            ->join('curso_instructor', 'curso_instructor.id', '=', 'versiones.curso_instructor_id');
        }

        if (Auth::user()->rol_select != 'jefatura') {
            if(!is_null($estado) && $estado <> 2){
                $trabajador = $trabajador->where('estado', '=', $estado);
            }

            if(!is_null($rut)){
                $trabajador = $trabajador->where('rut','=', $rut);
                /*if (strpos($rut, 'k') || strpos($rut, 'K')) {
                    if (strpos($rut, 'k')) {
                        $trabajador = $trabajador->where(function ($q) use ($rut) {
                            $q = $q->orWhere('rut', '=', $rut);
                            $q = $q->orWhere('rut', '=', str_replace('k', 'K', $rut));
                        });
                    }elseif(strpos($rut, 'K')) {
                        $trabajador = $trabajador->where(function ($q) use ($rut) {
                            $q = $q->orWhere('rut', '=', $rut);
                            $q = $q->orWhere('rut', '=', str_replace('K', 'k', $rut));
                        });
                    }
                }else{
                    $trabajador = $trabajador->where('rut','=', $rut);
                }*/
            }

            if(!is_null($apellido_paterno)){
                $trabajador = $trabajador->where('apellido_paterno','like','%'.$apellido_paterno.'%');
            }

            if(!is_null($wwid)){
                $trabajador = $trabajador->where('wwid','like','%'.$wwid.'%');
            }

            if(!is_null($pid)){
                $trabajador = $trabajador->where('pid','=',$pid);
            }
            
            if(!is_null($cargo)){
                $trabajador = $trabajador->where('trabajadores.cargos_codigo','=',$cargo);
            }

            if(!is_null($centroCosto)){
                $trabajador = $trabajador->where('trabajadores.centrosCostos_codigo','=',$centroCosto);
            }

            if(!is_null($sucursal)){
                $trabajador = $trabajador->where('trabajadores.sucursales_codigo','=',$sucursal);
            }

            if(!is_null($empresa)){
                $trabajador = $trabajador->where('trabajadores.empresas_id','=', $empresa);
            }
            
            if (Auth::user()->rol_select == 'instructor') {
                $trabajador = $trabajador->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut);
            }
        }

        if(!is_null($rut_jefatura)){
            $trabajador = $trabajador->where('rut_jefatura','=', $rut_jefatura);
            /*if (strpos($rut_jefatura, 'k') || strpos($rut_jefatura, 'K')) {
                if (strpos($rut_jefatura, 'k')) {
                    $trabajador = $trabajador->where(function ($q) use ($rut_jefatura) {
                        $q = $q->orWhere('rut_jefatura', '=', $rut_jefatura);
                        $q = $q->orWhere('rut_jefatura', '=', str_replace('k', 'K', $rut_jefatura));
                    });
                }elseif(strpos($rut_jefatura, 'K')) {
                    $trabajador = $trabajador->where(function ($q) use ($rut_jefatura) {
                        $q = $q->orWhere('rut_jefatura', '=', $rut_jefatura);
                        $q = $q->orWhere('rut_jefatura', '=', str_replace('K', 'k', $rut_jefatura));
                    });
                }
            }else{
                $trabajador = $trabajador->where('rut_jefatura','=', $rut_jefatura);
            }*/
        }

        $trabajadores = $trabajador->orderBy('apellido_paterno', 'asc')->groupBy('trabajadores.rut')->get();

        foreach ($trabajadores as $trabajador) {
            $trabajador->estado = ($trabajador->estado) ? "Activo" : "Inactivo" ;
        }

        if (Auth::user()->rol_select == 'jefatura'){
            $colaboradores = ArbolColaboradores($trabajadores, true);
            $colaboradores = array_values_recursive($colaboradores);

            foreach ($colaboradores as $key => $value) {
                foreach ($value as $key2 => $colaborador) {
                    $delete = 0;

                    if(!is_null($rut)){
                        $existe = stripos($colaboradores[$key][$key2]->rut, "K");

                        if ($existe !== false) {
                            $rutKMayuscula=str_replace('k', 'K', $rut);
                            $rutKMinuscula=str_replace('K', 'k', $rut);
                            if(($colaboradores[$key][$key2]->rut != $rutKMayuscula) && ($colaboradores[$key][$key2]->rut != $rutKMinuscula)){
                                $delete = 1;
                            }
                        }else{
                            if($colaboradores[$key][$key2]->rut != $rut){
                                $delete = 1;
                            }
                        }

                    }

                    if(!is_null($apellido_paterno)){
                        $existe = stripos($colaboradores[$key][$key2]->apellido_paterno, $apellido_paterno);
                        if ($existe === false) {
                            $delete = 1;
                        }
                    }

                    if(!is_null($wwid)){
                        if($colaboradores[$key][$key2]->wwid != $wwid){
                            $delete = 1;
                        }
                    }

                    if(!is_null($pid)){
                        if($colaboradores[$key][$key2]->pid != $pid){
                            $delete = 1;
                        }
                    }
                    
                    if(!is_null($cargo)){
                        if($colaboradores[$key][$key2]->cargos_codigo != $cargo){
                            $delete = 1;
                        }
                    }

                    if(!is_null($centroCosto)){
                        if($colaboradores[$key][$key2]->centrosCostos_codigo != $centroCosto){
                            $delete = 1;
                        }
                    }

                    if(!is_null($sucursal)){
                        if($colaboradores[$key][$key2]->sucursales_codigo != $sucursal){
                            $delete = 1;
                        }
                    }

                    if(!is_null($empresa)){
                        if($colaboradores[$key][$key2]->empresas_id != $empresa){
                            $delete = 1;
                        }
                    }

                    if(!is_null($estado) && $estado <> 2){
                        if ($estado == 1) {
                            if($colaboradores[$key][$key2]->estado != "Activo"){
                                $delete = 1;
                            }
                        }else{
                            if($colaboradores[$key][$key2]->estado != "Inactivo"){
                                $delete = 1;
                            }
                        }
                    }

                    if ($delete === 1) {
                        unset($colaboradores[$key][$key2]);
                    }

                }
            }
        }
        
        $centrosCostos = [];

        foreach(CentroCosto::orderBy('nombre', 'asc')->get() as $centroCosto):
            $centrosCostos[$centroCosto->codigo] = $centroCosto->nombre;
        endforeach;

        $sucursales = [];

        foreach(Sucursal::orderBy('nombre', 'asc')->get() as $sucursal):
            $sucursales[$sucursal->codigo] = $sucursal->nombre;
        endforeach;

        $empresas = [];

        foreach(Empresa::orderBy('nombre', 'asc')->get() as $empresa):
            $empresas[$empresa->id] = $empresa->nombre;
        endforeach;

        $cargos = [];

        foreach(Cargo::orderBy('nombre', 'asc')->get() as $cargo):
            $cargos[$cargo->codigo] = $cargo->nombre;
        endforeach;

        //$sucursales2 = Sucursal::where('sucursales.vigencia','=',true)->get();
        $sucursales2 = [];

        foreach(Sucursal::where('sucursales.vigencia','=',true)->orderBy('nombre', 'asc')->get() as $sucursal):
            $sucursales2[$sucursal->codigo] = $sucursal->nombre;
        endforeach;


        $rutB              = \Request::get('rutB');
        $apellido_paternoB = \Request::get('apellido_paternoB');

        if (Auth::user()->rol_select == 'jefatura') {
            $rut_jefaturaB = Auth::user()->trabajadores_rut;
        }else{
            $rut_jefaturaB = \Request::get('rut_jefaturaB');
        }

        $wwidB        = \Request::get('wwidB');
        $pidB         = \Request::get('pidB');
        $cargoB       = \Request::get('cargoB');
        $centroCostoB = \Request::get('centroCostoB');
        $sucursalB    = \Request::get('sucursalB');
        $empresaB     = \Request::get('empresaB');

        if (Auth::user()->rol_select == 'jefatura'){
            return view('trabajadores.index')
                ->with('trabajadores', $trabajadores)
                ->with(array('centrosCostos' => $centrosCostos))
                ->with(array('sucursales' => $sucursales))
                ->with(array('empresas' => $empresas))
                ->with(array('cargos' => $cargos))
                ->with('rutB', $rutB)
                ->with('apellido_paternoB', $apellido_paternoB)
                ->with('rut_jefaturaB', $rut_jefaturaB)
                ->with('wwidB', $wwidB)
                ->with('pidB', $pidB)
                ->with('cargoB', $cargoB)
                ->with('centroCostoB', $centroCostoB)
                ->with('sucursalB', $sucursalB)
                ->with('empresaB', $empresaB)
                ->with('estadoB', $estado)
                ->with('colaboradores', $colaboradores)
                ->with(array('sucursales2' => $sucursales2));
        }else{
            return view('trabajadores.index')
                ->with('trabajadores', $trabajadores)
                ->with(array('centrosCostos' => $centrosCostos))
                ->with(array('sucursales' => $sucursales))
                ->with(array('empresas' => $empresas))
                ->with(array('cargos' => $cargos))
                ->with('rutB', $rutB)
                ->with('apellido_paternoB', $apellido_paternoB)
                ->with('rut_jefaturaB', $rut_jefaturaB)
                ->with('wwidB', $wwidB)
                ->with('pidB', $pidB)
                ->with('cargoB', $cargoB)
                ->with('centroCostoB', $centroCostoB)
                ->with('sucursalB', $sucursalB)
                ->with('empresaB', $empresaB)
                ->with('estadoB', $estado)
                ->with(array('sucursales2' => $sucursales2));
        }

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $items = Trabajador::all('rut', 'nombres');

        $sucursales = Sucursal::where('sucursales.vigencia','=',true)->get();

        return view('trabajadores.index', compact('items',$items), compact('sucursales',$sucursales));
  
       // return view('trabajadores.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {

        if(empty($request->input('rut'))){
            return redirect()->route('trabajadores.index')->withInput()->with('error','Debe ingresar un rut');
        }elseif(empty($request->input('nombres'))){
            return redirect()->route('trabajadores.index')->withInput()->with('error','Debe ingresar un nombre');
        }elseif(empty($request->input('apellido_paterno'))){
            return redirect()->route('trabajadores.index')->withInput()->with('error','Debe ingresar un apellido paterno');
        }elseif(empty($request->input('apellido_materno'))){
            return redirect()->route('trabajadores.index')->withInput()->with('error','Debe ingresar un apellido materno');
        }

        if($request->input('browser') == 0){ //explorer
            $fecha_nacimiento       = $request->input('fecha_nacimiento');
            $fecha_ingreso          = $request->input('fecha_ingreso');
        }else if(\Request::get('browser') == 1){ //chrome
            $fecha_nacimiento       = $request->input('fecha_nacimientoChrome');
            $fecha_ingreso          = $request->input('fecha_ingresoChrome');
        }

        $correo = $request->input('email');
        $resp = false;
        if (filter_var($correo, FILTER_VALIDATE_EMAIL))
        {
            $resp = true;
        }
        if($resp == false){
            return redirect()->route('trabajadores.index')->withInput()->with('error','Correo incorrecto');
        }

        //$this->validate($request, ['rut' => 'unique:trabajadores,rut',$request->input('rut')], );
        $rules = ['rut' => 'unique:trabajadores,rut',$request->input('rut')];
        $this->validate($request,$rules);

        $rut = new ChileRut();
        $rutValido = $rut->check($request->input('rut'));

        $rutJefe = new ChileRut();
        $rutJefeValido = $rutJefe->check($request->input('rut_jefatura'));

        if ($rutValido){
            if ($rutJefeValido){
                $counter = User::where('email','=',$request->input('email'))->count();

                if($counter == 0){
                    $rut = str_replace('k', 'K', $request->input('rut'));

                    $trabajador = new Trabajador;
                    $trabajador->rut = $rut;
                    $trabajador->fecha_nacimiento = $fecha_nacimiento;
                    $trabajador->genero = $request->input('genero');
                    $trabajador->centrosCostos_codigo = $request->input('centroCosto');
                    $trabajador->cargos_codigo = $request->input('cargo');
                    $trabajador->sucursales_codigo = $request->input('sucursal');
                    $trabajador->empresas_id = $request->input('empresa');
                    $trabajador->wwid = $request->input('wwid');
                    $trabajador->pid = $request->input('pid');
                    $trabajador->fecha_ingreso = $fecha_ingreso;
                    $trabajador->rut_jefatura = $request->input('rut_jefatura');

                    $count = mb_strlen($request->input('nombres'), 'UTF-8');
                    if($count > 25){
                        return back()->with('error', 'Nombres demasiado largo, el máximo permitido es 25 caracteres');
                    }else{
                        $trabajador->nombres = $request->input('nombres');
                    }

                    $count = mb_strlen($request->input('apellido_paterno'), 'UTF-8');
                    if($count > 25){
                        return back()->with('error', 'Apellido paterno demasiado largo, el máximo permitido es 25 caracteres');
                    }else{
                        $trabajador->apellido_paterno = $request->input('apellido_paterno');
                    }

                    $count = mb_strlen($request->input('apellido_materno'), 'UTF-8');
                    if($count > 25){
                        return back()->with('error', 'Apellido materno demasiado largo, el máximo permitido es 25 caracteres');
                    }else{
                        $trabajador->apellido_materno = $request->input('apellido_materno');
                    }

                    $count = mb_strlen($request->input('email'), 'UTF-8');
                    if($count > 100){
                        return back()->with('error', 'Email demasiado largo, el máximo permitido es 100 caracteres');
                    }else{
                        $trabajador->email = $request->input('email');
                    }


                    $password  = $request->input('contrasena');
                    
                    if ( !is_null($request->input('ocultar')) ){

                        if(!is_null($password)){

                            $resp = false;
                            if (preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[.])([A-Za-z\d.]|[^ ]){6,25}$/",$password))
                            {
                                $resp = true;
                            }
                            if($resp == false){
                                return back()->with('error', 'Contrasena incorrecta');
                            }

                            $count = mb_strlen($password, 'UTF-8');
                            if($count > 25){
                                return back()->with('error', 'ContraseÃ±a demasiado larga, el mÃ¡ximo permitido es 25 caracteres');
                            }else{
                                $trabajador->contrasena = bcrypt($password);
                            }
                        }else{
                            return back()->with('error', 'Debe ingresar contraseña');
                        }

                    }else{
                        $mail = $request->input('email');
                        $password    = substr($mail, 0, stripos($mail, '@'));
                        $password    = ucfirst(substr($password, 0, 20)).date("Y");

                        $trabajador->contrasena = bcrypt($password);
                    }

                    /*
                    if(!is_null($password)){
                        $count = mb_strlen($password, 'UTF-8');
                        if($count > 25){
                            return back()->with('error', 'Contraseña demasiado larga, el máximo permitido es 25 caracteres');
                        }else{
                            $trabajador->contrasena = bcrypt($password);
                        }
                    }else{
                        $mail = $request->input('email');
                        $password    = substr($mail, 0, stripos($mail, '@'));
                        $password    = ucfirst(substr($password, 0, 20)).date("Y");

                        $trabajador->contrasena = bcrypt($password);
                    }
                    */

                    if ( is_null($request->input('estado')) ){
                        $trabajador->estado  = 0;
                    }else{
                        $trabajador->estado  = $request->input('estado');
                    }

                    $trabajador->save();

                    /*$rules1 = ['email' => 'unique:users,email',$request->input('email')];
                    $this->validate($request,$rules1);*/

                    $user = new User;
                    //$user->id = $request->input('id');
                    $user->name = $request->input('nombres').' '.$request->input('apellido_paterno').' '.$request->input('apellido_materno');
                    $user->email = $request->input('email');
                    $user->password = bcrypt($password);
                    $user->remember_token = ' ';
                    if($trabajador->estado == 1){
                        $user->rol = 'alumno';
                    }
                    $user->trabajadores_rut = $request->input('rut');
                    $user->save();

                    $jefe   = User::select('id', 'name')->where('trabajadores_rut', '=', $request->input('rut_jefatura'))->get();

                    $timeZone = 'America/Santiago'; 
                    date_default_timezone_set($timeZone); 
                    $now = date_create();

                    if($request->input("genero") == 'M'){
                        $genero = 'Masculino';
                    }else {
                        $genero = 'Femenino';
                    }

                    $nombres = utf8_encode($request->input("nombres"));
                    $ap_pat = utf8_encode($request->input("apellido_paterno"));
                    $ap_mat = utf8_encode($request->input("apellido_materno"));

              //      $texto = "Se te asignó un nuevo trabajador, Rut: ". $request->input("rut").", Nombre: ".$request->input("nombres")." ".$request->input("apellido_paterno")." ".$request->input("apellido_materno").", Correo: ".$request->input("email").", Genero:  ".$request->input("genero").", Centro de Costos: ". $request->input("centroCosto").", Cargo: ".$request->input("cargo").", Fecha de Ingreso: ".$fecha_ingreso.".";

                    $texto2 = "Se te asignó un nuevo trabajador, Rut: ". $request->input("rut");

                    $texto3 = ", Nombre: ".$nombres." ".$ap_pat." ".$ap_mat.", Correo: ".$request->input("email").", Genero:  ".$genero.", Centro de Costos: ". $request->input("centroCosto").", Cargo: ".$request->input("cargo").", Fecha de Ingreso: ".$fecha_ingreso.".";

                    if(count($jefe) > 0){
                        $notificacion           = new Notificacion;
                 //       $notificacion->texto    = utf8_encode($texto);
                        $notificacion->texto    = utf8_encode($texto2)." ".utf8_decode($texto3);
                        $notificacion->fecha    = date_format($now, 'Y-m-d H:i:s');
                        $notificacion->titulo   = 'Tienes un nuevo trabajador.';
                        $notificacion->url      = '/trabajadores/'.$request->input('rut');
                        $notificacion->visto    = false;
                        $notificacion->admin    = false;
                        $notificacion->rol      = 'Jefatura';
                        $notificacion->users_id = $jefe[0]->id;
                        $notificacion->save();
                    }

                    $Usuario            = User::where('trabajadores_rut', '=', $request->input('rut_jefatura'))->get();
                    $cadenanueva        = [];
                    $cadenadividida     = [];
                    $agregado           = 0;
                    $agregadoJef        = 0;
                    $roles              = null;
                    $contador           = 0;
                    if (count($Usuario) > 0) {
                        foreach ($Usuario as $key => $value) {
                            $usuario = User::find($value->id);
                            if($value->rol != ''){
                                $cadenadividida = explode(',', $value->rol);
                            }
                            if(count($cadenadividida)>0){
                                foreach ($cadenadividida as $key => $value) {
                                    if($value != 'jefatura'){
                                        array_push($cadenanueva,$value);
                                    }else if($value == 'jefatura' && $agregadoJef == 0){
                                        array_push($cadenanueva,'jefatura');
                                        $agregadoJef = 1;
                                        $contador = $contador + 1;
                                    }else{                                        
                                        $contador = $contador + 1;
                                    }
                                }
                            }
                            if($contador == 0){
                                array_push($cadenanueva,'jefatura');
                            }
                            if(count($cadenanueva)>0){
                                foreach ($cadenanueva as $key => $valor) {
                                    if($agregado == 0){
                                        $roles = $valor;
                                        $agregado = 1;
                                    }
                                    else{
                                        $roles = $roles.','.$valor;
                                    }
                                }
                            }
                            $usuario->rol = $roles;
                            $usuario->save();
                        }
                    }
                
                    //Guarda log al crear
                    Log::create([
                        'id_user' => Auth::user()->id,
                        'action' => "Agregar Trabajador",
                        'details' => "Se crea Trabajador: " . $request->input('nombres'),
                    ]);

                    //return redirect()->route('trabajadores.index');
                    return redirect()->route('trabajadores.index')->with('success','Trabajador creado correctamente');
                }else{
                    return redirect()->route('trabajadores.index')->with('error','Email ya existe favor ingrese nuevamente.');
                }
            }else{
                return redirect()->route('trabajadores.index')->with('error','Rut jefatura incorrecto, favor ingrese nuevamente.');
            }
        }else{
            return redirect()->route('trabajadores.index')->with('error','Rut incorrecto, favor ingrese nuevamente.');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($rut)
    {
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
        $cursos = CursoTrabajador::where('trabajadores_rut','=', $rut)->get();

        //Verificar rol
        if (Auth::user()->rol_select == 'admin') {
            return view('trabajadores.show')
                                            ->with('trabajador',$trabajador)
                                            ->with('cursos', $cursos);
        }else{
            if (Auth::user()->rol_select == 'instructor') {
                $programas = Version::select('programas.*')
                                    ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                    ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                                    ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                    ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                                    ->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut)
                                    ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                                    ->groupBy('programas.codigo')
                                    ->get();
            }else{
                $programas = Version::select('programas.*')
                                    ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                                    ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                                    ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                                    ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                                    ->groupBy('programas.codigo')
                                    ->get();
            }

            $cursos             = [];
            $asistencias        = [];
            $evaluaciones       = [];
            $notas              = [];
            $notafinal          = [];
            $diasasistidos      = [];
            $promedioaistencias = [];
            $cursotrabajador    = [];
            $evaluacionesRepechaje = [];

            foreach ($programas as $key => $value) {
                $cursos[$value->codigo] = Version::select('versiones.situacion','cursos.codigo', 'cursos.nombre', 'cursos.aprobacion_minima', 'cursos.repechaje', 'curso_trabajador.*')
                ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                ->where('programas.codigo', '=', $value->codigo)
                ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                ->groupBy('cursos.codigo', 'versiones.situacion', 'curso_trabajador.id')
                ->get();
            }

            foreach ($programas as $key => $value) {
                $notaprograma = 0;
                foreach ($cursos[$value->codigo] as $key2 => $value2) {
                    $asistencias[$value->codigo][$value2->codigo] = Version::select('asistencias.id', 'asistencias.fecha', 'asistencias.estado')
                    ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->join('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                    ->where('versiones.programas_codigo', '=', $value->codigo)
                    ->where('versiones.cursos_codigo', '=', $value2->codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                    ->get();

                    $diasasistidos[$value->codigo][$value2->codigo] = Version::select('asistencias.id', 'asistencias.fecha', 'asistencias.estado')
                    ->join('curso_trabajador', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->join('asistencias', 'curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                    ->where('estado', '=', true)
                    ->where('versiones.programas_codigo', '=', $value->codigo)
                    ->where('versiones.cursos_codigo', '=', $value2->codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                    ->get();

                    $evaluaciones[$value->codigo][$value2->codigo] = Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'notas.resultado')
                    ->join('notas', 'evaluaciones.id', '=', 'notas.evaluaciones_id')
                    ->join('curso_trabajador', 'curso_trabajador.id', '=', 'notas.curso_trabajador_id')
                    ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->where('versiones.programas_codigo', '=', $value->codigo)
                    ->where('versiones.cursos_codigo', '=', $value2->codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                    ->orderBy('evaluaciones.id')
                    ->get();

                    $evaluacionesRepechaje[$value->codigo][$value2->codigo] =Evaluacion::select('evaluaciones.id', 'evaluaciones.nombre', 'evaluaciones.porcentaje', 'repechaje.resultado')
                    ->join('repechaje', 'evaluaciones.id', '=', 'repechaje.evaluaciones_id')
                    ->join('curso_trabajador', 'curso_trabajador.id', '=', 'repechaje.curso_trabajador_id')
                    ->join('versiones', 'versiones.id', '=', 'curso_trabajador.versiones_id')
                    ->where('versiones.programas_codigo', '=', $value->codigo)
                    ->where('versiones.cursos_codigo', '=', $value2->codigo)
                    ->where('curso_trabajador.trabajadores_rut', '=', $rut)
                    ->orderBy('evaluaciones.id')
                    ->get();

                    if($value2->situacion == 'Con Evaluación'){
                        $value2->situacion = 'Con evaluación';
                    }elseif($value2->situacion == "Con Asistencia"){
                        $value2->situacion = "Con asistencia";
                    } 

                    $notafinal[$value->codigo][$value2->codigo] = 0;
                    if(count($evaluaciones[$value->codigo][$value2->codigo]) > 0){
                        $notafinal[$value->codigo][$value2->codigo] = 0;
                        foreach ($evaluaciones[$value->codigo][$value2->codigo] as $key3 => $value3) {
                            $notafinal[$value->codigo][$value2->codigo] = $notafinal[$value->codigo][$value2->codigo] + $value3->resultado * ($value3->porcentaje / 100);
                        }
                        $notaprograma = $notaprograma + $notafinal[$value->codigo][$value2->codigo];
                    }

                    if (count($asistencias[$value->codigo][$value2->codigo]) > 0) {
                         $promedioaistencias[$value->codigo][$value2->codigo] = (count($diasasistidos[$value->codigo][$value2->codigo]) * 100) / count($asistencias[$value->codigo][$value2->codigo]);             
                    } else {
                        $promedioaistencias[$value->codigo][$value2->codigo] = -1;
                    }
                }
            }

            return view('trabajadores.ver_trabajador')->with('trabajador', $trabajador)
                                                      ->with('cursos', $cursos)
                                                      ->with('programas', $programas)
                                                      ->with('asistencias', $asistencias)
                                                      ->with('evaluacionesRepechaje', $evaluacionesRepechaje)
                                                      ->with('evaluaciones', $evaluaciones)
                                                      ->with('notafinal', $notafinal)
                                                      ->with('promedioaistencias', $promedioaistencias);
        }        //Para mostrar los cursos del trabajador
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($rut)
    {
        $trabajador = Trabajador::find($rut);

        if ( $trabajador->estado == 1 ){
            $trabajador->estado = "true";
        }else{
            $trabajador->estado = "false";
        }

        //dd("pass:".$trabajador->contrasena);
        $centrosCostos = CentroCosto::orderBy('nombre', 'asc')->get();

        //$sucursales = Sucursal::orderBy('nombre', 'asc')->get();
        $sucursales = Sucursal::where('sucursales.vigencia','=',true)->orderBy('nombre', 'asc')->get();

        $empresas = Empresa::orderBy('nombre', 'asc')->get();

        $cargos = Cargo::orderBy('nombre', 'asc')->get();

        return view('trabajadores.edit', compact('trabajador', 'centrosCostos', 'sucursales', 'empresas', 'cargos'));

        //return view('trabajadores.edit')->with('trabajador',$trabajador)->with(array('centrosCostos' => $centrosCostos))->with(array('sucursales' => $sucursales))->with(array('empresas' => $empresas))->with(array('cargos' => $cargos));

    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $rut)
    {
        if(empty($request->input('rut'))){
            return redirect()->route('trabajadores.edit', $rut)->with('error','Debe ingresar un rut');
        }elseif(empty($request->input('nombres'))){
            return redirect()->route('trabajadores.edit', $rut)->with('error','Debe ingresar un nombre');
        }elseif(empty($request->input('apellido_paterno'))){
            return redirect()->route('trabajadores.edit', $rut)->with('error','Debe ingresar un apellido paterno');
        }elseif(empty($request->input('apellido_materno'))){
            return redirect()->route('trabajadores.edit', $rut)->with('error','Debe ingresar un apellido materno');
        }


        $rutJefe = new ChileRut();
        $rutJefeValido = $rutJefe->check($request->input('rut_jefatura'));

        if ($rutJefeValido){
            $trabajador = Trabajador::find($rut);    
            $nombresAntiguo = $trabajador->nombres;
            $trabajador->fecha_nacimiento = $request->input('fecha_nacimiento');
            $trabajador->genero = $request->input('genero');
            $trabajador->centrosCostos_codigo = $request->input('centroCosto');
            $trabajador->cargos_codigo = $request->input('cargo');
            $trabajador->sucursales_codigo = $request->input('sucursal');
            $trabajador->empresas_id = $request->input('empresa');
            $trabajador->wwid = $request->input('wwid');
            $trabajador->pid = $request->input('pid');
            $trabajador->fecha_ingreso = $request->input('fecha_ingreso');
            $trabajador->rut_jefatura = $request->input('rut_jefatura');

            $count = mb_strlen($request->input('nombres'), 'UTF-8');
            if($count > 25){
                return back()->with('error', 'Nombres demasiado largo, el máximo permitido es 25 caracteres');
            }else{
                $trabajador->nombres = $request->input('nombres');
            }

            $count = mb_strlen($request->input('apellido_paterno'), 'UTF-8');
            if($count > 25){
                return back()->with('error', 'Apellido paterno demasiado largo, el máximo permitido es 25 caracteres');
            }else{
                $trabajador->apellido_paterno = $request->input('apellido_paterno');
            }

            $count = mb_strlen($request->input('apellido_materno'), 'UTF-8');
            if($count > 25){
                return back()->with('error', 'Apellido materno demasiado largo, el máximo permitido es 25 caracteres');
            }else{
                $trabajador->apellido_materno = $request->input('apellido_materno');
            }

            $count = mb_strlen($request->input('email'), 'UTF-8');
            if($count > 100){
                return back()->with('error', 'Email demasiado largo, el máximo permitido es 100 caracteres');
            }else{
                $trabajador->email = $request->input('email');
            }

            $password  = $request->input('contrasena');
            //dd($password);
            
            if ( !is_null($request->input('ocultar')) ){

                if(!is_null($password)){

                    $resp = false;
                    if (preg_match("/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[.])([A-Za-z\d.]|[^ ]){6,25}$/",$password))
                    {
                        $resp = true;
                    }
                    if($resp == false){
                        return redirect()->route('trabajadores.index')->withInput()->with('error','Password incorrecta');
                    }

                    $count = mb_strlen($password, 'UTF-8');
                    if($count > 25){
                        return back()->with('error', 'Contraseña demasiado larga, el máximo permitido es 25 caracteres');
                    }else{
                        $trabajador->contrasena = bcrypt($password);
                    }
                }else{
                    return back()->with('error', 'Debe ingresar contraseña');
                }

            }


            if ( is_null($request->input('estado')) ){
                $trabajador->estado  = 0;
            }else{
                $trabajador->estado  = $request->input('estado');
            }

            $trabajador->save();

            $id = User::where('trabajadores_rut','=',$rut)->value('id');
            if(is_null($id)){
                $user = new User;
                $user->name = $request->input('nombres').' '.$request->input('apellido_paterno').' '.$request->input('apellido_materno');
                $user->email = $request->input('email');

                $password  = $request->input('contrasena');
                if(!is_null($password)){
                    $count = mb_strlen($password, 'UTF-8');
                    if($count > 25){
                        return back()->with('error', 'Contraseña demasiado larga, el máximo permitido es 25 caracteres');
                    }else{
                        $user->password = bcrypt($password);
                    }
                }

                $user->remember_token = ' ';
                if($trabajador->estado == 1){
                    $user->rol = 'alumno';
                }
                $user->trabajadores_rut = $rut;
                $user->save();

                $Usuario            = User::where('trabajadores_rut', '=', $request->input('rut_jefatura'))->get();
                $cadenanueva        = [];
                $cadenadividida     = [];
                $agregado           = 0;
                $agregadoJef        = 0;
                $roles              = null;
                $contador           = 0;
                if (count($Usuario) > 0) {
                    foreach ($Usuario as $key => $value) {
                        $usuario = User::find($value->id);
                        if($value->rol != ''){
                            $cadenadividida = explode(',', $value->rol);
                        }
                        if(count($cadenadividida)>0){
                            foreach ($cadenadividida as $key => $value) {
                                if($value != 'jefatura'){
                                    array_push($cadenanueva,$value);
                                }else if($value == 'jefatura' && $agregadoJef == 0){
                                    array_push($cadenanueva,'jefatura');
                                    $agregadoJef = 1;
                                    $contador = $contador + 1;
                                }else{                                        
                                    $contador = $contador + 1;
                                }
                            }
                        }
                        if($contador == 0){
                            array_push($cadenanueva,'jefatura');
                        }
                        if(count($cadenanueva)>0){
                            foreach ($cadenanueva as $key => $valor) {
                                if($agregado == 0){
                                    $roles = $valor;
                                    $agregado = 1;
                                }
                                else{
                                    $roles = $roles.','.$valor;
                                }
                            }
                        }
                        $usuario->rol = $roles;
                        $usuario->save();
                    }
                }
            }else{
                $Usuario            = User::where('trabajadores_rut', '=', $rut)->get();
                $cadenanueva        = [];
                $cadenadividida     = [];
                $agregado           = 0;
                $roles              = null;
                $contador           = 0;
                if (count($Usuario) > 0) {
                    if ($trabajador->estado == 1) {                        
                        foreach ($Usuario as $key => $value) {
                            $usuario = User::find($value->id);
                            if($value->rol != ''){
                                $cadenadividida = explode(',', $value->rol);
                            }
                            if(count($cadenadividida)>0){
                                foreach ($cadenadividida as $key => $value) {
                                    if($value != 'alumno'){
                                        array_push($cadenanueva,$value);
                                    }
                                    else{
                                        $contador = $contador + 1;
                                    }
                                }
                            }
                            if($contador == 0 || $contador == 1){
                                array_push($cadenanueva,'alumno');
                            }
                            if(count($cadenanueva)>0){
                                foreach ($cadenanueva as $key => $valor) {
                                    if($agregado == 0){
                                        $roles = $valor;
                                        $agregado = 1;
                                    }
                                    else{
                                        $roles = $roles.','.$valor;
                                    }
                                }
                            }
                            $usuario->name = $request->input('nombres').' '.$request->input('apellido_paterno').' '.$request->input('apellido_materno');
                            $usuario->email = $request->input('email');
                            
                            $password  = $request->input('contrasena');
                            if(!is_null($password)){
                                $count = mb_strlen($password, 'UTF-8');
                                if($count > 25){
                                    return back()->with('error', 'Contraseña demasiado larga, el máximo permitido es 25 caracteres');
                                }else{
                                    $usuario->password = bcrypt($password);
                                }
                            }
                            $usuario->rol = $roles;
                            $usuario->save();
                        }
                    }else{                         
                        foreach ($Usuario as $key => $value) {
                            $usuario = User::find($value->id);
                            if($value->rol != ''){
                                $cadenadividida = explode(',', $value->rol);
                            }
                            if(count($cadenadividida)>0){
                                foreach ($cadenadividida as $key => $value) {
                                    if($value != 'alumno'){
                                        array_push($cadenanueva,$value);
                                    }
                                }
                            }
                            if(count($cadenanueva)>0){
                                foreach ($cadenanueva as $key => $valor) {
                                    if($agregado == 0){
                                        $roles = $valor;
                                        $agregado = 1;
                                    }
                                    else{
                                        $roles = $roles.','.$valor;
                                    }
                                }
                            }
                            $usuario->name = $request->input('nombres').' '.$request->input('apellido_paterno').' '.$request->input('apellido_materno');
                            $usuario->email = $request->input('email');
                            
                            $password  = $request->input('contrasena');
                            if(!is_null($password)){
                                $count = mb_strlen($password, 'UTF-8');
                                if($count > 25){
                                    return back()->with('error', 'Contraseña demasiado larga, el máximo permitido es 25 caracteres');
                                }else{
                                    $usuario->password = bcrypt($password);
                                }
                            }
                            $usuario->rol = $roles;
                            $usuario->save();
                        }
                    }
                }
                //$user->save();

                $Usuario            = User::where('trabajadores_rut', '=', $request->input('rut_jefatura'))->get();
                $cadenanueva        = [];
                $cadenadividida     = [];
                $agregado           = 0;
                $agregadoJef        = 0;
                $roles              = null;
                $contador           = 0;
                if (count($Usuario) > 0) {
                    foreach ($Usuario as $key => $value) {
                        $usuario = User::find($value->id);
                        if($value->rol != ''){
                            $cadenadividida = explode(',', $value->rol);
                        }
                        if(count($cadenadividida)>0){
                            foreach ($cadenadividida as $key => $value) {
                                if($value != 'jefatura'){
                                    array_push($cadenanueva,$value);
                                }else if($value == 'jefatura' && $agregadoJef == 0){
                                    array_push($cadenanueva,'jefatura');
                                    $agregadoJef = 1;
                                    $contador = $contador + 1;
                                }else{                                        
                                    $contador = $contador + 1;
                                }
                            }
                        }
                        if($contador == 0){
                            array_push($cadenanueva,'jefatura');
                        }
                        if(count($cadenanueva)>0){
                            foreach ($cadenanueva as $key => $valor) {
                                if($agregado == 0){
                                    $roles = $valor;
                                    $agregado = 1;
                                }
                                else{
                                    $roles = $roles.','.$valor;
                                }
                            }
                        }
                        $usuario->rol = $roles;
                        $usuario->save();
                    }
                }
            }

            $idIns = Instructor::where('rut','=',$rut)->value('rut');
            if(is_null($idIns)){
               
            }else{
                $instructor = Instructor::find($idIns);
                $instructor->nombres = $request->input('nombres');
                $instructor->apellido_paterno = $request->input('apellido_paterno');
                $instructor->apellido_materno = $request->input('apellido_materno');
                $instructor->email = $request->input('email');
                $instructor->wwid = $request->input('wwid');
                $instructor->pid = $request->input('pid'); 

                if ( is_null($request->input('estado')) ){
                    $instructor->estado  = 0;
                }else{
                    $instructor->estado  = $request->input('estado');
                }

                $instructor->save();

                $Usuario            = User::where('trabajadores_rut', '=', $rut)->get();
                $cadenanueva        = [];
                $cadenadividida     = [];
                $agregado           = 0;
                $roles              = null;
                $contador           = 0;
                if (count($Usuario) > 0) {
                    if ($instructor->estado == 1) {
                        foreach ($Usuario as $key => $value) {
                            $usuario = User::find($value->id);
                            if($value->rol != ''){
                                $cadenadividida = explode(',', $value->rol);
                            }
                            if(count($cadenadividida)>0){
                                foreach ($cadenadividida as $key => $value) {
                                    if($value != 'instructor'){
                                        array_push($cadenanueva,$value);
                                    }
                                    else{
                                        $contador = $contador + 1;
                                    }
                                }
                            }
                            if($contador == 0 || $contador == 1){
                                array_push($cadenanueva,'instructor');
                            }
                            if(count($cadenanueva)>0){
                                foreach ($cadenanueva as $key => $valor) {
                                    if($agregado == 0){
                                        $roles = $valor;
                                        $agregado = 1;
                                    }
                                    else{
                                        $roles = $roles.','.$valor;
                                    }
                                }
                            }
                            $usuario->rol = $roles;
                            $usuario->save();
                        }
                    }else{                         
                        foreach ($Usuario as $key => $value) {
                            $usuario = User::find($value->id);
                            if($value->rol != ''){
                                $cadenadividida = explode(',', $value->rol);
                            }
                            if(count($cadenadividida)>0){
                                foreach ($cadenadividida as $key => $value) {
                                    if($value != 'instructor'){
                                        array_push($cadenanueva,$value);
                                    }
                                }
                            }
                            if(count($cadenanueva)>0){
                                foreach ($cadenanueva as $key => $valor) {
                                    if($agregado == 0){
                                        $roles = $valor;
                                        $agregado = 1;
                                    }
                                    else{
                                        $roles = $roles.','.$valor;
                                    }
                                }
                            }
                            $usuario->rol = $roles;
                            $usuario->save();
                        }
                    }
                }
            }

            //Guarda log al editar
            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Editar Trabajador",
                'details' => "Se edita Trabajador: " . $nombresAntiguo . " por: " . $request->input('nombres'),
            ]);

            return redirect()->route('trabajadores.index')->with('success','Trabajador editado correctamente');
        }else{
            return redirect()->route('trabajadores.index')->with('error','Rut Jefatura incorrecto, favor ingrese nuevamente.');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($rut)
    {

        $counter    = CursoTrabajador::where('trabajadores_rut','=',$rut)->count();
        $counter2   = Instructor::where('rut','=',$rut)->count();
        $counter3   = Trabajador::where('rut_jefatura','=',$rut)->count();
        $usuario    = User::select('id')->where('trabajadores_rut', '=', $rut)->get();
        $counter4   = Notificacion::where('users_id','=',$usuario[0]->id)->count();
        $rut_jefe   = Trabajador::select('rut_jefatura')->where('rut','=',$rut)->get();

        if ($counter <>'0' || $counter2 <>'0' || $counter3 <>'0' || $counter4 <>'0') {
            return redirect()->route('trabajadores.index')->with('error','No se puede eliminar el trabajador, existe dependencia con otros registros');
        }else {

            $user = User::where('trabajadores_rut','=',$rut);
            $user->delete();

            $trabajador = Trabajador::find($rut);
            $trabajador->delete();
            
            $cont_jefe  = Trabajador::where('rut_jefatura', '=', $rut_jefe[0]->rut_jefatura)->get();
            if(count($cont_jefe) == 0){
                $Usuario            = User::where('trabajadores_rut', '=', $rut_jefe[0]->rut_jefatura)->get();
                $cadenanueva        = [];
                $cadenadividida     = [];
                $agregado           = 0;
                $roles              = null;
                $contador           = 0;
                if (count($Usuario) > 0) {
                    foreach ($Usuario as $key => $value) {
                        $usuario = User::find($value->id);
                        if($value->rol != ''){
                            $cadenadividida = explode(',', $value->rol);
                        }
                        if(count($cadenadividida)>0){
                            foreach ($cadenadividida as $key => $value) {
                                if($value != 'jefatura'){
                                    array_push($cadenanueva,$value);
                                }
                            }
                        }
                        if(count($cadenanueva)>0){
                            foreach ($cadenanueva as $key => $valor) {
                                if($agregado == 0){
                                    $roles = $valor;
                                    $agregado = 1;
                                }
                                else{
                                    $roles = $roles.','.$valor;
                                }
                            }
                        }
                        $usuario->rol = $roles;
                        $usuario->save();
                    }
                }
            }

            //Guarda log al eliminar
            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Eliminar Trabajador",
                'details' => "Se elimina Trabajador: " . $rut,
            ]);

            return redirect()->route('trabajadores.index')->with('success','Trabajador eliminado correctamente');
        }
    }

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $rut                = $request->input('rutExcel');
        $apellido_paterno   = $request->input('apellido_paternoExcel'); 

        if (Auth::user()->rol_select == 'jefatura') {
            $rut_jefatura   = Auth::user()->trabajadores_rut;
        }else{
            $rut_jefatura   = $request->input('rut_jefaturaExcel');
        }

        $cargo              = $request->input('cargoExcel');
        $centroCosto        = $request->input('centroCostoExcel');
        $wwid               = $request->input('wwidExcel');
        $pid                = $request->input('pidExcel');
        $sucursal           = $request->input('sucursalExcel');
        $empresa            = $request->input('empresaExcel');     
        $estado             = $request->input('estadoExcel');   

        $trabajador = DB::table('trabajadores')->select('trabajadores.*');

        if (Auth::user()->rol_select == 'instructor') {
            $trabajador = $trabajador
                            ->join('curso_trabajador', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                            ->join('versiones', 'curso_trabajador.versiones_id', '=', 'versiones.id')
                            ->join('curso_instructor', 'curso_instructor.id', '=', 'versiones.curso_instructor_id');
        }

        if (Auth::user()->rol_select != 'jefatura') {
            if(!is_null($estado) && $estado <> 2){
                $trabajador = $trabajador->where('estado', '=', $estado);
            }

            if(!is_null($rut)){
                if (strpos($rut, 'k') || strpos($rut, 'K')) {
                    if (strpos($rut, 'k')) {
                        $trabajador = $trabajador->where(function ($q) use ($rut) {
                            $q = $q->orWhere('rut', '=', $rut);
                            $q = $q->orWhere('rut', '=', str_replace('k', 'K', $rut));
                        });
                    }elseif(strpos($rut, 'K')) {
                        $trabajador = $trabajador->where(function ($q) use ($rut) {
                            $q = $q->orWhere('rut', '=', $rut);
                            $q = $q->orWhere('rut', '=', str_replace('K', 'k', $rut));
                        });
                    }
                }else{
                    $trabajador = $trabajador->where('rut','=', $rut);
                }
            }

            if(!is_null($apellido_paterno)){
                $trabajador = $trabajador->where('apellido_paterno','like','%'.$apellido_paterno.'%');
            }

            if(!is_null($wwid)){
                $trabajador = $trabajador->where('wwid','like','%'.$wwid.'%');
            }

            if(!is_null($pid)){
                $trabajador = $trabajador->where('pid','=',$pid);
            }
            
            if(!is_null($cargo)){
                $trabajador = $trabajador->where('trabajadores.cargos_codigo','=',$cargo);
            }

            if(!is_null($centroCosto)){
                $trabajador = $trabajador->where('trabajadores.centrosCostos_codigo','=',$centroCosto);
            }

            if(!is_null($sucursal)){
                $trabajador = $trabajador->where('trabajadores.sucursales_codigo','=',$sucursal);
            }

            if(!is_null($empresa)){
                $trabajador = $trabajador->where('trabajadores.empresas_id','=', $empresa);
            }
            
            if (Auth::user()->rol_select == 'instructor') {
                $trabajador = $trabajador->where('curso_instructor.instructores_rut', '=', Auth::user()->trabajadores_rut);
            }
        }

        if(!is_null($rut_jefatura)){
            if (strpos($rut_jefatura, 'k') || strpos($rut_jefatura, 'K')) {
                if (strpos($rut_jefatura, 'k')) {
                    $trabajador = $trabajador->where(function ($q) use ($rut_jefatura) {
                        $q = $q->orWhere('rut_jefatura', '=', $rut_jefatura);
                        $q = $q->orWhere('rut_jefatura', '=', str_replace('k', 'K', $rut_jefatura));
                    });
                }elseif(strpos($rut_jefatura, 'K')) {
                    $trabajador = $trabajador->where(function ($q) use ($rut_jefatura) {
                        $q = $q->orWhere('rut_jefatura', '=', $rut_jefatura);
                        $q = $q->orWhere('rut_jefatura', '=', str_replace('K', 'k', $rut_jefatura));
                    });
                }
            }else{
                $trabajador = $trabajador->where('rut_jefatura','=', $rut_jefatura);
            }
        }

        $trabajadores = $trabajador->orderBy('apellido_paterno', 'asc')->groupBy('trabajadores.rut')->get();

        foreach ($trabajadores as $trabajador) {
            $trabajador->estado = ($trabajador->estado) ? "Activo" : "Inactivo" ;
        }

        if (Auth::user()->rol_select == 'jefatura'){
            $colaboradores = ArbolColaboradores($trabajadores, true);
            $colaboradores = array_values_recursive($colaboradores);

            foreach ($colaboradores as $key => $value) {
                foreach ($value as $key2 => $colaborador) {
                    $delete = 0;

                    if(!is_null($rut)){
                        $existe = stripos($colaboradores[$key][$key2]->rut, "K");

                        if ($existe !== false) {
                            $rutKMayuscula=str_replace('k', 'K', $rut);
                            $rutKMinuscula=str_replace('K', 'k', $rut);
                            if(($colaboradores[$key][$key2]->rut != $rutKMayuscula) && ($colaboradores[$key][$key2]->rut != $rutKMinuscula)){
                                $delete = 1;
                            }
                        }else{
                            if($colaboradores[$key][$key2]->rut != $rut){
                                $delete = 1;
                            }
                        }

                    }

                    if(!is_null($apellido_paterno)){
                        $existe = stripos($colaboradores[$key][$key2]->apellido_paterno, $apellido_paterno);
                        if ($existe === false) {
                            $delete = 1;
                        }
                    }

                    if(!is_null($wwid)){
                        if($colaboradores[$key][$key2]->wwid != $wwid){
                            $delete = 1;
                        }
                    }

                    if(!is_null($pid)){
                        if($colaboradores[$key][$key2]->pid != $pid){
                            $delete = 1;
                        }
                    }
                    
                    if(!is_null($cargo)){
                        if($colaboradores[$key][$key2]->cargos_codigo != $cargo){
                            $delete = 1;
                        }
                    }

                    if(!is_null($centroCosto)){
                        if($colaboradores[$key][$key2]->centrosCostos_codigo != $centroCosto){
                            $delete = 1;
                        }
                    }

                    if(!is_null($sucursal)){
                        if($colaboradores[$key][$key2]->sucursales_codigo != $sucursal){
                            $delete = 1;
                        }
                    }

                    if(!is_null($empresa)){
                        if($colaboradores[$key][$key2]->empresas_id != $empresa){
                            $delete = 1;
                        }
                    }

                    if(!is_null($estado) && $estado <> 2){
                        if ($estado == 1) {
                            if($colaboradores[$key][$key2]->estado != "Activo"){
                                $delete = 1;
                            }
                        }else{
                            if($colaboradores[$key][$key2]->estado != "Inactivo"){
                                $delete = 1;
                            }
                        }
                    }

                    if ($delete === 1) {
                        unset($colaboradores[$key][$key2]);
                    }

                }
            }
        }

        if (Auth::user()->rol_select == 'jefatura'){
             return Excel::create('ListadoColaboradores', function($excel) use ($colaboradores) {
                $excel->sheet('colaboradores', function($sheet) use ($colaboradores)
                {
                    $count = 2;
                    
                    $sheet->row(1, ['Rut', 'Nombres', 'Apellido paterno', 'Apellido materno', 'WWID', 'PID', 'Email', 'Estado']);
                    foreach ($colaboradores as $key => $value) {
                        foreach ($value as $key2 => $colaborador) {
                            $sheet->row($count, [$colaborador->rut, $colaborador->nombres, $colaborador->apellido_paterno, $colaborador->apellido_materno, $colaborador->wwid, $colaborador->pid, $colaborador->email, $colaborador->estado]);
                            $count = $count +1;
                        }
                    }
                });
            })->download('xlsx');
        }else{        
            return Excel::create('ListadoTrabajadores', function($excel) use ($trabajadores) {
                $excel->sheet('trabajadores', function($sheet) use ($trabajadores)
                {
                    $count = 2;
                    
                    $sheet->row(1, ['Rut', 'Nombres', 'Apellido paterno', 'Apellido materno', 'WWID', 'PID', 'Email', 'Estado']);
                    foreach ($trabajadores as $key => $value) {
                        $sheet->row($count, [$value->rut, $value->nombres, $value->apellido_paterno, $value->apellido_materno, $value->wwid, $value->pid, $value->email, $value->estado]);
                        $count = $count +1;
                    }
                });
            })->download('xlsx');
        }
    }

    //metodo para cargar data en tabla usando json
    /*public function data_trabajadores()
    {
        return Datatables::of(Trabajador::query())->make(true);
    }*/

    public function volver()
    {
        return view('trabajadores.index');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function saveImport(Request $request)
    {
        $trabajadores = Trabajador::all();
        foreach ($trabajadores as $trabajador) {
            $trabajador->carga_masiva = 0;
            $trabajador->save();
        }

        set_time_limit(0);
        //Se obtiene el campo file definido en el formulario
        $file = $request->file('file');
        $valor;

        //Valido si hay un archivo cargado
        if ($file == null) {
            return back()->with('error', 'No se ha cargado '. utf8_encode('ningún') .' archivo');
        }

        //Se obtiene el nombre del archivo
        $nombre = $file->getClientOriginalName();
     
        //se guarda un nuevo archivo en el disco local  
        \Storage::disk('trabajadores')->put($nombre,  \File::get($file));

        //Se obtiene ruta y nombre del archivo
        $path = storage_path('app/trabajadores/'.$nombre);

        Excel::selectSheetsByIndex(1)->load($path, function($reader) {
            //selectSheetsByIndex(1);//para seleccionar la hoja de Excel
            //Excel::setDelimiter('|')->load(...);

            $excel = $reader->get();

            if(!$excel->first()==null){
                $excelheader = $excel->first()->keys()->toArray();
                //dd($excelheader);

                if( stripos(serialize($excelheader), 'rut') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }elseif( stripos(serialize($excelheader), 'nombre') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }elseif( stripos(serialize($excelheader), 'email') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }elseif( stripos(serialize($excelheader), 'nac') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }elseif( stripos(serialize($excelheader), 'genero') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }elseif( stripos(serialize($excelheader), 'cc') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }elseif( stripos(serialize($excelheader), 'cargo') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }elseif( stripos(serialize($excelheader), 'codigo_sucursal') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }elseif( stripos(serialize($excelheader), 'soc') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                /*}elseif( stripos(serialize($excelheader), 'wwid') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }elseif( stripos(serialize($excelheader), 'pid') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                */
                }elseif( stripos(serialize($excelheader), 'fecha_ingreso') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }elseif( stripos(serialize($excelheader), 'rut_jefe') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }
                /*elseif( stripos(serialize($excelheader), 'estatus') == 0){
                    return back()->with('error', 'El archivo adjunto no tiene las columnas necesarias.');
                }*/

                $trabajadores = Trabajador::get();
                
                // iteracción
                $reader->each(function($temp) {
                //$reader->each(function($temp) {
                    $rutCorrecto = new ChileRut();
                    
                    //$rutPersona     = substr($temp->rut, strripos($temp->rut, ' ') + 1);
                    $rutPersona     = trim($temp->rut);
                    $rutP           = $rutCorrecto->check($rutPersona);                  

                    //$rutJefe        = substr($temp->rut_jefe, strripos($temp->rut_jefe, ' ') + 1);
                    $rutJefe        = trim($temp->rut_jefe);
                    $rutJ           = $rutCorrecto->check($rutJefe);

                    $nombreCompleto = $temp->nombre;

                    $ape_paterno    = '';
                    $ape_materno    = '';
                    $nombres        = '';

                    if ($nombreCompleto != null) {
                        $nombreDividido         = explode(',', $nombreCompleto);

                        //primer caso: viene apellido y nombre separados por una coma
                        if(count($nombreDividido) == 2){

                            foreach ($nombreDividido as $key => $value) {
                                $nom_ape[$key]  = $value;
                                $nom_ape[$key]  = trim($nom_ape[$key]);
                            }

                            //se separa los apellidos y se agrega el nombre
                            if(count($nom_ape) == 2){
                                $ape_divididos  = explode('  ', $nom_ape[0]);
                                $nombres        = $nom_ape[1];
                                foreach ($ape_divididos as $key => $valor) {
                                    $apellido[$key] = $valor;
                                }
                            }

                            if(count($apellido) == 2){
                                $ape_paterno    = $apellido[0];
                                $ape_materno    = $apellido[1];
                            }else if(count($apellido) == 1){
                                $ape_paterno    = $apellido[0];
                            }  

                        }
                        //en caso que solo venga lo anterior a la coma
                        else if (count($nombreDividido) == 1) {
                            $nombreCompleto     = trim($nombreCompleto);
                            $ape_divididos      = explode('  ', $nombreCompleto);
                            if(count($ape_divididos) > 0){
                                foreach ($ape_divididos as $key => $valor) {
                                    $apellido[$key] = $valor;
                                }

                                if(count($apellido) == 2){
                                    $ape_paterno    = $apellido[0];
                                    $ape_materno    = $apellido[1];
                                }else if(count($apellido) == 1){
                                    $ape_paterno    = $apellido[0];
                                }
                            }
                        }

                    }

                    //$apellidos      = substr($nombreCompleto, 0, stripos($nombreCompleto, ','));
                    //$ape_paterno    = substr($apellidos, 0, stripos($apellidos, ' '));
                    //$ape_materno    = substr($apellidos, strripos($apellidos, ' ') + 1);
                    //$nombres        = substr($nombreCompleto, stripos($nombreCompleto, ',') + 2);
                    
                    $password       = substr($temp->email, 0, stripos($temp->email, '@'));
                    $password       = ucfirst(substr($password, 0, 20)).date("Y");

                    $trabTabla      = Trabajador::find($rutPersona);
                    $count          = count($trabTabla);

                    $centroCosto    = 0;
                    $cargo          = 0;
                    $codigoSucursal = 0;
                    $empresa        = 0;  
                    $mailExiste     = 0;                  

                    if($temp->cc != null){
                        $centroCosto = CentroCosto::find($temp->cc);
                    }

                    if($temp->cargo != null){
                        $cargo = Cargo::find($temp->cargo);
                    }

                    if($temp->codigo_sucursal != null){
                        $codigoSucursal = Sucursal::find($temp->codigo_sucursal);
                    }

                    if($temp->soc != null){
                        $empresa = Empresa::find($temp->soc);
                    }

                    if($temp->email != null){
                        $mailExiste = User::where('email','=', $temp->email)
                            ->where('trabajadores_rut','<>', $rutPersona)
                            ->count();
                    }

                    if ($rutP){ //si es verdadeero el rut
                        if ($rutJ){ //si es verdadeero el rut
                            if($temp->email!=null && $mailExiste == 0){
                                if($temp->nac != null){
                                    if($temp->genero != null){
                                        if(count($centroCosto) > 0 && $temp->cc != null){
                                            if(count($cargo) > 0 && $temp->cargo != null){
                                                if(count($codigoSucursal) > 0 && $temp->codigo_sucursal != null){
                                                    if(count($empresa) > 0 && $temp->soc != null){
                                                        if($temp->fecha_ingreso != null){
                                                            if($count == 0){
                                                                //dd('count == 0');
                                                                $trabajador = new Trabajador;

                                                                $trabajador->rut = $rutPersona;
                                                                $trabajador->nombres = $nombres;
                                                                $trabajador->apellido_paterno = $ape_paterno;
                                                                $trabajador->apellido_materno = $ape_materno;
                                                                $trabajador->email = $temp->email;
                                                                $trabajador->fecha_nacimiento = $temp->nac;
                                                                $trabajador->genero = $temp->genero;
                                                                $trabajador->centrosCostos_codigo = $temp->cc;
                                                                $trabajador->cargos_codigo = $temp->cargo;
                                                                $trabajador->sucursales_codigo = $temp->codigo_sucursal;
                                                                $trabajador->empresas_id = $temp->soc;
                                                                $trabajador->wwid = ' ';//$temp->wwid;
                                                                $trabajador->pid = 0;
                                                                $trabajador->fecha_ingreso = $temp->fecha_ingreso;
                                                                $trabajador->rut_jefatura = $rutJefe;
                                                                $trabajador->contrasena = bcrypt($password);//$password;
                                                                $trabajador->estado = 1;//$temp->estado;
                                                                $trabajador->carga_masiva = 1;

                                                                $trabajador->save();

                                                                $user = new User;
                                                                $user->name = $nombres.' '.$ape_paterno.' '.$ape_materno;
                                                                $user->email = $temp->email;
                                                                $user->password = bcrypt($password);
                                                                $user->remember_token = ' ';
                                                                $user->rol = 'alumno';
                                                                $user->trabajadores_rut = $rutPersona;
                                                                $user->save();

                                                                $Usuario            = User::where('trabajadores_rut', '=', $rutJefe)->get();
                                                                $cadenanueva        = [];
                                                                $cadenadividida     = [];
                                                                $agregado           = 0;
                                                                $agregadoJef        = 0;
                                                                $roles              = null;
                                                                $contador           = 0;
                                                                if (count($Usuario) > 0) {
                                                                    foreach ($Usuario as $key => $value) {
                                                                        $usuario = User::find($value->id);
                                                                        if($value->rol != ''){
                                                                            $cadenadividida = explode(',', $value->rol);
                                                                        }
                                                                        if(count($cadenadividida)>0){
                                                                            foreach ($cadenadividida as $key => $value) {
                                                                                if($value != 'jefatura'){
                                                                                    array_push($cadenanueva,$value);
                                                                                }else if($value == 'jefatura' && $agregadoJef == 0){
                                                                                    array_push($cadenanueva,'jefatura');
                                                                                    $agregadoJef = 1;
                                                                                    $contador = $contador + 1;
                                                                                }else{                                        
                                                                                    $contador = $contador + 1;
                                                                                }
                                                                            }
                                                                        }
                                                                        if($contador == 0 || $contador == 1){
                                                                            array_push($cadenanueva,'jefatura');
                                                                        }
                                                                        if(count($cadenanueva)>0){
                                                                            foreach ($cadenanueva as $key => $valor) {
                                                                                if($agregado == 0){
                                                                                    $roles = $valor;
                                                                                    $agregado = 1;
                                                                                }
                                                                                else{
                                                                                    $roles = $roles.','.$valor;
                                                                                }
                                                                            }
                                                                        }
                                                                        $usuario->rol = $roles;
                                                                        $usuario->save();
                                                                    }
                                                                }
                                                            }else{
                                                                //dd('count == 1');
                                                                $trabTabla->nombres = $nombres;
                                                                $trabTabla->apellido_paterno = $ape_paterno;
                                                                $trabTabla->apellido_materno = $ape_materno;
                                                                $trabTabla->email = $temp->email;
                                                                $trabTabla->fecha_nacimiento = $temp->nac;
                                                                $trabTabla->genero = $temp->genero;
                                                                $trabTabla->centrosCostos_codigo = $temp->cc;
                                                                $trabTabla->cargos_codigo = $temp->cargo;
                                                                $trabTabla->sucursales_codigo = $temp->codigo_sucursal;
                                                                $trabTabla->empresas_id = $temp->soc;
                                                                $trabTabla->fecha_ingreso = $temp->fecha_ingreso;
                                                                $trabTabla->rut_jefatura = $rutJefe;
                                                                $trabTabla->contrasena = bcrypt($password);
                                                                $trabTabla->estado = 1;
                                                                $trabTabla->carga_masiva = 1;

                                                                $trabTabla->save();

                                                                $id = User::where('trabajadores_rut','=',$rutPersona)->value('id');
                                                                if(is_null($id)){
                                                                    $user = new User;
                                                                    $user->name = $nombres.' '.$ape_paterno.' '.$ape_materno;
                                                                    $user->email = $temp->email;
                                                                    $user->password = bcrypt($password);
                                                                    $user->remember_token = ' ';
                                                                    $user->rol = 'alumno';
                                                                    $user->trabajadores_rut = $rutPersona;
                                                                    $user->save();

                                                                    $Usuario            = User::where('trabajadores_rut', '=', $rutJefe)->get();
                                                                    $cadenanueva        = [];
                                                                    $cadenadividida     = [];
                                                                    $agregado           = 0;
                                                                    $agregadoJef        = 0;
                                                                    $roles              = null;
                                                                    $contador           = 0;
                                                                    if (count($Usuario) > 0) {
                                                                        foreach ($Usuario as $key => $value) {
                                                                            $usuario = User::find($value->id);
                                                                            if($value->rol != ''){
                                                                                $cadenadividida = explode(',', $value->rol);
                                                                            }
                                                                            if(count($cadenadividida)>0){
                                                                                foreach ($cadenadividida as $key => $value) {
                                                                                    if($value != 'jefatura'){
                                                                                        array_push($cadenanueva,$value);
                                                                                    }else if($value == 'jefatura' && $agregadoJef == 0){
                                                                                        array_push($cadenanueva,'jefatura');
                                                                                        $agregadoJef = 1;
                                                                                        $contador = $contador + 1;
                                                                                    }else{                                        
                                                                                        $contador = $contador + 1;
                                                                                    }
                                                                                }
                                                                            }
                                                                            if($contador == 0){
                                                                                array_push($cadenanueva,'jefatura');
                                                                            }
                                                                            if(count($cadenanueva)>0){
                                                                                foreach ($cadenanueva as $key => $valor) {
                                                                                    if($agregado == 0){
                                                                                        $roles = $valor;
                                                                                        $agregado = 1;
                                                                                    }
                                                                                    else{
                                                                                        $roles = $roles.','.$valor;
                                                                                    }
                                                                                }
                                                                            }
                                                                            $usuario->rol = $roles;
                                                                            $usuario->save();
                                                                        }
                                                                    }
                                                                }else{
                                                                    $user = User::find($id);
                                                                    $user->name = $nombres.' '.$ape_paterno.' '.$ape_materno;
                                                                    $user->email = $temp->email;
                                                                    $user->password = bcrypt($password);
                                                                    //$user->save();

                                                                    $Usuario            = User::where('trabajadores_rut', '=', $rutPersona)->get();
                                                                    $cadenanueva        = [];
                                                                    $cadenadividida     = [];
                                                                    $agregado           = 0;
                                                                    $roles              = null;
                                                                    $contador           = 0;
                                                                    if (count($Usuario) > 0) {
                                                                        if ($trabTabla->estado == 1) {                        
                                                                            foreach ($Usuario as $key => $value) {
                                                                                $usuario = User::find($value->id);
                                                                                if($value->rol != ''){
                                                                                    $cadenadividida = explode(',', $value->rol);
                                                                                }
                                                                                if(count($cadenadividida)>0){
                                                                                    foreach ($cadenadividida as $key => $value) {
                                                                                        if($value != 'alumno'){
                                                                                            array_push($cadenanueva,$value);
                                                                                        }
                                                                                        else{
                                                                                            $contador = $contador + 1;
                                                                                        }
                                                                                    }
                                                                                }
                                                                                if($contador == 0 || $contador == 1){
                                                                                    array_push($cadenanueva,'alumno');
                                                                                }
                                                                                if(count($cadenanueva)>0){
                                                                                    foreach ($cadenanueva as $key => $valor) {
                                                                                        if($agregado == 0){
                                                                                            $roles = $valor;
                                                                                            $agregado = 1;
                                                                                        }
                                                                                        else{
                                                                                            $roles = $roles.','.$valor;
                                                                                        }
                                                                                    }
                                                                                }                                       
                                                                                $user->rol = $roles;
                                                                                $user->save();
                                                                            }
                                                                        }
                                                                    }

                                                                    $Usuario            = User::where('trabajadores_rut', '=', $rutJefe)->get();
                                                                    $cadenanueva        = [];
                                                                    $cadenadividida     = [];
                                                                    $agregado           = 0;
                                                                    $agregadoJef        = 0;
                                                                    $roles              = null;
                                                                    $contador           = 0;
                                                                    if (count($Usuario) > 0) {
                                                                        foreach ($Usuario as $key => $value) {
                                                                            $usuario = User::find($value->id);
                                                                            if($value->rol != ''){
                                                                                $cadenadividida = explode(',', $value->rol);
                                                                            }
                                                                            if(count($cadenadividida)>0){
                                                                                foreach ($cadenadividida as $key => $value) {
                                                                                    if($value != 'jefatura'){
                                                                                        array_push($cadenanueva,$value);
                                                                                    }else if($value == 'jefatura' && $agregadoJef == 0){
                                                                                        array_push($cadenanueva,'jefatura');
                                                                                        $agregadoJef = 1;
                                                                                        $contador = $contador + 1;
                                                                                    }else{                                        
                                                                                        $contador = $contador + 1;
                                                                                    }
                                                                                }
                                                                            }
                                                                            if($contador == 0){
                                                                                array_push($cadenanueva,'jefatura');
                                                                            }
                                                                            if(count($cadenanueva)>0){
                                                                                foreach ($cadenanueva as $key => $valor) {
                                                                                    if($agregado == 0){
                                                                                        $roles = $valor;
                                                                                        $agregado = 1;
                                                                                    }
                                                                                    else{
                                                                                        $roles = $roles.','.$valor;
                                                                                    }
                                                                                }
                                                                            }
                                                                            $usuario->rol = $roles;
                                                                            $usuario->save();
                                                                        }
                                                                    }
                                                                }
                                                            } 
                                                        }
                                                    }
                                                }
                                            }
                                        }
                                    }
                                }
                            }
                        }
                    }
                });

                /*                
                $reader2 = $reader;

                $listTrab = $reader2->skipColumns(0)->takeColumns(1)->toArray();
                //dd(${existeTrab});

                //saco el numero de elementos
                $longitud = count($listTrab);

                foreach ($trabajadores as $trabTemp){
                    $rutTabla = $trabTemp->rut;
                    $existeTrab = 0;

                    //Recorro todos los elementos
                    for($i=0; $i<$longitud; $i++){
                        $rutExcel = substr($listTrab[$i]['rut'], strripos($listTrab[$i]['rut'], ' ') + 1);

                        if($rutTabla == $rutExcel){
                            $existeTrab = 1;
                        }
                    }

                    if($existeTrab == 0){
                        $jefe  = Trabajador::where('rut_jefatura', '=', $trabTemp->rut)->get();

                        if(count($jefe) == 0){
                            $trabTemp->estado = 0;
                            $trabTemp->save();

                            $instructor = Instructor::find($trabTemp->rut);
                            if (count($instructor) > 0) {
                                $instructor->estado = 0;
                                $instructor->save();
                            }

                            $User = User::where('trabajadores_rut', '=', $trabTemp->rut)->get();
                            foreach ($User as $user) {
                                $user->rol = null;
                                $user->save();
                            }                            
                        }
                    }
                }
                */

                $trabajadores = Trabajador::where('carga_masiva','=', 0)->get();

                foreach ($trabajadores as $trabajador) {
                    
                    $jefe  = Trabajador::where('rut_jefatura', '=', $trabajador->rut)->get();

                    if(count($jefe) == 0){
                        $trabajador->estado = 0;
                        $trabajador->save();

                        $instructor = Instructor::find($trabajador->rut);
                        if (count($instructor) > 0) {
                            $instructor->estado = 0;
                            $instructor->save();
                        }

                        $User = User::where('trabajadores_rut', '=', $trabajador->rut)->get();
                        foreach ($User as $user) {
                            $user->rol = null;
                            $user->save();
                        }                            
                    }
                }

                $trabajadores = Trabajador::all();

                $centrosCostos = [];

                foreach(CentroCosto::get() as $centroCosto):
                    $centrosCostos[$centroCosto->codigo] = $centroCosto->nombre;
                endforeach;

                $sucursales = [];

                foreach(Sucursal::get() as $sucursal):
                    $sucursales[$sucursal->codigo] = $sucursal->nombre;
                endforeach;

                $empresas = [];

                foreach(Empresa::get() as $empresa):
                    $empresas[$empresa->id] = $empresa->nombre;
                endforeach;

                $cargos = [];

                foreach(Cargo::get() as $cargo):
                    $cargos[$cargo->codigo] = $cargo->nombre;
                endforeach;

                $trabajadoresCargados = Trabajador::where('carga_masiva','=', 1)->get();
                if (count($trabajadoresCargados) > 0) {
                    return redirect()->route('trabajadores.index')->with('trabajadores', $trabajadores)->with(array('centrosCostos' => $centrosCostos))->with(array('sucursales' => $sucursales))->with(array('empresas' => $empresas))->with(array('cargos' => $cargos))->with('success','Nomina de trabajadores cargada correctamente, se cargaron/actualizaron ' . count($trabajadoresCargados) . ' trabajadores');
                }else{
                    return redirect()->route('trabajadores.index')->with('trabajadores', $trabajadores)->with(array('centrosCostos' => $centrosCostos))->with(array('sucursales' => $sucursales))->with(array('empresas' => $empresas))->with(array('cargos' => $cargos))->with('error','No se han cargado registros');
                }
            }else{
                return back()->with('error', 'El archivo no corresponde.');
            }
        });

        return redirect()->route('trabajadores.index');
    }

    public function getTrabajador(Request $request)
    {
        if ($request->ajax()) {
            $rut = str_replace('k', 'K', $request->input('valor'));
            $trabajador = Trabajador::find($rut);

            $counter = count($trabajador);

            if($counter > 0){
                return response()->json([
                    "trabajador" => $trabajador,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "trabajador" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }

    public function getTrabajadorCorreo(Request $request)
    {
        if ($request->ajax()) {

            $counter = User::where('email','=',$request->input('valor'))->count();

            if($counter > 0){
                return response()->json([
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "count" => '-1'
                ]);
            }
        }
    }

    public function getTrabajadorCorreoEdit(Request $request)
    {
        if ($request->ajax()) {

            $counter = User::where('email','=',$request->input('valor'))
                            ->where('trabajadores_rut','<>', $request->input('rut'))
                            ->count();

            if($counter > 0){
                return response()->json([
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "count" => '-1'
                ]);
            }
        }
    }

    public function obtenerCertificado($id)
    {

        $curso_trabajador = CursoTrabajador::find($id);

        //return view('certificados.comprobante')->with('curso_trabajador', $curso_trabajador);
        $pdf = PDF::loadView('certificados.comprobante', [ 'curso_trabajador' => $curso_trabajador ]);
        $pdf = $pdf->setPaper('A4', 'landscape');
        return $pdf->stream('Certificado de aprobacion.pdf');
    }

    public function descargarCertificado($id)
    {
        $curso_trabajador = CursoTrabajador::find($id);

        //return view('certificados.comprobante')->with('curso_trabajador', $curso_trabajador);
        $pdf = PDF::loadView('certificados.comprobante', [ 'curso_trabajador' => $curso_trabajador ]);
        $pdf = $pdf->setPaper('A4', 'landscape');
        return $pdf->download('Certificado de aprobacion.pdf');
    }
}