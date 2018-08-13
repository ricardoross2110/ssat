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
    <div id="app" style="margin-bottom: 25%;">
        <div class="login-box">

        @if(isset($error))
            <div class="alert alert-danger">
                {{ $error }}
            </div>
        @endif
        <div class="box box-danger">
            <div class="login-box-body">
                <p>
                    <b> Para volver a login haga click aquí... </b>
                    <a class="btn btn-danger" href="{{ url('/login') }}" role="button">Login</a>
                </p>
            </div><!-- /.login-box-body -->
        </div>

    </div><!-- /.login-box -->
    </div>

</body>

@endsection