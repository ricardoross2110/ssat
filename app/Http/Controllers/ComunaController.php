<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Comuna;
use App\Log;
use Auth;

class ComunaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $comunas = Comuna::get();

        foreach ($comunas as $comuna)
            if ( $comuna->vigencia == 1 ){
                $comuna->vigencia = "Activo";
            }else{
                $comuna->vigencia = "Inactivo";
            }
    
        return view('comunas.index')->with('comunas', $comunas);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {   

        $comunas = [];

        foreach(Comuna::get() as $comuna):
            $comunas[$comuna->codigo] = $comuna->nombre;
        endforeach;

        
        
        return View::make('comunas.create')->with(array('comunas' => $comunas));

        //return view('comunas.create');
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $comuna = new Comuna;
        $Comuna->codigo = $request->input('codigo');
        $comuna->nombre = $request->input('nombre');
        $comuna->descripcion  = $request->input('descripcion');

        if ( is_null($request->input('vigencia')) ){
            $comuna->vigencia  = 0;
        }else{
            $comuna->vigencia  = $request->input('vigencia');
        }

        $comuna->save();
        
        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Agregar Comuna",
            'details' => "Se crea Comuna: " . $request->input('nombre'),
        ]);

        return redirect()->route('comunas.index');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($codigo)
    {
        $comuna = Comuna::find($codigo);

        if ( $comuna->vigencia == 1 ){
            $comuna->vigencia = "Activo";
        }else{
            $comuna->vigencia = "Inactivo";
        }

        return view('comunas.show')->with('comuna',$comuna);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($codigo)
    {
        $comuna = Comuna::find($codigo);

        if ( $comuna->vigencia == 1 ){
            $comuna->vigencia = "true";
        }else{
            $comuna->vigencia = "false";
        }

        return view('comunas.edit')->with('comuna',$comuna);
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
        $comuna = Comuna::find($codigo);    
        $nombreAntiguo = $comuna->nombre;
        $comuna->nombre = $request->input('nombre');
        $comuna->descripcion  = $request->input('descripcion');
        $comuna->vigencia  = $request->input('vigencia');

        if ( is_null($request->input('vigencia')) ){
            $comuna->vigencia  = 0;
        }else{
            $comuna->vigencia  = $request->input('vigencia');
        }

        $comuna->save();

        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Editar Comuna",
            'details' => "Se edita Comuna: " . $nombreAntiguo . " por: " . $request->input('nombre'),
        ]);

        return redirect()->route('comunas.index');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($codigo)
    {
        $comuna = Comuna::find($codigo);
        $comuna->delete();

        Log::create([
            'id_user' => Auth::user()->id,
            'action' => "Eliminar Comuna",
            'details' => "Se elimina Comuna: " . $codigo,
        ]);

        return redirect()->route('comunas.index');
    }

}