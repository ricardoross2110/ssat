<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\UnidadNegocio;
use App\Log;
use App\Curso;
use Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class UnidadNegocioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$unidadesNegocio = UnidadNegocio::get();

        $codigo = \Request::get('codigo');
        $nombre = \Request::get('nombre');

        //dd('codigo:'.$codigo);
        //dd('nombre:'.$nombre);

        if(is_null($codigo)){
            if(is_null($nombre)){
                $unidadesNegocio = UnidadNegocio::orderBy('codigo', 'asc')->get();
            }else{
                $unidadesNegocio = UnidadNegocio::where('nombre','like','%'.$nombre.'%')->orderBy('codigo', 'asc')->get();
            }
        }else{
            if(is_null($nombre)){
                $unidadesNegocio = UnidadNegocio::where('codigo','=',$codigo)->orderBy('codigo', 'asc')->get();
            }else{
                $unidadesNegocio = UnidadNegocio::where('codigo','=',$codigo)->where('nombre','like','%'.$nombre.'%')->orderBy('codigo', 'asc')->get();
            }
        }

        return view('unidadesNegocio.index')->with('unidadesNegocio', $unidadesNegocio);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('unidadesNegocio.create');
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
            return redirect()->route('unidadesNegocio.create')->withInput()->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('unidadesNegocio.create')->withInput()->with('error','Debe ingresar un nombre');
        }

        //$this->validate($request, ['codigo' => 'unique:unidadesNegocio,codigo',$request->input('codigo')], );
        $rules = ['codigo' => 'unique:unidadesNegocio,codigo',$request->input('codigo')];
        $this->validate($request,$rules); 

        $unidadNegocio = new UnidadNegocio;
        $unidadNegocio->codigo = $request->input('codigo');

        /*Validacion de largos*/
        $count = mb_strlen($request->input('nombre'), 'UTF-8');
        if($count > 30){
            return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 30 caracteres');
        }else{
            $unidadNegocio->nombre = $request->input('nombre');
        }

        $count = mb_strlen($request->input('descripcion'), 'UTF-8');
        if($count > 200){
            return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
        }else{
            $unidadNegocio->descripcion = $request->input('descripcion');
        }

        $unidadNegocio->save();
        
        //Guarda log al crear
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Unidad de Negocio",
            'details' => "Se crea Unidad de Negocio: " . $request->input('nombre'),
        ]);

     //   return redirect()->route('unidadesNegocio.index');
        return redirect()->route('unidadesNegocio.index')->with('success','Unidad de negocio creada correctamente');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($codigo)
    {
        $unidadNegocio = UnidadNegocio::find($codigo);
        return view('unidadesNegocio.show')->with('unidadNegocio', $unidadNegocio);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($codigo)
    {
        $unidadNegocio = UnidadNegocio::find($codigo);
        return view('unidadesNegocio.edit')->with('unidadNegocio',$unidadNegocio);
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
            return redirect()->route('unidadesNegocio.edit', $codigo)->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('unidadesNegocio.edit', $codigo)->with('error','Debe ingresar un nombre');
        }

        $counter = Curso::where('unidadesNegocio_codigo','=',$codigo)->count();

        if ($counter <>'0') {
            return redirect()->route('unidadesNegocio.edit', $codigo)->with('error','No se puede editar el registro, existe información relacionada');
        }else {
            $unidadNegocio = UnidadNegocio::find($codigo);    
            $nombreAntiguo = $unidadNegocio->nombre;

            /*Validacion de largos*/
            $count = mb_strlen($request->input('nombre'), 'UTF-8');
            if($count > 30){
                return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 30 caracteres');
            }else{
                $unidadNegocio->nombre = $request->input('nombre');
            }

            $count = mb_strlen($request->input('descripcion'), 'UTF-8');
            if($count > 200){
                return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
            }else{
                $unidadNegocio->descripcion = $request->input('descripcion');
            }
            $unidadNegocio->save();

            //Guarda log al editar
            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Editar Unidad de Negocio",
                'details' => "Se edita Unidad de Negocio: " . $nombreAntiguo . " por: " . $request->input('nombre'),
            ]);

            return redirect()->route('unidadesNegocio.index')->with('success','Unidad de negocio editado correctamente');
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
        $counter = Curso::where('unidadesNegocio_codigo','=',$codigo)->count();

        if ($counter <>'0') {
            return redirect()->route('unidadesNegocio.index')->with('error','No se puede eliminar el registro, existe información relacionada');
        }else {
            $unidadNegocio = UnidadNegocio::find($codigo);
            if(!is_null($unidadNegocio)){
                $unidadNegocio->delete();

                //Guarda log al eliminar
                Log::create([
                    'id_user' => Auth::user()->id,
                    'action' => "Eliminar Unidad de Negocio",
                    'details' => "Se elimina Unidad de Negocio: " . $codigo,
                ]);

                return redirect()->route('unidadesNegocio.index')->with('success','Unidad de negocio eliminada correctamente');
            }else{
                return redirect()->route('unidadesNegocio.index')->with('error','Registro no existe');
            }
        }
    }

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $codigo         = $request->input('codigoExcel');
        $nombre         = $request->input('nombreExcel');   

        if(is_null($codigo)){
            if(is_null($nombre)){
                $unidadesNegocio = UnidadNegocio::orderBy('codigo', 'asc')->get();
            }else{
                $unidadesNegocio = UnidadNegocio::where('nombre','like','%'.$nombre.'%')->orderBy('codigo', 'asc')->get();
            }
        }else{
            if(is_null($nombre)){
                $unidadesNegocio = UnidadNegocio::where('codigo','=',$codigo)->orderBy('codigo', 'asc')->get();
            }else{
                $unidadesNegocio = UnidadNegocio::where('codigo','=',$codigo)->where('nombre','like','%'.$nombre.'%')->orderBy('codigo', 'asc')->get();
            }
        }

        return Excel::create('ListadoUnidadesNegocio', function($excel) use ($unidadesNegocio) {
            $excel->sheet('Unidades de negocio', function($sheet) use ($unidadesNegocio)
            {
                $count = 2;
                
                $sheet->row(1, ['Código', 'Nombre']);
                foreach ($unidadesNegocio as $key => $value) {
                    $sheet->row($count, [$value->codigo, $value->nombre]);
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

    public function volver()
    {
        return view('unidadesNegocio.index');
    }

}
