<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=11">
    <title> Sistema Seguimiento y Alerta Temprana | Error </title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>

    <link href="{{ asset('/css/all.css') }}" rel="stylesheet" type="text/css" />

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/img/ucsc.png') }}">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
</head>

<body class="hold-transition skin-red sidebar-mini">
  <div class="wrapper">
    <header class="main-header">
        <a href="{{ url('/login') }}" class="logo">
            <!-- mini logo for sidebar mini 50x50 pixels -->
            <img src="{{ asset('/img/logo_ucsc.png') }}" width="150" height="45" alt="UCSC Logo">
            <!-- logo for regular state and mobile devices -->
            <span class="logo-lg"><b>logo_ucsc</b></span>
        </a>
        <nav class="navbar navbar-static-top">
           {{--  <a href="#" class="sidebar-toggle" data-toggle="push-menu" role="button"> --}}
              <span class="sr-only">Toggle navigation</span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
              <span class="icon-bar"></span>
            </a>
        </nav>
    </header>

    <div class="content-wrapper">
      <section class="content-header">
        <h1>
          No se encuentra la página que busca
        </h1>
        <ol class="breadcrumb">
          <li><a href="{{ url('/login') }}"><i class="fa fa-home"></i>Home</a></li>  
          <li class="active">Error</li>
        </ol>
      </section>

      <section class="content">
        <div class="error-page">
            <h2 class="headline text-danger">
              <img src="{{ asset('/img/logo_ucsc.png') }}" style="height: 100px" alt="UCSC Logo" >
            </h2>

            <div class="error-content">
                <h3><i class="fa fa-warning text-danger"></i> Lo sentimos pero la página que busca no funciona.</h3>

                <p>
                    No pudimos encontrar la página que estabas buscando.
                    Mientras tanto, puedes regresar al home <a href="{{ url('/home') }}">haciendo click aquí</a>.
                </p>
            </div>
        </div>
      </section>
    </div>
    <footer class="main-footer" style="text-align: right;">
      <strong>(V1.0)</strong>
    </footer>
  </div>
</body>
</html>