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
    <a href="{{ url('/') }}" class="logo" style="background-color: #D90A07">
        <!-- mini logo for sidebar mini 50x50 pixels -->
        <img src="{{ asset('/img/logo_ucsc.png') }}" width="150" height="45" alt="UCSC Logo">
        <!-- logo for regular state and mobile devices -->
        <span class="logo-lg"><b>logo_ucsc</b></span>
    </a>

    <!-- Header Navbar -->
    <nav class="navbar navbar-static-top" role="navigation" style="background-color: #D90A07">
    </nav>
</header>
<body class="hold-transition login-page">
    <div id="app" style="margin-bottom: 20%;">
        <div class="login-box">
            <div class="login-logo">
                <b>Rol</b>
            </div><!-- /.login-logo -->

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
        <div class="box box-danger">
            <div class="login-box-body">
                <p class="login-box-msg"> {{ trans('adminlte_lang::message.selectRol') }} </p>
                <form action="{{ url('/login') }}" method="post">
                    <div id="mensaje"></div>
                    <input type="hidden" name="_token" value="{{ csrf_token() }}">
                    <div class="form-group has-feedback">
                        <input type="email" class="form-control" readonly="readonly" placeholder="{{ $correo }}" id="email" name="email" value="{{ $correo }}" />
                        <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                    </div>
                    <div class="form-group" id="rolesGenerados" name="rolesGenerados">
                        <select id='rol' name='rol' class='form-control' required='required' onchange="getRol(this)">
                            <option selected value=''>Seleccione Rol</option>
                            @foreach($rolesArreglo as $rol)
                                @if ($rol == 'admin'){
                                    <option value="{{$rol}}">Administrador</option>
                                @endif
                                @if ($rol == 'alumno'){
                                    <option value="{{$rol}}">Alumno</option>
                                @endif
                                @if ($rol == 'instructor'){
                                    <option value="{{$rol}}">Instructor</option>
                                @endif
                                @if ($rol == 'jefatura'){
                                    <option value="{{$rol}}">Jefatura</option>
                                @endif
                            }
                            @endforeach
                        </select>
                    </div>
                </form>

            </div><!-- /.login-box-body -->
        </div>

    </div><!-- /.login-box -->
    </div>



</body>

@endsection

@section('scripts_auth')

@parent

<script type="text/javascript">

    function getRol(valor) {
        var email = $('#email').val();
        if (valor.value != '') {
            var rol = valor.value;
            var token = "{{ csrf_token() }}";

            $.ajax({
                url: "{{ route('login.saveRolGoogle') }}",
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
                        location.replace("/sgc/public/home");
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