<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\CentroCosto;
use App\Log;
use App\Trabajador;
use Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class CentroCostoController extends Controller
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

        $centrosCostos = DB::table('centrosCostos')->select('codigo', 'nombre', 'vigencia');

        if(!is_null($vigencia) && $vigencia <> 2){
            $centrosCostos = $centrosCostos->where('vigencia','=',$vigencia);
        }

        if(!is_null($nombre)){
            $centrosCostos = $centrosCostos->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($codigo)){
            $centrosCostos = $centrosCostos->where('codigo','=',$codigo);
        }

        $centrosCostos = $centrosCostos->orderBy('codigo', 'asc')->get();

        foreach ($centrosCostos as $centroCostos)
            if ( $centroCostos->vigencia == 1 ){
                $centroCostos->vigencia = "Activo";
            }else{
                $centroCostos->vigencia = "Inactivo";
            }

        return view('centrosCostos.index')->with('centrosCostos', $centrosCostos)->with('vigencia', $vigencia);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('centrosCostos.create');
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
            return redirect()->route('centrosCostos.create')->withInput()->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('centrosCostos.create')->withInput()->with('error','Debe ingresar un nombre');
        }

        //$this->validate($request, ['codigo' => 'unique:centrosCostos,codigo',$request->input('codigo')], );
        $rules = ['codigo' => 'unique:centrosCostos,codigo',$request->input('codigo')];
        $this->validate($request,$rules); 
        $centroCostos = new CentroCosto;
        $centroCostos->codigo = $request->input('codigo');

        /*Validacion de largos*/
        $count = mb_strlen($request->input('nombre'), 'UTF-8');
        if($count > 30){
            return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 30 caracteres');
        }else{
            $centroCostos->nombre = $request->input('nombre');
        }

        $count = mb_strlen($request->input('descripcion'), 'UTF-8');
        if($count > 200){
            return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
        }else{
            $centroCostos->descripcion = $request->input('descripcion');
        }

        if ( is_null($request->input('vigencia')) ){
            $centroCostos->vigencia  = 0;
        }else{
            $centroCostos->vigencia  = $request->input('vigencia');
        }

        $centroCostos->save();
        
        //Guarda log al crear
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Centro de Costos",
            'details' => "Se crea Centro de Costos: " . $request->input('nombre'),
        ]);


        $centrosCostos = CentroCosto::get();

        foreach ($centrosCostos as $temp)
            if ( $temp->vigencia == 1 ){
                $temp->vigencia = "Activo";
            }else{
                $temp->vigencia = "Inactivo";
            }

        return redirect()->route('centrosCostos.index')->with('success','Centro de costos creado correctamente');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($codigo)
    {
        $centroCostos = CentroCosto::find($codigo);

        if ( $centroCostos->vigencia == 1 ){
            $centroCostos->vigencia = "Activo";
        }else{
            $centroCostos->vigencia = "Inactivo";
        }

        return view('centrosCostos.show')->with('centroCostos',$centroCostos);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($codigo)
    {
        $centroCostos = CentroCosto::find($codigo);

        if ( $centroCostos->vigencia == 1 ){
            $centroCostos->vigencia = "true";
        }else{
            $centroCostos->vigencia = "false";
        }

        return view('centrosCostos.edit')->with('centroCostos',$centroCostos);
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
            return redirect()->route('centrosCostos.edit', $codigo)->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('centrosCostos.edit', $codigo)->with('error','Debe ingresar un nombre');
        }

        $counter = Trabajador::where('centrosCostos_codigo','=',$codigo)->count();

        if ($counter <>'0') {
            return redirect()->route('centrosCostos.edit', $codigo)->with('error','No se puede editar el registro, existe información relacionada');
        }else {
            $centroCostos = CentroCosto::find($codigo);    
            $nombreAntiguo = $centroCostos->nombre;

            /*Validacion de largos*/
            $count = mb_strlen($request->input('nombre'), 'UTF-8');
            if($count > 30){
                return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 30 caracteres');
            }else{
                $centroCostos->nombre = $request->input('nombre');
            }

            $count = mb_strlen($request->input('descripcion'), 'UTF-8');
            if($count > 200){
                return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
            }else{
                $centroCostos->descripcion = $request->input('descripcion');
            }

            if ( is_null($request->input('vigencia')) ){
                $centroCostos->vigencia  = 0;
            }else{
                $centroCostos->vigencia  = $request->input('vigencia');
            }

            $centroCostos->save();

            //Guarda log al editar
            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Editar Centro de Costos",
                'details' => "Se edita Centro de Costos: " . $nombreAntiguo . " por: " . $request->input('nombre'),
            ]);

            $centrosCostos = CentroCosto::get();

            foreach ($centrosCostos as $temp)
                if ( $temp->vigencia == 1 ){
                    $temp->vigencia = "Activo";
                }else{
                    $temp->vigencia = "Inactivo";
                }

            return redirect()->route('centrosCostos.index')->with('success','Centro de costos editado correctamente');
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
        $counter = Trabajador::where('centrosCostos_codigo','=',$codigo)->count();

        if ($counter <>'0') {
            return redirect()->route('centrosCostos.index')->with('error','No se puede eliminar el registro, existe información relacionada');
        }else {
            $centroCostos = CentroCosto::find($codigo);
            if(!is_null($centroCostos)){
                $centroCostos->delete();

                //Guarda log al eliminar
                Log::create([
                    'id_user' => Auth::user()->id,
                    'action' => "Eliminar Centro de Costos",
                    'details' => "Se elimina Centro de Costos: " . $codigo,
                ]);

                return redirect()->route('centrosCostos.index')->with('success','Centro de costos eliminado correctamente');
            }else{
                return redirect()->route('centrosCostos.index')->with('error','Registro no existe');
            }
        }
    }    

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $codigo         = $request->input('codigoExcel');
        $nombre         = $request->input('nombreExcel'); 
        $vigencia       = $request->input('vigenciaExcel');   

        $centrosCostos = DB::table('centrosCostos')->select('codigo', 'nombre', 'vigencia');

        if(!is_null($codigo)){
            $centrosCostos = $centrosCostos->where('codigo','=',$codigo);
        }

        if(!is_null($nombre)){
            $centrosCostos = $centrosCostos->where('nombre','like','%'.$nombre.'%');
        }
        
        if(!is_null($vigencia) && $vigencia <> 2){
            $centrosCostos = $centrosCostos->where('vigencia','=',$vigencia);
        }
        
        $centrosCostos = $centrosCostos->orderBy('codigo', 'asc')->get();

        return Excel::create('ListadoCentrosCostos', function($excel) use ($centrosCostos) {
            $excel->sheet('Centros de costos', function($sheet) use ($centrosCostos)
            {
                $count = 2;
                
                $sheet->row(1, ['Código', 'Nombre', 'Vigencia']);
                foreach ($centrosCostos as $key => $value) {
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
        return view('centrosCostos.index');
    }

}