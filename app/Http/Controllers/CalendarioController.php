<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Yajra\DataTables\Datatables;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Facades\PHPExcel_Style_NumberFormat;
use Illuminate\Support\Facades\Mail;
use App\Calendario;
use App\Version;
use App\Fecha;
use App\Log;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\DB;

class CalendarioController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        if(\Request::get('browser') == 0){
            $fecha      = \Request::get('fecha');
            $horaInicio = \Request::get('hora_inicio');
            $horaFinal  = \Request::get('hora_final');
        }else if(\Request::get('browser') == 1){
            $fecha      = \Request::get('fechaChrome');
            $horaInicio = \Request::get('hora_inicioChrome');
            $horaFinal  = \Request::get('hora_finalChrome');
        }

        $calendarios = DB::table('calendarios')->select('calendarios.*');

        if(!is_null($fecha)){
            $calendarios = $calendarios->where('fecha','=', $fecha);
        }

        if(!is_null($horaInicio)){
            $calendarios = $calendarios->where('hora_inicio','>=', $horaInicio);
        }

        if(!is_null($horaFinal)){
            $calendarios = $calendarios->where('hora_final','<=', $horaFinal);
        }

        $calendarios = $calendarios->orderBy('fecha', 'desc')->get();

        return view('calendarios.index')->with('calendarios', $calendarios);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        return view('calendarios.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if($request->input('browser') == 0){ //explorer
            $fecha          = $request->input('fecha');
            $hora_inicio    = $request->input('hora_inicio');
            $hora_final     = $request->input('hora_final');
        }else if(\Request::get('browser') == 1){ //chrome
            $fecha          = $request->input('fechaChrome');
            $hora_inicio    = $request->input('hora_inicioChrome');
            $hora_final     = $request->input('hora_finalChrome');
        }

        if(empty($fecha)){
            return redirect()->route('calendarios.create')->withInput()->with('error','Debe seleccionar una fecha');
        }
        if(empty($hora_inicio)){
            return redirect()->route('calendarios.create')->withInput()->with('error','Debe seleccionar una hora de inicio');
        }
        if(empty($hora_final)){
            return redirect()->route('calendarios.create')->withInput()->with('error','Debe seleccionar una hora final');
        }

        $countC = Calendario::where('fecha','=',$fecha)->count();
        if($countC > 0){
            return redirect()->route('calendarios.create')->withInput()->with('error','Fecha ingresada ya existe');
        }

        $countF = Fecha::where('fecha', '=', $fecha)->count();
        if($countF > 0){
            return redirect()->route('calendarios.create')->withInput()->with('error','Curso asignado en fecha ingresada');
        }

        //$this->validate($request, ['codigo' => 'unique:cargos,codigo',$request->input('codigo')], );
        $rules = ['fecha' => 'unique:calendarios,fecha',$fecha];
        $this->validate($request,$rules);

        $calendario = new Calendario;
        $calendario->fecha = $fecha;
        $calendario->hora_inicio = $hora_inicio;
        $calendario->hora_final  = $hora_final;

        $count = mb_strlen($request->input('comentario'), 'UTF-8');
        if($count > 200){
            return back()->with('error', 'Comentario demasiado largo, el máximo permitido es 200 caracteres');
        }else{
            $calendario->comentario  = $request->input('comentario');
        }

        $calendario->save();
        
        //Guardar log al crear calendario
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Calendario",
            'details' => "Se crea día no hábil: " . $fecha,
        ]);

        return redirect()->route('calendarios.index')->with('success','Día no hábil creado correctamente');        
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $calendario = Calendario::find($id);
        return view('calendarios.show')->with('calendario',$calendario);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $calendario = Calendario::find($id);
        return view('calendarios.edit')->with('calendario',$calendario);
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
        if(empty($request->input('fecha'))){
            return redirect()->route('calendarios.edit', $id)->withInput()->with('error','Debe seleccionar una fecha');
        }
        if(empty($request->input('hora_inicio'))){
            return redirect()->route('calendarios.edit', $id)->withInput()->with('error','Debe seleccionar una hora de inicio');
        }
        if(empty($request->input('hora_final'))){
            return redirect()->route('calendarios.edit', $id)->withInput()->with('error','Debe seleccionar una hora final');
        } 

        $fecha = $request->input('fecha');

        $countF = Fecha::where('fecha', '=', $fecha)->count();
        if($countF > 0){
            return redirect()->route('calendarios.edit', $id)->withInput()->with('error','Curso asignado en fecha ingresada');
        }      

        $calendario = Calendario::find($id);    
        $fechaAntigua = $calendario->fecha;

        if($fechaAntigua != $request->input('fecha')){
            $count = Calendario::where('fecha','=',$request->input('fecha'))->where('id','<>',$id)->count();
            if($count>0){
                return back()->with('error', 'Fecha ingresada ya existe');
            }else{
                $calendario->fecha = $request->input('fecha');
                $calendario->hora_inicio = $request->input('hora_inicio');
                $calendario->hora_final  = $request->input('hora_final');
            }           
        }else if($fechaAntigua == $request->input('fecha')){
            $calendario->hora_inicio = $request->input('hora_inicio');
            $calendario->hora_final  = $request->input('hora_final');
        }

        $count = mb_strlen($request->input('comentario'), 'UTF-8');
        if($count > 200){
            return back()->with('error', 'Comentario demasiado largo, el máximo permitido es 200 caracteres');
        }else{
            $calendario->comentario  = $request->input('comentario');
        }

        $calendario->save();

        //Guardar log al crear usuario
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Editar Cargo",
            'details' => "Se edita Cargo: " . $fechaAntigua . " por: " . $request->input('fecha'),
        ]);

        return redirect()->route('calendarios.index')->with('success','Día no hábil editado correctamente');        
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $calendario = Calendario::find($id);
        if(!is_null($calendario)){
            $calendario->delete();

            Log::create([
                'id_user' => Auth::user()->id,
                'action' => "Eliminar Calendario",
                'details' => "Se elimina Calendario: " . $id,
            ]);

            return redirect()->route('calendarios.index')->with('success','Día no hábil eliminado correctamente');
        }else{
            return redirect()->route('calendarios.index')->with('error','Registro no existe');
        }
    }

    /*public function exportExcel()
    {
        $calendarios = Calendario::orderBy('fecha')->get();//->toArray();

        foreach ($calendarios as $temp)
            $temp->fecha = Carbon::parse($temp->fecha)->format('d/m/Y');
            //dd($date);

        $calendarios = $calendarios->toArray();

        return Excel::create('ListadoDiasNoHabiles', function($excel) use ($calendarios) {
            $excel->sheet('Calendario', function($sheet) use ($calendarios)
            {
                $sheet->fromArray($calendarios);
                $sheet->row(1, ['ID', 'Fecha', 'Hora Inicio', 'Hora Fin', 'Comentario']);
            });
        })->download('xlsx');
    }*/

    //exportar datos de grilla a excel
    public function exportExcel(Request $request)
    {
        $fecha          = $request->input('fechaExcel');
        $horaInicio     = $request->input('hora_inicioExcel');
        $horaFinal      = $request->input('hora_finalExcel');       

        $calendarios = DB::table('calendarios')->select('calendarios.*');

        if(!is_null($fecha)){
            $calendarios = $calendarios->where('fecha','=', $fecha);
        }

        if(!is_null($horaInicio)){
            $calendarios = $calendarios->where('hora_inicio','>=', $horaInicio);
        }

        if(!is_null($horaFinal)){
            $calendarios = $calendarios->where('hora_final','<=', $horaFinal);
        }

        $calendarios = $calendarios->orderBy('fecha', 'asc')->get();

        return Excel::create('ListadoDiasNoHabiles', function($excel) use ($calendarios) {
            $excel->sheet('Calendario', function($sheet) use ($calendarios)
            {
                $count = 2;

                $sheet->setColumnFormat(array(
                    'A' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                ));

                $sheet->row(1, ['Fecha', 'Hora inicio', 'Hora fin']);
                foreach ($calendarios as $key => $value) {

                    $fechaActual = strtotime($value->fecha);
                    $fechaActual = \PHPExcel_Shared_Date::PHPToExcel($fechaActual);

                    $sheet->appendRow(array($fechaActual, $value->hora_inicio, $value->hora_final));
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

    public function data_calendarios()
    {
        return Datatables::of(Calendario::query())->make(true);
    }    

}
