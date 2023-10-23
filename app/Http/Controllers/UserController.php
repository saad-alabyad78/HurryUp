<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;


use Illuminate\Validation\ValidationException;

class UserController extends Controller
{
    public function index()
    {
        $users = User::all();
        return response()->json($users);
    }

    public function getprofile()
    {
        $id = Auth::id() ;
        $user = User::where('id',$id)->first();
        return response()->json($user);
    }





    public function register(Request $request)
    {

        try{
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'gender' => 'required|in:Male,Female',
            'birth' => 'required|date',
            'phone' => 'required|string',
        ]);

        $user = new User([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
            'gender' => $request->gender,
            'birth' => $request->birth,
            'phone' => $request->phone,
        ]);

        $user->save();

        $token = auth()->login($user);

        return response()->json(['token' => $token], 201);
    } catch (ValidationException $e) {
        return response()->json(['error' => $e->errors()], 400);
    } catch (\Exception $e) {
        return response()->json(['error' => 'Registration failed'], 500);
    }
    }




public function login(Request $request)
{
    $credentials = $request->only('email', 'password');

    if (!Auth::attempt($credentials)) {
        return response()->json(['error' => 'Invalid credentials'], 401);
    }

    $user = Auth::user();
    $token = auth()->login($user);

    return response()->json(['token' => $token]);
}




public function getid()
{
    $userId = Auth::id();

    return response()->json([
        'user_id' => $userId
    ]);
}

public function logout()
{
    Auth::guard('api')->logout();

    return response()->json([
        'message' => 'Successfully logged out'
    ]);
}




}
