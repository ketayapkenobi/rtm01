<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requirement;
use Illuminate\Validation\Rule;

class RequirementController extends Controller
{
    public function index($projectId)
    {
        $requirements = Requirement::where('project_id', $projectId)->get();
        return response()->json(['requirements' => $requirements]);
    }

    public function create(Request $request)
    {
        $request->validate([
            'requirementID' => 'required',
            'name' => 'required',
            'description' => 'required',
            'priority_id' => ['required', Rule::exists('priority', 'id')],
            'status_id' => ['required', Rule::exists('status', 'id')],
            'project_id' => ['required', Rule::exists('projects', 'projectID')],
        ]);

        $requirement = Requirement::create([
            'requirementID' => $request->requirementID,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'project_id' => $request->project_id,
        ]);

        return response()->json($requirement, 201);
    }

    public function show($projectID)
    {
        $requirements = Requirement::with('priority', 'status')
            ->where('project_id', $projectID)
            ->get()
            ->map(function ($requirement) {
                return [
                    'id' => $requirement->id,
                    'requirementID' => $requirement->requirementID,
                    'name' => $requirement->name,
                    'description' => $requirement->description,
                    'priority_id' => $requirement->priority_id,
                    'priority_name' => $requirement->priority_name,
                    'status_id' => $requirement->status_id,
                    'status_name' => $requirement->status_name,
                    'project_id' => $requirement->project_id,
                    'created_at' => $requirement->created_at,
                    'updated_at' => $requirement->updated_at,
                ];
            });

        return response()->json(['requirements' => $requirements], 200);
    }

    public function update(Request $request, $requirementID)
    {
        $request->validate([
            'requirementID' => 'required',
            'name' => 'required',
            'description' => 'required',
            'priority_id' => ['required', Rule::exists('priority', 'id')],
            'status_id' => ['required', Rule::exists('status', 'id')],
            'project_id' => ['required', Rule::exists('projects', 'projectID')],
        ]);

        $requirement = Requirement::where('requirementID', $requirementID)->firstOrFail();

        $requirement->update([
            'requirementID' => $request->requirementID,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'project_id' => $request->project_id,
        ]);

        return response()->json($requirement, 200);
    }

    public function destroy(string $id)
    {
        //
    }

    public function checkRequirementIDExists($requirementID)
    {
        $requirement = Requirement::where('requirementID', $requirementID)->first();

        return response()->json(['exists' => !!$requirement]);
    }
}
