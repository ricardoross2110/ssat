<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('adminlte::auth.login');
});

Route::get('error_400', ['as' => 'error_400', 'uses' => 'HomeController@error400']);
Route::get('error_403', ['as' => 'error_403', 'uses' => 'HomeController@error403']);
Route::get('error_404', ['as' => 'error_404', 'uses' => 'HomeController@error404']);
Route::get('error_503', ['as' => 'error_503', 'uses' => 'HomeController@error503']);
Route::get('error_sql', ['as' => 'error_sql', 'uses' => 'HomeController@errorSql']);
Route::get('error', 	['as' => 'error', 'uses' => 'HomeController@errorGeneral']);

Route::get('/logout', function(){
	Auth::logout();
	Session::flush();
	return Redirect::to('/login');
});

Route::post('login.getRoles', 			'UserController@getRoles')->name('login.getRoles');
Route::post('login.saveRol', 			'UserController@saveRol')->name('login.saveRol');
Route::post('login.saveRolGoogle', 		'UserController@saveRolGoogle')->name('login.saveRolGoogle');

//A ESTAS RUTAS INGRESA ADMIN
Route::middleware('admin', 'auth')->group( function () {

	Route::resource('calendarios', 		'CalendarioController');
	Route::resource('calificaciones', 	'CalificacionController');
	Route::resource('reportes', 	    'ReportesController');
	Route::resource('cargos', 			'CargoController');
	Route::resource('centrosCostos', 	'CentroCostoController');
	Route::resource('cursos', 			'CursoController');	
	Route::resource('comunas', 			'ComunaController');
	Route::resource('empresas', 		'EmpresaController');
	Route::resource('errores', 			'LogErroresController');
	Route::resource('instructores', 	'InstructorController');
	Route::resource('sucursales', 		'SucursalController');
	Route::resource('tipoCursos', 		'TipoCursoController');
	Route::resource('tipoMotores', 		'TipoMotorController');	
	Route::resource('unidadesNegocio', 	'UnidadNegocioController');
	Route::resource('administradores', 	'AdministradorController');
	
	//Eliminar desde grilla
	Route::get('calendarios/destroy/{id}', 			'CalendarioController@destroy');
	Route::get('calificaciones/destroy/{codigo}', 	'CalificacionController@destroy');
	Route::get('cargos/destroy/{codigo}', 			'CargoController@destroy');
	Route::get('centrosCostos/destroy/{codigo}', 	'CentroCostoController@destroy');
	Route::get('cursos/destroy/{codigo}', 			'CursoController@destroy');
	Route::get('instructores/destroy/{codigo}', 	'InstructorController@destroy');
	Route::get('sucursales/destroy/{codigo}', 		'SucursalController@destroy');
	Route::get('tipoCursos/destroy/{codigo}', 		'TipoCursoController@destroy');
	Route::get('tipoMotores/destroy/{codigo}', 		'TipoMotorController@destroy');
	Route::get('trabajadores/destroy/{rut}', 		'TrabajadorController@destroy');
	Route::get('unidadesNegocio/destroy/{codigo}', 	'UnidadNegocioController@destroy');
	Route::get('programas/destroy/{codigo}', 		'ProgramaController@destroy');
	Route::get('administradores/destroy/{codigo}', 	'AdministradorController@destroy');

	//Exportar a Excel
	Route::post('calendarios.exportExcel', 			'CalendarioController@exportExcel')->name('calendarios.exportExcel');
	Route::post('cargos.exportExcel', 				'CargoController@exportExcel')->name('cargos.exportExcel');
	Route::post('centrosCostos.exportExcel', 		'CentroCostoController@exportExcel')->name('centrosCostos.exportExcel');
	Route::post('sucursales.exportExcel', 			'SucursalController@exportExcel')->name('sucursales.exportExcel');
	Route::post('tipoCursos.exportExcel', 			'TipoCursoController@exportExcel')->name('tipoCursos.exportExcel');
	Route::post('tipoMotores.exportExcel', 			'TipoMotorController@exportExcel')->name('tipoMotores.exportExcel');
	Route::post('unidadesNegocio.exportExcel',		'UnidadNegocioController@exportExcel')->name('unidadesNegocio.exportExcel');
	Route::post('calificaciones.exportExcel', 		'CalificacionController@exportExcel')->name('calificaciones.exportExcel');
	Route::post('cursos.exportExcel', 				'CursoController@exportExcel')->name('cursos.exportExcel');
	Route::post('cursos.exportExcelHist', 			'CursoController@exportExcelHist')->name('cursos.exportExcelHist');
	Route::post('instructores.exportExcel', 		'InstructorController@exportExcel')->name('instructores.exportExcel');
	Route::post('programas.exportExcelHist', 		'ProgramaController@exportExcelHist')->name('programas.exportExcelHist');
	Route::post('reporte.exportOcupacionInstructoresExcel', 		'ReportesController@exportOcupacionInstructoresExcel')->name('reporte.exportOcupacionInstructoresExcel');
	Route::post('reporte.exportOcupacionTrabajadoresExcel', 		'ReportesController@exportOcupacionTrabajadoresExcel')->name('reporte.exportOcupacionTrabajadoresExcel');
	Route::post('reporte.exportOcupacionUnidadNegocioExcel', 		'ReportesController@exportOcupacionUnidadNegocioExcel')->name('reporte.exportOcupacionUnidadNegocioExcel');
	Route::post('reporte.exportInformacionProgramasCalificaciones', 'ReportesController@exportInformacionProgramasCalificaciones')->name('reporte.exportInformacionProgramasCalificaciones');
	Route::post('reporte.exportSabana', 'ReportesController@exportSabana')->name('reporte.exportSabana');
	/*Route::post('cursos.exportExcelHist', 			'CursoController@exportExcelHist')->name('cursos.exportExcelHist');
	Route::post('instructores.exportExcel', 		'InstructorController@exportExcel')->name('instructores.exportExcel');
	Route::post('programas.exportExcelHist', 		'ProgramaController@exportExcelHist')->name('programas.exportExcelHist');*/
	Route::post('administradores.exportExcel', 		'AdministradorController@exportExcel')->name('administradores.exportExcel');


	//Envio datos cargar en datatables//Solo Ejemplo no se ocupa
	Route::get('cargos.data', 						'CargoController@data_cargos')->name('cargos.data');
	Route::get('calendarios.data', 					'CalendarioController@data_calendarios')->name('calendarios.data');
	
	//carga datos cursos tabla niveles
	Route::post('cursos.carga', 					'CursoController@cargaCurso')->name('cursos.carga');
	
	//carga datos (uso ajax)
	Route::post('calificaciones.carga', 			'CalificacionController@cargaCurso')->name('calificaciones.carga');
	Route::post('programas.cargaNivel', 			'ProgramaController@cargaNivel')->name('programas.cargaNivel');
	Route::post('programas.infoCurso', 				'ProgramaController@infoCurso')->name('programas.infoCurso');
	
	//cambio estado
	Route::get('cursos/cambio/{codigo}', 			'CursoController@cambioEstado')->name('cursos.cambio');
	Route::get('programas/cambio/{codigo}', 		'ProgramaController@cambioEstado')->name('programas.cambio');
	
	// asistencias y evaluaciones de programas
	Route::get('programas/asistencia/{codigo}',		'ProgramaController@asistencias');
	Route::get('programas/evaluacion/{codigo}',		'ProgramaController@evaluaciones');
	Route::get('programas/repechaje/{codigo}',		'ProgramaController@repechaje');
	
	//historiales
	Route::get('cursos/historial/{codigo}',			'CursoController@historial');
	Route::get('programas/historial/{codigo}',		'ProgramaController@historial');

	//Carga Masiva de Trabajadores
	Route::post('trabajadores.saveImport', 			'TrabajadorController@saveImport');

	//para generar fechas segun la ingresada en programa
	Route::post('programas.generaFechas', 			'ProgramaController@generaFechas')->name('programas.generaFechas');	

	//validar curso instructor (quitar) (uso ajax)
	Route::post('instructores.validarCursoL', 			'InstructorController@validarCursoL')->name('instructores.validarCursoL');
	Route::post('instructores.validarCursoR', 			'InstructorController@validarCursoR')->name('instructores.validarCursoR');

	//Reportes
	Route::get('reporteOcupacionInstructores', 	'ReportesController@reporteOcupacionInstructores');
	Route::get('reporteOcupacionTrabajadores', 	'ReportesController@reporteOcupacionTrabajadores');
	Route::get('reporteOcupacionUnidadNegocio', 	'ReportesController@reporteOcupacionUnidadNegocio');
	Route::get('reporteInformacionProgramasCalificaciones', 	'ReportesController@reporteInformacionProgramasCalificaciones');
	Route::get('reporteSabana', 	'ReportesController@reporteSabana');

});

//A ESTAS RUTAS INGRESA ADMIN, ALUMNO
Route::middleware('alumno', 'auth')->group( function () {
	Route::get('programas/asistencia/{codigo}',		'ProgramaController@asistencias');
	Route::get('programas/evaluacion/{codigo}',		'ProgramaController@evaluaciones');
	Route::get('programas/mensaje/{codigo}',		'ProgramaController@mensaje');
	Route::put('programas/storeMensaje/{codigo}',	'ProgramaController@storeMensaje')->name('programas.storeMensaje');
	Route::get('programas.indexAlumno', 			'ProgramaController@indexAlumno')->name('programas/indexAlumno');
});

//A ESTAS RUTAS INGRESA ADMIN, INSTRUCTOR
Route::middleware('instructor', 'auth')->group( function () {

	//asignar trabajadores
	Route::get('programas/asistencia/{codigo}',			'ProgramaController@asistencias');
	Route::get('programas/evaluacion/{codigo}',			'ProgramaController@evaluaciones');
	Route::get('programas/repechaje/{codigo}',			'ProgramaController@repechaje');
	Route::get('programas/asignacion/{codigo}',			'ProgramaController@asignaciones');
	Route::put('programas/storeAsignacion/{codigo}',	'ProgramaController@storeAsignacion')->name('programas.storeAsignacion');
	Route::post('programas.verificarSuc', 				'ProgramaController@verificarSuc')->name('programas.verificarSuc');
	Route::post('programas.verificarSucTodos', 			'ProgramaController@verificarSucTodos')->name('programas.verificarSucTodos');
	Route::get('programas/respuesta/{codigo}',			'ProgramaController@respuesta');
	Route::put('programas/storeRespuesta/{codigo}',		'ProgramaController@storeRespuesta')->name('programas.storeRespuesta');

});

//A ESTAS RUTAS INGRESA ADMIN, INSTRUCTOR, JEFATURA
Route::middleware('instructorjefatura', 'auth')->group( function () {
	Route::get('programas/asistencia/{codigo}',		'ProgramaController@asistencias');
	Route::get('programas/evaluacion/{codigo}',		'ProgramaController@evaluaciones');
	Route::get('programas/repechaje/{codigo}',		'ProgramaController@repechaje');
	Route::resource('evaluaciones', 				'EvaluacionController');
	Route::resource('trabajadores', 				'TrabajadorController');
    
});

//A ESTAS RUTAS INGRESA ADMIN, ALUMNO, INSTRUCTOR, JEFATURA
Route::middleware('todos', 'auth')->group( function () {
	Route::get('programas/asistencia/{codigo}',		'ProgramaController@asistencias');
	Route::get('programas/evaluacion/{codigo}',		'ProgramaController@evaluaciones');
	Route::resource('programas', 					'ProgramaController');

});

Route::get('certificado/{curso_trabajador}' , 'TrabajadorController@obtenerCertificado');
Route::get('certificado/descargar/{curso_trabajador}' , 'TrabajadorController@descargarCertificado');

//NO SE SABE A QUE ROL PERTENECEN
Route::post('trabajadores.exportExcel', 			'TrabajadorController@exportExcel')->name('trabajadores.exportExcel');
Route::post('programas.exportExcel', 				'ProgramaController@exportExcel')->name('programas.exportExcel');

Route::post('instructores.getTrabajador', 			'InstructorController@cargaDataTrab')->name('instructores.getTrabajador');
Route::post('instructores.getInstructor', 			'InstructorController@getInstructor')->name('instructores.getInstructor');
Route::post('instructores.getInstructorEdit', 		'InstructorController@getInstructorEdit')->name('instructores.getInstructorEdit');
Route::post('instructores.getInstructorCorreo', 	'InstructorController@getInstructorCorreo')->name('instructores.getInstructorCorreo');
Route::post('instructores.getInstructorCorreoEdit', 'InstructorController@getInstructorCorreoEdit')->name('instructores.getInstructorCorreoEdit');
Route::post('trabajadores.getTrabajador', 			'TrabajadorController@getTrabajador')->name('trabajadores.getTrabajador');
Route::post('trabajadores.getTrabajadorCorreo', 	'TrabajadorController@getTrabajadorCorreo')->name('trabajadores.getTrabajadorCorreo');
Route::post('trabajadores.getTrabajadorCorreoEdit', 	'TrabajadorController@getTrabajadorCorreoEdit')->name('trabajadores.getTrabajadorCorreoEdit');
Route::post('programas.getPrograma', 				'ProgramaController@getPrograma')->name('programas.getPrograma');
Route::post('calificaciones.getCalificacion', 				'CalificacionController@getCalificacion')->name('calificaciones.getCalificacion');
Route::post('sucursales.getSucursal', 				'SucursalController@getSucursal')->name('sucursales.getSucursal');

Route::post('empresas.comprobarEmpresa', 			'EmpresaController@comprobarEmpresa')->name('empresas.comprobarEmpresa');

Route::post('administradores.getTrabajador', 		'AdministradorController@getTrabajador')->name('administradores.getTrabajador');
Route::post('administradores.getAdministrador', 	'AdministradorController@getAdministrador')->name('administradores.getAdministrador');
Route::post('administradores.getAdminCorreo', 		'AdministradorController@getAdminCorreo')->name('administradores.getAdminCorreo');

//descarga archivos
Route::get('cursos/download/{file}' , 				'CursoController@downloadFile');

//Ver curso jefatura
Route::get('programas/curso/{codigo}', 				'ProgramaController@verCurso');

//Certificados
Route::get('certificado/{rut}', 	'TrabajadorController@obtenerCertificado');

Route::post('programas.agregarAsistencia', 			'ProgramaController@agregarAsistencia')->name('programas.agregarAsistencia');
Route::post('programas.agregarNota',	 			'ProgramaController@agregarNota')->name('programas.agregarNota');
Route::post('programas.agregarRepechaje',	 		'ProgramaController@agregarRepechaje')->name('programas.agregarRepechaje');
Route::post('programas.asignarRepechaje',	 		'ProgramaController@asignarRepechaje')->name('programas.asignarRepechaje');

Route::post('notificacion.cambiarEstado', 			'NotificacionController@cambiarEstado')->name('notificacion.cambiarEstado');


Route::post('empresas.store', 		'EmpresaController@store');

Route::get('ListarProgramasActivos/{User},{Pass},{FechaInicio},{FechaTermino},{Sucursal},{Instructor}','ProgramaController@ListarProgramasActivos');

Route::get('RegistrarTrabajadorPrograma/{User},{Pass},{Programa},{Trabajador}','ProgramaController@RegistrarTrabajadorPrograma');
Route::get('EliminarTrabajadorPrograma/{User},{Pass},{Programa},{Curso},{Trabajador}','ProgramaController@EliminarTrabajadorPrograma');

Route::get('ListarTrabajadoresProgramas/{User},{Pass},{Programa}','ProgramaController@ListarTrabajadoresProgramas');

//Route::get('auth/{provider}', 							'Auth\LoginController@redirectToProvider');
//Route::get('auth/{provider}/callback', 					'Auth\LoginController@handleProviderCallback');

//Route::get('callback', 								'Auth\LoginController@prueba');

Route::get('login/google', 							'Auth\LoginController@redirectToProvider')->name('login/google');;
Route::get('login/google/callback', 				'Auth\LoginController@handleProviderCallback');