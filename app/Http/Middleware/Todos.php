<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Facades\Auth;

class Todos
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
        if (Auth::user()->rol_select == 'admin' || Auth::user()->rol_select == 'alumno' || Auth::user()->rol_select == 'instructor' || Auth::user()->rol_select == 'jefatura') {
            return $next($request);            
        }
        //return $next($request);
        else{
            return redirect()->back();
        }
    }
}
