<?php

namespace App\Http\Controllers;

use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use PhpParser\Comment;

class AuthController extends Controller
{
    public function register(Request $request)
    {
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => $request->password,
        ]);

        $token = auth()->login($user);

        return $this->respondWithToken($token);
    }

    public function login()
    {
        $credentials = request(['email', 'password']);

        if (!$token = auth()->attempt($credentials)) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        return $this->respondWithToken($token);
    }

    public function logout()
    {
        auth()->logout();

        return response()->json(['message' => 'Successfully logged out']);
    }

    protected function respondWithToken($token)
    {
        return response()->json([
            'success' => true,
            'access_token' => $token,
            'token_type' => 'bearer',
            'expires_in' => auth()->factory()->getTTL() * 60
        ]);
    }

//  In case only pagination required

    /**
     * @param Request $req
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $req)
    {
        {
            try {
                if ($req->has('num')) {
                    $num = $req->input('num');
                    $items = User::paginate($num);
                } else {
                    $items = User::all();
                }
                if ($items) {
                    return response()->json([
                        'data' => $items
                    ], 200);
                }
                return response()->json([
                    'item' => "empty"
                ], 404);
            } catch (\Exception $e) {
                return response()->json([
                    'message' => $e
                ], 500);
            }
        }
    }
// Incase pagination with filter required

    /**
     * @param Request $request
     * @param User $user
     * @return mixed
     */
    public function filter(Request $request, User $user)
    {
        if ($request->has('num')) {
            $num = $request->input('num');
            // Search for a user based on their id.
            if ($request->has('id')) {
                return $user->where('id', $request->input('id'))
                    ->paginate($num);
            }
            // Search for a user based on their name.
            if ($request->has('name')) {
                return $user->where('name', $request->input('name'))->paginate($num);
            }

            // Search for a user based on their email.
            if ($request->has('email')) {
                return $user->where('email', $request->input('email'))->paginate($num);
            }

        }
        // Continue for all of the filters.

        // No filters have been provided, so
        // let's return all users. This is
        // bad - we should paginate in
        // reality.
        return User::paginate(10);
    }
    // Count how many usere registered

    /**
     * @param Request $request
     * @return int
     */
    public function usercount(Request $request){
        if ($request->has('due')) {
            $due = $request->input('due');
        $day = Carbon::now()->subDay(0);
        $duedate = Carbon::now()->subDay($due);
        $users = DB::table('users')->whereDate('created_at', '<=', $day)
            ->whereDate('created_at', '>=', $duedate)
            ->count();
        return $users;}
        return User::count();
    }
}
