<head>
    <meta charset="UTF-8">
    <meta http-equiv="X-UA-Compatible" content="IE=11">
    <title> Sistema Seguimiento y Alerta Temprana | @yield('htmlheader_title', 'Your title here') </title>
    <meta content='width=device-width, initial-scale=1, maximum-scale=1, user-scalable=no' name='viewport'>
    <!-- CSRF Token -->
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <link href="{{ asset('/css/all.css') }}" rel="stylesheet" type="text/css" />

    <!-- icheck -->
    <link href="{{ asset('/plugins/iCheck/all.css') }}" rel="stylesheet" type="text/css" />

    <link rel="icon" type="image/png" sizes="32x32" href="{{ asset('/img/ucsc.png') }}">

    <!-- datatable -->
    <link href="{{ asset('/plugins/datatables/dataTables.bootstrap.css') }}" rel="stylesheet" type="text/css" />

    <!-- multi select -->
    <link href="{{ asset('/plugins/multiselectjs/css/style.css') }}" rel="stylesheet" type="text/css" />

    <!-- Duallistbox -->
    <link href="{{ asset('/plugins/duallistbox/src/bootstrap-duallistbox.css') }}" rel="stylesheet" type="text/css" />

    <!-- Datepicker Files -->
    <link href="{{asset('/plugins/datePicker/datepicker3.css')}}" rel="stylesheet">

    <!-- Timepicker Files -->
    <link href="{{asset('/plugins/timepicker/bootstrap-timepicker.min.css')}}" rel="stylesheet">   

    <!-- Fullcalendar Files --> 
    <link rel="stylesheet" href="{{ asset('plugins/morris/morris.css') }}" rel="stylesheet">    
    <link href="{{ asset('plugins/fullcalendar/fullcalendar.min.css') }}" rel="stylesheet">

    <!-- HTML5 Shim and Respond.js IE8 support of HTML5 elements and media queries -->
    <!-- WARNING: Respond.js doesn't work if you view the page via file:// -->
    <!--[if lt IE 9]>
    <script src="https://oss.maxcdn.com/html5shiv/3.7.2/html5shiv.min.js"></script>
    <script src="https://oss.maxcdn.com/respond/1.4.2/respond.min.js"></script>
    <![endif]-->
    <style type="text/css" media="screen">
        .not-active {
            pointer-events: none;
            cursor: default;
            text-decoration: none;
        }
    </style>

    <script>
        window.Laravel = {!! json_encode([
            'csrfToken' => csrf_token(),
        ]) !!};
    </script>
</head>
