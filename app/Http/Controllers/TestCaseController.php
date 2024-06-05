<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\TestCase;
use App\Models\User;
use Carbon\Carbon;

class TestCaseController extends Controller
{
    
    public function index()
    {
        //
    }

    public function create(Request $request)
    {
        $request->validate([
            'testcaseID' => 'required',
            'name' => 'required',
            'description' => 'required',
            'priority_id' => ['required', Rule::exists('priority', 'id')],
            'status_id' => ['required', Rule::exists('status', 'id')],
            'project_id' => ['required', Rule::exists('projects', 'projectID')],
            'userId' => 'required',
        ]);

        $testcase = TestCase::create([
            'testcaseID' => $request->testcaseID,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'project_id' => $request->project_id,
            'created_by' => $request->userId,
        ]);

        DB::table('max_number_of')
        ->where('projectID', $request->project_id)
        ->where('category', 'testcase')
        ->increment('maxNumberOf');

        return response()->json($testcase, 201);
    }

    public function show($projectID)
    {
        // Fetch the test cases with related priority and status
        $testcases = TestCase::with('priority', 'status')
            ->where('project_id', $projectID)
            ->get()
            ->map(function ($testcase) {
                // Fetch requirements related to the testcase
                $requirements = DB::table('testcase_requirement')
                    ->where('testcase_id', $testcase->testcaseID)
                    ->pluck('requirement_id');
                
                // Fetch test plans related to the testcase
                $testplans = DB::table('testplan_testcase')
                    ->where('testcase_id', $testcase->testcaseID)
                    ->pluck('testplan_id');

                // Fetch creator's and updater's names based on user ID
                $createdBy = User::where('id', $testcase->created_by)->value('name');
                $updatedBy = User::where('id', $testcase->updated_by)->value('name');

                return [
                    'id' => $testcase->id,
                    'testcaseID' => $testcase->testcaseID,
                    'name' => $testcase->name,
                    'description' => $testcase->description,
                    'priority_id' => $testcase->priority_id,
                    'priority_name' => $testcase->priority_name,
                    'status_id' => $testcase->status_id,
                    'status_name' => $testcase->status_name,
                    'project_id' => $testcase->project_id,
                    'created_by' => $createdBy,
                    'updated_by' => $updatedBy,
                    'created_at' => Carbon::parse($testcase->created_at)->format('Y-m-d H:i:s'),
                    'updated_at' => Carbon::parse($testcase->updated_at)->format('Y-m-d H:i:s'),
                    'requirements' => $requirements,
                    'testplans' => $testplans,
                ];
            });

        $maxTCNumber = DB::table('max_number_of')
        ->where('projectID', $projectID)
        ->where('category', 'testcase')
        ->value('maxNumberOf');

        return response()->json([
            'testcases' => $testcases,
            'maxTestCaseNumber' => $maxTCNumber
        ], 200);
    }

    public function update(Request $request, $testcaseID)
    {
        $request->validate([
            'testcaseID' => 'required',
            'name' => 'required',
            'description' => 'required',
            'priority_id' => ['required', Rule::exists('priority', 'id')],
            'status_id' => ['required', Rule::exists('status', 'id')],
            'project_id' => ['required', Rule::exists('projects', 'projectID')],
            'userId' => 'required',
        ]);

        $testcase = TestCase::where('testcaseID', $testcaseID)->firstOrFail();

        $testcase->update([
            'testcaseID' => $request->testcaseID,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'project_id' => $request->project_id,
            'updated_by' => $request->userId,
        ]);

        return response()->json($testcase, 200);
    }

    public function destroy($testcaseID)
    {
        // Find the requirement by requirementID
        $testcase = TestCase::where('testcaseID', $testcaseID)->firstOrFail();

        // Store the project ID for updating max_number_of
        $projectID = $testcase->project_id;

        // Delete all rows in testcase_requirement with the requirementID
        DB::table('testcase_requirement')->where('testcase_id', $testcaseID)->delete();

        // Delete the requirement
        $testcase->delete();

        return response()->json(['message' => 'Test case and associated test case requirements deleted successfully'], 200);
    }

    public function checkTestCaseIDExists($testcaseID)
    {
        $testcase = TestCase::where('testcaseID', $testcaseID)->first();

        return response()->json(['exists' => !!$testcase]);
    }

    public function relateOrUnrelateRequirements($testcaseID, Request $request)
    {
        $testcase = TestCase::where('testcaseID', $testcaseID)->firstOrFail();

        $requirementIDs = $request->input('requirement_ids');

        // Get the currently related requirement IDs
        $currentRelatedIDs = DB::table('testcase_requirement')
            ->where('testcase_id', $testcaseID)
            ->pluck('requirement_id')
            ->toArray();

        // Determine IDs to be unlinked
        $unrelatedIDs = array_diff($currentRelatedIDs, $requirementIDs);

        // Determine IDs to be linked
        $newlyRelatedIDs = array_diff($requirementIDs, $currentRelatedIDs);

        // Unlink requirements
        foreach ($unrelatedIDs as $requirementID) {
            DB::table('testcase_requirement')
                ->where('testcase_id', $testcaseID)
                ->where('requirement_id', $requirementID)
                ->delete();
        }

        // Link new requirements
        foreach ($newlyRelatedIDs as $requirementID) {
            DB::table('testcase_requirement')->insert([
                'testcase_id' => strtoupper($testcaseID),
                'requirement_id' => strtoupper($requirementID),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $message = '';
        if (!empty($unrelatedIDs)) {
            $message .= "Requirements " . implode(', ', $unrelatedIDs) . " unrelated from the test case successfully. ";
        }

        if (!empty($newlyRelatedIDs)) {
            $message .= "Test case related to requirements " . implode(', ', $newlyRelatedIDs) . " successfully.";
        }

        return response()->json(['message' => $message], 200);
    }

    public function showTestCaseID($projectID)
    {
        $testcaseIDs = TestCase::where('project_id', $projectID)
            ->pluck('testcaseID');

        return response()->json(['testcaseIDs' => $testcaseIDs], 200);
    }

    // public function unrelateRequirements($testcaseID, Request $request)
    // {
    //     $testcase = TestCase::where('testcaseID', $testcaseID)->firstOrFail();

    //     $requirementIDs = $request->input('requirement_ids');

    //     $unrelatedIDs = [];
    //     foreach ($requirementIDs as $requirementID) {
    //         $existingRelation = DB::table('testcase_requirement')
    //             ->where('testcase_id', $testcaseID)
    //             ->where('requirement_id', $requirementID)
    //             ->delete();

    //         if ($existingRelation) {
    //             $unrelatedIDs[] = $requirementID;
    //         }
    //     }

    //     $message = '';
    //     if (!empty($unrelatedIDs)) {
    //         $message .= "Requirements " . implode(', ', $unrelatedIDs) . " unrelated from the test case successfully. ";
    //     }

    //     $remainingIDs = array_diff($requirementIDs, $unrelatedIDs);
    //     if (!empty($remainingIDs)) {
    //         $message .= "Requirements " . implode(', ', $remainingIDs) . " are not related to the test case.";
    //     }

    //     return response()->json(['message' => $message], 200);
    // }

    // public function getRelatedRequirements($testcaseID)
    // {
    //     $requirements = DB::table('testcase_requirement')
    //         ->where('testcase_id', $testcaseID)
    //         ->pluck('requirement_id');

    //     return response()->json(['requirements' => $requirements], 200);
    // }
}
