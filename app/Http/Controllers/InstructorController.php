<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use App\Instructor;
use App\Trabajador;
use App\Log;
use App\CursoInstructor;
use App\Curso;
use App\Version;
use App\Notificacion;
use Auth;
use Illuminate\Validation\Rule;
use Storage;
use Malahierba\ChileRut\ChileRut;
use Illuminate\Support\Facades\DB;
use App\User;

class InstructorController extends Controller
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
        $email = \Request::get('emailB');
        $wwid = \Request::get('wwidB');
        $pid = \Request::get('pidB');
        $estado = \Request::get('estadoB');

        //dd('rut:'.$rut);
        //dd('apellido_paterno:'.$apellido_paterno);
        //dd('estado:'.$estado);

        $instructores = DB::table('instructores')->select('rut', 'nombres', 'apellido_paterno', 'apellido_materno', 'wwid', 'pid', 'email', 'estado');

        if(!is_null($estado) && $estado <> 2){
            $instructores = $instructores->where('estado','=',$estado);
        }

        if(!is_null($rut)){
            if (strpos($rut, 'k') || strpos($rut, 'K')) {
                if (strpos($rut, 'k')) {
                    $instructores = $instructores->where(function ($q) use ($rut) {
                        $q = $q->orWhere('rut', '=', $rut);
                        $q = $q->orWhere('rut', '=', str_replace('k', 'K', $rut));
                    });
                }elseif(strpos($rut, 'K')) {
                    $instructores = $instructores->where(function ($q) use ($rut) {
                        $q = $q->orWhere('rut', '=', $rut);
                        $q = $q->orWhere('rut', '=', str_replace('K', 'k', $rut));
                    });
                }
            }else{
                $instructores = $instructores->where('rut','=', $rut);
            }
        }

        if(!is_null($apellido_paterno)){
            $instructores = $instructores->where('apellido_paterno','like','%'.$apellido_paterno.'%');
        }

        if(!is_null($email)){
            $instructores = $instructores->where('email','like','%'.$email.'%');
        }

        if(!is_null($wwid)){
            $instructores = $instructores->where('wwid','like','%'.$wwid.'%');
        }

        if(!is_null($pid)){
            $instructores = $instructores->where('pid','=',$pid);
        }

        $instructores = $instructores->orderBy('apellido_paterno', 'asc')->get();

        foreach ($instructores as $instructor)
            if ( $instructor->estado == 1 ){
                $instructor->estado = "Activo";
            }else{
                $instructor->estado = "Inactivo";
            }

        return view('instructores.index')->with('instructores', $instructores)->with('estadoB', $estado);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $items = Instructor::all('rut', 'nombres');

        $cursos = Curso::where('estado', '=', 'true')
                        ->orderBy('codigo')->get();

        return view('instructores.create', compact('items','cursos'));
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
            return redirect()->route('instructores.create')->withInput()->with('error','Debe ingresar un rut');
        }
        if($request->input('nombres') != null){
            if(empty($request->input('nombres'))){
                return redirect()->route('instructores.create')->withInput()->with('error','Debe ingresar un nombre');
            }
        }
        if($request->input('apellido_paterno') != null){
            if(empty($request->input('apellido_paterno'))){
                return redirect()->route('instructores.create')->withInput()->with('error','Debe ingresar un apellido paterno');
            }
        }
        if($request->input('apellido_materno') != null){
            if(empty($request->input('apellido_materno'))){
                return redirect()->route('instructores.create')->withInput()->with('error','Debe ingresar un apellido materno');
            }
        }
        if($request->input('email') != null){
            if(empty($request->input('email'))){
                return redirect()->route('instructores.create')->withInput()->with('error','Debe ingresar un correo');
            }
        }
        if(empty($request->input('telefono'))){
            return redirect()->route('instructores.create')->withInput()->with('error','Debe ingresar un teléfono');
        }
        // if ( empty($request->input('to')) ) {
        //     return redirect()->route('instructores.create')->withInput()->with('error','Debe ingresar al menos un curso');
        // } 
        if ( empty($request->file('file')) ) {
            return redirect()->route('instructores.create')->withInput()->with('error','No se ha cargado ningún archivo');
        }

        //Se obtiene el campo file definido en el formulario
        $file           = $request->file('file');
        //Se obtiene el nombre del archivo
        $nombreImagen   = $file->getClientOriginalName();
        $nombreImage    = mb_strlen($nombreImagen, 'UTF-8');

        if ( $nombreImage > 50 ) {
            return redirect()->route('instructores.create')->withInput()->with('error','Nombre de archivo supera los 50 carácteres');
        }

        //$this->validate($request, ['rut' => 'unique:instructores,rut',$request->input('rut')], );
        $rules = ['rut' => 'unique:instructores,rut',$request->input('rut')];
        $this->validate($request,$rules);

        $rut = new ChileRut();
        $rutValido = $rut->check($request->input('rut'));

        if ($rutValido){
            /*$trabajadores = Trabajador::where('rut', '=', $request->input('rut'))->get();

            foreach ($trabajadores as $trabajador) {*/
                $trab = Trabajador::find($request->input('rut'));

                $instructor = new Instructor;
                $instructor->rut = $request->input('rut');

                if($request->input('nombres') != null){
                    $instructor->nombres            = $request->input('nombres');
                    if($trab != null){
                        $trab->nombres              = $request->input('nombres');
                    }
                }else{
                    $instructor->nombres            = $trab->nombres;
                }
                if($request->input('apellido_paterno') != null){
                    $instructor->apellido_paterno   = $request->input('apellido_paterno');
                    if($trab != null){
                        $trab->apellido_paterno     = $request->input('apellido_paterno');
                    }
                }else{
                    $instructor->apellido_paterno   = $trab->apellido_paterno;
                }
                if($request->input('apellido_materno') != null){
                    $instructor->apellido_materno   = $request->input('apellido_materno');
                    if($trab != null){
                        $trab->apellido_materno     = $request->input('apellido_materno');
                    }
                }else{
                    $instructor->apellido_materno   = $trab->apellido_materno;
                }
                if($request->input('email') != null){
                    $instructor->email              = $request->input('email');
                    if($trab != null){
                        $trab->email                = $request->input('email');
                    }
                }else{
                    $instructor->email              = $trab->email;
                }
                if($request->input('wwid') != null){
                    $instructor->wwid               = $request->input('wwid');
                    if($trab != null){
                        $trab->wwid                 = $request->input('wwid');
                    }
                }else{
                    $instructor->wwid               = ' ';
                    if($trab != null){
                        $trab->wwid                 = ' ';
                    }
                }
                if($request->input('pid') != null){
                    $instructor->pid                = $request->input('pid');
                    if($trab != null){
                        $trab->pid                  = $request->input('pid');
                    }
                }else{
                    $instructor->pid               = 0;
                    if($trab != null){
                        $trab->pid                 = 0;
                    }
                }
                if($request->input('telefono') != null){
                    $instructor->telefono           = $request->input('telefono');
                }

                //Se obtiene el campo file definido en el formulario
                $file = $request->file('file');

                //Se obtiene el nombre del archivo
                $nombre = $file->getClientOriginalName();

                //nombre unico
                $nombre_unico = time().$nombre;
                
                //se guarda un nuevo archivo en el disco local  
                \Storage::disk('instructores')->put($nombre_unico,  \File::get($file));

                //Se obtiene ruta y nombre del archivo
                $path = 'app/instructores/'.$nombre_unico;

                $instructor->foto = $path;
                $instructor->nombre_foto = $nombre;

                if ( is_null($request->input('estado')) ){
                    $instructor->estado  = 0;
                }else{
                    $instructor->estado  = $request->input('estado');
                }
                
                if($trab != null){
                    $trab->save();
                }else{
                    $counter = User::where('email','=',$request->input('email'))->count();

                    if($counter == 0){
                        //dd($instructor);
                        $user = new User;

                        $mail = $instructor->email;
                        $password    = substr($mail, 0, stripos($mail, '@'));
                        $password    = ucfirst(substr($password, 0, 20)).date("Y");
                        
                        $user->name = $instructor->nombres.' '.$instructor->apellido_paterno.' '.$instructor->apellido_materno;
                        $user->email = $instructor->email;
                        $user->password = bcrypt($password);
                        $user->remember_token = ' ';
                        $user->rol = 'instructor';
                        $user->trabajadores_rut = $instructor->rut;
                        $user->foto = $instructor->foto;
                        $user->save();
                    }else{
                        return redirect()->route('instructores.create')->with('error','Email ya existe favor ingrese nuevamente.');
                    }
                }

                $instructor->save();
                
                $Usuario            = User::where('trabajadores_rut', '=', $request->input('rut'))->get();
                $cadenanueva        = [];
                $cadenadividida     = [];
                $agregado           = 0;
                $roles              = null;
                $contador           = 0;
                
                if (count($Usuario) > 0) {
                    if ($instructor->estado == 1) {
                        foreach ($Usuario as $key => $value) {
                            $usuario = User::find($value->id);
                            $usuario->name = $instructor->nombres.' '.$instructor->apellido_paterno.' '.$instructor->apellido_materno;
                            $usuario->foto = $instructor->foto;
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
                            $usuario->foto = $instructor->foto;
                            $usuario->name = $instructor->nombres.' '.$instructor->apellido_paterno.' '.$instructor->apellido_materno;
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
            //}

            //se guardan curso aptos para realizar por instructor
            $cursoIns =  $request->input('to');
            if(!is_null($cursoIns)){
                for ($i=0; $i < sizeof($cursoIns); $i++) {
                    $ci = new CursoInstructor;
                    $ci->cursos_codigo = $cursoIns[$i];
                    $ci->instructores_rut = $instructor->rut;
                    $ci->save();
                }
            }
            
            //Guarda log al crear
            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Agregar Instructor",
                'details' => "Se crea Instructor: " . $request->input('nombres'),
            ]);


            $instructores = Instructor::get();

            foreach ($instructores as $temp)
                if ( $temp->estado == 1 ){
                    $temp->estado = "Activo";
                }else{
                    $temp->estado = "Inactivo";
                }

            return redirect()->route('instructores.index')->with('success','Instructor creado correctamente');
        }else{
            return redirect()->route('instructores.create')->with('error','Rut no válido, favor ingrese nuevamente.');
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
        $instructor = Instructor::find($rut);

        if ( $instructor->estado == 1 ){
            $instructor->estado = "Activo";
        }else{
            $instructor->estado = "Inactivo";
        }

        //Para mostrar los programas del instructor
        $programas = DB::table('versiones')
                        ->select('curso_instructor.instructores_rut', 'programas.codigo', 'programas.nombre')
                        ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                        ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                        ->where('curso_instructor.instructores_rut','=',$rut)
                        ->where('programas.estado','=',true)
                        ->groupBy('curso_instructor.instructores_rut', 'programas.codigo', 'programas.nombre')
                        ->orderBy('programas.codigo')->get();         

        $detalle_horas_group = DB::table('versiones')
                            ->select('programas.codigo As codigo_programa', 'programas.nombre As nombre_programa')
                            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                            ->where('curso_instructor.instructores_rut','=',$rut)
                            ->where('programas.estado','=',true)
                            ->groupBy('programas.codigo')
                            ->orderBy('programas.codigo')
                            ->get();

        $detalle_horas = DB::table('versiones')
                            ->select('programas.codigo As codigo_programa', 'programas.nombre As nombre_programa', 'cursos.codigo As codigo_curso', 'cursos.nombre As nombre_curso', 'versiones.horas', 'versiones.fecha_inicio', 'versiones.fecha_fin', 'sucursales.nombre As nombre_sucursal')
                            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                            ->join('programas', 'versiones.programas_codigo', '=', 'programas.codigo')
                            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                            ->join('sucursales', 'versiones.lugar_ejecucion', '=', 'sucursales.codigo')
                            ->where('curso_instructor.instructores_rut','=',$rut)
                            ->where('programas.estado','=',true)
                            ->orderBy('programas.codigo')
                            ->get();


        return view('instructores.show')->with('instructor',$instructor)
                                        ->with('programas',$programas)
                                        ->with('detalle_horas',$detalle_horas)
                                        ->with('detalle_horas_group',$detalle_horas_group);
    }


    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($rut)
    {
        $instructor = Instructor::find($rut);

        $cursos = Curso::where('estado', '=', 'true')
                            ->orderBy('codigo')->get();

        $cursoIns = CursoInstructor::where('instructores_rut','=',$rut)->get();

        if ( $instructor->estado == 1 ){
            $instructor->estado = "true";
        }else{
            $instructor->estado = "false";
        }

        return view('instructores.edit')->with('instructor',$instructor)
                                        ->with('cursos',$cursos)
                                        ->with('cursoIns',$cursoIns);
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
            return redirect()->route('instructores.edit', $rut)->withInput()->with('error','Debe ingresar un rut');
        }
        if($request->input('nombres') != null){
            if(empty($request->input('nombres'))){
                return redirect()->route('instructores.edit', $rut)->withInput()->with('error','Debe ingresar un nombre');
            }
        }
        if($request->input('apellido_paterno') != null){
            if(empty($request->input('apellido_paterno'))){
                return redirect()->route('instructores.edit', $rut)->withInput()->with('error','Debe ingresar un apellido paterno');
            }
        }
        if($request->input('apellido_materno') != null){
            if(empty($request->input('apellido_materno'))){
                return redirect()->route('instructores.edit', $rut)->withInput()->with('error','Debe ingresar un apellido materno');
            }
        }
        if($request->input('email') != null){
            if(empty($request->input('email'))){
                return redirect()->route('instructores.edit', $rut)->withInput()->with('error','Debe ingresar un correo');
            }
        }
        if(empty($request->input('telefono'))){
            return redirect()->route('instructores.edit', $rut)->withInput()->with('error','Debe ingresar un teléfono');
        }
        if ( is_null($request->input('estado')) ){
            $count = DB::table('versiones')
                ->join('curso_instructor', 'curso_instructor.id','=','versiones.curso_instructor_id')
                ->join('instructores', 'instructores.rut','=','curso_instructor.instructores_rut')
                ->where('versiones.status','=','D')
                ->where('instructores_rut','=',$rut)->count();
                if($count > 0){    
                    return redirect()->route('instructores.edit', $rut)->withInput()->with('error','No se puede editar estado de instructor, tiene cursos asociados a programa');
                }
        }

        if(!empty($request->file('file'))){
            //Se obtiene el campo file definido en el formulario
            $file           = $request->file('file');
            //Se obtiene el nombre del archivo
            $nombreImagen   = $file->getClientOriginalName();
            $nombreImage    = mb_strlen($nombreImagen, 'UTF-8');

            if ( $nombreImage > 50 ) {
                return redirect()->route('instructores.edit', $rut)->withInput()->with('error','Nombre de archivo supera los 50 carácteres');
            }
        }

        // if ( empty($request->input('to')) ) {
        //     return redirect()->route('instructores.edit', $rut)->withInput()->with('error','Debe ingresar al menos un curso');
        // } 

        $cursoInsActual = CursoInstructor::where('instructores_rut','=',$rut)->get();
        $cursoIns =  $request->input('to');

        //validar si curso esta en versiones
        foreach($cursoInsActual as $value) {  
            $flag = false;
            for ($i=0; $i < sizeof($cursoIns); $i++) {         
                if ($value->cursos_codigo == $cursoIns[$i]) {
                    $flag = true;
                }
            }
            if ($flag == false) { 
                 $array = Version::where('curso_instructor_id','=',$value->id)->count();
                if ($array <> 0) {
                    return redirect()->route('instructores.edit', $rut)->withInput()->with('error','No se pudo editar listado de cursos, se intentó quitar curso asocidado a un programa');
                } 
            }
        }           

        $counter = User::where('email','=',$request->input('email'))
                        ->where('trabajadores_rut','!=',$rut)->count();

        if($counter == 0){

            $instructor = Instructor::find($rut);    
            $nombresAntiguo = $instructor->nombres;
            $instructor->nombres = $request->input('nombres');
            $instructor->apellido_paterno = $request->input('apellido_paterno');
            $instructor->apellido_materno = $request->input('apellido_materno');
            $instructor->email = $request->input('email');
            if($request->input('wwid') != null){
                $instructor->wwid               = $request->input('wwid');
            }else{
                $instructor->wwid               = 0;
            }
            if($request->input('pid') != null){
                $instructor->pid                = $request->input('pid');
            }else{
                $instructor->pid               = 0;
            }
            $instructor->telefono = $request->input('telefono');

            if ( is_null($request->input('estado')) ){
                $instructor->estado  = 0;
            }else{
                $instructor->estado  = $request->input('estado');
            }

            if ($instructor->estado <> 0) {
                //actualizar cursos apto para realizar por instructor
                //agregar
                for ($i=0; $i < sizeof($cursoIns); $i++) {
                    $flag = false;
                    foreach($cursoInsActual as $value) {       
                        if ($value->cursos_codigo == $cursoIns[$i]) {
                            $flag = true;
                        }
                    }
                    if ($flag == false) {    
                            $ci = new CursoInstructor;
                            $ci->cursos_codigo = $cursoIns[$i];
                            $ci->instructores_rut = $instructor->rut;
                            $ci->save();
                    }
                }

                //quitar
                foreach($cursoInsActual as $value) {  
                    $flag = false;
                    for ($i=0; $i < sizeof($cursoIns); $i++) {         
                        if ($value->cursos_codigo == $cursoIns[$i]) {
                            $flag = true;
                        }
                    }
                    if ($flag == false) {    
                            $value->delete();
                    }
                }  
            } 


            //Se obtiene el campo file definido en el formulario
            $file = $request->file('file');

            //Valido si hay un archivo cargado
            if ($file != null){

                //Se obtiene el nombre del archivo
                $nombre = $file->getClientOriginalName();

                //nombre unico
                $nombre_unico = time().$nombre;
                    
                //se guarda un nuevo archivo en el disco local  
                \Storage::disk('instructores')->put($nombre_unico,  \File::get($file));

                //Se obtiene ruta y nombre del archivo
                $path = 'app/instructores/'.$nombre_unico;

                $instructor->foto = $path;
                $instructor->nombre_foto = $nombre;

                if ( is_null($request->input('estado')) ){
                    $instructor->estado  = 0;
                }else{
                    $instructor->estado  = $request->input('estado');
                }
            }

            $instructor->save();

            $Usuario            = User::where('trabajadores_rut', '=', $request->input('rut'))->get();
            $cadenanueva        = [];
            $cadenadividida     = [];
            $agregado           = 0;
            $roles              = null;
            $contador           = 0;
            if (count($Usuario) > 0) {
                if ($instructor->estado == 1) {
                    foreach ($Usuario as $key => $value) {
                        $usuario = User::find($value->id);
                        $usuario->name = $instructor->nombres.' '.$instructor->apellido_paterno.' '.$instructor->apellido_materno;
                        $usuario->email = $instructor->email;
                        $usuario->foto = $instructor->foto;
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
                        $usuario->name = $instructor->nombres.' '.$instructor->apellido_paterno.' '.$instructor->apellido_materno;
                        $usuario->email = $instructor->email;
                        $usuario->foto = $instructor->foto;
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
        
        }else{
            return redirect()->route('instructores.edit', $rut)->with('error','Email ya existe favor ingrese nuevamente.');
        }

        //Guarda log al editar
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Editar Instructor",
            'details' => "Se edita Instructor: " . $nombresAntiguo . " por: " . $request->input('nombres'),
        ]);

        return redirect()->route('instructores.index')->with('success','Instructor editado correctamente');

    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($rut)
    {
        $instructor = Instructor::find($rut);

        if(!is_null($instructor)){
            //se valida que el Instructor no este dictando un curso, o haya dictado anteriormente.
            $count = DB::table('versiones')
                        ->join('curso_instructor', 'curso_instructor.id','=','versiones.curso_instructor_id')
                        ->join('instructores', 'instructores.rut','=','curso_instructor.instructores_rut')
                        ->where('instructores_rut','=',$rut)->count();

            if($count == 0){
                // se eliminan realciones de cursoInstructor
                $cursoInsEliminar = CursoInstructor::where('instructores_rut','=',$rut)->get();

                foreach($cursoInsEliminar as $value) {
                    $value->delete();
                }

                $trab = Trabajador::find($rut);

                if($trab != null){
                    $Usuario            = User::where('trabajadores_rut', '=', $rut)->get();
                    $cadenanueva        = [];
                    $cadenadividida     = [];
                    $agregado           = 0;
                    $roles              = null;
                    if (count($Usuario) > 0) {
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
                }else{
                    $usuario = User::select('id')->where('trabajadores_rut', '=', $rut)->get();
                    $counter = Notificacion::where('users_id','=',$usuario[0]->id)->count();

                    if ($counter <>'0') {
                        return redirect()->route('instructores.index')->with('error','No se puede eliminar el Instructor, existe dependencia con otros registros');
                    }else{
                        $user = User::where('trabajadores_rut','=',$rut);
                        $user->delete();
                    }

                }

                $instructor->delete();

                //Guarda log al eliminar
                Log::create([
                    'id_user' => Auth::user()->id,
                    'action' => "Eliminar Instructor",
                    'details' => "Se elimina Instructor: " . $rut,
                ]);
            }else{
                return redirect()->route('instructores.index')->with('error','No se puede eliminar Instructor, tiene cursos asociados');
            }

            return redirect()->route('instructores.index')->with('success','Instructor eliminado correctamente');
        }else{
            return redirect()->route('instructores.index')->with('error','Registro no existe');
        }
    }

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $rut                = $request->input('rutExcel');
        $apellido_paterno   = $request->input('apellido_paternoExcel'); 
        $email              = $request->input('emailExcel');
        $wwid               = $request->input('wwidExcel');
        $pid                = $request->input('pidExcel'); 
        $estado             = $request->input('estadoExcel');   

        $instructores = DB::table('instructores')->select('rut', 'nombres', 'apellido_paterno', 'apellido_materno', 'wwid', 'pid', 'email', 'estado');

        if(!is_null($estado) && $estado <> 2){
            $instructores = $instructores->where('estado','=',$estado);
        }

        if(!is_null($rut)){
            if (strpos($rut, 'k') || strpos($rut, 'K')) {
                if (strpos($rut, 'k')) {
                    $instructores = $instructores->where(function ($q) use ($rut) {
                        $q = $q->orWhere('rut', '=', $rut);
                        $q = $q->orWhere('rut', '=', str_replace('k', 'K', $rut));
                    });
                }elseif(strpos($rut, 'K')) {
                    $instructores = $instructores->where(function ($q) use ($rut) {
                        $q = $q->orWhere('rut', '=', $rut);
                        $q = $q->orWhere('rut', '=', str_replace('K', 'k', $rut));
                    });
                }
            }else{
                $instructores = $instructores->where('rut','=', $rut);
            }
        }

        if(!is_null($apellido_paterno)){
            $instructores = $instructores->where('apellido_paterno','like','%'.$apellido_paterno.'%');
        }

        if(!is_null($email)){
            $instructores = $instructores->where('email','like','%'.$email.'%');
        }

        if(!is_null($wwid)){
            $instructores = $instructores->where('wwid','like','%'.$wwid).'%';
        }

        if(!is_null($pid)){
            $instructores = $instructores->where('pid','=',$pid);
        }

        $instructores = $instructores->orderBy('apellido_paterno', 'asc')->get();

        return Excel::create('ListadoInstructores', function($excel) use ($instructores) {
            $excel->sheet('Instructores', function($sheet) use ($instructores)
            {
                $count = 2;
                
                $sheet->row(1, ['Rut', 'Nombres', 'Apellido paterno', 'Apellido materno', 'WWID', 'PID', 'Email', 'Estado']);
                foreach ($instructores as $key => $value) {
                    if ( $value->estado == 1 ){
                        $value->estado = "Activo";
                    }else{
                        $value->estado = "Inactivo";
                    }
                    $sheet->row($count, [$value->rut, $value->nombres, $value->apellido_paterno, $value->apellido_materno, $value->wwid, $value->pid, $value->email, $value->estado]);
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

    public function volver()
    {
        return view('instructores.index');
    }

    public function cargaDataTrab(Request $request)
    {
        if ($request->ajax()) {
            $rut        = $request->input('valor');

            if (strpos($rut, 'k') || strpos($rut, 'K')) {
                $instructor = DB::table('trabajadores')->select('trabajadores.*')
                                ->where('trabajadores.estado', '=', 'true');
                if (strpos($rut, 'k')) {
                    $instructor = $instructor->where(function ($q) use ($rut) {
                        $q = $q->orWhere('rut', '=', $rut);
                        $q = $q->orWhere('rut', '=', str_replace('k', 'K', $rut));
                    });
                }elseif(strpos($rut, 'K')) {
                    $instructor = $instructor->where(function ($q) use ($rut) {
                        $q = $q->orWhere('rut', '=', $rut);
                        $q = $q->orWhere('rut', '=', str_replace('K', 'k', $rut));
                    });
                }
                $instructor=$instructor->first();
            }else{
                //$instructor = Trabajador::find($request->input('valor'));
                $instructor = DB::table('trabajadores')->select('trabajadores.*')
                                ->where('trabajadores.estado', '=', 'true')
                                ->where('trabajadores.rut', '=', $request->input('valor'))->first();
            }

            $counter = count($instructor);

            if($counter > 0){
                return response()->json([
                    "instructor" => $instructor,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "instructor" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }

    public function getInstructor(Request $request)
    {
        if ($request->ajax()) {
            $instructor = Instructor::find($request->input('valor'));

            $counter = count($instructor);

            if($counter > 0){
                return response()->json([
                    "instructor" => $instructor,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "instructor" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }


    //validar curso a quitar de lista
    public function validarCursoL(Request $request)
    {
        if ($request->ajax()) {
            $curso = $request->input('curso');
            $rut = $request->input('rut');
            
            $cId = CursoInstructor::select('id')
                        ->where('instructores_rut','=',$rut)
                        ->where('cursos_codigo','=',$curso)->get();

            $count = DB::table('versiones')
                        ->where('curso_instructor_id','=',$cId[0]->id)->count();

            $cursoNombre = Curso::select('nombre')
                            ->where('codigo','=',$curso)->get();

            // tiene registros asociados
            if($count > 0){
                return response()->json([
                    "count" => $count,
                    "cursoNombre" => $cursoNombre[0],
                    "cId" => $cId
                ]);
            }else{ // no tiene registros asociados
                return response()->json([
                    "count" => '0',
                    "cursoNombre" => $cursoNombre[0],
                    "cId" => $cId
                ]);
            }
        }   
    }  


    //validar curso a agregado a lista
    public function validarCursoR(Request $request)
    {
        if ($request->ajax()) {
            $curso = $request->input('curso');
            $rut = $request->input('rut');
            
            
            $cuentaId = CursoInstructor::where('instructores_rut','=',$rut)
                        ->where('cursos_codigo','=',$curso)->count();

            // tiene registros asociados
            if ($cuentaId <> 0) {

                $cId = CursoInstructor::select('id')
                            ->where('instructores_rut','=',$rut)
                            ->where('cursos_codigo','=',$curso)->get();

                $count = DB::table('versiones')
                            ->where('curso_instructor_id','=',$cId[0]->id)
                            ->where('cursos_codigo','=',$curso)->count();

            } else {  // no tiene registros asociados
                $count = '0';
            }

            return response()->json([
                        "count" => $count
                    ]);
        }   
    }

    public function getInstructorEdit(Request $request)
    {
        if ($request->ajax()) {
            $instructor = Trabajador::find($request->input('valor'));

            $counter = count($instructor);

            if($counter > 0){
                return response()->json([
                    "instructor" => $instructor,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "instructor" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }

    public function getInstructorCorreo(Request $request)
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

    public function getInstructorCorreoEdit(Request $request)
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

}