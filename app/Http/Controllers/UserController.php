<?php

namespace App\Http\Controllers;

Use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        try {
            $validateUser = Validator::make($request->all(), [
                'email'    => 'required|email',
                'password' => 'required',
                'name'     => 'required',
            ]);

            if ($validateUser->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors'  => $validateUser->errors()
                ], 422);
            }

            $data = [
                'email'    => $request->email,
                'password' => Hash::make($request->password),
                'name'     => $request->name,
            ];

            $user = User::create($data);

            return response()->json($user, 200);
        } catch (\Throwable $th) {
            Log::error('Create user error: ' . $th->getMessage());
            return response()->json(['message' => 'Create user failed', 'error' => $th->getMessage()], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
