<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use stdClass;
use Symfony\Component\HttpFoundation\Response;

class CheckToken
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = Auth::user();
        if ($user == null) return response()->json(['message' => 'Unauthenticated'], 401);

        $token_count = DB::table('token_count')->where('user_id', $user->id)->first();

        if ($token_count->updated_at < date("Y-m-d")) {
            DB::table('token_count')->where('user_id', $user->id)->update([
                'daily_used' => 0,
                'updated_at' => now()
            ]);
        } else if ($token_count->daily_used == $token_count->max_token && $request->role == "user") {
            $res = new stdClass();
            $res->status = 2;
            $res->msg = "Out of tokens";
            return response()->json($res);
        }
        return $next($request);
    }
}
