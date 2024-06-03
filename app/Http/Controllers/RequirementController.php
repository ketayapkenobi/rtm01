<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Requirement;
use App\Models\TestCase;

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
        // Fetch all requirements for the given project ID
        $requirements = Requirement::with('priority', 'status')
            ->where('project_id', $projectID)
            ->get()
            ->map(function ($requirement) {
                // Fetch related test cases for each requirement
                $testCases = DB::table('testcase_requirement')
                    ->where('requirement_id', $requirement->requirementID)
                    ->pluck('testcase_id');

                // Fetch all related test plan IDs for the fetched test cases
                $testPlans = DB::table('testplan_testcase')
                    ->whereIn('testcase_id', $testCases)
                    ->pluck('testplan_id')
                    ->unique()
                    ->values()
                    ->all();

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
                    'testCases' => $testCases->all(), // Include related test case IDs
                    'testPlans' => $testPlans, // Include related test plan IDs
                ];
            });

        // Extract the maximum "R" number from the requirement IDs
        $maxRNumber = Requirement::where('project_id', $projectID)
            ->get()
            ->map(function ($requirement) {
                // Use regular expression to extract the "R" number
                if (preg_match('/R(\d+)$/', $requirement->requirementID, $matches)) {
                    return (int) $matches[1];
                }
                return 0;
            })
            ->max();

        return response()->json([
            'requirements' => $requirements,
            'maxRequirementNumber' => $maxRNumber,
        ], 200);
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

    public function showRequirementID($projectID)
    {
        $requirementIDs = Requirement::where('project_id', $projectID)
            ->pluck('requirementID');

        return response()->json(['requirementID' => $requirementIDs], 200);
    }

    public function checkRequirementIDExists($requirementID)
    {
        $requirement = Requirement::where('requirementID', $requirementID)->first();

        return response()->json(['exists' => !!$requirement]);
    }

    public function getRelatedTestCases($requirementID)
    {
        $testCases = DB::table('testcase_requirement')
            ->where('requirement_id', $requirementID)
            ->pluck('testcase_id');

        return response()->json(['testCases' => $testCases], 200);
    }
}
