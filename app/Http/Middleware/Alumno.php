<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Alumno
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return mixed
     */
    public function handle($request, Closure $next)
    {
        if (Auth::user()->rol_select == 'admin' || Auth::user()->rol_select == 'alumno') {
            return $next($request);            
        }
        //return $next($request);
        else{
            return redirect()->back();
        }
    }
}
