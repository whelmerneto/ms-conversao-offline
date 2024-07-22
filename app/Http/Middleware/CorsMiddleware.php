<?php
namespace App\Http\Middleware;

use Closure;

class CorsMiddleware
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
        if ($request->getRequestUri() ) {
            $permitedHosts = explode(',', env('CORS_ORIGIN', 'https://vetorzkm.movida.com.br'));
            $hasCorsPermission = in_array($request->headers->get('origin'), $permitedHosts);

            $headers = [
                'Access-Control-Allow-Origin'      => $hasCorsPermission === true ?
                    $request->headers->get('origin') :
                    $permitedHosts[0],
                'Access-Control-Allow-Methods'     => 'POST, GET, OPTIONS, PUT, DELETE, HEAD',
                'Access-Control-Allow-Credentials' => $hasCorsPermission,
                'Access-Control-Max-Age'           => '86400',
                'Access-Control-Allow-Headers'     => $request->header('Access-Control-Request-Headers')
            ];

            if ($request->isMethod('OPTIONS')) {
                return response()->json('{"method":"OPTIONS"}', 200, $headers);
            }

            $response = $next($request);
            foreach ($headers as $key => $value) {
                $response->header($key, $value);
            }

            return $response;
        }
    }
}
