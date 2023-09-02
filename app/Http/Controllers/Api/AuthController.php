<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Mail\VerifyRegister;
use App\Mail\WelcomeEmail;
use App\Models\User;
use Config;
use DB;
use Hash;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Mail;
use stdClass;
use Str;

class AuthController extends Controller
{
    public function changeURLAIArt(Request $request)
    {
        $request->validate([
            'base_url' => 'required'
        ]);
        $base_url = $request->base_url;

        if (Auth::check() && Auth::user()->email == "nguyenviet3057@gmail.com") {
            Cache::put('base_url', $base_url);
            return response()->json(['message' => 'Set Stable Diffusion Server to ' . $base_url . ' successfully'], 200, [], JSON_UNESCAPED_SLASHES);
        }
        return response()->json(['message' => 'Unauthenticated'], 401);
    }

    public function checkAuth(Request $request)
    {
        if (Auth::check()) {
            return response()->json(['message' => 'Authenticated'], 200);
        } else {
            return response()->json(['message' => 'Unauthenticated'], 401);
        }
    }

    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required | email',
            'password' => 'required | confirmed | min:8'
        ]);

        $name = $request->name;
        $email = $request->email;
        $password = $request->password;

        $is_exist = DB::table('users')->where('email', $email)->first();
        $response = new stdClass();
        $response->msg = "Email exists";
        $response->status = 0;
        if ($is_exist != null)
            return response()->json($response, 208);

        Mail::to($email)->send(new VerifyRegister($name, $email, $password));

        return response()->json('Email confirmation was sent', 200);
    }

    public function verify(Request $request)
    {
        $request->validate([
            'code' => 'required'
        ]);
        $code = $request->code;
        try {
            $decrypted = json_decode(Crypt::decryptString($code));
            // return response()->json($decrypted);

            $email_exist = DB::table('users')->where('email', $decrypted->email)->first();

            if ($email_exist == null) {
                $newUser = User::create([
                    'name' => $decrypted->name,
                    'email' => $decrypted->email,
                    'password' => Hash::make($decrypted->password),
                    'email_verified_at' => now()
                ]);
                Auth::setUser($newUser);
                $user = Auth::user();
                DB::table('token_count')->insert([
                    'user_id' => $user->id
                ]);
                $token = $user->createToken('authToken')->plainTextToken;

                $res = new stdClass();
                $res->status = 1;
                $res->token = $token;
                return response()->json($res, 200);
            } else {
                $res = new stdClass();
                $res->msg = "Email exists";
                $res->status = 0;
                return response()->json($res, 208);
            }
        } catch (DecryptException $e) {
            $res = new stdClass();
            $res->msg = "Invalid confirmation code";
            $res->status = -1;
            return response()->json($res, 404);
        }
    }

    public function loginFacebook(Request $request)
    {
        $request->validate([
            'userID' => 'required',
            'email' => 'required',
            'name' => 'required'
        ]);

        $userID = $request->userID;
        $email = $request->email;
        $name = $request->name;

        $existingUser = User::where('userID', $userID)->first();

        if ($existingUser) {
            // Auth::login($existingUser); //error
            Auth::setUser($existingUser);
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json(['token' => $token], 200);
        } else {
            // Creat new user if not exists
            $newUser = User::create([
                'userID' => $userID,
                'name' => $name,
                'email' => $email,
                'password' => bcrypt(Str::random(32)), // Random password
            ]);
            Auth::setUser($newUser);
            $user = Auth::user();
            DB::table('token_count')->insert([
                'user_id' => $user->id
            ]);
            $token = $user->createToken('authToken')->plainTextToken;

            return response()->json(['token' => $token], 200);
        }
    }

    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required | email',
            'password' => 'required | min:8'
        ]);

        $email = $request->email;
        $password = $request->password;
        if (Auth::guard('web')->attempt(['email' => $email, 'password' => $password])) {
            $user = Auth::user();
            $token = $user->createToken('authToken')->plainTextToken;

            $res = new stdClass();
            $res->status = 1;
            $res->token = $token;
            return response()->json($res, 200);
        }

        $res = new stdClass();
        $res->status = 0;
        $res->msg = "Account not found";
        return response()->json($res, 401);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json(['message' => 'Logged out'], 200);
    }
}