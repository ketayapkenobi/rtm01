<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
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
        $alreadyAssignedUserIds = [];

        foreach ($userIds as $userId) {
            $user = User::where('userID', $userId)->firstOrFail();

            // Check if the user is already assigned to the project
            if (!$project->members->contains($user)) {
                $project->members()->attach($user);
            } else {
                $alreadyAssignedUserIds[] = $userId;
            }
        }

        if (!empty($alreadyAssignedUserIds)) {
            return response()->json(['message' => 'Users with IDs ' . implode(', ', $alreadyAssignedUserIds) . ' are already assigned to this project'], 400);
        }

        return response()->json(['message' => 'Users assigned to project successfully'], 200);
    }

    public function getProjectMembers($id)
    {
        $project = Project::where('id', $id)->firstOrFail();
        $members = $project->members;

        return response()->json(['members' => $members], 200);
    }

}

