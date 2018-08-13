<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Evaluacion;
use App\Log;

use Auth;

class EvaluacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $evaluaciones = Evaluacion::get();
    
        return view('cursos.index')->with('evaluaciones', $evaluaciones);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('cursos.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $evaluacion = new Evaluacion;
        $evaluacion->id = $request->input('id');
        $evaluacion->nombre = $request->input('nombre');
        $evaluacion->porcentaje   = $request->input('porcentaje');
        $evaluacion->cursos_codigo = $request->input('cursos_codigo');

        $evaluacion->save();
        
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Evaluacion",
            'details' => "Se crea Evaluacion: " . $request->input('nombre'),
        ]);

      //  return redirect()->route('cursos.index');
        return redirect()->route('cursos.index')->with('success','Evaluación creada correctamente');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $evaluacion = Evaluacion::find($id);        

        return view('cursos.show')->with('evaluacion',$evaluacion);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $evaluacion = Evaluacion::find($id);

        $cursos = [];

        foreach(Curso::get() as $curso):
            $cursos[$curso->id] = $curso->nombre;
        endforeach;

        //return view('cursos.create')->with(array('cursos' => $cursos));

        return view('cursos.edit')->with('evaluacion',$evaluacion)->with(array('cursos' => $cursos));
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
        $evaluacion = Evaluacion::find($id);    
        $nombreAntiguo = $evaluacion->nombre;
        $evaluacion->nombre = $request->input('nombre');
        $evaluacion->porcentaje   = $request->input('porcentaje');
        $evaluacion->cursos_codigo = $request->input('curso');

        $evaluacion->save();

        Log::create([
            'id_user' => Auth::user()->id,
            'action'  => "Editar Evaluacion",
            'details' => "Se edita Evaluacion: " . $nombreAntiguo . " por: " . $request->input('nombre'),
        ]);

        return redirect()->route('cursos.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $evaluacion = Evaluacion::find($id);
        $evaluacion->delete();

        Log::create([
            'id_user' => Auth::user()->id,
            'action'  => "Eliminar Evaluacion",
            'details' => "Se elimina Evaluacion: " . $id,
        ]);

        return redirect()->route('cursos.index');
    }

    // metodo exportr a excel datos mantenedor
    public function exportExcel()
    {
        $evaluaciones = Evaluacion::get()->toArray();

        return Excel::create('ListadoEvaluaciones', function($excel) use ($evaluaciones) {
            $excel->sheet('Evaluaciones', function($sheet) use ($evaluaciones)
            {
                $sheet->fromArray($evaluaciones);
            });
        })->download('xlsx');
    }

}