@extends('adminlte::layouts.app')

@section('htmlheader_title')
	{{ trans('adminlte_lang::message.home') }}
@endsection

@section('contentheader_title')
    Dashboard
@endsection

@section('footer_title')
  SGC-F010
@endsection

@section('breadcrumb_nivel')
        <li><a href="{{ url('/login') }}"><i class="fa fa-home"></i>Dashboard</a></li>  
@endsection

@section('main-content')

    <div class="row">
      <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="box box-danger">
          <div class="box-header with-border">
            <h4>Factores de riesgo</h4>
            <div class="col-lg-3 col-xs-6">
              <div class="small-box bg-aqua">
                <div class="inner">
                  <h3>40<sup style="font-size: 20px">%</sup></h3>
                  <p>Riesgo Académico</p>
                </div>
                <div class="icon">
                  <i class="ion ion-pie-graph"></i>
                </div>
                <a href="informacion.html" class="small-box-footer">Más información <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div>
            <div class="col-lg-3 col-xs-6">
             <div class="small-box bg-green">
                <div class="inner">
                  <h3>30<sup style="font-size: 20px">%</sup></h3>
                  <p>Riesgo Socioeconómico</p>
                </div>
                <div class="icon">
                  <i class="ion ion-pie-graph"></i>
                </div>
                <a href="informacion.html" class="small-box-footer">Más información <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div>
            <div class="col-lg-3 col-xs-6">
              <div class="small-box bg-yellow">
                <div class="inner">
                  <h3>10<sup style="font-size: 20px">%</sup></h3>
                  <p>Riesgo Contexto</p>
                </div>
                <div class="icon">
                  <i class="ion ion-pie-graph"></i>
                </div>
                <a href="informacion.html" class="small-box-footer">Más información <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div>
            <div class="col-lg-3 col-xs-6">
              <div class="small-box bg-red">
                <div class="inner">
                  <h3>20<sup style="font-size: 20px">%</sup></h3>
                  <p>Riesgo Financiero</p>
                </div>
                <div class="icon">
                  <i class="ion ion-pie-graph"></i>
                </div>
                <a href="informacion.html" class="small-box-footer">Más información <i class="fa fa-arrow-circle-right"></i></a>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="row">
      <div class="col-md-6 col-sm-4 col-xs-12">
        <div class="box box-danger">
          <div class="box-header with-border">
            <h3 class="box-title">Total alumnos por riesgo</h3>
            <div class="box-body chart-responsive">
              <div class="chart" id="chart-alumnos" style="height: 200px; position: relative;"></div>
            </div>
          </div>
        </div>
      </div>

      <div class="col-md-6 col-sm-4 col-xs-12">
        <div class="box box-danger">
          <div class="box-header with-border">
            <h3 class="box-title">Riegos por facultad</h3>
            <div class="box-body chart-responsive">
              <div class="chart" id="chart-facultades" style="height: 200px; position: relative;"></div>
            </div>
          </div>
        </div>
      </div>     
    </div>

    <br>
    <div class="row">
      <div class="col-md-12 col-sm-12 col-xs-12">
        <div class="box box-danger">
          <div class="box-header with-border">
            <h4>Estado derivaciones</h4>

              <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                  <span class="info-box-icon bg-green"><i class="ion ion-checkmark-round"></i></span>

                  <div class="info-box-content">
                    <span class="info-box-text">Cerrado</span>
                    <span class="info-box-text">por existoso</span>
                    <span class="info-box-number">50<small>%</small></span>
                  </div>
                </div>
              </div>

              <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                  <span class="info-box-icon bg-red"><i class="ion ion-close-round"></i></span>

                  <div class="info-box-content">
                    <span class="info-box-text">Cerrado </span>
                    <span class="info-box-text">por deserción</span>
                    <span class="info-box-number">20<small>%</small></span>
                  </div>
                </div>
              </div>

              <!-- fix for small devices only -->
              <div class="clearfix visible-sm-block"></div>

              <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                  <span class="info-box-icon bg-aqua"><i class="ion ion-refresh"></i></span>

                  <div class="info-box-content">
                    <span class="info-box-text">Cerrado </span>
                    <span class="info-box-text">por no corresponder</span>
                    <span class="info-box-number">15<small>%</small></span>
                  </div>
                </div>
              </div>

              <div class="col-md-3 col-sm-6 col-xs-12">
                <div class="info-box">
                  <span class="info-box-icon bg-yellow"><i class="ion ion-email"></i></span>

                  <div class="info-box-content">
                    <span class="info-box-text">Cerrado</span>
                    <span class="info-box-text">por no respuesta alumno</span>
                    <span class="info-box-number">15<small>%</small></span>
                  </div>
                </div>
              </div>

          </div>
        </div>
      </div>     
    </div>

@endsection

@section('scripts')
@parent

    <script type="text/javascript">
      $(function () {
        "use strict";

        //BAR Total alumnos por riesgo
        var bar = new Morris.Bar({
          element: 'chart-alumnos',
          resize: true,
          data: [
            {y: 'Académico', a: 100},
            {y: 'Socioeconómico', a: 75},
            {y: 'Contexto', a: 50},
            {y: 'Financiero', a: 80}
          ],
          barColors: ['#4D4D4D'],
          xkey: 'y',
          ykeys: ['a'],
          labels: ['N° alumnos'],
          hideHover: 'auto'
        });

        //BAR Riegos por facultad
        var bar = new Morris.Bar({
          element: 'chart-facultades',
          resize: true,
          data: [
            {y: 'Facultad 1', a: 100, b: 90, c: 85, d:76},
            {y: 'Facultad 2', a: 75, b: 65, c: 85, d:76},
            {y: 'Facultad 3', a: 50, b: 40, c: 85, d:76},
            {y: 'Facultad 4', a: 75, b: 65, c: 85, d:76},
            {y: 'Facultad 5', a: 50, b: 40, c: 85, d:76}
          ],
          barColors: ["#3c8dbc", "#00a65a", "#f39c12", "#f56954"],
          xkey: 'y',
          ykeys: ['a', 'b', 'c', 'd'],
          labels: ['Académico', 'Socioeconómico', 'Contexto', 'Financiero'],
          hideHover: 'auto'
        });
      });
    </script>

@endsection