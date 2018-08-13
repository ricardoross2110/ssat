<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Calificacion;
use App\Log;
use App\Curso;
use App\Nivel;
use App\Prerrequisito;
use App\Version;

use Auth;
use Illuminate\Support\Facades\DB;

class CalificacionController extends Controller
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

        if(is_null($codigo)){
            if(is_null($nombre)){
                $calificaciones = Calificacion::orderBy('codigo', 'asc')->get();
            }else{
                $calificaciones = Calificacion::where('nombre','like','%'.$nombre.'%')->orderBy('codigo', 'asc')->get();
            }
        }else{
            if(is_null($nombre)){
                $calificaciones = Calificacion::where('codigo','=',$codigo)->orderBy('codigo', 'asc')->get();
            }else{
                $calificaciones = Calificacion::where('codigo','=',$codigo)->where('nombre','like','%'.$nombre.'%')->orderBy('codigo', 'asc')->get();
            }
        }       

        $calificacionCurso = Calificacion::select('calificaciones.codigo as codigo_calificacion', 'calificaciones.nombre as nombre_calificacion', 'niveles.nivel_num', 'cursos.codigo as codigo_curso', 'cursos.nombre as nombre_curso')
                ->join('niveles', 'niveles.calificaciones_codigo', '=', 'calificaciones.codigo')
                ->join('cursos', 'cursos.codigo', '=', 'niveles.cursos_codigo')
                ->groupBy('calificaciones.codigo', 'calificaciones.nombre', 'niveles.nivel_num', 'cursos.codigo', 'cursos.nombre')
                ->orderBy('calificaciones.codigo', 'asc')->get();        


        return view('calificaciones.index')->with('calificaciones', $calificaciones)
                                            ->with('calificacionCurso', $calificacionCurso);        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {   

        $cursos = Curso::where('estado', '=', 'true')
                            ->orderBy('codigo')->get(); 
        
        return view('calificaciones.create', compact('cursos', 'cursos'));

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
            return redirect()->route('calificaciones.create')->withInput()->with('error','Debe ingresar un código');
        }
        if(empty($request->input('nombre'))){
            return redirect()->route('calificaciones.create')->withInput()->with('error','Debe ingresar un nombre');
        }              

        //$this->validate($request, ['codigo' => 'unique:cursos,codigo',$request->input('codigo')], );
        $rules = ['codigo' => 'unique:calificaciones,codigo',$request->input('codigo')];
        $this->validate($request,$rules); 

        //valido que no venga vacio el nivel asociado a la calificacion
        if ( is_null(($request->input('niveles'))) ) {
            return redirect()->route('calificaciones.create')->withInput()->with('error','Niveles deben tener mínimo un curso asociado');
        }else {

            $calificacion = new Calificacion;
            $calificacion->codigo = $request->input('codigo');
            $calificacion->nombre = $request->input('nombre');
            $calificacion->save();
  
            //se guardan niveles de la calificacion
            $nivelId = $_POST['niveles'];
            $cursoId = $_POST['cursos'];
            for ($i=0; $i < sizeof($nivelId); $i++) { 
                $nivel = new Nivel;
                $nivel->nivel_num = $nivelId[$i];
                $nivel->calificaciones_codigo = $calificacion->codigo;
                $nivel->cursos_codigo = $cursoId[$i];
                $nivel->save();
            }          

            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Agregar Calificacion",
                'details' => "Se crea Calificacion: " . $request->input('nombre'),
            ]);

            return redirect()->route('calificaciones.index')->with('success','Calificacion creada correctamente');

        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($codigo)
    {
        $calificacion = Calificacion::find($codigo);

        $nivelesPre = Nivel::where('calificaciones_codigo','=', $codigo)->orderBy('nivel_num')->get();
        $niveles = $nivelesPre->groupBy('nivel_num');

        return view('calificaciones.show')
                            ->with('calificacion',$calificacion)
                            ->with('niveles',$niveles);

    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($codigo)
    {
        $calificacion = Calificacion::find($codigo);

        //validar que calificacion no se este dictanto (count=0)
        $counter = Version::where('calificaciones_codigo','=',$codigo)->count();
        
        if ($counter <>'0') {
            return redirect()->route('calificaciones.index')->with('error','No se puede editar calificación');
        }else {          

            $niveles = Nivel::where('calificaciones_codigo','=', $codigo)->orderBy('nivel_num')->get();
            $results = $niveles->groupBy('nivel_num');

           $cursos = Curso::where('estado', '=', 'true')
                            ->orderBy('codigo')->get();

            return view('calificaciones.edit')->with('calificacion',$calificacion)
                                             //   ->with('niveles',$niveles)
                                                ->with('cursos',$cursos)
                                                ->with('results',$results);
        }
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

        if(empty($request->input('nombre'))){
            return redirect()->route('calificaciones.edit', $codigo)->withInput()->with('error','Debe ingresar un nombre');
        }        

        //valido que no venga vacio el nivel asociado a la calificacion
        if ( is_null(($request->input('niveles'))) ) {
            return redirect()->route('calificaciones.edit', $codigo)->withInput()->with('error','Niveles deben tener mínimo un curso asociado');
        }else { 

            $calificacion = Calificacion::find($codigo);    
            $nombreAntiguo = $calificacion->nombre;
            $calificacion->nombre = $request->input('nombre');
            $calificacion->save();

            //se eliminan niveles del curso para luego guardar segun cambios edicion 
            $nivelesEliminar = Nivel::where('calificaciones_codigo','=',$codigo)->get();

            foreach($nivelesEliminar as $value) {
                $value->delete();
            }         

            //se guardan niveles de la calificacion
            $nivelId = $_POST['niveles'];
            $cursoId = $_POST['cursos'];
            for ($i=0; $i < sizeof($nivelId); $i++) { 
                $nivel = new Nivel;
                $nivel->nivel_num = $nivelId[$i];
                $nivel->calificaciones_codigo = $calificacion->codigo;
                $nivel->cursos_codigo = $cursoId[$i];
                $nivel->save();
            }           

            Log::create([
                'id_user' => Auth::user()->id,
                'action'  => "Editar Calificacion",
                'details' => "Se edita Calificacion: " . $nombreAntiguo . " por: " . $request->input('nombre'),
            ]);

            return redirect()->route('calificaciones.index')->with('success','Calificación editada correctamente');
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
        //validar que calificacion no se este dictanto (count=0)
        $counter = Version::where('calificaciones_codigo','=',$codigo)->count();
        
        if ($counter <>'0') {
            return redirect()->route('calificaciones.index')->with('error','No se puede eliminar el registro, existe información relacionada');
        }else {   
            //se eliminan niveles de calificacion asociada 
            $nivelesEliminar = Nivel::where('calificaciones_codigo','=',$codigo)->get();
            foreach($nivelesEliminar as $value) {
                $value->delete();
            } 

            $calificacion = Calificacion::find($codigo);
            $calificacion->delete();

            Log::create([
                'id_user' => Auth::user()->id,
                'action'  => "Eliminar Calificacion",
                'details' => "Se elimina Calificacion: " . $codigo,
            ]);

            return redirect()->route('calificaciones.index')->with('success','Calificación eliminada correctamente');
        }
    }

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $codigo         = $request->input('codigoExcel');
        $nombre         = $request->input('nombreExcel');

        if(is_null($codigo)){
            if(is_null($nombre)){
                $calificaciones = Calificacion::orderBy('codigo', 'asc')->get();
            }else{
                $calificaciones = Calificacion::where('nombre','like','%'.$nombre.'%')->orderBy('codigo', 'asc')->get();
            }
        }else{
            if(is_null($nombre)){
                $calificaciones = Calificacion::where('codigo','=',$codigo)->orderBy('codigo', 'asc')->get();
            }else{
                $calificaciones = Calificacion::where('codigo','=',$codigo)->where('nombre','like','%'.$nombre.'%')->orderBy('codigo', 'asc')->get();
            }
        }       

        /*
        $calificacionCurso = Calificacion::select('calificaciones.codigo as codigo_calificacion', 'calificaciones.nombre as nombre_calificacion', 'niveles.nivel_num', 'cursos.codigo as codigo_curso', 'cursos.nombre as nombre_curso')
                ->join('niveles', 'niveles.calificaciones_codigo', '=', 'calificaciones.codigo')
                ->join('cursos', 'cursos.codigo', '=', 'niveles.cursos_codigo')
                ->groupBy('calificaciones.codigo', 'calificaciones.nombre', 'niveles.nivel_num', 'cursos.codigo', 'cursos.nombre')
                ->orderBy('calificaciones.codigo', 'asc')->get();
        */ 

        return Excel::create('ListadoCalificaciones', function($excel) use ($calificaciones) {
            $excel->sheet('Calificaciones', function($sheet) use ($calificaciones)
            {
                $count = 2;
                
                $sheet->row(1, ['Código', 'Nombre']);
                foreach ($calificaciones as $key => $value) {
                    $sheet->row($count, [$value->codigo, $value->nombre]);
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

    // metodo para cargar datos curso seleccionado en tabla de niveles
    public function cargaCurso(Request $request)
    {
        if ($request->ajax()) {

            $cursoSelecionado= $request->input('curso');
            $cursos = Curso::where('codigo','=', $cursoSelecionado)->get();

            $prerrequisitosCurso = Prerrequisito::where('cursos_codigo_hijo','=', $cursoSelecionado)->get();    
            $cursosPre = Prerrequisito::where('cursos_codigo_hijo','=', $cursoSelecionado)->count();
            if ($cursosPre == 0 ) {
                return response()->json([
                    "mensaje" => $cursoSelecionado,
                    "mensaje2" => $cursos[0]->nombre,
                    "mensaje3" => 'sin_pre',
                    "prerrequisitosCurso" => $prerrequisitosCurso
                ]);

            }else{ 

                return response()->json([
                    "mensaje" => $cursoSelecionado,
                    "mensaje2" => $cursos[0]->nombre,
                    "mensaje3" => 'con_pre',
                    "prerrequisitosCurso" => $prerrequisitosCurso
                ]);

            }
        }     
    }

    public function getCalificacion(Request $request)
    {
        if ($request->ajax()) {
            $calificacion = Calificacion::find($request->input('valor'));

            $counter = count($calificacion);

            if($counter > 0){
                return response()->json([
                    "calificacion" => $calificacion,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "calificacion" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }          

}