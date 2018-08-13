<?php

namespace App\Exceptions;

use Exception;
use Illuminate\Foundation\Exceptions\Handler as ExceptionHandler;

class Handler extends ExceptionHandler
{
    /**
     * A list of the exception types that are not reported.
     *
     * @var array
     */
    protected $dontReport = [
        //
    ];

    /**
     * A list of the inputs that are never flashed for validation exceptions.
     *
     * @var array
     */
    protected $dontFlash = [
        'password',
        'password_confirmation',
    ];

    /**
     * Report or log an exception.
     *
     * This is a great spot to send exceptions to Sentry, Bugsnag, etc.
     *
     * @param  \Exception  $exception
     * @return void
     */
    public function report(Exception $exception)
    {
        parent::report($exception);
    }

    /**
     * Render an exception into an HTTP response.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Exception  $exception
     * @return \Illuminate\Http\Response
     */
    public function render($request, Exception $exception)
    {

        // if(isset($exception->status)){
        //     if ($exception->status == 422) {
        //         return parent::render($request, $exception);
        //     }
        // }
        
        // $mensaje = "";
        // if ($exception->getMessage()) {
        //     $mensaje = $exception->getMessage();
        // }

        // if ($exception->getMessage() == "Unauthenticated.") {
        //     return redirect()->route('login');
        // }


        // if ($exception instanceof CustomException) {
        //     return response()->route('error', ['mensaje='.$mensaje]);
        // }

        // if ($this->isHttpException($exception)){
        //     switch ($exception->getStatusCode()){
        //         case '400':
        //             return redirect()->route('error_400');
        //             break;
        //         case '403':
        //             return redirect()->route('error_403');
        //             break;
        //         case '422':
        //             return redirect()->route('login');
        //             break;
        //         case '404':
        //             return redirect()->route('error_404');
        //             break;
        //         case '500':
        //             return redirect()->route('error_503', ['mensaje='.$mensaje]);
        //             break;
        //         case '503':
        //             return redirect()->route('error_503', ['mensaje='.$mensaje]);
        //             break;
        //         default:
        //             return parent::render($request, $exception);
        //             break;
        //     }
        // }else{
        //     return redirect()->route('error_sql', ['mensaje='.$mensaje]);
        // }
        
        return parent::render($request, $exception);

    }
}
