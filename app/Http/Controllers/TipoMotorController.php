<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\TipoMotor;
use App\Log;
use App\Curso;
use Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TipoMotorController extends Controller
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
        $vigencia = \Request::get('vigencia');

        //dd('codigo:'.$codigo);
        //dd('nombre:'.$nombre);
        //dd('vigencia:'.$vigencia);

        $tipoMotores = DB::table('tipoMotores')->select('codigo', 'nombre', 'vigencia');

        if(!is_null($vigencia) && $vigencia <> 2){
            $tipoMotores = $tipoMotores->where('vigencia','=',$vigencia);
        }

        if(!is_null($nombre)){
            $tipoMotores = $tipoMotores->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($codigo)){
            $tipoMotores = $tipoMotores->where('codigo','=',$codigo);
        }

        $tipoMotores = $tipoMotores->orderBy('codigo', 'asc')->get();
        
        foreach ($tipoMotores as $tipoMotor)
            if ( $tipoMotor->vigencia == 1 ){
                $tipoMotor->vigencia = "Activo";
            }else{
                $tipoMotor->vigencia = "Inactivo";
            }

        return view('tipoMotores.index')->with('tipoMotores', $tipoMotores)->with('vigencia', $vigencia);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tipoMotores.create');
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
            return redirect()->route('tipoMotores.create')->withInput()->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('tipoMotores.create')->withInput()->with('error','Debe ingresar un nombre');
        }

        //$this->validate($request, ['codigo' => 'unique:tipoMotores,codigo',$request->input('codigo')], );
        $rules = ['codigo' => 'unique:tipoMotores,codigo',$request->input('codigo')];
        $this->validate($request,$rules); 

        $tipoMotor = new TipoMotor;
        $tipoMotor->codigo = $request->input('codigo');

        /*Validacion de largos*/
        $count = mb_strlen($request->input('nombre'), 'UTF-8');
        if($count > 25){
            return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 25 caracteres');
        }else{
            $tipoMotor->nombre = $request->input('nombre');
        }

        $count = mb_strlen($request->input('descripcion'), 'UTF-8');
        if($count > 200){
            return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
        }else{
            $tipoMotor->descripcion = $request->input('descripcion');
        }

        if ( is_null($request->input('vigencia')) ){
            $tipoMotor->vigencia  = 0;
        }else{
            $tipoMotor->vigencia  = $request->input('vigencia');
        }

        $tipoMotor->save();
        
        //Guarda log al crear
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Tipo de Motor",
            'details' => "Se crea Tipo de Motor: " . $request->input('nombre'),
        ]);

        return redirect()->route('tipoMotores.index')->with('success','Tipo de motor creado correctamente');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($codigo)
    {
        $tipoMotor = TipoMotor::find($codigo);

        if ( $tipoMotor->vigencia == 1 ){
            $tipoMotor->vigencia = "Activo";
        }else{
            $tipoMotor->vigencia = "Inactivo";
        }

        return view('tipoMotores.show')->with('tipoMotor',$tipoMotor);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($codigo)
    {
        $tipoMotor = TipoMotor::find($codigo);

        if ( $tipoMotor->vigencia == 1 ){
            $tipoMotor->vigencia = "true";
        }else{
            $tipoMotor->vigencia = "false";
        }

        return view('tipoMotores.edit')->with('tipoMotor',$tipoMotor);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $codigo)
    {
        if(empty($request->input('codigo'))){
            return redirect()->route('tipoMotores.edit', $codigo)->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('tipoMotores.edit', $codigo)->with('error','Debe ingresar un nombre');
        }

        $counter = Curso::where('tipoMotores_codigo','=',$codigo)->count();

        if ($counter <>'0') {
            return redirect()->route('tipoMotores.edit', $codigo)->with('error','No se puede editar el registro, existe información relacionada');
        }else {
            $tipoMotor = TipoMotor::find($codigo);    
            $nombreAntiguo = $tipoMotor->nombre;

            /*Validacion de largos*/
            $count = mb_strlen($request->input('nombre'), 'UTF-8');
            if($count > 25){
                return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 25 caracteres');
            }else{
                $tipoMotor->nombre = $request->input('nombre');
            }

            $count = mb_strlen($request->input('descripcion'), 'UTF-8');
            if($count > 200){
                return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
            }else{
                $tipoMotor->descripcion = $request->input('descripcion');
            }

            if ( is_null($request->input('vigencia')) ){
                $tipoMotor->vigencia  = 0;
            }else{
                $tipoMotor->vigencia  = $request->input('vigencia');
            }

            $tipoMotor->save();

            //Guarda log al editar
            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Editar Tipo de Motor",
                'details' => "Se edita Tipo de Motor: " . $nombreAntiguo . " por: " . $request->input('nombre'),
            ]);

            return redirect()->route('tipoMotores.index')->with('success','Tipo de motor editado correctamente');
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($codigo)
    {
        $counter = Curso::where('tipoMotores_codigo','=',$codigo)->count();

        if ($counter <>'0') {
            return redirect()->route('tipoMotores.index')->with('error','No se puede eliminar el registro, existe información relacionada');
        }else {
            $tipoMotor = TipoMotor::find($codigo);
            if(!is_null($tipoMotor)){
                $tipoMotor->delete();

                //Guarda log al eliminar
                Log::create([
                    'id_user' => Auth::user()->id,
                    'action' => "Eliminar Tipo de Motor",
                    'details' => "Se elimina Tipo de Motor: " . $codigo,
                ]);

                return redirect()->route('tipoMotores.index')->with('success','Tipo de motor eliminado correctamente');
            }else{
                return redirect()->route('tipoMotores.index')->with('error','Registro no existe');
            }
        }
    }

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $codigo         = $request->input('codigoExcel');
        $nombre         = $request->input('nombreExcel'); 
        $vigencia       = $request->input('vigenciaExcel');   

        $tipoMotores = DB::table('tipoMotores')->select('codigo', 'nombre', 'vigencia');

        if(!is_null($codigo)){
            $tipoMotores = $tipoMotores->where('codigo','=',$codigo);
        }

        if(!is_null($nombre)){
            $tipoMotores = $tipoMotores->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($vigencia) && $vigencia <> 2){
            $tipoMotores = $tipoMotores->where('vigencia','=',$vigencia);
        }

        $tipoMotores = $tipoMotores->orderBy('codigo', 'asc')->get();

        return Excel::create('ListadoTipoMotores', function($excel) use ($tipoMotores) {
            $excel->sheet('Tipos de motores', function($sheet) use ($tipoMotores)
            {
                $count = 2;
                
                $sheet->row(1, ['Código', 'Nombre', 'Vigencia']);
                foreach ($tipoMotores as $key => $value) {
                    if ( $value->vigencia == 1 ){
                        $value->vigencia = "Activo";
                    }else{
                        $value->vigencia = "Inactivo";
                    }
                    $sheet->row($count, [$value->codigo, $value->nombre, $value->vigencia]);
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

    public function volver()
    {
        return view('tipoMotores.index');
    }

}