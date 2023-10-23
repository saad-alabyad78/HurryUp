<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Admin;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Hash;
use App\Http\Requests\LoginUserRequest;
use App\Http\Requests\StoreUserRequest;
use App\Traits\HttpResponses;
use Laravel\Sanctum\PersonalAccessToken;
use Symfony\Component\CssSelector\Parser\Token;
class AuthController extends Controller
{
    public function login(Request $request)
    {
        $credentials = $request->only('name', 'password');

        if (Auth::guard('admin')->attempt($credentials)) {
            $admin = Admin::where('name', $request->email)->first();
            $token = $admin->createToken('Admin Token')->plainTextToken;

            return response()->json(['token' => $token]);
        }

        return response()->json(['message' => 'Invalid credentials'], 401);
    }

public function register(StoreUserRequest $request)
{
    $request->validated($request->only(['name', 'password']));

    $user = Admin::create([
        'name' => $request->name,
        'password' => Hash::make($request->password),
        'api_token' => Str::random(20),
    ]);

    return $this->success([
        'user' => $user,
        'token' => $user->api_token
    ]);
}
}
