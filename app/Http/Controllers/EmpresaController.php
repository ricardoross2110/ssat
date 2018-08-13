<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use App\Empresa;
use App\Log;

use Auth;

class EmpresaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $empresas = Empresa::get();
    
        return view('cursos.index')->with('empresas', $empresas);
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
        try {
            $ultimaEmpresa  = Empresa::orderBy('id', 'desc')->first();
            if ($ultimaEmpresa->id == 3002 ) {
                $empresa         = new Empresa;
                $empresa->id     = 3004;
                $empresa->nombre = $request->input('nombre');
                $empresa->save();
            }else{
                $empresa         = new Empresa;
                $empresa->nombre = $request->input('nombre');
                $empresa->save();
            }


	        Log::create([
	            'id_user' => Auth::user()->id,
	            'action'  => "Guardar Empresa",
	            'details' => "Se guarda Empresa: ".$request->input('nombre').".",
	        ]);

            $empresas       = Empresa::orderBy('nombre', 'asc')->get();
            $selectEmpresa  = "<option value=''>Seleccione empresa</option>";

            foreach ($empresas as $value) {
                if ($value->nombre == $request->input('nombre')) {
                    $selectEmpresa .= "<option value='".$value->id."' selected='selected'>".$value->nombre."</option>";
                }else{
                    $selectEmpresa .= "<option value='".$value->id."'>".$value->nombre."</option>";
                }
            }

            return response()->json([
                "message"       => "ok",
                "selectEmpresa" => $selectEmpresa
            ]);
        } catch (Exception $e) {
            return response()->json([
                "message" => $e
            ]);
        }

    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        $empresa = Empresa::find($id);        

        return view('cursos.show')->with('empresa',$empresa);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $empresa = Empresa::find($id);

        $cursos = [];

        foreach(Curso::get() as $curso):
            $cursos[$curso->id] = $curso->nombre;
        endforeach;

        //return view('cursos.create')->with(array('cursos' => $cursos));

        return view('cursos.edit')->with('empresa',$empresa)->with(array('cursos' => $cursos));
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
        $empresa = Empresa::find($id);    
        $nombreAntiguo = $empresa->nombre;
        $empresa->nombre = $request->input('nombre');
        $empresa->porcentaje   = $request->input('porcentaje');
        $empresa->cursos_id = $request->input('curso');

        $empresa->save();

        Log::create([
            'id_user' => Auth::user()->id,
            'action'  => "Editar Empresa",
            'details' => "Se edita Empresa: " . $nombreAntiguo . " por: " . $request->input('nombre'),
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
        $empresa = Empresa::find($id);
        $empresa->delete();

        Log::create([
            'id_user' => Auth::user()->id,
            'action'  => "Eliminar Empresa",
            'details' => "Se elimina Empresa: " . $id,
        ]);

        return redirect()->route('cursos.index');
    }

    public function comprobarEmpresa(Request $request)
    {
        $nombre     = $request->input('nombre');
        $valido     = false;

        $empresa    = Empresa::where('nombre', '=', $nombre)->get();
        if (count($empresa) == 0) {
            $valido = true;
        }

        return response()->json([
            "valido" => $valido
        ]);
    }

    // metodo exportr a excel datos mantenedor
    public function exportExcel()
    {
        $empresas = Empresa::get()->toArray();

        return Excel::create('Listadoempresas', function($excel) use ($empresas) {
            $excel->sheet('empresas', function($sheet) use ($empresas)
            {
                $sheet->fromArray($empresas);
            });
        })->download('xlsx');
    }

}