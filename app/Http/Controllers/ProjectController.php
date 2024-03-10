<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use App\Models\Project;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::all();
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
        ]);

        // Check if projectID already exists
        $existingProject = Project::where('projectID', $request->projectID)->first();
        if ($existingProject) {
            return response()->json(['error' => 'Project ID already exists'], 400);
        }

        $project = Project::create($request->all());

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


    // public function assignUserToProject(Request $request)
    // {
    //     // Validate request
    //     $validator = Validator::make($request->all(), [
    //         'projectID' => 'required|exists:projects,id',
    //         'userID'    => 'required|exists:users,id',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json(['message' => 'Validation error', 'errors' => $validator->errors()], 422);
    //     }

    //     // Check if the user is already assigned to the project
    //     $existingAssignment = ProjectMember::where('project_id', $request->projectID)
    //         ->where('userID', $request->userID)
    //         ->exists();

    //     if ($existingAssignment) {
    //         return response()->json(['message' => 'User already assigned to the project.'], 422);
    //     }

    //     // Assign the user to the project
    //     $assignment = ProjectMember::create([
    //         'projectID' => $request->projectID,
    //         'userID'    => $request->userID,
    //     ]);

    //     return response()->json(['message' => 'User assigned to the project successfully.', 'assignment' => $assignment], 201);
    // }
}
