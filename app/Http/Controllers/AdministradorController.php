<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;
use App\Trabajador;
use App\Log;
use Auth;
use Maatwebsite\Excel\Facades\Excel;
use Malahierba\ChileRut\ChileRut;
use Illuminate\Support\Facades\DB;
use App\Administrador;

class AdministradorController extends Controller
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

        $administradores = DB::table('administradores')->select('*')
                                ->where('estado', '=', true);

        if(!is_null($rut)){
            if (strpos($rut, 'k') || strpos($rut, 'K')) {
                if (strpos($rut, 'k')) {
                    $administradores = $administradores->where(function ($q) use ($rut) {
                        $q = $q->orWhere('rut', '=', $rut);
                        $q = $q->orWhere('rut', '=', str_replace('k', 'K', $rut));
                    });
                }elseif(strpos($rut, 'K')) {
                    $administradores = $administradores->where(function ($q) use ($rut) {
                        $q = $q->orWhere('rut', '=', $rut);
                        $q = $q->orWhere('rut', '=', str_replace('K', 'k', $rut));
                    });
                }
            }else{
                $administradores = $administradores->where('rut','=', $rut);
            }
        }

        if(!is_null($apellido_paterno)){
            $administradores = $administradores->where('apellido_paterno','like','%'.$apellido_paterno.'%');
        }

        if(!is_null($email)){
            $administradores = $administradores->where('email','like','%'.$email.'%');
        }

        $administradores = $administradores->orderBy('email', 'asc')->get();

        return view('administradores.index')->with('administradores', $administradores);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('administradores.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->input('nombres') != null){
            if(empty($request->input('nombres'))){
                return redirect()->route('administradores.create')->withInput()->with('error','Debe ingresar un nombre');
            }
        }
        if($request->input('email') != null){
            if(empty($request->input('email'))){
                return redirect()->route('administradores.create')->withInput()->with('error','Debe ingresar un correo');
            }
        }

        //$this->validate($request, ['rut' => 'unique:instructores,rut',$request->input('rut')], );
        $rules = ['email' => 'unique:administradores,email',$request->input('email')];
        $this->validate($request,$rules);
   
        $rut = new ChileRut();
        $rutValido = $rut->check($request->input('rut'));

        if ($rutValido || $request->input('rut') == null){

            $administrador = new Administrador;
            $administrador->rut                = $request->input('rut');
            $administrador->nombres            = $request->input('nombres');
            $administrador->apellido_paterno   = $request->input('apellido_paterno');
            $administrador->apellido_materno   = $request->input('apellido_materno');
            $administrador->email              = $request->input('email');
            $administrador->estado             = true;
            $administrador->save();

            $counter = User::where('email','=',$request->input('email'))->count();

            if($counter == 0){
                $user = new User;

                $mail = $administrador->email;
                $password    = substr($mail, 0, stripos($mail, '@'));
                $password    = ucfirst(substr($password, 0, 20)).date("Y");
                        
                $user->name = $administrador->nombres.' '.$administrador->apellido_paterno.' '.$administrador->apellido_materno;
                $user->email = $administrador->email;
                $user->password = bcrypt($password);
                $user->remember_token = ' ';
                $user->rol = 'admin';
                $user->trabajadores_rut = $administrador->rut;
                $user->foto = $administrador->foto;
                $user->save();
            }
                    
          //    $Usuario            = User::where('trabajadores_rut', '=', $request->input('rut'))->get();
            $Usuario            = User::where('email', '=', $request->input('email'))->get();
            $cadenanueva        = [];
            $cadenadividida     = [];
            $agregado           = 0;
            $roles              = null;
            $contador           = 0;
                    
            if (count($Usuario) > 0) {
                if ($administrador->estado == 1) {
                    foreach ($Usuario as $key => $value) {
                        $usuario = User::find($value->id);
                        $usuario->name = $administrador->nombres.' '.$administrador->apellido_paterno.' '.$administrador->apellido_materno;
                        if($value->rol != ''){
                            $cadenadividida = explode(',', $value->rol);
                        }
                        if(count($cadenadividida)>0){
                            foreach ($cadenadividida as $key => $value) {
                                if($value != 'admin'){
                                    array_push($cadenanueva,$value);
                                }
                                else{
                                    $contador = $contador + 1;
                                }
                            }
                        }
                        if($contador == 0 || $contador == 1){
                            array_push($cadenanueva,'admin');
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
                        $usuario->name = $administrador->nombres.' '.$administrador->apellido_paterno.' '.$administrador->apellido_materno;
                        if($value->rol != ''){
                            $cadenadividida = explode(',', $value->rol);
                        }
                        if(count($cadenadividida)>0){
                            foreach ($cadenadividida as $key => $value) {
                                if($value != 'admin'){
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

            //Guarda log al crear
            Log::create([
                   'id_user' => Auth::user()->id,
                   'action' => "Agregar Administrador",
                   'details' => "Se crea Administrador: " . $request->input('nombres').' '.$request->input('apellido_paterno').' '.$request->input('apellido_materno'),
            ]);
                return redirect()->route('administradores.index')->with('success','Administrador creado correctamente');
        }else{
            return redirect()->route('administradores.create')->with('error','Rut no válido, favor ingrese nuevamente.');
        }
       
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {        
        $administrador = Administrador::find($id);

        return view('administradores.edit')->with('administrador',$administrador);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $administrador = Administrador::find($id);
        $rut = $administrador->rut;

    //    $trab = Trabajador::find($rut);
        $trab = DB::table('trabajadores')->where('rut', '=', $rut)->get();
        $counterTrab = count($trab);

        if ($counterTrab > 0) {
   //        $Usuario            = User::where('trabajadores_rut', '=', $rut)->get();
            $Usuario            = User::where('email', '=', $administrador->email)->get();
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
                            if($value != 'admin'){
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

            $administrador->delete();

        }else {
            $Usuario  = User::where('email', '=', $administrador->email);
            $Usuario->delete();

            $administrador->delete();

        }

        // //Guarda log al eliminar
        Log::create([
               'id_user' => Auth::user()->id,
               'action' => "Eliminar Administrador",
               'details' => "Se elimina Administrador: " . $rut,
        ]);

        return redirect()->route('administradores.index')->with('success','Administrador eliminado correctamente');

    }

    public function getTrabajador(Request $request)
    {
        if ($request->ajax()) {

            $email = $request->input('valor');

            $adminTrab = DB::table('trabajadores')->select('trabajadores.*')
                                 ->where('trabajadores.estado', '=', 'true')
                                 ->where('trabajadores.email', '=', $email)->first();
            $counterAdminTrab = count($adminTrab);

            $administrador = DB::table('administradores')->where('email', '=', $email)->get();
            $counterAdmin = count($administrador);


            if($counterAdminTrab > 0 && $counterAdmin > 0 || $counterAdminTrab == null && $counterAdmin > 0){
                $counter = '0';
            }
            if($counterAdminTrab > 0 && $counterAdmin == null){
                $counter = '1';
            }
            if($counterAdminTrab == null && $counterAdmin == null){
                $counter = '-1';
            }

            return response()->json([
                       "administrador" => $adminTrab,
                        "count" => $counter
                    ]);
        }
    } 

    public function getAdministrador(Request $request)
    {
        if ($request->ajax()) {
            $email = $request->input('valor');

            $administrador = DB::table('trabajadores')->select('trabajadores.*')
                                 ->where('trabajadores.estado', '=', 'true')
                                 ->where('trabajadores.email', '=', $email)->first();
            $counter = count($administrador);

            if($counter > 0){
                return response()->json([
                    "administrador" => $administrador,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "administrador" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }

    public function getAdminCorreo(Request $request)
    {
        if ($request->ajax()) {

            $counter = Administrador::where('email','=',$request->input('valor'))
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

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $rut                = $request->input('rutExcel');
        $apellido_paterno   = $request->input('apellido_paternoExcel'); 
        $email              = $request->input('emailExcel');

        $administradores = DB::table('administradores')->select('*')
                                ->where('estado', '=', true);

        if(!is_null($rut)){
            if (strpos($rut, 'k') || strpos($rut, 'K')) {
                if (strpos($rut, 'k')) {
                    $administradores = $administradores->where(function ($q) use ($rut) {
                        $q = $q->orWhere('rut', '=', $rut);
                        $q = $q->orWhere('rut', '=', str_replace('k', 'K', $rut));
                    });
                }elseif(strpos($rut, 'K')) {
                    $administradores = $administradores->where(function ($q) use ($rut) {
                        $q = $q->orWhere('rut', '=', $rut);
                        $q = $q->orWhere('rut', '=', str_replace('K', 'k', $rut));
                    });
                }
            }else{
                $administradores = $administradores->where('rut','=', $rut);
            }
        }

        if(!is_null($apellido_paterno)){
            $administradores = $administradores->where('apellido_paterno','like','%'.$apellido_paterno.'%');
        }

        if(!is_null($email)){
            $administradores = $administradores->where('email','like','%'.$email.'%');
        }

        $administradores = $administradores->orderBy('email', 'asc')->get();

        return Excel::create('ListadoAdministradores', function($excel) use ($administradores) {
            $excel->sheet('Administradores', function($sheet) use ($administradores)
            {
                $count = 2;
                
                $sheet->row(1, ['Rut', 'Nombres', 'Apellido paterno', 'Apellido materno', 'Email']);
                foreach ($administradores as $key => $value) {
                    $sheet->row($count, [$value->rut, $value->nombres, $value->apellido_paterno, $value->apellido_materno, $value->email]);
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

}
