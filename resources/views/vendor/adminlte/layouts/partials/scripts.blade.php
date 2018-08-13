<!-- REQUIRED JS SCRIPTS -->

<!-- JQuery and bootstrap are required by Laravel 5.3 in resources/assets/js/bootstrap.js-->
<!-- Laravel App -->
<script src="{{ asset('/js/app.js') }}" type="text/javascript"></script>

<!-- icheck -->
<script src="{{ asset('/plugins/iCheck/icheck.min.js')}}"></script>

<!-- datatable -->
<script src="{{ asset('/plugins/datatables/jquery.dataTables.min.js') }}" type="text/javascript"></script>
<script src="{{ asset('/plugins/datatables/dataTables.bootstrap.min.js') }}" type="text/javascript"></script>

<!-- Duallistbox Files -->
<script src="{{ asset('/plugins/duallistbox/src/jquery.bootstrap-duallistbox.js') }}" type="text/javascript"></script>

<!-- multi select -->
<script src="{{ asset('/plugins/multiselectjs/dist/js/multiselect.min.js') }}" type="text/javascript"></script>

<!-- Datepicker Files -->
<script src="{{ asset('/plugins/datePicker/bootstrap-datepicker.js') }}"></script>
<!-- Languaje Datepicker -->
<script src="{{ asset('/plugins/datePicker/locales/bootstrap-datepicker.es.js') }}"></script>

<!-- Timepicker Files -->
<script src="{{ asset('/plugins/timepicker/bootstrap-timepicker.min.js') }}"></script>

<!-- graficos -->
<script src="{{ asset('plugins/raphael/raphael.min.js') }}"></script>
<script src="{{ asset('plugins/morris/morris.min.js') }}"></script>

<!-- Fullcalendar files -->
<script src="{{ asset('plugins/moment/min/moment.min.js') }}"></script>
<script src="{{ asset('plugins/fullcalendar/fullcalendar.min.js') }}"></script>
<script src="{{ asset('plugins/fullcalendar/locale/es.js') }}"></script>

<!-- treegrid -->
<script src="{{ asset('/plugins/jquery-treegrid/js/jquery.treegrid.js') }}"></script>
<script src="{{ asset('/plugins/jquery-treegrid/js/jquery.treegrid.bootstrap3.js') }}"></script>

<!-- Optionally, you can add Slimscroll and FastClick plugins.
      Both of these plugins are recommended to enhance the
      user experience. Slimscroll is required when using the
      fixed layout. -->
<script type="text/javascript">

    var patronPermitido =/^[A-Za-z\u00E1\u00E9\u00ED\u00F3\u00FA\u00F1\u00D1\u00C1\u00C9\u00CD\u00D3\u00DA0-9 ]*$/;

    $(function() {
        if(!$.support.placeholder) { 
            var active = document.activeElement;
            $('input[type="textarea"], textarea').focus(function () {
                if ($(this).attr('placeholder') != '' && $(this).val() == $(this).attr('placeholder')) {
                    $(this).val('').removeClass('hasPlaceholder');
                }
            }).blur(function () {
                if ($(this).attr('placeholder') != '' && ($(this).val() == '' || $(this).val() == $(this).attr('placeholder'))) {
                    $(this).val($(this).attr('placeholder')).addClass('hasPlaceholder');
                }
            });
            $('input[type="textarea"], textarea').blur();
            $('form').submit(function () {
                $(this).find('.hasPlaceholder').each(function() { $(this).val(''); });
            });
        }
    });

    function number_format(number, decimals, dec_point, thousands_sep) {
        // Strip all characters but numerical ones.
        number = (number + '').replace(/[^0-9+\-Ee.]/g, '');
        var n = !isFinite(+number) ? 0 : +number,
            prec = !isFinite(+decimals) ? 0 : Math.abs(decimals),
            sep = (typeof thousands_sep === 'undefined') ? ',' : thousands_sep,
            dec = (typeof dec_point === 'undefined') ? '.' : dec_point,
            s = '',
            toFixedFix = function (n, prec) {
                var k = Math.pow(10, prec);
                return '' + Math.round(n * k) / k;
            };
        // Fix for IE parseFloat(0.55).toFixed(0) = 0;
        s = (prec ? toFixedFix(n, prec) : '' + Math.round(n)).split('.');
        if (s[0].length > 3) {
            s[0] = s[0].replace(/\B(?=(?:\d{3})+(?!\d))/g, sep);
        }
        if ((s[1] || '').length < prec) {
            s[1] = s[1] || '';
            s[1] += new Array(prec - s[1].length + 1).join('0');
        }
        return s.join(dec);
    }

    function onlyNumbers(evt) {
        if(evt.charCode < 47 || evt.charCode > 57){
            return false;
        }
    }

    function isNumber(event) {
        var number = parseFloat($('#' + event).val());
        if ($.isNumeric(number) === false) {
            $('#' + event).val('');
        }
    }

    function maxLengthCheck(object){
        if (object.value.length > object.maxLength){
            object.value = object.value.slice(0, object.maxLength)
        }
    }

    function onlyLetters(e) {
        tecla = (document.all) ? e.keyCode : e.which;
        if (tecla==8) 
            return true;
        //patron =/[A-Za-záéíóúñÑÁÉÍÓÚ0-9 ]/;
        patron =/[A-Za-z\u00E1\u00E9\u00ED\u00F3\u00FA\u00F1\u00D1\u00C1\u00C9\u00CD\u00D3\u00DA0-9 ]/;
        te = String.fromCharCode(tecla);
        return patron.test(te);
    }

    window.Laravel = {!! json_encode([
        'csrfToken' => csrf_token(),
    ]) !!};

    // esconder mensajes de alerta
  	window.setTimeout(function() {
  	    $(".alert").fadeTo(500, 0).slideUp(500, function(){
  	        $(this).remove(); 
  	    });
  	}, 4000);

    // iCheck
    $('input[type="checkbox"].minimal-red, input[type="radio"].minimal-red').iCheck({
        checkboxClass: 'icheckbox_minimal-red',
        radioClass   : 'iradio_minimal-red'
    })  


    // cambio caracteres para ordenamiento
    jQuery.extend( jQuery.fn.dataTableExt.oSort, {
        "letras-pre": function ( data ) {

        return data.toLowerCase()
            .replace(/\u00E1/g, 'a') //á
            .replace(/\u00E9/g, 'e') //é
            .replace(/\u00ED/g, 'i') //í
            .replace(/\u00F3/g, 'o') //ó
            .replace(/\u00FA/g, 'u') //ú
            .replace(/\u00F1/g, 'n') //ñ
            .replace(/\u00D1/g, 'n') //Ñ
            .replace(/\u00C1/g, 'a') //Á
            .replace(/\u00C9/g, 'e') //É
            .replace(/\u00CD/g, 'i') //Í
            .replace(/\u00D3/g, 'o') //Ó
            .replace(/\u00DA/g, 'u') //Ú           
            .replace(/ç/g, 'c');
        },
        "letras-asc": function ( a, b ) {
            return ((a < b) ? -1 : ((a > b) ? 1 : 0));
        },
        "letras-desc": function ( a, b ) {
            return ((a < b) ? 1 : ((a > b) ? -1 : 0));
        }
    });


    jQuery.extend( jQuery.fn.dataTableExt.oSort, {
        "num-html-pre": function ( a ) {
            var x = String(a).replace( /<[\s\S]*?>/g, "" );
            return parseFloat( x );
        },
       
        "num-html-asc": function ( a, b ) {
            return ((a < b) ? -1 : ((a > b) ? 1 : 0));
        },
       
        "num-html-desc": function ( a, b ) {
            return ((a < b) ? 1 : ((a > b) ? -1 : 0));
        }
    });  

    // datatable grillas
    $('#MyTable').DataTable({
        processing: true,
        pageLength: 10,
        searching   : false,
        language: {
            "url": '{!! asset('/plugins/datatables/latino.json') !!}'
        },
        columnDefs: [
            { type: 'num-html', targets: 0 },
            { type: 'letras', targets: "_all" }
        ]

    });

    // datatable "Programas Hoy" Home
    $('#MyTable2').DataTable({
        processing: true,
        //serverSide: true,
        pageLength: 10,
        //select: true,
        searching   : false,
        language: {
            "url": '{!! asset('/plugins/datatables/latino.json') !!}'
        }
    });
    // datatable "notificaciones" Home
    var table3 = $('#MyTable3').DataTable({
        processing: true,
        //serverSide: true,
        pageLength: 10,
        //select: true,
        searching   : false,
        order: [[ 0, 'asc' ], [ 1, 'desc' ]],
        columnDefs: [
            { "visible": false, "targets": 0 },
            { "visible": false, "targets": 1 }
        ],
        language: {
            "url": '{!! asset('/plugins/datatables/latino.json') !!}'
        }
    });

    // datatable grillas
    $('.MyTableC').DataTable({
        processing  : true,
        pageLength  : 5,
        searching   : false,
        ordering    : false,
        language    : {
            "url": '{!! asset('/plugins/datatables/latino.json') !!}'
        },
        columnDefs  : [
            { type: 'num-html', targets: 0 },
            { type: 'letras', targets: "_all" }
        ]

    });

    // multi selector -> <-
    jQuery(document).ready(function($) {
        $('#multiselect1').multiselect({
            search: {
                left: '<input type="text" name="q" class="form-control" placeholder="Filtrar" onkeypress="return onlyLetters(event)" />',
                right: '<input type="text" name="q" class="form-control" placeholder="Filtrar" onkeypress="return onlyLetters(event)" />',
            },
            fireSearch: function(value) {
                return value.length > 3;
            }
        });
        $('#multiselect2').multiselect();
    }); 

    $(document).ready(function() {
        $('#codigo').focusout(function(event) {
        //    console.log($(this).val());
            if (!/^([0-9])*$/.test($(this).val())) {
                $(this).val('');
            }
        });

        $('#horas').focusout(function(event) {
        //    console.log($(this).val());
            if (!/^([0-9])*$/.test($(this).val())) {
                $(this).val('');
            }
        });

        $('#codigo').change(function(event) {
            if (parseInt($(this).val()) > 999999999) {
                $(this).val('');
            }
        });
        
        $('#nivel').focusout(function(event) {
        //    console.log($(this).val());
            if (!/^([0-9])*$/.test($(this).val())) {
                $(this).val('');
            }
        });     

    }); 
    
</script>
