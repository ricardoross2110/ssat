<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Sucursal;
use App\Comuna;
use App\Log;
use App\Version;
use App\Curso;
use App\Trabajador;
use Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class SucursalController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$sucursales = Sucursal::get();

        $codigo = \Request::get('codigo');
        $nombre = \Request::get('nombre');
        $vigencia = \Request::get('vigencia');

        //dd('codigo:'.$codigo);
        //dd('nombre:'.$nombre);
        //dd('vigencia:'.$vigencia);

        $sucursales = DB::table('sucursales')->select('sucursales.*', 'comunas.nombre as comuna')->join('comunas', 'sucursales.comunas_codigo', '=', 'comunas.codigo');

        if(!is_null($vigencia) && $vigencia <> 2){
            $sucursales = $sucursales->where('vigencia','=',$vigencia);
        }

        if(!is_null($codigo)){
            $sucursales = $sucursales->where('sucursales.codigo','=', $codigo);
        }

        if(!is_null($nombre)){
            $sucursales = $sucursales->where('sucursales.nombre','like','%'.$nombre.'%');
        }

        $sucursales = $sucursales->orderBy('codigo', 'asc')->get();

        foreach ($sucursales as $sucursal)
            if ( $sucursal->vigencia == 1 ){
                $sucursal->vigencia = "Activo";
            }else{
                $sucursal->vigencia = "Inactivo";
            }

        return view('sucursales.index')->with('sucursales', $sucursales)->with('vigencia', $vigencia);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {

        $comunas = [];

        foreach(Comuna::orderBy('nombre', 'asc')->get() as $comuna):
            $comunas[$comuna->codigo] = $comuna->nombre;
        endforeach;

        return view('sucursales.create')->with(array('comunas' => $comunas));
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
            return redirect()->route('sucursales.create')->withInput()->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('sucursales.create')->withInput()->with('error','Debe ingresar un nombre');
        }elseif(empty($request->input('tipo'))){
            return redirect()->route('sucursales.create')->withInput()->with('error','Debe seleccionar un tipo');
        }elseif(empty($request->input('comuna'))){
            return redirect()->route('sucursales.create')->withInput()->with('error','Debe seleccionar una comuna');
        }

        //$this->validate($request, ['codigo' => 'unique:sucursales,codigo',$request->input('codigo')], );
        $rules = ['codigo' => 'unique:sucursales,codigo',$request->input('codigo')];
        $this->validate($request,$rules); 
        $sucursal = new Sucursal;
        $sucursal->codigo = $request->input('codigo');
        $sucursal->tipo   = $request->input('tipo');
        $sucursal->comunas_codigo = $request->input('comuna');

        /*Validacion de largos*/
        $count = mb_strlen($request->input('nombre'), 'UTF-8');
        if($count > 30){
            return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 30 caracteres');
        }else{
            $sucursal->nombre = $request->input('nombre');
        }

        $count = mb_strlen($request->input('descripcion'), 'UTF-8');
        if($count > 200){
            return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
        }else{
            $sucursal->descripcion = $request->input('descripcion');
        }

        if ( is_null($request->input('vigencia')) ){
            $sucursal->vigencia  = 0;
        }else{
            $sucursal->vigencia  = $request->input('vigencia');
        }

        $sucursal->save();
        
        //Guarda log al crear
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Sucursal",
            'details' => "Se crea Sucursal: " . $request->input('nombre'),
        ]);

        $sucursales = Sucursal::get();

        foreach ($sucursales as $temp)
            if ( $temp->vigencia == 1 ){
                $temp->vigencia = "Activo";
            }else{
                $temp->vigencia = "Inactivo";
            }

        return redirect()->route('sucursales.index')->with('success','Sucursal creada correctamente');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($codigo)
    {
        $sucursal = Sucursal::find($codigo);

        if ( $sucursal->vigencia == 1 ){
            $sucursal->vigencia = "Activo";
        }else{
            $sucursal->vigencia = "Inactivo";
        }

        if ( $sucursal->tipo == 1 ){
            $sucursal->tipo = "Sucursal";
        }else{
            $sucursal->tipo = "Faena";
        }        

        return view('sucursales.show')->with('sucursal',$sucursal);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($codigo)
    {
        $sucursal = Sucursal::find($codigo);

        if ( $sucursal->vigencia == 1 ){
            $sucursal->vigencia = "true";
        }else{
            $sucursal->vigencia = "false";
        }

        $comunas = Comuna::orderBy('nombre', 'asc')->get();

        return view('sucursales.edit', compact('sucursal', 'comunas'));

        /*$comunas = [];

        foreach(Comuna::get() as $comuna):
            $comunas[$comuna->codigo] = $comuna->nombre;
        endforeach;*/

        //return view('sucursales.edit')->with('sucursal',$sucursal)->with(array('comunas' => $comunas));
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
            return redirect()->route('sucursales.edit', $codigo)->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('sucursales.edit', $codigo)->with('error','Debe ingresar un nombre');
        }elseif(empty($request->input('tipo'))){
            return redirect()->route('sucursales.edit', $codigo)->with('error','Debe seleccionar un tipo');
        }elseif(empty($request->input('comuna'))){
            return redirect()->route('sucursales.edit', $codigo)->with('error','Debe seleccionar una comuna');
        }
        
        $count1 = Version::where('lugar_ejecucion','=',$codigo)->count();

        $count2 = Curso::where('sucursales_codigo','=',$codigo)->count();

        $count3 = Trabajador::where('sucursales_codigo','=',$codigo)->count();

        $counter = $count1 + $count2 + $count3;
        
        if ($counter <>'0') {
            return redirect()->route('sucursales.edit', $codigo)->with('error','No se puede editar el registro, existe información relacionada');
        }else {
            $sucursal = Sucursal::find($codigo);    
            $nombreAntiguo = $sucursal->nombre;
            $sucursal->tipo   = $request->input('tipo');
            $sucursal->comunas_codigo = $request->input('comuna');

            /*Validacion de largos*/
            $count = mb_strlen($request->input('nombre'), 'UTF-8');
            if($count > 30){
                return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 30 caracteres');
            }else{
                $sucursal->nombre = $request->input('nombre');
            }

            $count = mb_strlen($request->input('descripcion'), 'UTF-8');
            if($count > 200){
                return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
            }else{
                $sucursal->descripcion = $request->input('descripcion');
            }

            if ( is_null($request->input('vigencia')) ){
                $sucursal->vigencia  = 0;
            }else{
                $sucursal->vigencia  = $request->input('vigencia');
            }

            $sucursal->save();

            //Guarda log al editar
            Log::create([
                'id_user' => Auth::user()->id,
                'action'  => "Editar Sucursal",
                'details' => "Se edita Sucursal: " . $nombreAntiguo . " por: " . $request->input('nombre'),
            ]);

            return redirect()->route('sucursales.index')->with('success','Sucursal editada correctamente');
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
        $count1 = Version::where('lugar_ejecucion','=',$codigo)->count();

        $count2 = Curso::where('sucursales_codigo','=',$codigo)->count();

        $count3 = Trabajador::where('sucursales_codigo','=',$codigo)->count();

        $counter = $count1 + $count2 + $count3;
        
        if ($counter <>'0') {
            return redirect()->route('sucursales.index')->with('error','No se puede eliminar el registro, existe información relacionada');
        }else {
            $sucursal = Sucursal::find($codigo);
            if(!is_null($sucursal)){
                $sucursal->delete();

                //Guarda log al eliminar
                Log::create([
                    'id_user' => Auth::user()->id,
                    'action'  => "Eliminar Sucursal",
                    'details' => "Se elimina Sucursal: " . $codigo,
                ]);

                return redirect()->route('sucursales.index')->with('success','Sucursal eliminada correctamente');
            }else{
                return redirect()->route('sucursales.index')->with('error','Registro no existe');
            }
        }
    }

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $codigo         = $request->input('codigoExcel');
        $nombre         = $request->input('nombreExcel'); 
        $vigencia       = $request->input('vigenciaExcel');   

        $sucursales = DB::table('sucursales')->select('sucursales.*', 'comunas.nombre as comuna')->join('comunas', 'sucursales.comunas_codigo', '=', 'comunas.codigo');

        if(!is_null($codigo)){
            $sucursales = $sucursales->where('sucursales.codigo','=', $codigo);
        }

        if(!is_null($nombre)){
            $sucursales = $sucursales->where('sucursales.nombre','like','%'.$nombre.'%');
        }
        
        if(!is_null($vigencia) && $vigencia <> 2){
            $sucursales = $sucursales->where('vigencia','=',$vigencia);
        }

        $sucursales = $sucursales->orderBy('codigo', 'asc')->get();

        return Excel::create('ListadoSucursales', function($excel) use ($sucursales) {
            $excel->sheet('Sucursales', function($sheet) use ($sucursales)
            {
                $count = 2;
                
                $sheet->row(1, ['Código', 'Nombre', 'Comuna', 'Vigencia']);
                foreach ($sucursales as $key => $value) {
                    if ( $value->vigencia == 1 ){
                        $value->vigencia = "Activo";
                    }else{
                        $value->vigencia = "Inactivo";
                    }
                    $sheet->row($count, [$value->codigo, $value->nombre, $value->comuna, $value->vigencia]);
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

    public function volver()
    {
        return view('sucursales.index');
    }

     public function getSucursal(Request $request)
    {
        if ($request->ajax()) {
            $sucursal = Sucursal::find($request->input('valor'));

            $counter = count($sucursal);

            if($counter > 0){
                return response()->json([
                    "sucursal" => $sucursal,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "sucursal" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }

}