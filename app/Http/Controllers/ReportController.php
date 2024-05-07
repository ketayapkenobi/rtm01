<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requirement;
use App\Models\TestCase;
use App\Models\TestPlan;
use App\Models\TestExecution;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\TestExecutionController;

// use PDF;

class ReportController extends Controller
{
    public function generateProjectRequirementsReport($projectID)
    {
        // Fetch requirements for the given project
        $requirements = Requirement::where('project_id', $projectID)->get();

        // Iterate over requirements to find related test cases
        foreach ($requirements as $requirement) {
            $testcaseIds = DB::table('testcase_requirement')
                ->where('requirement_id', $requirement->requirementID)
                ->pluck('testcase_id')
                ->toArray();

            // Fill the test_cases array for each requirement
            $requirement->test_cases = $testcaseIds;
        }

        // Return requirements with related test cases as JSON
        return response()->json([
            'project_id' => $projectID,
            'requirements' => $requirements,
        ]);
    }

    public function generateProjectTestCasesReport($projectID)
    {
        $testcases = TestCase::where('project_id', $projectID)->get();

        // Iterate over test cases to find related requirements
        foreach ($testcases as $testcase) {
            $requirementIds = DB::table('testcase_requirement')
                ->where('testcase_id', $testcase->testcaseID)
                ->pluck('requirement_id')
                ->toArray();

            // Fill the requirements array for each test case
            $testcase->requirements = $requirementIds;
        }

        return response()->json([
            'project_id' => $projectID,
            'testcases' => $testcases,
        ]);
    }

    public function generateProjectTestPlansReport($projectID)
    {
        $testplans = TestPlan::where('project_id', $projectID)->get();

        // Iterate over test plans to find related test cases
        foreach ($testplans as $testplan) {
            $testcaseIds = DB::table('testplan_testcase')
                ->where('testplan_id', $testplan->testplanID)
                ->pluck('testcase_id')
                ->toArray();

            // Fill the test_cases array for each test plan
            $testplan->test_cases = $testcaseIds;
        }

        return response()->json([
            'project_id' => $projectID,
            'testplans' => $testplans,
        ]);
    }

    public function generateProjectTestExecutionsReport($projectID)
    {
        $testexecutions = TestExecution::where('testexecutionID', 'like', '%' . $projectID . '%')->get();

        $reportData = [];
        foreach ($testexecutions as $testexecution) {
            $totalSteps = DB::table('test_results')
                ->where('testexecution_id', $testexecution->testexecutionID)
                ->count();

            $results = DB::table('test_results')
                ->select('result_id', DB::raw('COUNT(*) as count'))
                ->where('testexecution_id', $testexecution->testexecutionID)
                ->groupBy('result_id')
                ->orderBy('result_id', 'desc') // Sort by result_id in ascending order
                ->get();

            $totalPercentage = 0; // Total percentage for all results except result_id = 1

            foreach ($results as $result) {
                $percentage = $totalSteps > 0 ? ($result->count / $totalSteps) * 100 : 0;
                if ($result->result_id != 1 && $result->result_id != 2) {
                    $totalPercentage += $percentage;
                }
            }

            // Format total_percentage to have only two decimal places
            $totalPercentage = number_format($totalPercentage, 2);

            $reportData[] = [
                'testexecutionID' => $testexecution->testexecutionID,
                'data' => $testexecution,
                'total_percentage' => $totalPercentage,
            ];
        }

        return response()->json([
            'project_id' => $projectID,
            'testexecutions' => $reportData,
        ]);
    }

    public function getRequirementTestcaseMatrix($projectId)
    {
        $requirements = Requirement::where('project_id', $projectId)->get();
        $testcases = Testcase::where('project_id', $projectId)->get();

        $matrix = [];
        foreach ($requirements as $requirement) {
            $matrixRow = [];
            foreach ($testcases as $testcase) {
                if ($testcase->requirements->contains($requirement->requirementID)) {
                    $matrixRow[] = 1; // Requirement is linked to test case
                } else {
                    $matrixRow[] = 0; // Requirement is not linked to test case
                }
            }
            $matrix[] = $matrixRow;
        }

        return response()->json(['matrix' => $matrix]);
    }


}
