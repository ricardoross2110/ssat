@extends('adminlte::layouts.app')

@section('htmlheader_title')
	{{ trans('adminlte_lang::message.home') }}
@endsection

@section('contentheader_title')
    Error 503
@endsection

@section('contentheader_description')    
    Error en el servidor.
@endsection

{{-- @section('footer_title')
    SGC-F009
@endsection --}}

@section('breadcrumb_nivel')
    <li><a href="{{ url('/login') }}"><i class="fa fa-home"></i>Home</a></li>  
    <li class="active">Error</li>
@endsection

@section('main-content')

    <br>

    <div class="error-page">
    	<h2 class="headline text-warning"> 503</h2>

    	<div class="error-content">
      		<h3><i class="fa fa-warning text-warning"></i> Lo sentimos pero tenemos problemas con el servidor.</h3>

	        <p>
        		Porfavor vuelva a ingresar a esta página más tarde.
        		Mientras tanto, puedes regresar al home <a href="{{ url('/home') }}">haciendo click aquí</a>.
      		</p>
        </div>
  	</div>

@endsection