<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\User;

class UserController extends Controller
{
    //

    public function getRoles(Request $request)
    {
        if ($request->ajax()) {
            $usuario = User::select('id', 'rol')->where('email','=',$request->input('valor'))->get();
            $counter = count($usuario);
            foreach ($usuario as $key => $value) {
            	$usuario = $value->rol;
                if ($usuario == null) {
                    $user = User::find($value->id);
                    $user->rol_select = null;
                    $user->save();
                }
            }

            if($counter > 0){
                return response()->json([
                    "usuario" => $usuario,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "usuario" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }

    public function saveRol(Request $request)
    {
        if ($request->ajax()) {
            $usuario = User::select('id')->where('email','=',$request->input('email'))->get();
            $counter = count($usuario);

            if($counter > 0){
                $user = User::find($usuario[0]->id);
                $user->rol_select = $request->input('valor');
                $user->save();
                return response()->json([
                    "usuario" => $usuario,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "usuario" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }

    public function saveRolGoogle(Request $request)
    {
        if ($request->ajax()) {
            $usuario = User::select('id')->where('email','=',$request->input('email'))->get();
            $counter = count($usuario);

            if($counter > 0){
                $user = User::find($usuario[0]->id);
                $user->rol_select = $request->input('valor');
                $user->save();
                auth()->login($user, true);
                return response()->json([
                    "usuario" => $usuario,
                    "count" => $counter
                ]);
            }else{
                return response()->json([
                    "usuario" => ' ',
                    "count" => '-1'
                ]);
            }
        }
    }


}
