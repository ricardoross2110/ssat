<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Notificacion;
use Auth;

class NotificacionController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
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
        //
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }

    public function cambiarEstado(Request $request){
        if ($request->ajax()) {
            /*echo $request->input('id').' '.$request->input('contador');*/
            $id = $request->input('id');
            $contador = $request->input('contador');
            $notificacion = Notificacion::find($id);
            if ( Auth::user()->rol_select == 'admin' ) {
                $notificacion->visto_admin = true;
            }else{
                $notificacion->visto = true;
            }
            $notificacion->save();

            $div_notificacion = "";
            
            if (Auth::user()->rol_select == 'admin' ) {
                $notificaciones = Notificacion::select('*')->where('admin', '=', true)->orderBy('visto_admin')->orderBy('fecha', 'desc');
            }else{
                $notificaciones = Notificacion::select('*')->orderBy('visto')->orderBy('fecha', 'desc');
            }

            if ( Auth::user()->rol_select == 'admin' ) {
                $notificaciones = $notificaciones->get();
            }
            if ( Auth::user()->rol_select == 'jefatura' ) {
                $notificaciones = $notificaciones->where('users_id', '=', Auth::user()->id)->where('rol', '=', 'Jefatura')->get();
            }
            if ( Auth::user()->rol_select == 'instructor' ) {
                $notificaciones = $notificaciones->where('users_id', '=', Auth::user()->id)->where('rol', '=', 'Instructor')->get();
            }
            if ( Auth::user()->rol_select == 'alumno' ) {
                $notificaciones = $notificaciones->where('users_id', '=', Auth::user()->id)->where('rol', '=', 'Alumno')->get();
            }

            $modal_notificacion     =   '';
            $div_notificacion       =   '';
            $array_notificacion     =   [];
            $notificaciones_nuevas  =   0; 

            if (Auth::user()->rol_select == 'admin') {
                foreach ($notificaciones as $i=>$notificacion){
                    $i = $i+count($contador);
                    $array_notificacion[$i] = [
                                                'fecha' => \Carbon\Carbon::parse($notificacion->fecha)->format('d/m/Y'),
                                                'fecha_notificacion' => $notificacion->fecha,
                                                'notificacion_id' => $notificacion->id,
                                                'i' => $i,
                                                'titulo' => $notificacion->titulo_admin,
                                                'visto' => $notificacion->visto_admin
                                              ];
                    if ($notificacion->visto_admin == false) {
                        $notificaciones_nuevas++;
                    }
                    
                    if($i <= 2){
                        if($notificacion->visto_admin == true){
                        	$div_notificacion .= '<span class="info-box-text"><strong>'.\Carbon\Carbon::parse($notificacion->fecha)->format('d/m').' - </strong><a href="#" class="small-box-footer" data-toggle="modal" data-backdrop="static" data-keyboard="false" data-target="#notificacion-'.$notificacion->id.'" > '.$notificacion->titulo_admin.'</a></span>';
                        }else{
                        	$div_notificacion .= '<span class="info-box-text"><strong>'.\Carbon\Carbon::parse($notificacion->fecha)->format('d/m').' - <a href="#" class="small-box-footer" data-toggle="modal" data-backdrop="static" data-keyboard="false" data-target="#notificacion-'.$notificacion->id.'" onclick="cambiarEstado('.$notificacion->id.','.$i.')" >'.$notificacion->titulo_admin.'</a></strong></span>';
                        }
                    }
                }
            }else{    
                foreach ($notificaciones as $i=>$notificacion){
                    $i = $i+count($contador);
                    $array_notificacion[$i] = [
                                                'fecha' => \Carbon\Carbon::parse($notificacion->fecha)->format('d/m/Y'),
                                                'fecha_notificacion' => $notificacion->fecha,
                                                'notificacion_id' => $notificacion->id,
                                                'i' => $i,
                                                'titulo' => $notificacion->titulo,
                                                'visto' => $notificacion->visto
                                              ];
                    if ($notificacion->visto == false) {
                        $notificaciones_nuevas++;
                    }
                    
                    if($i <= 2){
                        if($notificacion->visto == true){
                            $div_notificacion .= '<span class="info-box-text"><strong>'.\Carbon\Carbon::parse($notificacion->fecha)->format('d/m').' - </strong><a href="#" class="small-box-footer" data-toggle="modal" data-backdrop="static" data-keyboard="false" data-target="#notificacion-'.$notificacion->id.'" > '.$notificacion->titulo.'</a></span>';
                        }else{
                             $div_notificacion .= '<span class="info-box-text"><strong>'.\Carbon\Carbon::parse($notificacion->fecha)->format('d/m').' - <a href="#" class="small-box-footer" data-toggle="modal" data-backdrop="static" data-keyboard="false" data-target="#notificacion-'.$notificacion->id.'" onclick="cambiarEstado('.$notificacion->id.','.$i.')" >'.$notificacion->titulo.'</a></strong></span>';
                        }
                    }
                }
            }
            return response()->json([
                "notificacion"          => json_encode($notificaciones),
                "array_notificacion"    => json_encode($array_notificacion),
                "div_notificacion"      => $div_notificacion,
                "modal_notificacion"    => $modal_notificacion,
                "notificaciones_nuevas" => $notificaciones_nuevas
            ]);
        } 
    }
}
