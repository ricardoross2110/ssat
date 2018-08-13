<!DOCTYPE html>
<html  lang="en">

@section('htmlheader')
    @include('adminlte::layouts.partials.htmlheader')
@show

@yield('content')

<!-- footer para index-->
@if (! Auth::guest())
    @include('adminlte::layouts.partials.footer')
@else
    @include('adminlte::layouts.partials.footerIndex')
@endif

@section('scripts_auth')
    @include('adminlte::layouts.partials.scripts_auth')
@show

</html>