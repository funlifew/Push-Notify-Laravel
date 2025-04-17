<?php
namespace Funlifew\PushNotify\Http\Middlewares;

use Closure;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as BaseVerifier;

class DisableCsrfMiddleware extends BaseVerifier{
    public function handle($request, Closure $next){
        if($request->is("notify/api/*")){
            return $next($request);
        }

        return parent::handle($request, $next);
    }
}