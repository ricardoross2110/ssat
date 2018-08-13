<?php

	function ArbolColaboradores($trabajador, $estado)
    {

        $array 		   = [];

        array_push($array, $trabajador);

        $colaboradores = [];
        

        if ($estado) {
            foreach ($trabajador as $key => $value) {
                $t[$value->rut] =  DB::table('trabajadores')->select('trabajadores.*')->where('rut_jefatura','=', $value->rut)->where('rut_jefatura', '<>', Auth::user()->trabajadores_rut)->get();

                foreach ($t[$value->rut] as $colaborador) {
                    $colaborador->estado = ($colaborador->estado) ? "Activo" : "Inactivo" ;
                }

                if (count($t[$value->rut]) > 0) {
    	        	array_push($colaboradores, ArbolColaboradores($t[$value->rut], true));
                }
            }

            if (count($colaboradores) > 0) {
    	    	array_push($array, $colaboradores);
            }
        }else{
            
        }


        return $array;
    }

    function ArbolColaboradoresJefatura($trabajador, $filtrar, $estado)
    {

        $array         = [];

        array_push($array, $trabajador);

        $colaboradores = [];

        if ($estado) {
            foreach ($trabajador as $key => $value) {
                $t[$value->rut]   = DB::table('curso_trabajador')
                                        ->select('curso_trabajador.*', 'trabajadores.*', 'empresas.nombre As nombre_empresa', 'sucursales.nombre As nombre_sucursal')
                                        ->join('trabajadores', 'curso_trabajador.trabajadores_rut', '=', 'trabajadores.rut')
                                        ->join('empresas', 'trabajadores.empresas_id', '=', 'empresas.id')
                                        ->join('sucursales', 'trabajadores.sucursales_codigo', '=', 'sucursales.codigo');
        
                $t[$value->rut] = $t[$value->rut]->where(function ($q) use ($filtrar) {
                    foreach($filtrar as $vf_id){
                        $q->orWhere('versiones_id', '=', $vf_id->id);
                    }
                });

                $t[$value->rut]       = $t[$value->rut]->where('rut_jefatura','=', $value->rut)->where('rut_jefatura', '<>', Auth::user()->trabajadores_rut)->get();

                foreach ($t[$value->rut] as $colaborador) {
                    $colaborador->estado = ($colaborador->estado) ? "Activo" : "Inactivo" ;
                }

                if (count($t[$value->rut]) > 0) {
                    array_push($colaboradores, ArbolColaboradoresJefatura($t[$value->rut], $filtrar, true));
                }
            }

            if (count($colaboradores) > 0) {
                array_push($array, $colaboradores);
            }
        }else{
            
        }

        return $array;
    }

    function ArbolColaboradoresBusqueda($trabajador, $rut, $apellido_paterno, $wwid, $pid, $cargo, $centroCosto, $sucursal, $empresa, $status, $estado)
    {

        $array         = [];

        array_push($array, $trabajador);

        $colaboradores = [];
        
        foreach ($trabajador as $key => $value) {
            $t[$value->rut] =  DB::table('trabajadores')->select('trabajadores.*')->where('rut_jefatura','=', $value->rut)->where('rut_jefatura', '<>', Auth::user()->trabajadores_rut);

            if(!is_null($status) && $status <> 2){
                $t[$value->rut] = $t[$value->rut]->where('estado', '=', $status);
            }

            if(!is_null($rut)){
                if (strpos($rut, 'k') || strpos($rut, 'K')) {
                    if (strpos($rut, 'k')) {
                        $t[$value->rut] = $t[$value->rut]->where(function ($q) use ($rut) {
                            $q = $q->orWhere('rut', '=', $rut);
                            $q = $q->orWhere('rut', '=', str_replace('k', 'K', $rut));
                        });
                    }elseif(strpos($rut, 'K')) {
                        $t[$value->rut] = $t[$value->rut]->where(function ($q) use ($rut) {
                            $q = $q->orWhere('rut', '=', $rut);
                            $q = $q->orWhere('rut', '=', str_replace('K', 'k', $rut));
                        });
                    }
                }else{
                    $t[$value->rut] = $t[$value->rut]->where('rut','=', $rut);
                }
            }

            if(!is_null($wwid)){
                $t[$value->rut] = $t[$value->rut]->where('wwid','=',$wwid);
            }

            if(!is_null($pid)){
                $t[$value->rut] = $t[$value->rut]->where('pid','=',$pid);
            }
            
            if(!is_null($cargo)){
                $t[$value->rut] = $t[$value->rut]->where('cargos_codigo','=',$cargo);
            }

            if(!is_null($centroCosto)){
                $t[$value->rut] = $t[$value->rut]->where('centrosCostos_codigo','=',$centroCosto);
            }

            if(!is_null($sucursal)){
                $t[$value->rut] = $t[$value->rut]->where('sucursales_codigo','=',$sucursal);
            }

            if(!is_null($empresa)){
                $t[$value->rut] = $t[$value->rut]->where('empresas_id','=', $empresa);
            }

            $t[$value->rut] = $t[$value->rut]->orderBy('apellido_paterno', 'asc')->groupBy('trabajadores.rut')->get();

            if (count($t[$value->rut]) > 0) {
                array_push($colaboradores, ArbolColaboradores($t[$value->rut],  $rut, $apellido_paterno, $wwid, $pid, $cargo, $centroCosto, $sucursal, $empresa, $status, true));
            }
        }

        if (count($colaboradores) > 0) {
            array_push($array, $colaboradores);
        }

        return $array;
    }
    
    function array_values_recursive($array) {
	  	$flat = array();

	  	foreach($array as $value) {
	    	if (is_array($value)) {
	        	$flat = array_merge($flat, array_values_recursive($value));
	    	}else {
	        	$flat[] = $value;
	    	}
		}
		return $flat;
	}