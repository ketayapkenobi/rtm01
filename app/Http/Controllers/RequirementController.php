<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\Requirement;
use App\Models\TestCase;
use App\Models\User;
use Carbon\Carbon;

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
            'userId' => 'required',
        ]);

        $requirement = Requirement::create([
            'requirementID' => $request->requirementID,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'project_id' => $request->project_id,
            'created_by' => $request->userId,
        ]);

        // Increment maxNumberOf for category 'requirement'
        DB::table('max_number_of')
            ->where('projectID', $request->project_id)
            ->where('category', 'requirement')
            ->increment('maxNumberOf');

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

                // Fetch creator's and updater's names based on user ID
                $createdBy = User::where('id', $requirement->created_by)->value('name');
                $updatedBy = User::where('id', $requirement->updated_by)->value('name');

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
                    'created_by' => $createdBy,
                    'updated_by' => $updatedBy,
                    'created_at' => Carbon::parse($requirement->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($requirement->updated_at)->format('Y-m-d H:i:s'),
                    'testCases' => $testCases->all(), // Include related test case IDs
                    'testPlans' => $testPlans, // Include related test plan IDs
                ];
            });

        $maxRNumber = DB::table('max_number_of')
            ->where('projectID', $projectID)
            ->where('category', 'requirement')
            ->value('maxNumberOf');

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
            'userId' => 'required',
        ]);

        $requirement = Requirement::where('requirementID', $requirementID)->firstOrFail();

        $requirement->update([
            'requirementID' => $request->requirementID,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'project_id' => $request->project_id,
            'updated_by' => $request->userId,
        ]);

        return response()->json($requirement, 200);
    }


    public function destroy($requirementID)
    {
        // Find the requirement by requirementID
        $requirement = Requirement::where('requirementID', $requirementID)->firstOrFail();

        // Store the project ID for updating max_number_of
        $projectID = $requirement->project_id;

        // Delete all rows in testcase_requirement with the requirementID
        DB::table('testcase_requirement')->where('requirement_id', $requirementID)->delete();

        // Delete the requirement
        $requirement->delete();

        return response()->json(['message' => 'Requirement and associated test case requirements deleted successfully'], 200);
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
