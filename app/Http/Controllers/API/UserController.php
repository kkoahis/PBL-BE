<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use App\Models\User;
use Illuminate\Auth\Access\Gate;
use Illuminate\Support\Str;


class UserController extends Controller
{
    //
    public function register(Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'name' => 'required|string|min:1|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|min:5',
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 400);
            }

            $user = new User();

            $user->name = $request->name;
            $user->email = $request->email;
            $user->password = Hash::make($request->password);

            $user->save();

            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function login(Request $request)
    {
        try {
            $validator = Validator::make($request->all(), [
                'email' => 'required|email',
                'password' => 'required|string|min:1'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 400);
            }

            $user = User::where('email', $request->email)->first();

            if (!$user || !Hash::check($request->password, $user->password)) {
                return response()->json(['error' => 'The email or password is incorrect, please try again'], 422);
            }

            $token = $user->createToken(Str::random(40));
            return response()->json([
                'token' => $token->plainTextToken,
                'data' => $user
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function logout(Request $request)
    {
        try {

            $validator = Validator::make($request->all(), [
                'email' => 'required|email|exists:users,email'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 400);
            }

            $user = User::where('email', $request->email)->first();

            $user->tokens()->delete();

            return response()->json(['success' => 'Logged Out Successfully!']);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function updateRole(Request $request, $email)
    {
        try {
            $validator = Validator::make($request->all(), [
                'role' => 'required|string|in:admin,hotel'
            ]);

            if ($validator->fails()) {
                return response()->json($validator->messages(), 400);
            }

            $user = User::where('email', $email)->first();

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            $user->role = $request->role;
            $user->save();

            return response()->json($user);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function getAllUser()
    {
        try {
            $users = User::all();

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
