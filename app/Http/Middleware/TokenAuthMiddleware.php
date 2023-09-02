<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class TokenAuthMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->header('Authorization') ?? $request->Authorization; // Assuming token is sent in the 'Authorization' header

        if (!$token || !Auth::check()) {
            if ($request->isMethod("POST")) return response()->json(['message' => 'Unauthenticated'], 401);
            else abort(404);
        }

        return $next($request);
    }
}
