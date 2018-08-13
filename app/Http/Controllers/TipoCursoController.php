<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\TipoCurso;
use App\Log;
use App\Curso;
use Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class TipoCursoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //$tipoCursos = TipoCurso::get();

        $codigo = \Request::get('codigo');
        $nombre = \Request::get('nombre');
        $vigencia = \Request::get('vigencia');

        //dd('codigo:'.$codigo);
        //dd('nombre:'.$nombre);
        //dd('vigencia:'.$vigencia);

        $tipoCursos = DB::table('tipoCursos')->select('codigo', 'nombre', 'vigencia');

        if(!is_null($vigencia) && $vigencia <> 2){
            $tipoCursos = $tipoCursos->where('vigencia','=',$vigencia);
        }

        if(!is_null($nombre)){
            $tipoCursos = $tipoCursos->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($codigo)){
            $tipoCursos = $tipoCursos->where('codigo','=',$codigo);
        }

        $tipoCursos = $tipoCursos->orderBy('codigo', 'asc')->get();

        foreach ($tipoCursos as $tipoCurso)
            if ( $tipoCurso->vigencia == 1 ){
                $tipoCurso->vigencia = "Activo";
            }else{
                $tipoCurso->vigencia = "Inactivo";
            }

        return view('tipoCursos.index')->with('tipoCursos', $tipoCursos)->with('vigencia', $vigencia);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('tipoCursos.create');
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
            return redirect()->route('tipoCursos.create')->withInput()->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('tipoCursos.create')->withInput()->with('error','Debe ingresar un nombre');
        }

        //$this->validate($request, ['codigo' => 'unique:tipoCursos,codigo',$request->input('codigo')], );
        $rules = ['codigo' => 'unique:tipoCursos,codigo',$request->input('codigo')];
        $this->validate($request,$rules); 

        $tipoCurso = new TipoCurso;
        $tipoCurso->codigo = $request->input('codigo');

        /*Validacion de largos*/
        $count = mb_strlen($request->input('nombre'), 'UTF-8');
        if($count > 25){
            return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 25 caracteres');
        }else{
            $tipoCurso->nombre = $request->input('nombre');
        }

        $count = mb_strlen($request->input('descripcion'), 'UTF-8');
        if($count > 200){
            return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
        }else{
            $tipoCurso->descripcion = $request->input('descripcion');
        }

        if ( is_null($request->input('vigencia')) ){
            $tipoCurso->vigencia  = 0;
        }else{
            $tipoCurso->vigencia  = $request->input('vigencia');
        }

        $tipoCurso->save();
        
        //Guarda log al crear
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Tipo de Curso",
            'details' => "Se crea Tipo de Curso: " . $request->input('nombre'),
        ]);

        return redirect()->route('tipoCursos.index')->with('success','Tipo de curso creado correctamente');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($codigo)
    {
        $tipoCurso = TipoCurso::find($codigo);

        if ( $tipoCurso->vigencia == 1 ){
            $tipoCurso->vigencia = "Activo";
        }else{
            $tipoCurso->vigencia = "Inactivo";
        }

        return view('tipoCursos.show')->with('tipoCurso',$tipoCurso);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($codigo)
    {
        $tipoCurso = TipoCurso::find($codigo);

        if ( $tipoCurso->vigencia == 1 ){
            $tipoCurso->vigencia = "true";
        }else{
            $tipoCurso->vigencia = "false";
        }

        return view('tipoCursos.edit')->with('tipoCurso',$tipoCurso);
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
            return redirect()->route('tipoCursos.edit', $codigo)->with('error','Debe ingresar un código');
        }elseif(empty($request->input('nombre'))){
            return redirect()->route('tipoCursos.edit', $codigo)->with('error','Debe ingresar un nombre');
        }

        $counter = Curso::where('tipoCursos_codigo','=',$codigo)->count();

        if ($counter <>'0') {
            return redirect()->route('tipoCursos.edit', $codigo)->with('error','No se puede editar el registro, existe información relacionada');
        }else {
            $tipoCurso = TipoCurso::find($codigo);    
            $nombreAntiguo = $tipoCurso->nombre;

            /*Validacion de largos*/
            $count = mb_strlen($request->input('nombre'), 'UTF-8');
            if($count > 25){
                return back()->with('error', 'Nombre demasiado largo, el máximo permitido es 25 caracteres');
            }else{
                $tipoCurso->nombre = $request->input('nombre');
            }

            $count = mb_strlen($request->input('descripcion'), 'UTF-8');
            if($count > 200){
                return back()->with('error', 'Descripción demasiado larga, el máximo permitido es 200 caracteres');
            }else{
                $tipoCurso->descripcion = $request->input('descripcion');
            }

            if ( is_null($request->input('vigencia')) ){
                $tipoCurso->vigencia  = 0;
            }else{
                $tipoCurso->vigencia  = $request->input('vigencia');
            }

            $tipoCurso->save();

            //Guarda log al editar
            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Editar Tipo de Curso",
                'details' => "Se edita Tipo de Curso: " . $nombreAntiguo . " por: " . $request->input('nombre'),
            ]);

            return redirect()->route('tipoCursos.index')->with('success','Tipo de curso editado correctamente');
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
        $counter = Curso::where('tipoCursos_codigo','=',$codigo)->count();

        if ($counter <>'0') {
            return redirect()->route('tipoCursos.index')->with('error','No se puede eliminar el registro, existe información relacionada');
        }else {
            $tipoCurso = TipoCurso::find($codigo);
            if(!is_null($tipoCurso)){
                $tipoCurso->delete();

                //Guarda log al eliminar
                Log::create([
                    'id_user' => Auth::user()->id,
                    'action' => "Eliminar Tipo de Curso",
                    'details' => "Se elimina Tipo de Curso: " . $codigo,
                ]);

                return redirect()->route('tipoCursos.index')->with('success','Tipo de curso eliminado correctamente');
            }else{
                return redirect()->route('tipoCursos.index')->with('error','Registro no existe');
            }
        }
    }

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $codigo         = $request->input('codigoExcel');
        $nombre         = $request->input('nombreExcel'); 
        $vigencia       = $request->input('vigenciaExcel');   

        $tipoCursos = DB::table('tipoCursos')->select('codigo', 'nombre', 'vigencia');

        if(!is_null($vigencia) && $vigencia <> 2){
            $tipoCursos = $tipoCursos->where('vigencia','=',$vigencia);
        }

        if(!is_null($nombre)){
            $tipoCursos = $tipoCursos->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($codigo)){
            $tipoCursos = $tipoCursos->where('codigo','=',$codigo);
        }

        $tipoCursos = $tipoCursos->orderBy('codigo', 'asc')->get();

        return Excel::create('ListadoTipoCursos', function($excel) use ($tipoCursos) {
            $excel->sheet('Tipos de cursos', function($sheet) use ($tipoCursos)
            {
                $count = 2;
                
                $sheet->row(1, ['Código', 'Nombre', 'Vigencia']);
                foreach ($tipoCursos as $key => $value) {
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
        return view('tipoCursos.index');
    }

}