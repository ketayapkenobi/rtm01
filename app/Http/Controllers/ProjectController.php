<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\User;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::all();
        return response()->json($projects);
    }

    public function getProjectsByUserId($userId)
    {
        // Find the id of the user in the users table
        $user = DB::table('users')
                    ->where('userID', $userId)
                    ->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        // Get all the projectIDs related to the userID in the project_members table
        $projectIds = DB::table('project_members')
                        ->where('userID', $user->id)
                        ->pluck('projectID');

        // Get the projects based on the projectIDs
        $projects = Project::whereIn('id', $projectIds)->get();

        return response()->json($projects);
    }

    public function show($id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json(['message' => 'project not found'], 404);
        }
        return response()->json($project);
    }

    public function create(Request $request)
    {
        $this->validate($request, [
            'projectID' => 'required',
            'projectName' => 'required',
            'projectDesc' => 'required',
            'selectedUsers' => 'required|array', // Add validation for selectedUsers
        ]);

        // Check if projectID already exists
        $existingProject = Project::where('projectID', $request->projectID)->first();
        if ($existingProject) {
            return response()->json(['error' => 'Project ID already exists'], 400);
        }

        $project = Project::create($request->all());

        // Assign selected users to the project
        $project->members()->attach($request->selectedUsers);

        return response()->json($project, 201);
    }

    public function update($id, Request $request)
    {
        $project = Project::find($id);
        
        if(!$project) {
            return response()->json([
                'message' => 'project not found'
            ], 404);
        }

        $validateProject = Validator::make($request->all(), [
            'projectName' => 'required',
            'projectDesc' => 'required',
        ]);

        if ($validateProject->fails()) {
            return response()->json([
                'message' => 'validation error',
                'errors' => $validateProject->errors()
            ], 422);
        }

        $data = [
            'projectName' => $request->projectName,
            'projectDesc' => $request->projectDesc
        ];

        $project->update($data);

        return response()->json($project, 200);
    }

    public function destroy($id)
    {
        $delete = Project::find($id);

        if (!$delete) {
            return response()->json([
                'message' => 'project not found'
            ], 404);
        }

        $delete->delete();

        return response()->json([
            'message' => 'successfully deleted'
        ], 200);
    }

    public function checkProjectIdExists($projectId)
    {
        $project = Project::where('projectID', $projectId)->first();

        return response()->json(['exists' => !!$project]);
    }
}
