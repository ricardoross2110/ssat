@extends('adminlte::layouts.auth')

@section('htmlheader_title')
    Login
@endsection

@section('footer_title')
    SGC-F001
@endsection

@section('content')
<!-- Main Header -->
<header class="main-header">

    <!-- Logo -->
    <a href="{{ url('/') }}" class="logo" style="background-color: #FFFFFF">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <img src="{{ asset('/img/logo_ucsc.png') }}" width="150" height="45" alt="UCSC Logo">
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg"><b>logo_ucsc</b></span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation" style="background-color: #FFFFFF">
    </nav>
</header>
<body class="hold-transition login-page">
    <div id="app">
        <div class="login-box">
            <div class="login-logo">
                <b style="color: #D6092C">Sistema de Seguimiento y Alerta Temprana UCSC</b>
            </div><!-- /.login-logo -->
        @if(isset($error))
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif
        @if (count($errors) > 0)
            <div class="alert alert-danger">
                {{ trans('adminlte_lang::message.someproblems') }}<br><br>
                <ul>
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div id="mensaje"></div>
        <div class="box box-danger">
            <div class="login-box-body">
                <p class="login-box-msg"> {{ trans('adminlte_lang::message.siginsession') }} </p>
                <form action="{{ url('/login') }}" method="post">
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="form-group has-feedback">
                        <input type="email" class="form-control" required="required" placeholder="{{ trans('adminlte_lang::message.email') }}" id="email" name="email" onblur="getRoles(this);" />
                        <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                    </div>
                    <div class="form-group has-feedback">
                        <input type="password" class="form-control" required="required" placeholder="{{ trans('adminlte_lang::message.password') }}" name="password"/>
                        <span class="glyphicon glyphicon-lock form-control-feedback"></span>
                    </div>
                    <div class="form-group" id="rolesGenerados" name="rolesGenerados">
                        <select id='rol' name='rol' class='form-control' required='required'>
                            <option selected value=''>Seleccione Rol</option>
                        </select>
                    </div>

                    <div class="row">
                        <div class="col-xs-12">
                            <button type="submit" class="btn btn-danger btn-block btn-flat">{{ trans('adminlte_lang::message.buttonsign') }}</button>
                        </div><!-- /.col -->
                    </div>
                </form>

               {{--  @include('adminlte::auth.partials.social_login') --}}

            </div><!-- /.login-box-body -->
        </div>

    </div><!-- /.login-box -->
    </div>



</body>

@endsection

@section('scripts_auth')

@parent

<script type="text/javascript">

    function getRoles(valor) {
        if (valor.value != '') {
            var correo = valor.value;
            var token = "{{ csrf_token() }}";

            $.ajax({
                url: "{{ route('login.getRoles') }}",
                type: 'POST',
                dataType: 'JSON',
                data: {
                    valor: correo, 
                    "_token": token
                },
                success:function(resp) {
                    $("#rol").remove();
                    console.log(resp.count);
                    if(resp.count == '-1'){
                        $("#email").val('');
                        
                        $("#alerta-1").remove();
                        $("#mensaje").append("<div id='alerta-1' class='alert alert-danger alert-block'><button type='button' class='close' data-dismiss='alert'>×</button><i class='icon fa fa-ban'></i><strong>Correo no encontrado, favor ingrese correo válido</strong></div>");
                        $("#mensaje").animate(
                            { 
                                marginTop:'toggle',
                                display:'block'
                            }, 4000, function() {
                                $("#alerta-1").remove();
                            }
                        );
                    }else{
                        if(resp.usuario != null){                                          
                            var cadenadividida = resp.usuario.split([","]);
                            var optionRoles = "";
                            var nombre_rol = null;
                            for (var i = 0; i < cadenadividida.length; i++) {
                                if (cadenadividida[i] == 'admin'){
                                    nombre_rol = 'Administrador';
                                }else if(cadenadividida[i] == 'alumno'){
                                    nombre_rol = 'Alumno';
                                }else if(cadenadividida[i] == 'instructor'){
                                    nombre_rol = 'Instructor';
                                }else if(cadenadividida[i] == 'jefatura'){
                                    nombre_rol = 'Jefatura';
                                }
                                optionRoles = optionRoles+"<option value='"+cadenadividida[i]+"' >"+nombre_rol+"</option>"
                            }
                            $("#rolesGenerados").append("<select id='rol' name='rol' class='form-control' required='required' onchange='getRol(this)' ><option selected value=''>Seleccione Rol</option>"+optionRoles+"</select>");
                        }else{
                            $("#email").val('');
                        
                            $("#alerta-1").remove();
                            $("#mensaje").append("<div id='alerta-1' class='alert alert-danger alert-block'><button type='button' class='close' data-dismiss='alert'>×</button><i class='icon fa fa-ban'></i><strong>Correo no registra roles asociados</strong></div>");
                            $("#mensaje").animate(
                                { 
                                    marginTop:'toggle',
                                    display:'block'
                                }, 4000, function() {
                                    $("#alerta-1").remove();
                                }
                            );
                        }
                    }   
                },
                error:function() {
                    console.log('error')
                }
            });
        }else{
            
        }
    }

    function getRol(valor) {
        var email = $('#email').val();
        if (valor.value != '') {
            var rol = valor.value;
            var token = "{{ csrf_token() }}";

            $.ajax({
                url: "{{ route('login.saveRol') }}",
                type: 'POST',
                dataType: 'JSON',
                data: {
                    valor: rol,
                    email: email,
                    "_token": token
                },
                success:function(resp) {
                    if(resp.count == '-1'){
                        console.log(resp.count);
                    }else{                    
                        console.log(resp.count);                     
                    }
                },
                error:function() {
                    console.log('error')
                }
            });
        }
    }

</script>

@endsection