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

use function PHPUnit\Framework\isNull;

class UserController extends Controller
{
    //
    public function register(Request $request)
    {
        try {
            $validator = Validator::make(request()->all(), [
                'name' => 'required|string|min:1|max:255',
                'email' => 'required|email|unique:users,email',
                'password' => 'required|string|regex:/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d).+$/|min:8|max:255',
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
            $user = Auth::user();
            if ($user->role != 'admin') {
                return response()->json(['error' => 'You are not authorized to perform this action'], 403);
            }

            $validator = Validator::make($request->all(), [
                'role' => 'required|string|in:admin,hotel,user'
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
            $user = Auth::user();
            if ($user->role != 'admin') {
                return response()->json(['error' => 'You are not authorized to perform this action'], 403);
            }

            // paginate user with out role admin
            $users = User::where('role', '!=', 'admin')->paginate(10);

            return response()->json($users);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getProfile($id)
    {
        try {
            $user = Auth::user();

            if ($user->id != $id) {
                return response()->json(['error' => 'You are not authorized to perform this action'], 403);
            }

            $user = User::find($id);

            if (!$user) {
                return response()->json(['error' => 'User not found'], 404);
            }

            return response()->json([
                "name" => $user->name,
                "phone number" => $user->phone,
                "address" => $user->address,
                "date of birth" => $user->date_of_birth,
                "avatar" => $user->avatar,
                "gender" => $user->gender,
                "created_at" => $user->created_at,
                "email" => $user->email,
                "role" => $user->role,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function editProfile(Request $request, $id)
    {
        $input = $request->all();
        $user = Auth::user();
        if ($user->id != $id) {
            return response()->json(['error' => 'You are not authorized to perform this action'], 403);
        }

        $validator = Validator::make($input, [
            'name' => 'string|min:1|max:255',
            'phone_number' =>  'regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'gender' => 'in:0,1',
            'date_of_birth' => 'date_format:Y-m-d',
            'avatar',
            'address' => 'string|min:1|max:255',
            'password' => 'required|regex:/^(?=.*[A-Z])(?=.*\d).+$/',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->messages(), 400);
        }

        $userModel = User::find($id);

        if (!$userModel) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // if user not change avatar, keep old avatar
        if ($request->hasFile('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = time() . '.' . $avatar->getClientOriginalExtension();
            $avatar->move(public_path('images'), $avatarName);
            $userModel->avatar = $avatarName;
        }


        $userModel->name = $input['name'];
        $userModel->phone_number = $input['phone_number'];
        $userModel->gender = $input['gender'];
        $userModel->date_of_birth = $input['date_of_birth'];
        // date of birth not in the past
        if ($userModel->date_of_birth > date('Y-m-d')) {
            return response()->json(['error' => 'Date of birth must be in the past'], 400);
        }
        if (isNull($userModel->avatar)) {
            $userModel->avatar = 'https://static.vecteezy.com/system/resources/previews/009/734/564/original/default-avatar-profile-icon-of-social-media-user-vector.jpg';
        }
        $userModel->address = $input['address'];
        $userModel->password = Hash::make($input['password']);

        $userModel->save();

        if ($userModel->save()) {
            return response()->json(([
                'success' => true,
                'message' => 'User updated successfully',
                'data' => $userModel
            ]), 200);
        } else {
            return response()->json(([
                'success' => false,
                'message' => 'User updated failed',
                'data' => $userModel
            ]), 400);
        }
    }

    public function Me(Request $request)
    {
        $token = $request->bearerToken();

    }
}
