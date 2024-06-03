<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\User;

class ProjectMemberController extends Controller
{
    public function assignUser(Request $request)
    {
        $request->validate([
            'project_id' => 'required|string',
            'user_ids' => 'required|array',
        ]);

        $project = Project::where('projectID', $request->project_id)->firstOrFail();
        $userIds = $request->user_ids;

        try {
            // Begin a transaction
            DB::beginTransaction();

            // Delete all existing project members
            $project->members()->detach();

            // Attach new project members
            foreach ($userIds as $userId) {
                $user = User::where('userID', $userId)->firstOrFail();
                $project->members()->attach($user);
            }

            // Commit the transaction
            DB::commit();

            return response()->json(['message' => 'Users assigned to project successfully'], 200);
        } catch (\Exception $e) {
            // Rollback the transaction in case of error
            DB::rollback();
            
            return response()->json(['message' => 'Failed to assign users to project'], 500);
        }
    }


    public function getProjectMembers($id)
    {
        $project = Project::where('id', $id)->firstOrFail();
        $members = $project->members;

        return response()->json(['members' => $members], 200);
    }

}

