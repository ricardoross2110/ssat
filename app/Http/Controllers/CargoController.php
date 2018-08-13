<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use App\Cargo;
use App\Log;
use App\Trabajador;
use Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CargoController extends Controller
{
    protected $cargosGlobal;

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

        $cargos = DB::table('cargos')->select('codigo', 'nombre', 'vigencia');

        if(!is_null($vigencia) && $vigencia <> 2){
            $cargos = $cargos->where('vigencia','=',$vigencia);
        }

        if(!is_null($nombre)){
            $cargos = $cargos->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($codigo)){
            $cargos = $cargos->where('codigo','=',$codigo);
        }

        $cargos = $cargos->orderBy('codigo', 'asc')->get();

        foreach ($cargos as $cargo)
            if ( $cargo->vigencia == 1 ){
                $cargo->vigencia = "Activo";
            }else{
                $cargo->vigencia = "Inactivo";
            }

        return view('cargos.index')->with('cargos', $cargos)->with('vigencia', $vigencia);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $items = Cargo::all('codigo', 'nombre');
        return view('cargos.create', compact('items',$items));
  
       // return view('cargos.create');
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
            return redirect()->route('cargos.create')->withInput()->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('cargos.create')->withInput()->with('error','Debe ingresar un nombre');
        }

        //$this->validate($request, ['codigo' => 'unique:cargos,codigo',$request->input('codigo')], );
        $rules = ['codigo' => 'unique:cargos,codigo',$request->input('codigo')];
        $this->validate($request,$rules); 

        $cargo = new Cargo;
        $cargo->codigo = $request->input('codigo');

        /*Validacion de largos*/
        $count = mb_strlen($request->input('nombre'), 'UTF-8');
        if($count > 50){
            return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 50 caracteres');
        }else{
            $cargo->nombre = $request->input('nombre');
        }

        $count = mb_strlen($request->input('descripcion'), 'UTF-8');
        if($count > 200){
            return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
        }else{
            $cargo->descripcion  = $request->input('descripcion');
        }

        if ( is_null($request->input('vigencia')) ){
            $cargo->vigencia  = 0;
        }else{
            $cargo->vigencia  = $request->input('vigencia');
        }

        $cargo->save();
        
        //Guarda log al crear
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Cargo",
            'details' => "Se crea Cargo: " . $request->input('nombre'),
        ]);

        $cargos = Cargo::get();

        foreach ($cargos as $temp)
            if ( $temp->vigencia == 1 ){
                $temp->vigencia = "Activo";
            }else{
                $temp->vigencia = "Inactivo";
            }

        return redirect()->route('cargos.index')->with('success','Cargo creado correctamente');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($codigo)
    {
        $cargo = Cargo::find($codigo);

        if ( $cargo->vigencia == 1 ){
            $cargo->vigencia = "Activo";
        }else{
            $cargo->vigencia = "Inactivo";
        }

        return view('cargos.show')->with('cargo',$cargo);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($codigo)
    {
        $cargo = Cargo::find($codigo);

        if ( $cargo->vigencia == 1 ){
            $cargo->vigencia = "true";
        }else{
            $cargo->vigencia = "false";
        }

        return view('cargos.edit')->with('cargo',$cargo);
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
            return redirect()->route('cargos.edit', $codigo)->with('error','Debe ingresar un Código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('cargos.edit', $codigo)->with('error','Debe ingresar un Nombre');
        }

        $counter = Trabajador::where('cargos_codigo','=',$codigo)->count();

        if ($counter <>'0') {
            return redirect()->route('cargos.edit', $codigo)->with('error','No se puede editar el registro, existe información relacionada');
        }else {
            $cargo = Cargo::find($codigo);    
            $nombreAntiguo = $cargo->nombre;

            /*Validacion de largos*/
            $count = mb_strlen($request->input('nombre'), 'UTF-8');
            if($count > 50){
                return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 50 caracteres');
            }else{
                $cargo->nombre = $request->input('nombre');
            }

            $count = mb_strlen($request->input('descripcion'), 'UTF-8');
            if($count > 200){
                return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
            }else{
                $cargo->descripcion  = $request->input('descripcion');
            }

            if ( is_null($request->input('vigencia')) ){
                $cargo->vigencia  = 0;
            }else{
                $cargo->vigencia  = $request->input('vigencia');
            }

            $cargo->save();

            //Guarda log al editar
            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Editar Cargo",
                'details' => "Se edita Cargo: " . $nombreAntiguo . " por: " . $request->input('nombre'),
            ]);

            $cargos = Cargo::get();

            foreach ($cargos as $temp)
                if ( $temp->vigencia == 1 ){
                    $temp->vigencia = "Activo";
                }else{
                    $temp->vigencia = "Inactivo";
                }

            return redirect()->route('cargos.index')->with('success','Cargo editado correctamente');
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
        $counter = Trabajador::where('cargos_codigo','=',$codigo)->count();

        if ($counter <>'0') {
            return redirect()->route('cargos.index')->with('error','No se puede eliminar el registro, existe información relacionada');
        }else {
            $cargo = Cargo::find($codigo);
            if(!is_null($cargo)){
                $cargo->delete();

                //Guarda log al eliminar
                Log::create([
                    'id_user' => Auth::user()->id,
                    'action' => "Eliminar Cargo",
                    'details' => "Se elimina Cargo: " . $codigo,
                ]);

                return redirect()->route('cargos.index')->with('success','Cargo eliminado correctamente');
            }else{
                return redirect()->route('cargos.index')->with('error','Registro no existe');
            }
        }
    }

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $codigo         = $request->input('codigoExcel');
        $nombre         = $request->input('nombreExcel'); 
        $vigencia       = $request->input('vigenciaExcel');   

        $cargos = DB::table('cargos')->select('codigo', 'nombre', 'vigencia');

        if(!is_null($codigo)){
            $cargos = $cargos->where('codigo','=',$codigo);
        }

        if(!is_null($nombre)){
            $cargos = $cargos->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($vigencia) && $vigencia <> 2){
            $cargos = $cargos->where('vigencia','=',$vigencia);
        }

        $cargos = $cargos->orderBy('codigo', 'asc')->get();

        return Excel::create('ListadoCargos', function($excel) use ($cargos) {
            $excel->sheet('Cargos', function($sheet) use ($cargos)
            {
                $count = 2;
                
                $sheet->row(1, ['Código', 'Nombre', 'Vigencia']);
                foreach ($cargos as $key => $value) {
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

    //metodo para cargar data en tabla usando json
    /*public function data_cargos()
    {
        return Datatables::of(Cargo::query())->make(true);
    }*/

    public function volver()
    {
        return view('cargos.index');
    }

}