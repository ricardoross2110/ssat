<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Curso;
use App\Evaluacion;
use App\TipoCurso;
use App\TipoMotor;
use App\Log;
use App\Prerrequisito;
use App\Documento;
use App\Sucursal;
use App\UnidadNegocio;
use App\Version;
use Auth;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CursoController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {

        $codigo             = \Request::get('codigo');
        $nombre             = \Request::get('nombre');
        $estado             = \Request::get('estado');
        $tipoCurso          = \Request::get('tipoCursoB');
        $tipoMotor          = \Request::get('tipoMotorB');
        $horas              = \Request::get('horas');
        $aprobacion_minima  = \Request::get('aprobacion_minima');
        $convalidable       = \Request::get('convalidable');
        $repechaje          = \Request::get('repechaje');

	
        $cursos = DB::table('cursos')->select('codigo', 'nombre', 'estado');

        if(!is_null($convalidable) && $convalidable <> 2){
            $cursos = $cursos->where('convalidable','=',$convalidable);
        }

        if(!is_null($repechaje) && $repechaje <> 2){
            $cursos = $cursos->where('repechaje','=',$repechaje);
        }

        if(!is_null($nombre)){
            $cursos = $cursos->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($codigo)){
            $cursos = $cursos->where('codigo','=',$codigo);
        }

        if(!is_null($estado) && $estado <> 2){
            $cursos = $cursos->where('estado','=',$estado);
        }

        if(!is_null($tipoCurso)){
            $cursos = $cursos->where('tipoCursos_codigo','=',$tipoCurso);
        }

        if(!is_null($tipoMotor)){
            $cursos = $cursos->where('tipoMotores_codigo','=',$tipoMotor);
        }

        if(!is_null($horas)){
            $cursos = $cursos->where('horas','=',$horas);
        }

        if(!is_null($aprobacion_minima)){
        	$aprobacion_minima  = str_replace(',', '.', $aprobacion_minima);
            $cursos = $cursos->where('aprobacion_minima','=',$aprobacion_minima);
        }

        $cursos = $cursos->orderBy('codigo', 'asc')->get();

        foreach ($cursos as $curso) {
            if ( $curso->estado == 1 ){
                $curso->estado = "Activo";
            }else{
                $curso->estado = "Cancelado";
            }
        }   

        $tipoCursos = [];

        foreach(TipoCurso::orderBy('nombre', 'asc')->get() as $tipoCurso):
            $tipoCursos[$tipoCurso->codigo] = $tipoCurso->nombre;
        endforeach;

        $tipoMotores = [];

        foreach(TipoMotor::orderBy('nombre', 'asc')->get() as $tipoMotor):
            $tipoMotores[$tipoMotor->codigo] = $tipoMotor->nombre;
        endforeach;

        $tipoCursoB       = \Request::get('tipoCursoB');
        $tipoMotorB       = \Request::get('tipoMotorB');
       
        return view('cursos.index')->with('cursos', $cursos)->with(array('tipoCursos' => $tipoCursos))->with(array('tipoMotores' => $tipoMotores))->with('convalidable', $convalidable)->with('repechaje', $repechaje)->with('estado', $estado)->with('tipoCursoB', $tipoCursoB)->with('tipoMotorB', $tipoMotorB);        

    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $tipoCursos = [];

        foreach(TipoCurso::where('vigencia','=',true)->orderBy('nombre', 'asc')->get() as $tipoCurso):
            $tipoCursos[$tipoCurso->codigo] = $tipoCurso->nombre;
        endforeach;

        $tipoMotores = [];

        foreach(TipoMotor::where('vigencia','=',true)->orderBy('nombre', 'asc')->get() as $tipoMotor):
            $tipoMotores[$tipoMotor->codigo] = $tipoMotor->nombre;
        endforeach;

        $cod_curso_ultimo = (DB::table('cursos')->max('codigo'))+1;
        $cursos = Curso::where('estado','=','true')->orderBy('nombre', 'asc')->get();
        $sucursales = Sucursal::where('sucursales.vigencia','=',true)->orderBy('nombre', 'asc')->get();
        $unidadesNegocio = UnidadNegocio::orderBy('nombre', 'asc')->get();

        return view('cursos.create')->with('tipoCursos',$tipoCursos)
                                    ->with('tipoMotores',$tipoMotores)
                                    ->with('cursos',$cursos)
                                    ->with('sucursales',$sucursales)
                                    ->with('unidadesNegocio',$unidadesNegocio)
                                    ->with('cod_curso_ultimo',$cod_curso_ultimo);
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
            return redirect()->route('cursos.create')->withInput()->with('error','Debe ingresar un código');
        }
        if(empty($request->input('nombre'))){
            return redirect()->route('cursos.create')->withInput()->with('error','Debe ingresar un nombre');
        }
        if(($request->input('estado')) == ''  ){
            return redirect()->route('cursos.create')->withInput()->with('error','Debe seleccionar un estado');
        }
        if(($request->input('tipoCurso')) == ''  ){
            return redirect()->route('cursos.create')->withInput()->with('error','Debe seleccionar un tipo de curso');
        }        
        if(empty($request->input('horas'))){
            return redirect()->route('cursos.create')->withInput()->with('error','Debe ingresar una cantidad de horas');
        }
        if(empty($request->input('aprobacion_minima'))){
            return redirect()->route('cursos.create')->withInput()->with('error','Debe ingresar una aprobación mínima');
        }
        if(($request->input('categoria')) == ''  ){
            return redirect()->route('cursos.create')->withInput()->with('error','Debe seleccionar una categoria');
        }        
        if (empty($request->input('nomEval')) ) {
            return redirect()->route('cursos.create')->withInput()->with('error','Debe ingresar al menos una evaluación');
        }

        $aprobacion_minima = $request->input('aprobacion_minima');
        $aprobacion_minima = str_replace(',', '.', $aprobacion_minima);
        if (($aprobacion_minima > 100) || ($aprobacion_minima < 0)) {
            return redirect()->route('cursos.create')->withInput()->with('error','La nota debe ser entre 0 y 100');   
        }

        // $count = 0;
        // $nombresEval = $request->input('nomEval');
        // foreach ($nombresEval as $key => $value) {            
        //     if (empty($value)){
        //         $count = $count + 1;
        //     }
        // }

        // if($count > 0){
        //     return redirect()->route('cursos.create')->withInput()->with('error','Debe ingresar nombre de evaluación');
        // }

        //$this->validate($request, ['codigo' => 'unique:cursos,codigo',$request->input('codigo')], );
        $rules = ['codigo' => 'unique:cursos,codigo',$request->input('codigo')];
        $this->validate($request,$rules); 

      
        $curso = new Curso;
        $curso->codigo = $request->input('codigo');
        $curso->nombre = $request->input('nombre');
       
        if ( is_null($request->input('estado')) ){
            $curso->estado  = 0;
        }else{
            $curso->estado  = $request->input('estado');
        }

        $curso->tipoCursos_codigo = $request->input('tipoCurso');
        
        $curso->tipoMotores_codigo = $request->input('tipoMotor');
        
        $curso->horas = $request->input('horas');
        $curso->aprobacion_minima = $aprobacion_minima;

        if ( is_null($request->input('convalidable')) ){
            $curso->convalidable  = 0;
        }else{
            $curso->convalidable  = $request->input('convalidable');
        }

        if ( is_null($request->input('repechaje')) ){
            $curso->repechaje  = 0;
        }else{
            $curso->repechaje  = $request->input('repechaje');
        }        

        $curso->categoria = $request->input('categoria');
        if ($request->input('categoria') == 'E') {
            $curso->unidadesNegocio_codigo = $request->input('unidadNegocio');
            $curso->sucursales_codigo = $request->input('sucursal');
        }else {
            $curso->unidadesNegocio_codigo = null;
            $curso->sucursales_codigo = null;
        }

        $curso->save();

        //se guardan evaluaciones del curso
        $nomEvaluacion = $_POST['nomEval'];
        $porcEvaluacion = $_POST['porcEval'];
        for ($i=0; $i < sizeof($nomEvaluacion); $i++) { 
            $evaluacion = new Evaluacion;
            $evaluacion->nombre = $nomEvaluacion[$i];
            $evaluacion->porcentaje = $porcEvaluacion[$i];
            $evaluacion->cursos_codigo = $curso->codigo;
            $evaluacion->save();
        }

        //valido que pueda venir vacio campo documentos
        $document = $request->file('documentos');
        if(count($document)>0){    
            //se obtiene el campo file definido en el formulario
            $file = $request->file('documentos');

            //se obtiene el nombre del archivo
            $nombre = $file->getClientOriginalName();
         
            //nombre unico
            $nombre_unico = time().$nombre;

            //se guarda un nuevo archivo en el disco local
            \Storage::disk('public')->put($nombre_unico,  \File::get($file));

            //Se obtiene ruta y nombre del archivo
            $path = 'app/public/'.$nombre_unico;     

            //se guarda un nuevo archivo en base datos
            $documentos = new Documento;
            $documentos->nombre = $nombre;
            $documentos->nombre_unico = $nombre_unico;
            $documentos->ruta = $path;
            $documentos->extension = $file->getClientOriginalExtension(); // obtengo extension archivo
            $documentos->cursos_codigo = $curso->codigo;
            $documentos->save();
        }

        //valido que pueda venir vacio campo prerrequisitos
        if ( !is_null(($request->input('to'))) ) {
            //se guardan prerrequisitos del curso
            $cursoPadre = $_POST['to'];
            for ($i=0; $i < sizeof($cursoPadre); $i++) { 
                $prerrequisito = new Prerrequisito;
                $prerrequisito->cursos_codigo_padre = $cursoPadre[$i];
                $prerrequisito->cursos_codigo_hijo = $curso->codigo;
                $prerrequisito->save();
            }  
        }

        //Guardar log al crear curso
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Curso",
            'details' => "Se crea Curso: " . $request->input('nombre'),
        ]);

        return redirect()->route('cursos.index')->with('success','Curso creado correctamente');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($codigo)
    {
        $curso = Curso::find($codigo);

        $curso->estado          = ($curso->estado) ? 'Si' : 'No' ;
        $curso->convalidable    = ($curso->convalidable) ? 'Si' : 'No' ;
        $curso->repechaje       = ($curso->repechaje) ? 'Si' : 'No' ;
        $curso->categoria       = ($curso->categoria == 'I') ? 'Interno' : 'Externo' ;

        $documentos = Documento::where('cursos_codigo','=',$codigo)->get();
        $evaluaciones = Evaluacion::where('cursos_codigo','=',$codigo)->get();
        $prerrequisitos = Prerrequisito::where('cursos_codigo_hijo','=',$codigo)->get();    

        return view('cursos.show')->with('curso',$curso)
                                   ->with('documentos',$documentos)
                                   ->with('evaluaciones',$evaluaciones)
                                   ->with('prerrequisitos',$prerrequisitos);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($codigo)
    {
        $curso = Curso::find($codigo);
        
        //validar que curso no se este dictanto o se haya dictado
        $counter = Version::where('cursos_codigo','=',$codigo)
                          ->where('status','<>','A')->count();
        
        if ($curso->estado == false || $counter <>'0') {
            return redirect()->route('cursos.index')->with('error','No se puede editar el curso');
        }else {        
         
            $cursos = Curso::where('estado', '=', 'true')
                            ->where('codigo', '<>', $codigo)
                            ->orderBy('nombre')->get();

            $tipoMotores = TipoMotor::where('vigencia','=',true)->orderBy('nombre', 'asc')->get();
            $tipoCursos = TipoCurso::where('vigencia','=',true)->orderBy('nombre', 'asc')->get();
            $unidadesNegocio = UnidadNegocio::orderBy('nombre', 'asc')->get();
            $sucursales = Sucursal::where('sucursales.vigencia','=',true)->orderBy('nombre', 'asc')->get();

            $documentos = Documento::where('cursos_codigo','=',$codigo)->get();
            $evaluaciones = Evaluacion::where('cursos_codigo','=',$codigo)->get();
            $prerrequisitos = Prerrequisito::where('cursos_codigo_hijo','=',$codigo)->get();        

            if ( $curso->convalidable == 1 ){
                $curso->convalidable = "true";
            }else{
                $curso->convalidable = "false";
            }        

            if ( $curso->repechaje == 1 ){
                $curso->repechaje = "true";
            }else{
                $curso->repechaje = "false";
            }           

            return view('cursos.edit')->with('curso',$curso)
                                       ->with('cursos',$cursos)
                                       ->with('documentos',$documentos)
                                       ->with('tipoMotores',$tipoMotores)
                                       ->with('tipoCursos',$tipoCursos)
                                       ->with('evaluaciones',$evaluaciones)
                                       ->with('prerrequisitos',$prerrequisitos)
                                       ->with('sucursales',$sucursales)
                                       ->with('unidadesNegocio',$unidadesNegocio);
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

        if(empty($request->input('codigo'))){
            return redirect()->route('cursos.edit', $codigo)->withInput()->with('error','Debe ingresar un código');
        }
        if(empty($request->input('nombre'))){
            return redirect()->route('cursos.edit', $codigo)->withInput()->with('error','Debe ingresar un nombre');
        }
        if(($request->input('estado')) == ''  ){
            return redirect()->route('cursos.edit', $codigo)->withInput()->with('error','Debe seleccionar un estado');
        }
        if(($request->input('tipoCurso')) == ''  ){
            return redirect()->route('cursos.edit', $codigo)->withInput()->with('error','Debe seleccionar un tipo de curso');
        }        
        if(empty($request->input('horas'))){
            return redirect()->route('cursos.edit', $codigo)->withInput()->with('error','Debe ingresar cantidad de horas');
        }
        if(empty($request->input('aprobacion_minima'))){
            return redirect()->route('cursos.edit', $codigo)->withInput()->with('error','Debe ingresar una aprobación mínima');
        }
        if(($request->input('categoria')) == ''  ){
            return redirect()->route('cursos.edit', $codigo)->withInput()->with('error','Debe seleccionar una categoria');
        }        
        if ( empty($request->input('nomEval')) ) {
            return redirect()->route('cursos.edit', $codigo)->withInput()->with('error','Debe ingresar al menos una evaluación');
        }

        $aprobacion_minima = $request->input('aprobacion_minima');
        $aprobacion_minima = str_replace(',', '.', $aprobacion_minima);
        if (($aprobacion_minima > 100) || ($aprobacion_minima < 0)) {
            return redirect()->route('cursos.create')->withInput()->with('error','La nota debe ser entre 0 y 100');   
        }

        // $count = 0;
        // $nombresEval = $request->input('nomEval');
        // foreach ($nombresEval as $key => $value) {            
        //     if (empty($value)){
        //         $count = $count + 1;
        //     }
        // }

        // if($count > 0){
        //     return redirect()->route('cursos.edit', $codigo)->withInput()->with('error','Debe ingresar nombre de evaluación');
        // }

        $curso = Curso::find($codigo);
        $nombreAntiguo = $curso->nombre;
        $document = $request->file('documentos');
        $curso->nombre = $request->input('nombre');
       
        if ( is_null($request->input('estado')) ){
            $curso->estado  = 0;
        }else{
            $curso->estado  = $request->input('estado');
        }

        $curso->tipoCursos_codigo = $request->input('tipoCurso');

        $curso->tipoMotores_codigo = $request->input('tipoMotor');

        $curso->horas = $request->input('horas');
        $curso->aprobacion_minima = $aprobacion_minima;

        if ( is_null($request->input('convalidable')) ){
            $curso->convalidable  = 0;
        }else{
            $curso->convalidable  = $request->input('convalidable');
        }

        if ( is_null($request->input('repechaje')) ){
            $curso->repechaje  = 0;
        }else{
            $curso->repechaje  = $request->input('repechaje');
        }        

        $curso->categoria = $request->input('categoria');
        if ($request->input('categoria') == 'E') {
            $curso->unidadesNegocio_codigo = $request->input('unidadNegocio');
            $curso->sucursales_codigo = $request->input('sucursal');
        }else {
            $curso->unidadesNegocio_codigo = null;
            $curso->sucursales_codigo = null;
        }

        $curso->save();


        //Evaluaciones del curso

//         $evaluacionesCurso = Evaluacion::where('cursos_codigo','=',$codigo)->get();

// dd(request()->all(), $evaluacionesCurso);

//         //se guardan evaluaciones del curso
//         $nomEvaluacion = $_POST['nomEval'];
//         $porcEvaluacion = $_POST['porcEval'];

//         foreach($evaluacionesCurso as $value) {
//            // dd('db: '.$value->nombre);
//             for ($i=0; $i < sizeof($nomEvaluacion); $i++) { 
//         //        dd('ingresado: '.$nomEvaluacion[$i]);
//                 $evaluacion = new Evaluacion;
//                 $evaluacion->nombre = $nomEvaluacion[$i];
//                 $evaluacion->porcentaje = $porcEvaluacion[$i];
//                 $evaluacion->cursos_codigo = $curso->codigo;
//                 $evaluacion->save();
//             }  
//         }


        $evaluacionEliminar = Evaluacion::where('cursos_codigo','=',$codigo)->get();
        foreach($evaluacionEliminar as $value) {
            $value->delete();
        } 

        //se guardan evaluaciones del curso
        $nomEvaluacion = $_POST['nomEval'];
        $porcEvaluacion = $_POST['porcEval'];
        for ($i=0; $i < sizeof($nomEvaluacion); $i++) { 
            $evaluacion = new Evaluacion;
            $evaluacion->nombre = $nomEvaluacion[$i];
            $evaluacion->porcentaje = $porcEvaluacion[$i];
            $evaluacion->cursos_codigo = $curso->codigo;
            $evaluacion->save();
        }   
/* +++  +++ */


        //Prerrequisitos del curso
        $prerrequitoEliminar = Prerrequisito::where('cursos_codigo_hijo','=',$codigo)->get();
        foreach($prerrequitoEliminar as $value) {
            $value->delete();
        } 

        //valido que pueda venir vacio campo prerrequisitos
        if ( !is_null(($request->input('to'))) ) {
            //se guardan prerrequisitos del curso
            $cursoPadre = $_POST['to'];
            for ($i=0; $i < sizeof($cursoPadre); $i++) { 
                $prerrequisito = new Prerrequisito;
                $prerrequisito->cursos_codigo_padre = $cursoPadre[$i];
                $prerrequisito->cursos_codigo_hijo = $curso->codigo;
                $prerrequisito->save();
            }  
        }                      

        //Documentos del curso
        $documentosFinal =  $request->input('documentosFinal');
        $documentosBase = Documento::select('id', 'ruta', 'nombre_unico')->where('cursos_codigo','=',$codigo)->get();

        //elimino documento de base datos y elimino de servidor
        foreach($documentosBase as $value) {  
            $flag = false;
            for ($i=0; $i < sizeof($documentosFinal); $i++) {       
                if ($value->id == $documentosFinal[$i]) {
                    $flag = true;
                }
            }
            if ($flag == false) {    
                $archivo = 'storage/'.$value->nombre_unico;
                $archivo2 = storage_path().'/'.$value->ruta;
             //   dd($archivo,  storage_path(), $archivo2);
             //   \File::delete($archivo);
                if(\File::exists($archivo2)) {
                    \File::delete($archivo2);
                }
                $value->delete();
            }
        }  

        //valido que pueda venir vacio campo documentos
        if(count($document)>0){  
            //se obtiene el campo file definido en el formulario
            $file = $request->file('documentos');

            //se obtiene el nombre del archivo
            $nombre = $file->getClientOriginalName();
         
            //nombre unico
            $nombre_unico = time().$nombre;

            //se guarda un nuevo archivo en el disco local
            \Storage::disk('public')->put($nombre_unico,  \File::get($file));

            //Se obtiene ruta y nombre del archivo
            $path = 'app/public/'.$nombre_unico; 

            //se guardan documentos del curso
            $documentos = new Documento;
            $documentos->nombre = $nombre;
            $documentos->nombre_unico = $nombre_unico;
            $documentos->ruta = $path;
            $documentos->extension = $file->getClientOriginalExtension(); // obtengo extension archivo
            $documentos->cursos_codigo = $curso->codigo;
            $documentos->save();
        }


        //Guardar log al crear usuario
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Editar Curso",
            'details' => "Se edita Curso: " . $nombreAntiguo . " por: " . $request->input('nombre'),
        ]);

        return redirect()->route('cursos.index')->with('success','Curso editado correctamente');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($codigo)
    {
        $curso = Curso::find($codigo);
        $curso->delete();

        //Guardar log al crear usuario
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Eliminar Curso",
            'details' => "Se elimina Curso: " . $codigo,
        ]);

        return redirect()->route('cursos.index')->with('success','Curso eliminado correctamente');
    }


    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $codigo             = $request->input('codigoExcel');
        $nombre             = $request->input('nombreExcel');
        $estado             = $request->input('estadoExcel');
        $tipoCurso          = $request->input('tipoCursoExcel');
        $tipoMotor          = $request->input('tipoMotorExcel');
        $horas              = $request->input('horasExcel');
        $aprobacion_minima  = $request->input('aprobacion_minimaExcel');
        $convalidable       = $request->input('convalidableExcel');
        $repechaje          = $request->input('repechajeExcel'); 

        $cursos = DB::table('cursos')->select('codigo', 'nombre', 'estado');

        if(!is_null($convalidable) && $convalidable <> 2){
            $cursos = $cursos->where('convalidable','=',$convalidable);
        }

        if(!is_null($repechaje) && $repechaje <> 2){
            $cursos = $cursos->where('repechaje','=',$repechaje);
        }

        if(!is_null($nombre)){
            $cursos = $cursos->where('nombre','like','%'.$nombre.'%');
        }

        if(!is_null($codigo)){
            $cursos = $cursos->where('codigo','=',$codigo);
        }

        if(!is_null($estado) && $estado <> 2){
            $cursos = $cursos->where('estado','=',$estado);
        }

        if(!is_null($tipoCurso)){
            $cursos = $cursos->where('tipoCursos_codigo','=',$tipoCurso);
        }

        if(!is_null($tipoMotor)){
            $cursos = $cursos->where('tipoMotores_codigo','=',$tipoMotor);
        }

        if(!is_null($horas)){
            $cursos = $cursos->where('horas','=',$horas);
        }

        if(!is_null($aprobacion_minima)){
            $cursos = $cursos->where('aprobacion_minima','=',$aprobacion_minima);
        }

        $cursos = $cursos->orderBy('codigo', 'asc')->get();

        return Excel::create('ListadoCursos', function($excel) use ($cursos) {
            $excel->sheet('Cursos', function($sheet) use ($cursos)
            {
                $count = 2;
                
                $sheet->row(1, ['Código', 'Nombre', 'Estado']);
                foreach ($cursos as $key => $value) {
                    if ( $value->estado == 1 ){
                        $value->estado = "Activo";
                    }else{
                        $value->estado = "Cancelado";
                    }
                    $sheet->row($count, [$value->codigo, $value->nombre, $value->estado]);
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

    // metodo exportr a excel datos mantenedor
    public function exportExcelHist(Request $request)
    {
        $codigo         = $request->input('codigoExcel'); 

        $curso          = Curso::find($codigo);

        $historialCurso = Version::select('versiones.id','versiones.programas_codigo','versiones.fecha_inicio','versiones.fecha_fin','versiones.cursos_codigo','sucursales.nombre','instructores.nombres','instructores.apellido_paterno','instructores.apellido_materno', DB::raw('count(curso_trabajador.trabajadores_rut) as cuenta'))
                ->leftJoin('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')
                ->join('sucursales', 'sucursales.codigo', '=', 'versiones.lugar_ejecucion')
                ->join('curso_instructor', 'curso_instructor.id', '=', 'versiones.curso_instructor_id')
                ->join('instructores', 'instructores.rut', '=', 'curso_instructor.instructores_rut')
                ->where('versiones.cursos_codigo', '=', $codigo)
                ->groupBy('versiones.id','versiones.programas_codigo','versiones.fecha_inicio','versiones.fecha_fin','versiones.cursos_codigo','sucursales.nombre','instructores.nombres','instructores.apellido_paterno','instructores.apellido_materno')
                ->orderBy('versiones.fecha_inicio', 'desc')->get();

        return Excel::create('HistorialCursos', function($excel) use ($historialCurso) {
            $excel->sheet('Cursos', function($sheet) use ($historialCurso)
            {
                $count = 2;

                $sheet->setColumnFormat(array(
                    'A' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                    'B' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                ));
    
                $sheet->row(1, ['Fecha inicio', 'Fecha fin', 'Lugar', 'Instructor', 'N° Alumnos']);
                foreach ($historialCurso as $hc) {
                    if (is_null($hc->fecha_inicio)) {
                        $fechaInicio    = "";
                    }else{
                        $fechaInicio    = strtotime($hc->fecha_inicio);
                        $fechaInicio    = \PHPExcel_Shared_Date::PHPToExcel($fechaInicio);
                    }

                    if (is_null($hc->fecha_fin)) {
                        $fechaFinal    = "";
                    }else{
                        $fechaFinal     = strtotime($hc->fecha_fin);
                        $fechaFinal     = \PHPExcel_Shared_Date::PHPToExcel($fechaFinal);
                    }

                    if ($fechaInicio != "" || $fechaFinal != "") {
                        $sheet->appendRow(array($fechaInicio, $fechaFinal, $hc->nombre, $hc->nombres.' '.$hc->apellido_paterno.' '.$hc->apellido_materno, $hc->cuenta));
                    }
                    
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

 /*   public function volver()
    {
        return view('cursos.index');
    }
*/

    //metodo para cambiar estado curso, segun corresponda
    public function cambioEstado($codigo)
    {
        $curso = Curso::find($codigo);

        //validar que curso no se este dictanto o programado a dictar
        $counter = Version::where('cursos_codigo','=',$codigo)
                        ->where('status','<>','C')->count();   

        if ($counter <>'0') {                          
            return redirect()->route('cursos.index')->with('error','No se puede cambiar estado del curso');   
        }else {

            if ( $curso->estado  == 1){
                $curso->estado  = 0;
            }else{
                $curso->estado  = 1;
            }

            $curso->save();

            return redirect()->route('cursos.index')->with('success','Cambio estado realizado correctamente');            
        } 
    }

    //metodo para descarga archivos desde vista detalle curso
    public function downloadFile($file)
    {

        $documento = Documento::find($file);

        $pathToFile=storage_path()."/app/public/".$documento->nombre_unico;
        return response()->download($pathToFile);  
    }


    public function historial($codigo)
    {
        $curso = Curso::find($codigo);

        $historialCurso = Version::select('versiones.id','versiones.programas_codigo','versiones.fecha_inicio','versiones.fecha_fin','versiones.cursos_codigo','sucursales.nombre','instructores.nombres','instructores.apellido_paterno','instructores.apellido_materno', DB::raw('count(curso_trabajador.trabajadores_rut) as cuenta'))
                ->leftJoin('curso_trabajador', 'curso_trabajador.versiones_id', '=', 'versiones.id')
                ->join('sucursales', 'sucursales.codigo', '=', 'versiones.lugar_ejecucion')
                ->join('curso_instructor', 'curso_instructor.id', '=', 'versiones.curso_instructor_id')
                ->join('instructores', 'instructores.rut', '=', 'curso_instructor.instructores_rut')
                ->where('versiones.cursos_codigo', '=', $codigo)
                ->groupBy('versiones.id','versiones.programas_codigo','versiones.fecha_inicio','versiones.fecha_fin','versiones.cursos_codigo','sucursales.nombre','instructores.nombres','instructores.apellido_paterno','instructores.apellido_materno')
                ->orderBy('versiones.programas_codigo')->get();


        return view('cursos.historial')->with('curso',$curso)
                                        ->with('historialCurso',$historialCurso);
    }     

}
