<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\TestExecution;
use App\Models\TestResult;

class TestExecutionController extends Controller
{

    public function index($projectID)
    {
        $testExecutions = TestExecution::where('testexecutionID', 'like', $projectID . '-TE%')->get(['id', 'testexecutionID']);

        return response()->json(['testExecutions' => $testExecutions], 200);
    }

    public function create()
    {
        //
    }

    public function show($projectID)
    {
        $testExecutions = TestExecution::where('testexecutionID', 'like', $projectID . '-TE%')->get();

        // Array to store the test execution IDs and their related step IDs
        $result = [];

        foreach ($testExecutions as $testExecution) {
            $testexecutionID = $testExecution->testexecutionID;
            $testcases = DB::table('test_results')
                ->join('steps', 'test_results.step_id', '=', 'steps.id')
                ->where('test_results.testexecution_id', $testexecutionID)
                ->orderBy('steps.testcase_id')
                ->select('steps.testcase_id', 'steps.id as step_id', 'steps.action', 'steps.input', 'steps.expected_result', 'test_results.actual_result', 'test_results.checked_by', 'test_results.result_id')
                ->get();

            $formattedTestcases = [];
            foreach ($testcases as $testcase) {
                $testcaseID = $testcase->testcase_id;
                $stepID = $testcase->step_id;

                if (!isset($formattedTestcases[$testcaseID])) {
                    $formattedTestcases[$testcaseID] = ['steps' => []];
                }

                // Retrieve the user name from the users table based on the checked_by userID
                $checkedByUser = DB::table('users')->where('userID', $testcase->checked_by)->value('name');

                $formattedTestcases[$testcaseID]['steps'][] = [
                    'step_id' => $stepID,
                    'action' => $testcase->action,
                    'input' => $testcase->input,
                    'expected_result' => $testcase->expected_result,
                    'actual_result' => $testcase->actual_result,
                    'checked_by' => [
                        'userID' => $testcase->checked_by,
                        'userName' => $checkedByUser, // Include the user name for checked_by
                    ],
                    'result_id' => $testcase->result_id, // Include the result_id for each step
                ];
            }

            // Get the testplanID from the test_execution
            $testplanID = DB::table('test_executions')->where('testexecutionID', $testexecutionID)->value('testplanID');

            // Get the result_id and number_of_execution from the test_execution
            $resultId = DB::table('test_executions')->where('testexecutionID', $testexecutionID)->value('result_id');
            $numberOfExecution = DB::table('test_executions')->where('testexecutionID', $testexecutionID)->value('number_of_execution');

            // Add the test execution ID, testplanID, result_id, number_of_execution, and its related testcase_ids with steps to the result array
            $result[] = [
                'id' => $testExecution->id,
                'testexecutionID' => $testexecutionID,
                'testplanID' => $testplanID,
                'result_id' => $resultId,
                'number_of_execution' => $numberOfExecution,
                'testcase_id' => $formattedTestcases,
            ];
        }

        return response()->json(['testExecutions' => $result], 200);
    }


    public function update(Request $request, string $testexecution_id, string $step_id)
    {
        $request->validate([
            'actual_result' => 'string',
            'checked_by' => 'string',
            'result_id' => 'integer',
        ]);

        // Assuming 'test_results' is the table name
        $testResult = DB::table('test_results')->where('testexecution_id', $testexecution_id)->where('step_id', $step_id)->first();

        if (!$testResult) {
            return response()->json(['message' => 'Test result not found'], 404);
        }

        // Update the actual_result, checked_by, and result_id fields
        DB::table('test_results')
            ->where('testexecution_id', $testexecution_id)
            ->where('step_id', $step_id)
            ->update([
                'actual_result' => $request->input('actual_result'),
                'checked_by' => $request->input('checked_by'),
                'result_id' => $request->input('result_id'),
            ]);

        return response()->json(['message' => 'Test result updated successfully'], 200);
    }

    public function destroy(string $id)
    {
        //
    }

    public function getProgress($testexecutionID)
    {
        $totalSteps = DB::table('test_results')
            ->where('testexecution_id', $testexecutionID)
            ->count();

        $results = DB::table('test_results')
            ->select('result_id', DB::raw('COUNT(*) as count'))
            ->where('testexecution_id', $testexecutionID)
            ->groupBy('result_id')
            ->orderBy('result_id', 'desc') // Sort by result_id in ascending order
            ->get();

        $totalPercentage = 0; // Total percentage for all results except result_id = 1

        $progress = [];

        foreach ($results as $result) {
            $percentage = $totalSteps > 0 ? ($result->count / $totalSteps) * 100 : 0;
            if ($result->result_id != 1 && $result->result_id != 2) {
                $totalPercentage += $percentage;
            }
        
            $progress[] = [
                'result_id' => $result->result_id,
                'percentage' => $percentage,
            ];
        }        

        return response()->json(['progress' => $progress, 'total_percentage' => $totalPercentage], 200);
    }
}
