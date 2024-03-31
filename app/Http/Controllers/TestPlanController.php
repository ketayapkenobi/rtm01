<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;
use App\Models\TestPlan;
use App\Models\TestCase;

class TestPlanController extends Controller
{
    public function index()
    {
        //
    }

    public function create(Request $request)
    {
        $request->validate([
            'testplanID' => 'required',
            'name' => 'required',
            'description' => 'required',
            'priority_id' => ['required', Rule::exists('priority', 'id')],
            'status_id' => ['required', Rule::exists('status', 'id')],
            'project_id' => ['required', Rule::exists('projects', 'projectID')],
        ]);

        $testPlan = TestPlan::create([
            'testplanID' => $request->testplanID,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'project_id' => $request->project_id,
        ]);

        return response()->json($testPlan, 201);
    }


    public function show($projectID)
    {
        $testPlans = TestPlan::with('priority', 'status')
            ->where('project_id', $projectID)
            ->get()
            ->map(function ($testPlan) {
                return [
                    'id' => $testPlan->id,
                    'testplanID' => $testPlan->testplanID,
                    'name' => $testPlan->name,
                    'description' => $testPlan->description,
                    'priority_id' => $testPlan->priority_id,
                    'priority_name' => $testPlan->priority->name,
                    'status_id' => $testPlan->status_id,
                    'status_name' => $testPlan->status->name,
                    'project_id' => $testPlan->project_id,
                    'created_at' => $testPlan->created_at,
                    'updated_at' => $testPlan->updated_at,
                ];
            });

        return response()->json(['testPlans' => $testPlans], 200);
    }

    public function update(Request $request, string $id)
    {
        //
    }

    public function destroy($testplanID)
    {
        DB::table('test_plans')->where('testplanID', $testplanID)->delete();

        DB::table('testplan_testcase')->where('testplan_id', $testplanID)->delete();

        return response()->json(['message' => 'Test plan deleted successfully'], 200);
    }


    public function getLatestTestPlanNumber($projectID)
    {
        $latestTestPlan = TestPlan::where('project_id', $projectID)
            ->orderBy('testplanID', 'desc')
            ->first();

        if ($latestTestPlan) {
            $testPlanParts = explode('-', $latestTestPlan->testplanID);
            $latestTestPlanNumber = (int) substr($testPlanParts[1], 2); // Extract the digits after 'TP' and convert to int
            return $latestTestPlanNumber;
        }

        return null; // Return null if no test plan is found for the project
    }

    public function relateOrUnrelateTestCases($testplanID, Request $request)
    {
        $testplan = TestPlan::where('testplanID', $testplanID)->firstOrFail();

        $testCaseIDs = $request->input('testcase_ids');

        // Get the currently related test case IDs
        $currentRelatedIDs = DB::table('testplan_testcase')
            ->where('testplan_id', $testplanID)
            ->pluck('testcase_id')
            ->toArray();

        // Determine IDs to be unlinked
        $unrelatedIDs = array_diff($currentRelatedIDs, $testCaseIDs);

        // Determine IDs to be linked
        $newlyRelatedIDs = array_diff($testCaseIDs, $currentRelatedIDs);

        // Unlink test cases
        foreach ($unrelatedIDs as $testCaseID) {
            DB::table('testplan_testcase')
                ->where('testplan_id', $testplanID)
                ->where('testcase_id', $testCaseID)
                ->delete();
        }

        // Link new test cases
        foreach ($newlyRelatedIDs as $testCaseID) {
            DB::table('testplan_testcase')->insert([
                'testplan_id' => strtoupper($testplanID),
                'testcase_id' => strtoupper($testCaseID),
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $message = '';
        if (!empty($unrelatedIDs)) {
            $message .= "Test cases " . implode(', ', $unrelatedIDs) . " unlinked from the test plan successfully. ";
        }

        if (!empty($newlyRelatedIDs)) {
            $message .= "Test plan linked to test cases " . implode(', ', $newlyRelatedIDs) . " successfully.";
        }

        return response()->json(['message' => $message], 200);
    }

    public function getRelatedTestCases($testplanID)
    {
        $relatedTestCases = DB::table('testplan_testcase')
            ->where('testplan_id', $testplanID)
            ->pluck('testcase_id')
            ->toArray();

        return response()->json(['relatedTestCases' => $relatedTestCases], 200);
    }


}
