<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">

    <!-- sidebar: style can be found in sidebar.less -->
    <section class="sidebar">

        <!-- Sidebar user panel (optional) -->
        @if (! Auth::guest())
            <div class="user-panel">
                <div class="pull-left info">
                    <p>Administrador</p>
                </div>
            </div>
        @endif

        <!-- Sidebar Menu -->
        <ul class="sidebar-menu">
            <li class="header">{{ trans('adminlte_lang::message.header') }}</li>
            @if ( Auth::user()->rol_select == 'admin' )
                <li class="treeview">
                    <a href="#"><i class="fa fa-gears"></i> <span>{{ trans('adminlte_lang::message.administracion') }}</span>
                        <span class="pull-right-container">
                            <i class="fa fa-angle-left pull-right"></i>
                        </span>
                    </a>
                    <ul class="treeview-menu">
                        <!--  <li><a href="usuarios.html">Usuarios y Perfiles</a></li> -->
                        <li><a href="tipoAccion.html">{{ trans('adminlte_lang::message.tiposAccion') }}</a></li>
                        <li><a href="derivacion.html">{{ trans('adminlte_lang::message.derivaciones') }}</a></li>
                        <!--   <li><a href="#">Encuestas</a></li> -->
                        <li class="treeview">
                        <a href="#">{{ trans('adminlte_lang::message.variableRiesgo') }}
                            <span class="pull-right-container">
                                <i class="fa fa-angle-left pull-right"></i>
                            </span>
                        </a>
                        <ul class="treeview-menu">
                            <li><a href="variables.html">{{ trans('adminlte_lang::message.tipoRiesgo') }}</a></li>
                            <li><a href= "riesgoAcad.html">{{ trans('adminlte_lang::message.riesgoAcademico') }}</a></li>
                            <li><a href="riesgoSocioEcon.html">{{ trans('adminlte_lang::message.riesgoSocioeconomico') }}</a></li>
                            <li><a href="riesgoContex.html">{{ trans('adminlte_lang::message.riesgoContexto') }}</a></li>
                            <li><a href="riesgoFinan.html">{{ trans('adminlte_lang::message.riesgoFinanciero') }}</a></li>
                        </ul>
                    </li>
                  </ul>
                </li>
                <li><a href="informacion.html"><i class="fa fa-file-text"></i> <span>{{ trans('adminlte_lang::message.informacion') }}</span></a></li>
                <li><a href="predictivo.html"><i class="fa fa-bell"></i> <span>{{ trans('adminlte_lang::message.predictivoAlertas') }}</span></a></li>   
                <li><a href="seguimiento.html"><i class="fa fa-calendar-plus-o"></i> <span>{{ trans('adminlte_lang::message.seguimiento') }}</span></a></li>
                <li><a href="index_administrador.html"><i class="fa fa-bar-chart"></i> <span>{{ trans('adminlte_lang::message.reportes') }}</span></a></li>    
            @endif
        </ul>
    </section>
</aside>
