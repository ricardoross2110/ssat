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
use App\CursoTrabajador;
use App\Programa;
use App\TipoCurso;
use App\Reportes;
use App\CentroCosto;
use App\Sucursal;
use Carbon\Carbon;
use Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class ReportesController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */

    public function reporteOcupacionInstructores(){
        
        if(\Request::get('browser') == 0){
            $fechadesde = \Request::get('fechadesde');
            $fechahasta = \Request::get('fechahasta');
        }else if(\Request::get('browser') == 1){
            $fechadesde = \Request::get('fechadesdeChrome');
            $fechahasta = \Request::get('fechahastaChrome');
        }

        $horasinstruc = [];
        $instructores = [];
        $instructores_hh = [];
            
        $horasinstruc = DB::table('versiones')
                        ->select ('curso_instructor.instructores_rut','instructores.nombres','instructores.apellido_paterno','instructores.apellido_materno',
                                    DB::raw('count(cursos.nombre) as total_cursos'),
                                    DB::raw('sum(versiones.horas)as total_horas'),
                                    DB::raw('min(versiones.fecha_inicio)as fecha_inicio'), 
                                    DB::raw('max(versiones.fecha_fin) as fecha_final'))
                        ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                        ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                        ->join('instructores', 'curso_instructor.instructores_rut', '=', 'instructores.rut');
           
        if (!is_null($fechadesde)) {
            $horasinstruc = $horasinstruc->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $horasinstruc = $horasinstruc->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $horasinstruc = $horasinstruc
                        ->groupby('curso_instructor.instructores_rut' ,'instructores.nombres' ,'instructores.apellido_paterno' ,'instructores.apellido_materno')
                        ->get();

        foreach ($horasinstruc as $horas_trabajadas) {
            $instructores_hh[$horas_trabajadas->instructores_rut] = 0;
        }

        foreach ($horasinstruc as $horas_trabajadas) {
            $instructores_hh[$horas_trabajadas->instructores_rut] = $instructores_hh[$horas_trabajadas->instructores_rut] + $horas_trabajadas->total_horas;
        }

        return view('reportes.ocupacion_instruc')
                    ->with('horasinstruc', $horasinstruc)
                    ->with('instructores_hh', $instructores_hh);
    }

    public function reporteOcupacionTrabajadores(){
        $rut              = \Request::get('rutB');
        $apellido_paterno = \Request::get('apellido_paternoB');
        $rut_jefatura     = \Request::get('rut_jefaturaB');
        $wwid             = \Request::get('wwidB');
        $pid              = \Request::get('pidB');
        $centroCosto      = \Request::get('centroCostoB');
        $sucursal         = \Request::get('sucursalB');        

        if(\Request::get('browser') == 0){
            $fechadesde = \Request::get('fechadesde');
            $fechahasta = \Request::get('fechahasta');
        }else if(\Request::get('browser') == 1){
            $fechadesde = \Request::get('fechadesdeChrome');
            $fechahasta = \Request::get('fechahastaChrome');
        }

        $ocupaciontrabajador = [];
        $ocupaciontrabajador= DB::table('curso_trabajador')
                            ->select ('trabajadores.rut','trabajadores.nombres', 'trabajadores.apellido_paterno','trabajadores.wwid','trabajadores.pid',
                                            DB::raw('count(versiones.id)as cursos_total'),
                                            DB::raw('sum(versiones.horas)as horas_totales'),
                                            DB::raw('min(versiones.fecha_inicio)as fecha_inicio'),
                                            DB::raw('max(versiones.fecha_fin) as fecha_final')
                                        )
                            ->join('trabajadores','curso_trabajador.trabajadores_rut','=','trabajadores.rut')
                            ->join('versiones','curso_trabajador.versiones_id','=','versiones.id');

        $centroCost = DB::table('curso_trabajador')
                    ->select('centrosCostos.codigo', 'centrosCostos.nombre',
                                DB::raw('count(versiones.id)as cursosto'),
                                DB::raw('sum(versiones.horas)as sumahora' ),
                                DB::raw('min(versiones.fecha_inicio)as fecha_inicio'),
                                DB::raw('max(versiones.fecha_fin) as fecha_final')
                            )

                    ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                    ->join('versiones','curso_trabajador.versiones_id','=','versiones.id')
                    ->join('centrosCostos', 'trabajadores.centrosCostos_codigo','=','centrosCostos.codigo');

        if(!is_null($rut)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('rut','=', $rut);
            $centroCost         = $centroCost->where('rut','=', $rut);
        }

        if(!is_null($apellido_paterno)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('apellido_paterno','like','%'.$apellido_paterno.'%');
            $centroCost         = $centroCost->where('apellido_paterno','like','%'.$apellido_paterno.'%');
        }

        if(!is_null($rut_jefatura)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('rut_jefatura','=',$rut_jefatura);
            $centroCost         = $centroCost->where('rut_jefatura','=',$rut_jefatura);
        }

        if(!is_null($wwid)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('wwid','=',$wwid);
            $centroCost         = $centroCost->where('wwid','=',$wwid);
        }

        if(!is_null($pid)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('pid','=',$pid);
            $centroCost         = $centroCost->where('pid','=',$pid);
        }
        
        if(!is_null($centroCosto)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('centrosCostos_codigo','=',$centroCosto);
            $centroCost         = $centroCost->where('centrosCostos_codigo','=',$centroCosto);
        }

        if(!is_null($sucursal)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('sucursales_codigo','=',$sucursal);
            $centroCost         = $centroCost->where('sucursales_codigo','=',$sucursal);
        }

        if (!is_null($fechadesde)) {
            $ocupaciontrabajador = $ocupaciontrabajador->where('versiones.fecha_inicio', '>=', $fechadesde);
            $centroCost         = $centroCost->where('versiones.fecha_inicio', '>=', $fechadesde);
        }

        if(!is_null($fechahasta)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('versiones.fecha_fin', '<=', $fechahasta);
            $centroCost         = $centroCost->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $centroCost = $centroCost
                    ->where ('versiones.status', '=', 'C')
                    ->groupBy('centrosCostos.codigo')
                    ->get();

        $centrosCostos = [];
        foreach(CentroCosto::orderBy('nombre', 'asc')->get() as $centroCosto):
            $centrosCostos[$centroCosto->codigo] = $centroCosto->nombre;
        endforeach;

        $sucursales = [];
        foreach(Sucursal::orderBy('nombre', 'asc')->get() as $sucursal):
            $sucursales[$sucursal->codigo] = $sucursal->nombre;
        endforeach;

        $ocupaciontrabajador = $ocupaciontrabajador
                                ->where ('versiones.status', '=', 'C')
                                ->groupby ('trabajadores.rut')
                                ->get();

        $total_cursos = DB::table('trabajadores')
                        ->select('trabajadores.rut',
                                    DB::raw ('count(curso_trabajador.status) as total_cur')
                                )
                        ->join('curso_trabajador','trabajadores.rut','=','curso_trabajador.trabajadores_rut')
                        ->join('versiones','curso_trabajador.versiones_id','=','versiones.id')
                        ->where('versiones.status', '=', 'C')
                        ->groupBy('trabajadores.rut')
                        ->get();

        $totalCursos = [];
        foreach ($total_cursos as $cursos) {
            $totalCursos[$cursos->rut] = $cursos->total_cur;
        }

        $totalapro = DB::table('trabajadores')
                    ->select('curso_trabajador.status',
                                'trabajadores.rut',
                                DB::raw ('count(curso_trabajador.status) as totalaprobado')
                            )
                    ->join('curso_trabajador','trabajadores.rut','=','curso_trabajador.trabajadores_rut')
                    ->join('versiones','curso_trabajador.versiones_id','=','versiones.id')
                    ->where('versiones.status', '=', 'C')
                    ->where('curso_trabajador.status', '=', 'A')
                    ->groupBy('trabajadores.rut','curso_trabajador.status')
                    ->get();

        $totalaprorepe = DB::table('trabajadores')
                        ->select('curso_trabajador.status_repechaje',
                                    'trabajadores.rut',
                                    DB::raw ('count(curso_trabajador.status_repechaje) as totalaprobado')
                                )
                        ->join('curso_trabajador','trabajadores.rut','=','curso_trabajador.trabajadores_rut')
                        ->join('versiones','curso_trabajador.versiones_id','=','versiones.id')
                        ->where('versiones.status', '=', 'C')
                        ->where('curso_trabajador.status_repechaje', '=', 'A')
                        ->groupBy('trabajadores.rut','curso_trabajador.status_repechaje')
                        ->get();

        $cursosaprobados = [];
        foreach ($totalapro as $key => $value) {
            $cursosaprobados[$value->rut] = ($value->totalaprobado * 100 / $totalCursos[$value->rut]); 
            $cursosaprobados[$value->rut] = number_format($cursosaprobados[$value->rut], 1, '.', ',');
        }

        foreach ($totalaprorepe as $key => $value) {
            if (!isset($cursosaprobados[$value->rut])) {
                $cursosaprobados[$value->rut] = ($value->totalaprobado * 100 / $totalCursos[$value->rut]); 
                $cursosaprobados[$value->rut] = number_format($cursosaprobados[$value->rut], 1, '.', ',');
            }
        }

        $total_asistencia = DB::table('asistencias')
                            ->select('curso_trabajador.trabajadores_rut as rut',
                                        DB::raw('count(asistencias.id) as total_as')
                                    )
                            ->join ('curso_trabajador','asistencias.curso_trabajador_id','=','curso_trabajador.id')
                            ->join ('versiones','curso_trabajador.versiones_id','=','versiones.id')
                            ->where ('versiones.status', '=', 'C')
                            ->groupBY('curso_trabajador.trabajadores_rut')
                            ->get();

        $total_ = DB::table('asistencias')
                    ->select('curso_trabajador.trabajadores_rut as rut',
                                DB::raw ('count(asistencias.estado) as total')
                            )
                    ->join ('curso_trabajador','asistencias.curso_trabajador_id','=','curso_trabajador.id')
                    ->join ('versiones','curso_trabajador.versiones_id','=','versiones.id')
                    ->where ('versiones.status', '=', 'C')
                    ->where ('asistencias.estado', '=', 'true')
                    ->groupBy('curso_trabajador.trabajadores_rut')
                    ->get();                    

        $asistenciastotal   = [];
        foreach ($total_ as $key => $value) {
            $asistenciastotal[$value->rut] = ($value->total * 100 / $total_asistencia[$key]->total_as);
            $asistenciastotal[$value->rut] = number_format($asistenciastotal[$value->rut], 1, '.', ',');
        }

        $centroCostoB     = \Request::get('centroCostoB');
        $sucursalB        = \Request::get('sucursalB');    

        return view('reportes.ocupacion_trabajadores')
            ->with('ocupaciontrabajador', $ocupaciontrabajador)
            ->with('cursosaprobados', $cursosaprobados)
            ->with('centroCostoB', $centroCostoB)
            ->with('sucursalB', $sucursalB)
            ->with('totalapro', $totalapro)
            ->with('centroCost', $centroCost)
            ->with('asistenciastotal', $asistenciastotal)
            ->with(array('centrosCostos' => $centrosCostos))
            ->with(array('sucursales' => $sucursales));                            
    }

    public function reporteOcupacionUnidadNegocio(){
        if(\Request::get('browser') == 0){
            $fechadesde = \Request::get('fechadesde');
            $fechahasta = \Request::get('fechahasta');
        }else if(\Request::get('browser') == 1){
            $fechadesde = \Request::get('fechadesdeChrome');
            $fechahasta = \Request::get('fechahastaChrome');
        }       

        $graficoUnidadNegocio = [];
        $graficoUnidadNegocio = DB::table('centrosCostos')
                                ->select('unidadesNegocio.codigo', 'unidadesNegocio.nombre',
                                            DB::raw('sum(versiones.horas) as horas'),
                                            DB::raw('count(versiones.cursos_codigo) as total_cursos')
                                        )
                                ->join('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                                ->join('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                                ->join('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                                ->join('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                                ->join('unidadesNegocio','cursos.unidadesNegocio_codigo', '=', 'unidadesNegocio.codigo')
                                ->where('versiones.status', '=', 'C');

        if (!is_null($fechadesde)) {
            $graficoUnidadNegocio = $graficoUnidadNegocio->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $graficoUnidadNegocio = $graficoUnidadNegocio->where('versiones.fecha_fin', '<=', $fechahasta);
        }
    
        $graficoUnidadNegocio = $graficoUnidadNegocio->groupBy('unidadesNegocio.codigo')->get();

        $unidadnegocio = [];      
        $unidadnegocio = DB::table('centrosCostos')
                        ->select('centrosCostos.codigo', 'centrosCostos.nombre',
                                    DB::raw('sum(versiones.horas)as horas'),
                                    DB::raw('count(versiones.cursos_codigo) as total_cursos')
                                )
                        ->join('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                        ->join('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                        ->join('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                        ->join('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                        ->join('unidadesNegocio','cursos.unidadesNegocio_codigo', '=', 'unidadesNegocio.codigo')
                        ->where('versiones.status', '=', 'C');

        if (!is_null($fechadesde)) {                
            $unidadnegocio = $unidadnegocio->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $unidadnegocio = $unidadnegocio->where('versiones.fecha_fin', '<=', $fechahasta);
        }
    
        $unidadnegocio = $unidadnegocio->groupBy('centrosCostos.codigo')->get();

        $totcursunidad = DB::table('centrosCostos')
                        ->select('centrosCostos.codigo',
                                    DB::raw('count(versiones.cursos_codigo) as total_cursos')
                                )        
                        ->join('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                        ->join('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                        ->join('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                        ->join('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                        ->where('versiones.status', '=', 'C');

        if (!is_null($fechadesde)) {                
            $totcursunidad = $totcursunidad->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $totcursunidad = $totcursunidad->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $totcursunidad = $totcursunidad->groupBy('centrosCostos.codigo')->get();
	
        $totalCursosuni = [];
        foreach ($totcursunidad as $cursos) {
            $totalCursosuni[$cursos->codigo] = $cursos->total_cursos;
        }

        $totcursunidadapro = DB::table('centrosCostos')
                            ->select ('centrosCostos.codigo',
                                        DB::raw('count(curso_trabajador.status) as total_cursosapro')
                                    )        
                            ->join ('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                            ->join ('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                            ->join ('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                            ->join ('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                            ->where ('versiones.status', '=', 'C')
                            ->where ('curso_trabajador.status', '=', 'A');

        if (!is_null($fechadesde)) {                
            $totcursunidadapro = $totcursunidadapro->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $totcursunidadapro = $totcursunidadapro->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $totcursunidadapro = $totcursunidadapro->groupBy('centrosCostos.codigo')->get();

        $totcursunidadaprorepe = DB::table('centrosCostos')
                                ->select ('centrosCostos.codigo',
                                            DB::raw('count(curso_trabajador.status_repechaje) as total_cursosapro')
                                        )        
                                ->join ('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                                ->join ('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                                ->join ('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                                ->join ('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                                ->where ('versiones.status', '=', 'C')
                                ->where('curso_trabajador.status_repechaje', '=', 'A');

        if (!is_null($fechadesde)) {                
            $totcursunidadaprorepe = $totcursunidadaprorepe->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $totcursunidadaprorepe = $totcursunidadaprorepe->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $totcursunidadaprorepe = $totcursunidadaprorepe->groupBy('centrosCostos.codigo')->get();
        //dd($totalCursosuni, $totcursunidadapro, $totcursunidadaprorepe);

        $cursosaprobados = [];
        foreach ($totcursunidadapro as $key => $value) {
            if (isset($totalCursosuni[$value->codigo])) {
                $cursosaprobados[$value->codigo] = ($value->total_cursosapro * 100 / $totalCursosuni[$value->codigo]); 
                $cursosaprobados[$value->codigo] = number_format($cursosaprobados[$value->codigo], 1, '.', ',');
            }
        }

        foreach ($totcursunidadaprorepe as $key => $value) {
            if (isset($totalCursosuni[$value->codigo])) {
                $cursosaprobados[$value->codigo] = ($value->total_cursosapro * 100 / $totalCursosuni[$value->codigo]); 
                $cursosaprobados[$value->codigo] = number_format($cursosaprobados[$value->codigo], 1, '.', ',');
            }
        }

        $totasisunidad = DB::table('centrosCostos')
                        ->select('centrosCostos.codigo',
                                     DB::raw ('count(fechas.id) as total')
                                 )
                        ->join('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                        ->join('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                        ->join('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                        ->join('fechas','fechas.versiones_id', '=', 'versiones.id')
                        ->join('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                        //->join('asistencias','curso_trabajador.id','=','asistencias.curso_trabajador_id')
                        ->where('versiones.status', '=', 'C');

        if (!is_null($fechadesde)){                
            $totasisunidad = $totasisunidad->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $totasisunidad = $totasisunidad->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $totasisunidad = $totasisunidad->groupBy('centrosCostos.codigo')->get();

        $totalCursosunidad = [];
        foreach ($totasisunidad as $cursos) {
            $totalCursosunidad[$cursos->codigo] = $cursos->total;
        }

        $totasisunidadapro = DB::table('centrosCostos')
                            ->select('centrosCostos.codigo',
                                        DB::raw ('count(asistencias.id) as total_apro')
                                    )
                            ->join('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                            ->join('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                            ->join('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                            //->join('fechas','fechas.versiones_id', '=', 'versiones.id')
                            ->join('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                            ->join('asistencias','curso_trabajador.id','=','asistencias.curso_trabajador_id')
                            ->where('versiones.status', '=', 'C');

        if (!is_null($fechadesde)){                
            $totasisunidadapro = $totasisunidadapro->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $totasisunidadapro = $totasisunidadapro->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $totasisunidadapro = $totasisunidadapro->groupBy('centrosCostos.codigo')->get();
        //dd($totasisunidad, $totasisunidadapro);

        $cursosaprobadosasis = [];
        foreach ($totasisunidadapro as $key => $value) {
            if (isset($totalCursosunidad[$value->codigo])) {
                $cursosaprobadosasis[$value->codigo] = ($value->total_apro * 100 / $totalCursosunidad[$value->codigo]); 
                $cursosaprobadosasis[$value->codigo] = number_format($cursosaprobadosasis[$value->codigo], 1, '.', ',');
            }
        }

        return view('reportes.ocupacion_unidad_negocio')
                        ->with('graficoUnidadNegocio', $graficoUnidadNegocio)
                        ->with('totalCursosuni', $totalCursosuni)
                        ->with('cursosaprobados', $cursosaprobados)
                        ->with('cursosaprobadosasis', $cursosaprobadosasis)
                        ->with('unidadnegocio', $unidadnegocio);
    }

    public function reporteInformacionProgramasCalificaciones(){

        $modalidad = \Request::get('modalidad');

       if(\Request::get('browser') == 0){
            $fechadesde = \Request::get('fechadesde');
            $fechahasta = \Request::get('fechahasta');
        }else if(\Request::get('browser') == 1){
            $fechadesde = \Request::get('fechadesdeChrome');
            $fechahasta = \Request::get('fechahastaChrome');
        }

        $reportes = DB::table('versiones')
                    ->select('versiones.id as id_version', 'versiones.cursos_codigo as codigo_curso',
                         'versiones.calificaciones_codigo',
                         'programas.nombre as nombre_programa',
                         'programas.codigo as cod_programa',
                         'calificaciones.nombre',
                         'cursos.nombre as nom_curso',
                            DB::raw('min(versiones.fecha_inicio) as fecha_inicio'),
                            DB::raw('max(versiones.fecha_fin) as fecha_fin'),
                            DB::raw('count(curso_trabajador.trabajadores_rut) as trabcurso')
                        )
                        ->leftjoin('programas', 'versiones.programas_codigo','=', 'programas.codigo')
                        ->leftjoin('calificaciones' ,'versiones.calificaciones_codigo','=','calificaciones.codigo')
                        ->join('cursos','versiones.cursos_codigo','=','cursos.codigo')
                        ->join('curso_trabajador','versiones.id','=','curso_trabajador.versiones_id')
                                        ->groupby('versiones.id','programas.nombre','calificaciones.nombre','cursos.nombre','programas.codigo')
                                        ->orderby('programas.codigo','programas.nombre','cursos.nombre');

        if (!is_null($modalidad)){
            if ($modalidad != "Todos") {
                $reportes = $reportes->where('modalidad','=', $modalidad);
            }
        }

        if (!is_null($fechadesde)) {
            $reportes = $reportes->where('versiones.fecha_inicio', '>=', $fechadesde);
        }

        if(!is_null($fechahasta)){
            $reportes = $reportes->where('versiones.fecha_fin', '<=', $fechahasta);
        }
        
        $reportes = $reportes->where('versiones.status', '=', 'C')->get();

        $totalAlumnos        = [];
        $aprobados           = [];
        $aprobadosRepechaje  = [];
        $reprobados          = [];
        $reprobadosRepechaje = [];
        $aprobadosGrafico    = [];
        $reprobadosGrafico   = [];
        $promediocurso       = [];
        $aprobado            = [];
        $aprobaciontotal     = [];
        $final               = [];
        $final2              = [];
        $final3              = [];
        $asistotal           = []; 
        $asistenciastotal    = [];
        $todo                = [];
        $todo2               = [];
        $todo3               = [];
        $totalasistidos      = [];

        foreach ($reportes as $reporte) {
            $totalAlumnos[$reporte->id_version] = DB::table('curso_trabajador')
                                                ->select('*')->where('versiones_id', '=', $reporte->id_version)->count();

            $aprobados[$reporte->id_version] = DB::table('curso_trabajador')
                                                ->select('*')->where('status', '=', 'A')
                                                ->where('versiones_id', '=', $reporte->id_version)->count();

            $aprobadosRepechaje[$reporte->id_version] = DB::table('curso_trabajador')
                                                        ->select('*')->where('status_repechaje', '=', 'A')
                                                        ->where('versiones_id', '=', $reporte->id_version)->count();

            $reprobados[$reporte->id_version] = DB::table('curso_trabajador')
                                                ->select('*')->where('status', '=', 'R')->whereNull('status_repechaje')
                                                ->where('versiones_id', '=', $reporte->id_version)->count();

            $reprobadosRepechaje[$reporte->id_version] = DB::table('curso_trabajador')
                                                        ->select('*')->where('status_repechaje', '=', 'R')
                                                        ->where('versiones_id', '=', $reporte->id_version)->count();

            $aprobados[$reporte->id_version]    =  $aprobados[$reporte->id_version] + $aprobadosRepechaje[$reporte->id_version];

            $reprobados[$reporte->id_version]    =  $reprobados[$reporte->id_version] + $reprobadosRepechaje[$reporte->id_version];

            if ($aprobados[$reporte->id_version] > 0 || $reprobados[$reporte->id_version] > 0) {
                $aprobadosGrafico[$reporte->id_version]    =  ($aprobados[$reporte->id_version] * 100 / $totalAlumnos[$reporte->id_version]);
                $reprobadosGrafico[$reporte->id_version]    =  ($reprobados[$reporte->id_version] * 100 / $totalAlumnos[$reporte->id_version]);

                $aprobadosGrafico[$reporte->id_version]   = number_format($aprobadosGrafico[$reporte->id_version],1, '.', ',');
                $reprobadosGrafico[$reporte->id_version]   = number_format($reprobadosGrafico[$reporte->id_version],1, '.', ',');
            }

            $total_curso= DB::table('versiones')
                                ->select('versiones.id as id_version','cursos.nombre as nombre_curso','programas.nombre',
                                        DB::raw('count(curso_trabajador.trabajadores_rut) as trabcurso'),
                                        DB::raw('min(versiones.fecha_inicio) as fecha_inicio'),
                                        DB::raw('max(versiones.fecha_fin) as fecha_fin')
                                    )
                                ->leftjoin('programas', 'versiones.programas_codigo','=', 'programas.codigo')
                                ->leftjoin('calificaciones' ,'versiones.calificaciones_codigo','=','calificaciones.codigo')
                                ->join('cursos','versiones.cursos_codigo','=','cursos.codigo')
                                ->join('curso_trabajador','versiones.id','=','curso_trabajador.versiones_id')
                                ->groupby('versiones.id','programas.codigo','calificaciones.nombre','cursos.nombre')
                                ->orderby('programas.codigo','programas.nombre','cursos.nombre');

            if (!is_null($modalidad)){
                if ($modalidad != "Todos") {
                    $total_curso = $total_curso->where('modalidad','=', $modalidad);
                }
            }

            if (!is_null($fechadesde)) {
                $total_curso = $total_curso->where('versiones.fecha_inicio', '>=', $fechadesde);
            }

            if(!is_null($fechahasta)){
                $total_curso = $total_curso->where('versiones.fecha_fin', '<=', $fechahasta);
            }
            
            $total_curso = $total_curso->where('versiones.status', '=', 'C')->get();

            foreach ($total_curso as $versiones){
                $aprobado[$versiones->id_version] = $versiones->trabcurso;
            }

            $promediocurso[$reporte->id_version]    =  DB::table('curso_trabajador')
                                                                ->select(
                                                                            DB::raw('avg(curso_trabajador.nota_final) as prom_curso')
                                                                        )
                                                                ->where('versiones_id', '=', $reporte->id_version)
                                                                ->get();

            $promediocursoRepechaje[$reporte->id_version]    =  DB::table('curso_trabajador')
                                                                ->select(
                                                                            DB::raw('avg(curso_trabajador.nota_final_repechaje) as prom_curso_repechaje')
                                                                        )
                                                                ->where('versiones_id', '=', $reporte->id_version)
                                                                ->get();                                                   

            foreach ($promediocurso[$reporte->id_version] as $promedio => $value) {
                if (!is_null($promediocursoRepechaje[$reporte->id_version][$promedio]->prom_curso_repechaje)) {
                    $promediocurso[$reporte->id_version] = ($promediocursoRepechaje[$reporte->id_version][$promedio]->prom_curso_repechaje + $value->prom_curso) / 2;
                }else{
                    $promediocurso[$reporte->id_version] = $value->prom_curso;
                }
            }


            $totalasis  = DB::table('versiones')  
                            ->select ('versiones.id as id_version','cursos.nombre as nombre_curso','programas.nombre as nombre_programa',
                                        DB::raw ('count(asistencias.id) as total'),
                                        DB::raw('min(versiones.fecha_inicio) as fecha_inicio'),
                                        DB::raw('max(versiones.fecha_fin) as fecha_fin')
                                    )
                            ->leftjoin('programas', 'versiones.programas_codigo','=', 'programas.codigo')
                            ->leftjoin('calificaciones' ,'versiones.calificaciones_codigo','=','calificaciones.codigo')
                            ->join('cursos','versiones.cursos_codigo','=','cursos.codigo')
                            ->join('curso_trabajador','versiones.id','=','curso_trabajador.versiones_id')
                            ->join('asistencias','curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                            ->groupby('cursos.nombre','programas.codigo','versiones.id')
                            ->orderby('programas.codigo','cursos.nombre');

            if (!is_null($modalidad)){
                if ($modalidad != "Todos") {
                    $totalasis = $totalasis->where('modalidad','=', $modalidad);
                }
            }

            if (!is_null($fechadesde)) {
                $totalasis = $totalasis->where('versiones.fecha_inicio', '>=', $fechadesde);
            }

            if(!is_null($fechahasta)){
                $totalasis = $totalasis->where('versiones.fecha_fin', '<=', $fechahasta);
            }
            
            $totalasis = $totalasis->where('versiones.status', '=', 'C')->get();

            $totalasistidos  = DB::table('versiones')  
                                    ->select('versiones.id as id_version','cursos.nombre as nombre_curso','programas.nombre as nombre_programa',
                                                DB::raw ('count(asistencias.id) as total_asistidos'),
                                                DB::raw('min(versiones.fecha_inicio) as fecha_inicio'),
                                                DB::raw('max(versiones.fecha_fin) as fecha_fin')
                                            )
                                    ->leftjoin('programas', 'versiones.programas_codigo','=', 'programas.codigo')
                                    ->leftjoin('calificaciones' ,'versiones.calificaciones_codigo','=','calificaciones.codigo')
                                    ->join('cursos','versiones.cursos_codigo','=','cursos.codigo')
                                    ->join('curso_trabajador','versiones.id','=','curso_trabajador.versiones_id')
                                    ->join('asistencias','curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                                    ->where('asistencias.estado','=', 'true')
                                    ->groupby('cursos.nombre','programas.codigo','versiones.id')
                                    ->orderby('programas.codigo','cursos.nombre');


            if (!is_null($modalidad)){
                if ($modalidad != "Todos") {
                    $totalasistidos = $totalasistidos->where('modalidad','=', $modalidad);
                }
            }

            if (!is_null($fechadesde)) {
                $totalasistidos = $totalasistidos->where('versiones.fecha_inicio', '>=', $fechadesde);
            }

            if(!is_null($fechahasta)){
                $totalasistidos = $totalasistidos->where('versiones.fecha_fin', '<=', $fechahasta);
            }
            
            $totalasistidos = $totalasistidos->where('versiones.status', '=', 'C')->get();

            foreach ($totalasis as $versiones){
                $asistotal[$versiones->id_version] = $versiones->total;
            }

            foreach ($totalasistidos as $key => $value) {
                $asistenciastotal[$value->id_version] = ($value->total_asistidos *100 / $asistotal[$value->id_version]);
                $asistenciastotal[$value->id_version] = number_format($asistenciastotal[$value->id_version], 1, '.', ',');
                $todo[$key] = number_format($asistenciastotal[$value->id_version], 1, '.', ',');
                $todo2[$key] = $value->nombre_programa.' - '.$value->nombre_curso;
                $todo3[$key] = 100-number_format($asistenciastotal[$value->id_version], 1, '.', ',');
            }
        }

        return view('reportes.programas_calificaciones')
                            ->with('reportes', $reportes)
                            ->with('aprobados', $aprobados)
                            ->with('reprobados', $reprobados)
                            ->with('aprobadosGrafico', $aprobadosGrafico)
                            ->with('reprobadosGrafico', $reprobadosGrafico)
                            ->with('promediocurso', $promediocurso)
                            ->with('aprobado', $aprobado)
                            ->with('totalasistidos', $totalasistidos)
                            ->with('todo', $todo)
                            ->with('todo2', $todo2)
                            ->with('todo3', $todo3)
                            ->with('final', $final)
                            ->with('final2', $final2)
                            ->with('final3', $final3)
                            ->with('aprobaciontotal', $aprobaciontotal)
                            ->with('asistenciastotal',$asistenciastotal);
    }

    public function reporteSabana(){
        if(\Request::get('browser') == 0){
            $fechadesde = \Request::get('fechadesde');
            $fechahasta = \Request::get('fechahasta');
        }else if(\Request::get('browser') == 1){
            $fechadesde = \Request::get('fechadesdeChrome');
            $fechahasta = \Request::get('fechahastaChrome');
        }

        $sabana = [];
        $sabana=DB::table('trabajadores')
            ->select('trabajadores.rut as ruttraba','trabajadores.nombres as nombretraba','trabajadores.apellido_paterno as apeptrabaja','trabajadores.apellido_materno as apemtrabaja','trabajadores.email','trabajadores.fecha_nacimiento','trabajadores.genero','trabajadores.wwid','trabajadores.pid','trabajadores.fecha_ingreso','trabajadores.rut_jefatura','trabajadores.estado','empresas.nombre as empresa','sucursales.nombre as sucursal','cargos.nombre as cargo','centrosCostos.nombre as centrocosto','curso_trabajador.cursos_codigo','curso_trabajador.id as curtrabaja','cursos.nombre as nombre_curso','versiones.horas','versiones.lugar_ejecucion','instructores.rut as rut_instructor','instructores.nombres as nombre_instruc','instructores.wwid as wwid_instruc','instructores.pid as pid_instruc','instructores.email as email_instruc','instructores.telefono as telefono_instruc','tipoMotores.nombre as nom_motor','tipoCursos.nombre as nom_tipo_curso','curso_trabajador.nota_final','versiones.fecha_inicio','versiones.fecha_fin')
            ->join ('empresas','trabajadores.empresas_id','=','empresas.id')
            ->join ('sucursales','trabajadores.sucursales_codigo','=','sucursales.codigo')
            ->join ('centrosCostos','trabajadores.centrosCostos_codigo' ,'=','centrosCostos.codigo')
            ->join ('cargos','trabajadores.cargos_codigo', '=','cargos.codigo')
            ->join ('curso_trabajador','trabajadores.rut','=','curso_trabajador.trabajadores_rut') 
            ->join ('versiones','curso_trabajador.versiones_id','=','versiones.id')
            ->join ('curso_instructor','versiones.curso_instructor_id','=','curso_instructor.id')
            ->join ('instructores','curso_instructor.instructores_rut','=','instructores.rut')
            ->join ('cursos','versiones.cursos_codigo','=', 'cursos.codigo')
            ->join ('tipoMotores','cursos.tipoMotores_codigo', '=' ,'tipoMotores.codigo')
            ->join ('tipoCursos' , 'cursos.tipoCursos_codigo','=','tipoCursos.codigo')
            ->join ('evaluaciones','cursos.codigo', '=', 'evaluaciones.cursos_codigo')
            ->join ('notas','curso_trabajador.id', '=', 'notas.curso_trabajador_id')
            ->where ('versiones.status', '=', 'C')
            ->groupby ('trabajadores.rut','empresas.nombre','sucursales.nombre','cargos.nombre','centrosCostos.nombre','curso_trabajador.cursos_codigo','cursos.nombre','versiones.horas','versiones.lugar_ejecucion','instructores.rut','tipoMotores.nombre','tipoCursos.nombre','curso_trabajador.nota_final','versiones.fecha_inicio','versiones.fecha_fin','curso_trabajador.id');

        if (!is_null($fechadesde)){
            $sabana = $sabana->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $sabana = $sabana->where('versiones.fecha_fin', '<=', $fechahasta);
        }

            
        $sabana = $sabana->get();

        foreach ($sabana as $temp) {
            $temp->genero = ($temp->genero == 'F') ? "Femenino" : "Masculino";
            $temp->estado = ($temp->estado) ? "Activo" : "Inactivo";

            $temp->lugar_ejecucion = ($temp->lugar_ejecucion > 0) ? Sucursal::find($temp->lugar_ejecucion) : "-";
        }

        //dd($sabana);
        $totalasis=DB::table('trabajadores')
            ->select('trabajadores.rut','cursos.nombre','curso_trabajador.id',
             DB::raw ('count (asistencias.curso_trabajador_id) as asistenciatotal'))  
            ->join ('curso_trabajador','trabajadores.rut' ,'=','curso_trabajador.trabajadores_rut')
            ->join ('cursos', 'curso_trabajador.cursos_codigo', '=', 'cursos.codigo')
            ->join ('asistencias','curso_trabajador.id' ,'=' ,'asistencias.curso_trabajador_id')
            ->groupby ('trabajadores.rut','cursos.nombre','curso_trabajador.id')->get();


        $toasistesabana = [];

        foreach ($totalasis as $curso_trabajador){
            $toasistesabana[$curso_trabajador->id] = $curso_trabajador->asistenciatotal;
        }
           
        $totalasistida=DB::table('trabajadores')
            ->select('trabajadores.rut','cursos.nombre','curso_trabajador.id as curtrabaja',
             DB::raw ('count (asistencias.curso_trabajador_id) as asistenciatotal'))  
            ->join ('curso_trabajador','trabajadores.rut' ,'=','curso_trabajador.trabajadores_rut')
            ->join ('cursos', 'curso_trabajador.cursos_codigo', '=', 'cursos.codigo')
            ->join ('asistencias','curso_trabajador.id' ,'=' ,'asistencias.curso_trabajador_id')
            ->where('asistencias.estado' ,'=','true')
            ->groupby ('trabajadores.rut','cursos.nombre','curso_trabajador.id')->get();


        $sabanaasistencia = [];
        foreach ($totalasistida as $key => $value){
            $sabanaasistencia[$value->curtrabaja] = ($value->asistenciatotal * 100 / $toasistesabana[$value->curtrabaja]); 
            $sabanaasistencia[$value->curtrabaja] = number_format($sabanaasistencia[$value->curtrabaja], 1, '.', ',');
        }

        return view('reportes.sabana_informacion')
            ->with('sabana', $sabana)
            ->with('sabanaasistencia', $sabanaasistencia);       
    }


    //exportar datos de grilla a excel
    public function exportOcupacionInstructoresExcel(Request $request){
        $fechadesde     = $request->input('fechadesdeExcel');
        $fechahasta      = $request->input('fechahastaExcel');       

        
        $horasinstruc = [];
        $instructores = [];
        $instructores_hh = [];
            
        $horasinstruc=DB::table('versiones')
                            ->select ('curso_instructor.instructores_rut','instructores.nombres','instructores.apellido_paterno','instructores.apellido_materno',
                                        DB::raw('count(cursos.nombre) as total_cursos'),
                                        DB::raw('sum(versiones.horas)as total_horas'),
                                        DB::raw('min(versiones.fecha_inicio)as fecha_inicio'), 
                                        DB::raw('max(versiones.fecha_fin) as fecha_final'))
                            ->join('curso_instructor', 'versiones.curso_instructor_id', '=', 'curso_instructor.id')
                            ->join('cursos', 'versiones.cursos_codigo', '=', 'cursos.codigo')
                            ->join('instructores', 'curso_instructor.instructores_rut', '=', 'instructores.rut');
           
        if (!is_null($fechadesde)) {
            $horasinstruc = $horasinstruc->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $horasinstruc = $horasinstruc->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $horasinstruc = $horasinstruc->groupby('curso_instructor.instructores_rut' ,'instructores.nombres' ,'instructores.apellido_paterno' ,'instructores.apellido_materno')
                            ->orderBy('curso_instructor.instructores_rut')
                            ->get();

        foreach ($horasinstruc as $horas_trabajadas) {
            $instructores_hh[$horas_trabajadas->instructores_rut] = 0;
        }
        foreach ($horasinstruc as $horas_trabajadas) {
            $instructores_hh[$horas_trabajadas->instructores_rut] = $instructores_hh[$horas_trabajadas->instructores_rut] + $horas_trabajadas->total_horas;
        }

        return Excel::create('ReporteOcupaciónDeInstructores', function($excel) use ($horasinstruc) {
            $excel->sheet('Programa', function($sheet) use ($horasinstruc)
            {
                $count = 2;
                
                $sheet->setColumnFormat(array(
                    'E' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                    'F' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                ));

                $sheet->row(1, ['Rut instructor','Nombre instructor','Cursos impartidos','Horas impartidas','Fecha inicio', utf8_encode('Fecha término')]);

                foreach ($horasinstruc as $hi) {
                    $fecha_inicio = strtotime($hi->fecha_inicio);
                    $fecha_inicio = \PHPExcel_Shared_Date::PHPToExcel($fecha_inicio);
                    $fecha_final    = strtotime($hi->fecha_final);
                    $fecha_final    = \PHPExcel_Shared_Date::PHPToExcel($fecha_final);

                    $sheet->appendRow(array($hi->instructores_rut, $hi->nombres.' '.$hi->apellido_paterno.' '.$hi->apellido_materno, $hi->total_cursos,$hi->total_horas, $fecha_inicio, $fecha_final));
                    $count = $count + 1;
                }
            });
        })->download('xlsx');
    }

    public function exportOcupacionTrabajadoresExcel(Request $request){
       
        $fechadesde         =   $request->input('fechadesdeExcel');
        $fechahasta         =   $request->input('fechahastaExcel');
        $rut                =   $request->input('rutExcel');
        $apellido_paterno   =   $request->input('apellido_paternoExcel');
        $sucursal           =   $request->input('sucursalExcel');
        $centroCosto        =   $request->input('centroCostoExcel');
        $wwid               =   $request->input('wwidExcel');
        $pid                =   $request->input('pidExcel');
        $rut_jefatura       =   $request->input('rut_jefaturaExcel');

        $ocupaciontrabajador = [];
        $ocupaciontrabajador = DB::table('curso_trabajador')
                                ->select ('trabajadores.rut','trabajadores.nombres', 'trabajadores.apellido_paterno','trabajadores.wwid','trabajadores.pid',
                                                DB::raw('count(versiones.id)as cursos_total'),
                                                DB::raw('sum(versiones.horas)as horas_totales'),
                                                DB::raw('min(versiones.fecha_inicio)as fecha_inicio'),
                                                DB::raw('max(versiones.fecha_fin) as fecha_final')
                                            )
                                ->join('trabajadores','curso_trabajador.trabajadores_rut','=','trabajadores.rut')
                                ->join('versiones','curso_trabajador.versiones_id','=','versiones.id');

        if(!is_null($rut)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('rut','=', $rut);
        }

        if(!is_null($apellido_paterno)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('apellido_paterno','like','%'.$apellido_paterno.'%');
        }

        if(!is_null($rut_jefatura)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('rut_jefatura','=',$rut_jefatura);
        }

        if(!is_null($wwid)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('wwid','=',$wwid);
        }

        if(!is_null($pid)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('pid','=',$pid);
        }
        
        if(!is_null($centroCosto)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('centrosCostos_codigo','=',$centroCosto);
        }

        if(!is_null($sucursal)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('sucursales_codigo','=',$sucursal);
        }

        if (!is_null($fechadesde)) {
            $ocupaciontrabajador = $ocupaciontrabajador->where('versiones.fecha_inicio', '>=', $fechadesde);
        }

        if(!is_null($fechahasta)){
            $ocupaciontrabajador = $ocupaciontrabajador->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $ocupaciontrabajador = $ocupaciontrabajador
                                ->where('versiones.status', '=', 'C')
                                ->orderby('trabajadores.rut')
                                ->groupby('trabajadores.rut')
                                ->get();

        $total_cursos = DB::table('trabajadores')
                        ->select('trabajadores.rut',
                                    DB::raw ('count(curso_trabajador.status) as total_cur')
                                )
                        ->join('curso_trabajador','trabajadores.rut','=','curso_trabajador.trabajadores_rut')
                        ->join('versiones','curso_trabajador.versiones_id','=','versiones.id')
                        ->where('versiones.status', '=', 'C')
                        ->groupBy('trabajadores.rut')
                        ->get();

        $totalCursos = [];
        foreach ($total_cursos as $cursos) {
            $totalCursos[$cursos->rut] = $cursos->total_cur;
        }

        $totalapro = DB::table('trabajadores')
                    ->select('curso_trabajador.status',
                                'trabajadores.rut',
                                DB::raw ('count(curso_trabajador.status) as totalaprobado')
                            )
                    ->join('curso_trabajador','trabajadores.rut','=','curso_trabajador.trabajadores_rut')
                    ->join('versiones','curso_trabajador.versiones_id','=','versiones.id')
                    ->where('versiones.status', '=', 'C')
                    ->where('curso_trabajador.status', '=', 'A')
                    ->groupBy('trabajadores.rut','curso_trabajador.status')
                    ->get();

        $totalaprorepe = DB::table('trabajadores')
                        ->select('curso_trabajador.status_repechaje',
                                    'trabajadores.rut',
                                    DB::raw ('count(curso_trabajador.status_repechaje) as totalaprobado')
                                )
                        ->join('curso_trabajador','trabajadores.rut','=','curso_trabajador.trabajadores_rut')
                        ->join('versiones','curso_trabajador.versiones_id','=','versiones.id')
                        ->where('versiones.status', '=', 'C')
                        ->where('curso_trabajador.status_repechaje', '=', 'A')
                        ->groupBy('trabajadores.rut','curso_trabajador.status_repechaje')
                        ->get();

        $cursosaprobados = [];
        foreach ($totalapro as $key => $value) {
            $cursosaprobados[$value->rut] = ($value->totalaprobado * 100 / $totalCursos[$value->rut]); 
            $cursosaprobados[$value->rut] = number_format($cursosaprobados[$value->rut], 1, '.', ',');
        }

        foreach ($totalaprorepe as $key => $value) {
            if (!isset($cursosaprobados[$value->rut])) {
                $cursosaprobados[$value->rut] = ($value->totalaprobado * 100 / $totalCursos[$value->rut]); 
                $cursosaprobados[$value->rut] = number_format($cursosaprobados[$value->rut], 1, '.', ',');
            }
        }

        $total_asistencia = DB::table('asistencias')
                            ->select('curso_trabajador.trabajadores_rut as rut',
                                        DB::raw('count(asistencias.id) as total_as')
                                    )
                            ->join ('curso_trabajador','asistencias.curso_trabajador_id','=','curso_trabajador.id')
                            ->join ('versiones','curso_trabajador.versiones_id','=','versiones.id')
                            ->where ('versiones.status', '=', 'C')
                            ->groupBY('curso_trabajador.trabajadores_rut')
                            ->get();

        $total_ = DB::table('asistencias')
                ->select('curso_trabajador.trabajadores_rut as rut',
                            DB::raw ('count(asistencias.estado) as total')
                        )
                ->join ('curso_trabajador','asistencias.curso_trabajador_id','=','curso_trabajador.id')
                ->join ('versiones','curso_trabajador.versiones_id','=','versiones.id')
                ->where ('versiones.status', '=', 'C')
                ->where ('asistencias.estado', '=', 'true')
                ->groupBy('curso_trabajador.trabajadores_rut')
                ->get();                    

        $asistenciastotal   = [];
        foreach ($total_ as $key => $value) {
            $asistenciastotal[$value->rut] = ($value->total *100 / $total_asistencia[$key]->total_as);
            $asistenciastotal[$value->rut] = number_format($asistenciastotal[$value->rut], 1, '.', ',');
        }

        return Excel::create('ReporteOcupacionTrabajadores', function($excel) use ($ocupaciontrabajador, $cursosaprobados, $asistenciastotal){
            $excel->sheet('Reporte', function($sheet) use ($ocupaciontrabajador, $cursosaprobados, $asistenciastotal)
            {
                $count = 2;
            
                $sheet->row(1, ['Rut trabajador','Nombre trabajador','Apellido paterno','Cursos impartidos','Horas impartidos',utf8_encode('Porcentaje de aprobación'),'Porcentaje de asistencia']);

                foreach ($ocupaciontrabajador as $key => $value) {

                    if (!isset($cursosaprobados[$value->rut])) {
                        $cursosaprobados[$value->rut] = "-";
                    }
                    
                    if (!isset($asistenciastotal[$value->rut])) {
                        $asistenciastotal[$value->rut] = "-";
                    }

                    $sheet->row($count, [$value->rut,$value->nombres,$value->apellido_paterno,$value->cursos_total,$value->horas_totales, $cursosaprobados[$value->rut], $asistenciastotal[$value->rut]]);
                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }

    public function exportOcupacionUnidadNegocioExcel(Request $request){
        $fechadesde     = $request->input('fechadesdeExcel');
        $fechahasta     = $request->input('fechahastaExcel');

        $unidadnegocio = [];      
        $unidadnegocio = DB::table('centrosCostos')
                        ->select('centrosCostos.codigo', 'centrosCostos.nombre',
                                    DB::raw('sum(versiones.horas)as horas'),
                                    DB::raw('count(versiones.cursos_codigo) as total_cursos')
                                )
                        ->join('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                        ->join('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                        ->join('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                        ->join('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                        ->join('unidadesNegocio','cursos.unidadesNegocio_codigo', '=', 'unidadesNegocio.codigo')
                        ->where('versiones.status', '=', 'C');

        if (!is_null($fechadesde)) {                
            $unidadnegocio = $unidadnegocio->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $unidadnegocio = $unidadnegocio->where('versiones.fecha_fin', '<=', $fechahasta);
        }
    
        $unidadnegocio = $unidadnegocio->groupBy('centrosCostos.codigo')->orderBy('centrosCostos.codigo')->get();

        $totcursunidad = DB::table('centrosCostos')
                        ->select('centrosCostos.codigo',
                                    DB::raw('count(versiones.cursos_codigo) as total_cursos')
                                )        
                        ->join('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                        ->join('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                        ->join('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                        ->join('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                        ->where('versiones.status', '=', 'C');

        if (!is_null($fechadesde)) {                
            $totcursunidad = $totcursunidad->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $totcursunidad = $totcursunidad->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $totcursunidad = $totcursunidad->groupBy('centrosCostos.codigo')->get();
    
        $totalCursosuni = [];
        foreach ($totcursunidad as $cursos) {
            $totalCursosuni[$cursos->codigo] = $cursos->total_cursos;
        }

        $totcursunidadapro = DB::table('centrosCostos')
                            ->select ('centrosCostos.codigo',
                                        DB::raw('count(curso_trabajador.status) as total_cursosapro')
                                    )        
                            ->join ('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                            ->join ('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                            ->join ('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                            ->join ('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                            ->where ('versiones.status', '=', 'C')
                            ->where ('curso_trabajador.status', '=', 'A');

        if (!is_null($fechadesde)) {                
            $totcursunidadapro = $totcursunidadapro->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $totcursunidadapro = $totcursunidadapro->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $totcursunidadapro = $totcursunidadapro->groupBy('centrosCostos.codigo')->get();

        $totcursunidadaprorepe = DB::table('centrosCostos')
                                ->select ('centrosCostos.codigo',
                                            DB::raw('count(curso_trabajador.status_repechaje) as total_cursosapro')
                                        )        
                                ->join ('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                                ->join ('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                                ->join ('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                                ->join ('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                                ->where ('versiones.status', '=', 'C')
                                ->where('curso_trabajador.status_repechaje', '=', 'A');

        if (!is_null($fechadesde)) {                
            $totcursunidadaprorepe = $totcursunidadaprorepe->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $totcursunidadaprorepe = $totcursunidadaprorepe->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $totcursunidadaprorepe = $totcursunidadaprorepe->groupBy('centrosCostos.codigo')->get();
        //dd($totalCursosuni, $totcursunidadapro, $totcursunidadaprorepe);

        $cursosaprobados = [];
        foreach ($totcursunidadapro as $key => $value) {
            if (isset($totalCursosuni[$value->codigo])) {
                $cursosaprobados[$value->codigo] = ($value->total_cursosapro * 100 / $totalCursosuni[$value->codigo]); 
                $cursosaprobados[$value->codigo] = number_format($cursosaprobados[$value->codigo], 1, '.', ',');
            }
        }

        foreach ($totcursunidadaprorepe as $key => $value) {
            if (isset($totalCursosuni[$value->codigo])) {
                $cursosaprobados[$value->codigo] = ($value->total_cursosapro * 100 / $totalCursosuni[$value->codigo]); 
                $cursosaprobados[$value->codigo] = number_format($cursosaprobados[$value->codigo], 1, '.', ',');
            }
        }

        $totasisunidad = DB::table('centrosCostos')
                        ->select('centrosCostos.codigo',
                                     DB::raw ('count(fechas.id) as total')
                                 )
                        ->join('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                        ->join('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                        ->join('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                        ->join('fechas','fechas.versiones_id', '=', 'versiones.id')
                        ->join('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                        //->join('asistencias','curso_trabajador.id','=','asistencias.curso_trabajador_id')
                        ->where('versiones.status', '=', 'C');

        if (!is_null($fechadesde)){                
            $totasisunidad = $totasisunidad->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $totasisunidad = $totasisunidad->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $totasisunidad = $totasisunidad->groupBy('centrosCostos.codigo')->get();

        $totalCursosunidad = [];
        foreach ($totasisunidad as $cursos) {
            $totalCursosunidad[$cursos->codigo] = $cursos->total;
        }

        $totasisunidadapro = DB::table('centrosCostos')
                            ->select('centrosCostos.codigo',
                                        DB::raw ('count(asistencias.id) as total_apro')
                                    )
                            ->join('trabajadores','centrosCostos.codigo', '=', 'trabajadores.centrosCostos_codigo')
                            ->join('curso_trabajador' ,'trabajadores.rut', '=', 'curso_trabajador.trabajadores_rut')
                            ->join('versiones','curso_trabajador.versiones_id', '=', 'versiones.id')
                            //->join('fechas','fechas.versiones_id', '=', 'versiones.id')
                            ->join('cursos','versiones.cursos_codigo', '=', 'cursos.codigo')
                            ->join('asistencias','curso_trabajador.id','=','asistencias.curso_trabajador_id')
                            ->where('versiones.status', '=', 'C');

        if (!is_null($fechadesde)){                
            $totasisunidadapro = $totasisunidadapro->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $totasisunidadapro = $totasisunidadapro->where('versiones.fecha_fin', '<=', $fechahasta);
        }

        $totasisunidadapro = $totasisunidadapro->groupBy('centrosCostos.codigo')->get();
        //dd($totasisunidad, $totasisunidadapro);

        $cursosaprobadosasis = [];
        foreach ($totasisunidadapro as $key => $value) {
            if (isset($totalCursosunidad[$value->codigo])) {
                $cursosaprobadosasis[$value->codigo] = ($value->total_apro * 100 / $totalCursosunidad[$value->codigo]); 
                $cursosaprobadosasis[$value->codigo] = number_format($cursosaprobadosasis[$value->codigo], 1, '.', ',');
            }
        }

        return Excel::create('ReporteOcupacionPorUnidadDeNegocio', function($excel) use ($unidadnegocio, $cursosaprobados,$cursosaprobadosasis){
            $excel->sheet('Reporte', function($sheet) use ($unidadnegocio, $cursosaprobados,$cursosaprobadosasis)
            {
                $count = 2;
                
                $sheet->row(1, [utf8_encode('Código centro costo'), 'Nombre centro costo', 'Cursos impartidos', 'Horas impartidas','Porcentaje de '.utf8_encode('aprobación'), 'Porcentaje de asistencia']);

                $nuevovalor = [];
                foreach ($unidadnegocio as $key1 => $value){
                    if (!isset($cursosaprobados[$value->codigo])) {
                        $cursosaprobados[$value->codigo] = "-";
                    }
                    
                    if (!isset($cursosaprobadosasis[$value->codigo])) {
                        $cursosaprobadosasis[$value->codigo] = "-";
                    }

                    $sheet->row($count, [$value->codigo, $value->nombre, $value->total_cursos, $value->horas, $cursosaprobados[$value->codigo], $cursosaprobadosasis[$value->codigo]]);
                    $count = $count +1; 
                }
            });
        })->download('xlsx');
    }
    
    public function exportInformacionProgramasCalificaciones(Request $request){
        $modalidad      = $request->input('modalidadExcel');
        $fechadesde     = $request->input('fechadesdeExcel');
        $fechahasta     = $request->input('fechahastaExcel');

        $reportes = DB::table('versiones')
                                    ->select('versiones.id as id_version', 'versiones.cursos_codigo as codigo_curso',
                                         'versiones.calificaciones_codigo',
                                         'programas.nombre as nombre_programa',
                                         'programas.codigo as cod_programa',
                                         'calificaciones.nombre',
                                         'cursos.nombre as nom_curso',
                                            DB::raw('min(versiones.fecha_inicio) as fecha_inicio'),
                                            DB::raw('max(versiones.fecha_fin) as fecha_fin'),
                                            DB::raw('count(curso_trabajador.trabajadores_rut) as trabcurso')
                                        )
                                        ->leftjoin('programas', 'versiones.programas_codigo','=', 'programas.codigo')
                                        ->leftjoin('calificaciones' ,'versiones.calificaciones_codigo','=','calificaciones.codigo')
                                        ->join('cursos','versiones.cursos_codigo','=','cursos.codigo')
                                        ->join('curso_trabajador','versiones.id','=','curso_trabajador.versiones_id')
                                        ->groupby('versiones.id','programas.nombre','calificaciones.nombre','cursos.nombre','programas.codigo')
                                        ->orderby('programas.codigo','programas.nombre','cursos.nombre');

        if (!is_null($modalidad)){
            if ($modalidad != "Todos") {
                $reportes = $reportes->where('modalidad','=', $modalidad);
            }
        }

        if (!is_null($fechadesde)) {
            $reportes = $reportes->where('versiones.fecha_inicio', '>=', $fechadesde);
        }

        if(!is_null($fechahasta)){
            $reportes = $reportes->where('versiones.fecha_fin', '<=', $fechahasta);
        }
        
        $reportes = $reportes->where('versiones.status', '=', 'C')->get();

        $totalAlumnos        = [];
        $aprobados           = [];
        $aprobadosRepechaje  = [];
        $reprobados          = [];
        $reprobadosRepechaje = [];
        $aprobadosGrafico    = [];
        $reprobadosGrafico   = [];
        $promediocurso       = [];
        $aprobado            = [];
        $aprobaciontotal     = [];
        $final               = [];
        $final2              = [];
        $final3              = [];
        $asistotal           = []; 
        $asistenciastotal    = [];
        $todo                = [];
        $todo2               = [];
        $todo3               = [];
        $totalasistidos      = [];

        foreach ($reportes as $reporte) {
            $totalAlumnos[$reporte->id_version]  =   DB::table('curso_trabajador')->select('*')->where('versiones_id', '=', $reporte->id_version)->count();

            $aprobados[$reporte->id_version]    =  DB::table('curso_trabajador')->select('*')->where('status', '=', 'A')->where('versiones_id', '=', $reporte->id_version)->count();

            $aprobadosRepechaje[$reporte->id_version]    =  DB::table('curso_trabajador')->select('*')->where('status_repechaje', '=', 'A')->where('versiones_id', '=', $reporte->id_version)->count();

            $reprobados[$reporte->id_version]             =  DB::table('curso_trabajador')->select('*')->where('status', '=', 'R')->whereNull('status_repechaje')->where('versiones_id', '=', $reporte->id_version)->count();

            $reprobadosRepechaje[$reporte->id_version]    =  DB::table('curso_trabajador')->select('*')->where('status_repechaje', '=', 'R')->where('versiones_id', '=', $reporte->id_version)->count();

            $aprobados[$reporte->id_version]    =  $aprobados[$reporte->id_version] + $aprobadosRepechaje[$reporte->id_version];

            $reprobados[$reporte->id_version]    =  $reprobados[$reporte->id_version] + $reprobadosRepechaje[$reporte->id_version];

            if ($aprobados[$reporte->id_version] > 0 || $reprobados[$reporte->id_version] > 0) {
                $aprobadosGrafico[$reporte->id_version]    =  ($aprobados[$reporte->id_version] * 100 / $totalAlumnos[$reporte->id_version]);
                $reprobadosGrafico[$reporte->id_version]    =  ($reprobados[$reporte->id_version] * 100 / $totalAlumnos[$reporte->id_version]);

                $aprobadosGrafico[$reporte->id_version]   = number_format($aprobadosGrafico[$reporte->id_version],1, '.', ',');
                $reprobadosGrafico[$reporte->id_version]   = number_format($reprobadosGrafico[$reporte->id_version],1, '.', ',');
            }

            $total_curso= DB::table('versiones')
                                ->select('versiones.id as id_version','cursos.nombre as nombre_curso','programas.nombre',
                                        DB::raw('count(curso_trabajador.trabajadores_rut) as trabcurso'),
                                        DB::raw('min(versiones.fecha_inicio) as fecha_inicio'),
                                        DB::raw('max(versiones.fecha_fin) as fecha_fin')
                                    )
                                ->leftjoin('programas', 'versiones.programas_codigo','=', 'programas.codigo')
                                ->leftjoin('calificaciones' ,'versiones.calificaciones_codigo','=','calificaciones.codigo')
                                ->join('cursos','versiones.cursos_codigo','=','cursos.codigo')
                                ->join('curso_trabajador','versiones.id','=','curso_trabajador.versiones_id')
                                ->groupby('versiones.id','programas.codigo','calificaciones.nombre','cursos.nombre')
                                ->orderby('programas.codigo','programas.nombre','cursos.nombre');

            if (!is_null($modalidad)){
                if ($modalidad != "Todos") {
                    $total_curso = $total_curso->where('modalidad','=', $modalidad);
                }
            }

            if (!is_null($fechadesde)) {
                $total_curso = $total_curso->where('versiones.fecha_inicio', '>=', $fechadesde);
            }

            if(!is_null($fechahasta)){
                $total_curso = $total_curso->where('versiones.fecha_fin', '<=', $fechahasta);
            }
            
            $total_curso = $total_curso->where('versiones.status', '=', 'C')->get();

            foreach ($total_curso as $versiones){
                $aprobado[$versiones->id_version] = $versiones->trabcurso;
            }


            $promediocurso[$reporte->id_version]    =  DB::table('curso_trabajador')
                                                                ->select(
                                                                            DB::raw('avg(curso_trabajador.nota_final) as prom_curso')
                                                                        )
                                                                ->where('versiones_id', '=', $reporte->id_version)
                                                                ->get();

            $promediocursoRepechaje[$reporte->id_version]    =  DB::table('curso_trabajador')
                                                                ->select(
                                                                            DB::raw('avg(curso_trabajador.nota_final_repechaje) as prom_curso_repechaje')
                                                                        )
                                                                ->where('versiones_id', '=', $reporte->id_version)
                                                                ->get();                                                   

            foreach ($promediocurso[$reporte->id_version] as $promedio => $value) {
                if (!is_null($promediocursoRepechaje[$reporte->id_version][$promedio]->prom_curso_repechaje)) {
                    $promediocurso[$reporte->id_version] = ($promediocursoRepechaje[$reporte->id_version][$promedio]->prom_curso_repechaje + $value->prom_curso) / 2;
                }else{
                    $promediocurso[$reporte->id_version] = $value->prom_curso;
                }
            }


            $totalasis  = DB::table('versiones')  
                            ->select ('versiones.id as id_version','cursos.nombre as nombre_curso','programas.nombre as nombre_programa',
                                        DB::raw ('count(asistencias.id) as total'),
                                        DB::raw('min(versiones.fecha_inicio) as fecha_inicio'),
                                        DB::raw('max(versiones.fecha_fin) as fecha_fin')
                                    )
                            ->leftjoin('programas', 'versiones.programas_codigo','=', 'programas.codigo')
                            ->leftjoin('calificaciones' ,'versiones.calificaciones_codigo','=','calificaciones.codigo')
                            ->join('cursos','versiones.cursos_codigo','=','cursos.codigo')
                            ->join('curso_trabajador','versiones.id','=','curso_trabajador.versiones_id')
                            ->join('asistencias','curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                            ->groupby('cursos.nombre','programas.codigo','versiones.id')
                            ->orderby('programas.codigo','cursos.nombre');

            if (!is_null($modalidad)){
                if ($modalidad != "Todos") {
                    $totalasis = $totalasis->where('modalidad','=', $modalidad);
                }
            }

            if (!is_null($fechadesde)) {
                $totalasis = $totalasis->where('versiones.fecha_inicio', '>=', $fechadesde);
            }

            if(!is_null($fechahasta)){
                $totalasis = $totalasis->where('versiones.fecha_fin', '<=', $fechahasta);
            }
            
            $totalasis = $totalasis->where('versiones.status', '=', 'C')->get();

            $totalasistidos  = DB::table('versiones')  
                                    ->select('versiones.id as id_version','cursos.nombre as nombre_curso','programas.nombre as nombre_programa',
                                                DB::raw ('count(asistencias.id) as total_asistidos'),
                                                DB::raw('min(versiones.fecha_inicio) as fecha_inicio'),
                                                DB::raw('max(versiones.fecha_fin) as fecha_fin')
                                            )
                                    ->leftjoin('programas', 'versiones.programas_codigo','=', 'programas.codigo')
                                    ->leftjoin('calificaciones' ,'versiones.calificaciones_codigo','=','calificaciones.codigo')
                                    ->join('cursos','versiones.cursos_codigo','=','cursos.codigo')
                                    ->join('curso_trabajador','versiones.id','=','curso_trabajador.versiones_id')
                                    ->join('asistencias','curso_trabajador.id', '=', 'asistencias.curso_trabajador_id')
                                    ->where('asistencias.estado','=', 'true')
                                    ->groupby('cursos.nombre','programas.codigo','versiones.id')
                                    ->orderby('programas.codigo','cursos.nombre');


            if (!is_null($modalidad)){
                if ($modalidad != "Todos") {
                    $totalasistidos = $totalasistidos->where('modalidad','=', $modalidad);
                }
            }

            if (!is_null($fechadesde)) {
                $totalasistidos = $totalasistidos->where('versiones.fecha_inicio', '>=', $fechadesde);
            }

            if(!is_null($fechahasta)){
                $totalasistidos = $totalasistidos->where('versiones.fecha_fin', '<=', $fechahasta);
            }
            
            $totalasistidos = $totalasistidos->where('versiones.status', '=', 'C')->get();

            foreach ($totalasis as $versiones){
                $asistotal[$versiones->id_version] = $versiones->total;
            }

            foreach ($totalasistidos as $key => $value) {
                $asistenciastotal[$value->id_version] = ($value->total_asistidos *100 / $asistotal[$value->id_version]);
                $asistenciastotal[$value->id_version] = number_format($asistenciastotal[$value->id_version], 1, '.', ',');
                $todo[$key] = number_format($asistenciastotal[$value->id_version], 1, '.', ',');
                $todo2[$key] = $value->nombre_programa.' - '.$value->nombre_curso;
                $todo3[$key] = 100-number_format($asistenciastotal[$value->id_version], 1, '.', ',');
            }            
        }

           
        return Excel::create('ReporteInformacionProgramasCalificaciones', function($excel) use ($aprobados, $promediocurso, $asistenciastotal, $reportes) {
            $excel->sheet('Reporte', function($sheet) use ($aprobados, $promediocurso, $asistenciastotal, $reportes)
            {
                $count = 2;
                $sheet->row(1, [utf8_encode('Código').' programa','Nombre programa','Nombre curso ','Fecha inicial','Fecha final','Trabajadores inscritos', 'Aprobados', 'Promedio del Curso', 'Porcentaje de asistencia']);


                $sheet->setColumnFormat(array(
                    'D' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                    'E' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                ));

                foreach ($reportes as $reporte) {
                    $naprobados     = "";
                    $notapromedio   = "";
                    $porcentajeAsistencia = "";
                    if(isset($promediocurso[$reporte->id_version])){
                        $notapromedio = number_format($promediocurso[$reporte->id_version], 1, ',' , '');
                    }else{
                        $notapromedio = "-";
                    }
                    if(isset($aprobados[$reporte->id_version])){
                        $naprobados = $aprobados[$reporte->id_version];
                    }else{
                        $naprobados = "-";
                    }
                    if(isset($asistenciastotal[$reporte->id_version])){
                        $porcentajeAsistencia = number_format($asistenciastotal[$reporte->id_version], 1, ',' , '');
                    }else{
                        $porcentajeAsistencia = "-";
                    }

                    $fecha_inicio = strtotime($reporte->fecha_inicio);
                    $fecha_inicio = \PHPExcel_Shared_Date::PHPToExcel($fecha_inicio);
                    $fecha_final    = strtotime($reporte->fecha_fin);
                    $fecha_final    = \PHPExcel_Shared_Date::PHPToExcel($fecha_final);

                    $sheet->appendRow(array($reporte->cod_programa,$reporte->nombre_programa, $reporte->nom_curso, $fecha_inicio,$fecha_final,$reporte->trabcurso, $naprobados, $notapromedio, $porcentajeAsistencia));
                    $count = $count + 1;
                }
            });
        })->download('xlsx');                   
    }

    public function exportSabana(Request $request){
        
        $fechadesde     = $request->input('fechadesdeExcel');
        $fechahasta     = $request->input('fechahastaExcel'); 
       
        $sabana = [];
        $sabana=DB::table('trabajadores')
            ->select('trabajadores.rut as ruttraba','trabajadores.nombres as nombretraba','trabajadores.apellido_paterno as apeptrabaja','trabajadores.apellido_materno as apemtrabaja','trabajadores.email','trabajadores.fecha_nacimiento','trabajadores.genero','trabajadores.wwid','trabajadores.pid','trabajadores.fecha_ingreso','trabajadores.rut_jefatura','trabajadores.estado','empresas.nombre as empresa','sucursales.nombre as sucursal','cargos.nombre as cargo','centrosCostos.nombre as centrocosto','curso_trabajador.cursos_codigo','curso_trabajador.id as curtrabaja','cursos.nombre as nombre_curso','versiones.horas','versiones.lugar_ejecucion','instructores.rut as rut_instructor','instructores.nombres as nombre_instruc','instructores.wwid as wwid_instruc','instructores.pid as pid_instruc','instructores.email as email_instruc','instructores.telefono as telefono_instruc','tipoMotores.nombre as nom_motor','tipoCursos.nombre as nom_tipo_curso','curso_trabajador.nota_final','versiones.fecha_inicio','versiones.fecha_fin')
            ->join ('empresas','trabajadores.empresas_id','=','empresas.id')
            ->join ('sucursales','trabajadores.sucursales_codigo','=','sucursales.codigo')
            ->join ('centrosCostos','trabajadores.centrosCostos_codigo' ,'=','centrosCostos.codigo')
            ->join ('cargos','trabajadores.cargos_codigo', '=','cargos.codigo')
            ->join ('curso_trabajador','trabajadores.rut','=','curso_trabajador.trabajadores_rut') 
            ->join ('versiones','curso_trabajador.versiones_id','=','versiones.id')
            ->join ('curso_instructor','versiones.curso_instructor_id','=','curso_instructor.id')
            ->join ('instructores','curso_instructor.instructores_rut','=','instructores.rut')
            ->join ('cursos','versiones.cursos_codigo','=', 'cursos.codigo')
            ->join ('tipoMotores','cursos.tipoMotores_codigo', '=' ,'tipoMotores.codigo')
            ->join ('tipoCursos' , 'cursos.tipoCursos_codigo','=','tipoCursos.codigo')
            ->join ('evaluaciones','cursos.codigo', '=', 'evaluaciones.cursos_codigo')
            ->join ('notas','curso_trabajador.id', '=', 'notas.curso_trabajador_id')
            ->where ('versiones.status', '=', 'C')
            ->groupby ('trabajadores.rut','empresas.nombre','sucursales.nombre','cargos.nombre','centrosCostos.nombre','curso_trabajador.cursos_codigo','cursos.nombre','versiones.horas','versiones.lugar_ejecucion','instructores.rut','tipoMotores.nombre','tipoCursos.nombre','curso_trabajador.nota_final','versiones.fecha_inicio','versiones.fecha_fin','curso_trabajador.id');

        if (!is_null($fechadesde)){
            $sabana = $sabana->where('versiones.fecha_inicio', '>=', $fechadesde);      
        }

        if(!is_null($fechahasta)){
            $sabana = $sabana->where('versiones.fecha_fin', '<=', $fechahasta);
        }

            
        $sabana = $sabana->get();

        foreach ($sabana as $temp) {
            $temp->genero = ($temp->genero == 'F') ? "Femenino" : "Masculino";
            $temp->estado = ($temp->estado) ? "Activo" : "Inactivo";
            $temp->lugar_ejecucion = ($temp->lugar_ejecucion > 0) ? Sucursal::find($temp->lugar_ejecucion) : "-";

        }

        //dd($sabana);
        $totalasis=DB::table('trabajadores')
            ->select('trabajadores.rut','cursos.nombre','curso_trabajador.id',
             DB::raw ('count (asistencias.curso_trabajador_id) as asistenciatotal'))  
            ->join ('curso_trabajador','trabajadores.rut' ,'=','curso_trabajador.trabajadores_rut')
            ->join ('cursos', 'curso_trabajador.cursos_codigo', '=', 'cursos.codigo')
            ->join ('asistencias','curso_trabajador.id' ,'=' ,'asistencias.curso_trabajador_id')

            ->groupby ('trabajadores.rut','cursos.nombre','curso_trabajador.id')->get();


        $toasistesabana = [];

        foreach ($totalasis as $curso_trabajador){
            $toasistesabana[$curso_trabajador->id] = $curso_trabajador->asistenciatotal;
        }
           
        $totalasistida=DB::table('trabajadores')
            ->select('trabajadores.rut','cursos.nombre','curso_trabajador.id as curtrabaja',
             DB::raw ('count (asistencias.curso_trabajador_id) as asistenciatotal'))  
            ->join ('curso_trabajador','trabajadores.rut' ,'=','curso_trabajador.trabajadores_rut')
            ->join ('cursos', 'curso_trabajador.cursos_codigo', '=', 'cursos.codigo')
            ->join ('asistencias','curso_trabajador.id' ,'=' ,'asistencias.curso_trabajador_id')
            ->where('asistencias.estado' ,'=','true')
            ->groupby ('trabajadores.rut','cursos.nombre','curso_trabajador.id')->get();


        $sabanaasistencia = [];
        foreach ($totalasistida as $key => $value){
            $sabanaasistencia[$value->curtrabaja] = ($value->asistenciatotal * 100 / $toasistesabana[$value->curtrabaja]); 
            $sabanaasistencia[$value->curtrabaja] = number_format($sabanaasistencia[$value->curtrabaja], 1, '.', ',');
        }

        return Excel::create('SabanaDatos', function($excel) use ($sabana) {
            $excel->sheet('Reporte', function($sheet) use ($sabana)
            {
                $count = 2;
                

                $sheet->setColumnFormat(array(
                    'F' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                    'M' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                    'X' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                    'Y' => \PHPExcel_Style_NumberFormat::FORMAT_DATE_DDMMYYYY,
                ));

                $sheet->row(1, [
                                'Rut',
                                'Nombre trabajador',
                                'Apellido paterno',
                                'Apellido materno',
                                'Empresa',
                                'Fecha ingreso',
                                'Estado',
                                utf8_encode('Género'),
                                'Sucursal',
                                'Centro costo',
                                'Cargo',
                                'Email',
                                'Fecha nacimiento',
                                'Rut jefatura',
                                'WWID',
                                'PID',
                                utf8_encode('Código').' curso',
                                'Tipo curso',
                                'Motor',
                                'Horas',
                                'Nombre curso',
                                'Lugar de '.utf8_encode('ejecución'),
                                utf8_encode('Evaluación'),
                                'Fecha inicial',
                                'Fecha final',
                                'Asistencia',
                                utf8_encode('Código').' instructor',
                                'Nombre instructor',
                                'WWID instructor',
                                'PID instructor',
                                'Email instructor',
                                utf8_encode('Télefono').' instructor'
                            ]);
                foreach ($sabana as $key => $value) {
                    $totalAsistencias;
                    if (isset($sabanaasistencia[$value->curtrabaja])) {
                        $totalAsistencias = number_format($sabanaasistencia[$value->curtrabaja], 1, ',', '.');
                    }else{
                        $totalAsistencias = "-";
                    }

                    $sheet->appendRow(array($value->ruttraba,
                                            $value->nombretraba,
                                            $value->apeptrabaja,
                                            $value->apemtrabaja,
                                            $value->empresa,
                                            \PHPExcel_Shared_Date::PHPToExcel(strtotime($value->fecha_ingreso)),
                                            $value->estado,
                                            $value->genero,
                                            $value->sucursal,
                                            $value->centrocosto,
                                            $value->cargo,
                                            $value->email,
                                            \PHPExcel_Shared_Date::PHPToExcel(strtotime($value->fecha_nacimiento)),
                                            $value->rut_jefatura,
                                            $value->wwid,
                                            $value->pid,
                                            $value->cursos_codigo,
                                            $value->nom_tipo_curso,
                                            $value->nom_motor,
                                            $value->horas,
                                            $value->nombre_curso,
                                            $value->lugar_ejecucion->nombre,
                                            number_format($value->nota_final, 1, ',', '.'),
                                            \PHPExcel_Shared_Date::PHPToExcel(strtotime($value->fecha_inicio)),
                                            \PHPExcel_Shared_Date::PHPToExcel(strtotime($value->fecha_fin)),
                                            $totalAsistencias,
                                            $value->rut_instructor,
                                            $value->nombre_instruc,
                                            $value->wwid_instruc,
                                            $value->pid_instruc,
                                            $value->email_instruc,
                                            $value->telefono_instruc));

                    $count = $count +1;
                }
            });
        })->download('xlsx');
    }
}

