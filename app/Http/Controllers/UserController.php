<?php

namespace App\Http\Controllers;

Use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Traits\HasRoles;
use Symfony\Component\HttpFoundation\Response;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $sql = User::query();

        if ($request->roles) {
            $roles_param = explode(",", $request->roles);

            $sql->whereHas('roles', function ($q) use ($roles_param) {
                $q->whereIn('id', $roles_param);
            });
        }

        if (isset($request->keyword)) {
            $sql->where('name', 'like', '%' . $request->keyword . '%')
                ->orWhere('staff_no', 'like', '%' . $request->keyword . '%');
        }

        if (isset($request->size))
            $users = $sql->paginate($request->size);
        else
            $users = $sql->get();

        // Transform user data to include role name
        $users = $users->map(function ($user) {
            $user->role_name = $user->role ? $user->role->name : null;
            unset($user->role); // Remove the 'role' attribute from the user object
            return $user;
        });

        return response()->json([
            'users' => $users
        ], 200);
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
                'role_id'  => 'required',
                'userID'   => 'required',
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
                'role_id'  => $request->role_id,
                'userID'   => $request->userID,
            ];

            $user = User::create($data);

            return response()->json($user, 200);
        } catch (\Throwable $th) {
            Log::error('Create user error: ' . $th->getMessage());
            return response()->json(['message' => 'Create user failed', 'error' => $th->getMessage()], 500);
        }
    }


    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $user = User::find($id);

        if (!$user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        // Get the user's roles
        $userRoles = $user->roles->pluck('name')->toArray();

        // Get the project IDs the user belongs to
        $projectIDs = DB::table('project_members')
                        ->where('userID', $id)
                        ->pluck('projectID')
                        ->toArray();

        // Get the names of the projects
        $projects = DB::table('projects')
                        ->whereIn('id', $projectIDs)
                        ->pluck('projectName')
                        ->toArray();

        // Add the projects to the user data
        $user->projects = $projects;

        // Return the user data
        return response()->json($user, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    /**
 * Update the specified resource in storage.
 */
    /**
 * Update the specified resource in storage.
 */
    public function update(Request $request, string $id)
    {
        try {
            $user = User::find($id);

            if (!$user) {
                return response()->json(['message' => 'User not found'], 404);
            }

            $validateUser = Validator::make($request->all(), [
                'email'             => 'required|email',
                'current_password'  => 'required_with:password',
                'password'          => 'nullable',
                'name'              => 'required',
                'role_id'           => 'required',
                'userID'            => 'required',
            ]);

            if ($validateUser->fails()) {
                return response()->json([
                    'message' => 'Validation error',
                    'errors'  => $validateUser->errors()
                ], 422);
            }

            // Verify current password if a new password is provided
            if ($request->has('password')) {
                if (!Hash::check($request->current_password, $user->password)) {
                    return response()->json(['message' => 'Current password is incorrect'], 400);
                }
                $user->password = Hash::make($request->password);
            }

            // Update user attributes
            $user->email = $request->email;
            $user->name = $request->name;
            $user->role_id = $request->role_id;
            $user->userID = $request->userID;
            
            $user->save();

            return response()->json(['message' => 'User updated successfully', 'user' => $user], 200);
        } catch (\Throwable $th) {
            Log::error('Update user error: ' . $th->getMessage());
            return response()->json(['message' => 'Update user failed', 'error' => $th->getMessage()], 500);
        }
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            // Find the user by ID
            $user = User::findOrFail($id);
    
            // Delete the user
            $user->delete();
    
            // Return a success response
            return response()->json(['message' => 'User deleted successfully'], 200);
        } catch (\Exception $e) {
            // Return an error response if user deletion fails
            return response()->json(['message' => 'Failed to delete user'], 500);
        }
    }

    public function checkUserIDExists(string $userID)
    {
        $user = User::where('userID', $userID)->first();

        return response()->json(['exists' => !!$user]);
    }

    public function checkEmailExists(string $email)
    {
        $user = User::where('email', $email)->first();

        return response()->json(['exists' => !!$user]);
    }
}
