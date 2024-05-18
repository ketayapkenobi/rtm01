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

    public function getRequirementTestcaseCoverage($projectID)
    {
        // Get all requirement IDs for the project
        $requirements = Requirement::where('project_id', $projectID)->get();

        // Get the count of associated test cases for each requirement
        $coverage = [];
        $totalRequirements = $requirements->count();
        $coveredRequirements = 0;
        foreach ($requirements as $requirement) {
            $testcases = DB::table('testcase_requirement')
                ->where('requirement_id', $requirement->requirementID)
                ->pluck('testcase_id')
                ->toArray();

            $testcaseCount = count($testcases);

            if ($testcaseCount > 0) {
                $coveredRequirements++;
            }

            $coverage[] = [
                'requirement_id' => $requirement->requirementID,
                'testcase_count' => $testcaseCount,
                'testcases' => $testcases,
            ];
        }

        // Calculate coverage percentage
        $coveragePercentage = $totalRequirements > 0 ? ($coveredRequirements / $totalRequirements) * 100 : 0;
        $notcoveredPercentage = 100 - $coveragePercentage;

        $coveragePercentageFormatted = number_format($coveragePercentage, 2);
        $notcoveredPercentageFormatted = number_format($notcoveredPercentage, 2);

        // Get the actual project ID from the projects table
        $project = DB::table('projects')->where('projectID', $projectID)->first();

        // If the project is not found, return an error message
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $actualProjectID = $project->id;

        // Get project members
        $projectMemberIDs = DB::table('project_members')
            ->where('projectID', $actualProjectID)
            ->pluck('userID')
            ->toArray();

        $projectMembers = DB::table('users')
            ->whereIn('id', $projectMemberIDs)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'project_id' => $projectID,
            'total_requirements' => $totalRequirements,
            'covered_requirements' => $coveredRequirements,
            'coverage_percentage' => (float) $coveragePercentageFormatted,
            'notcovered_percentage' => (float) $notcoveredPercentageFormatted,
            'coverage' => $coverage,
            'project_members' => $projectMembers,
        ]);
    }

    public function getTestcaseTestplanCoverage($projectID)
    {
        // Get all testcase IDs for the project
        $testcases = TestCase::where('project_id', $projectID)->get();

        // Get the count of associated test plans for each testcase
        $coverage = [];
        $totalTestcases = $testcases->count();
        $coveredTestcases = 0;
        foreach ($testcases as $testcase) {
            $testplans = DB::table('testplan_testcase')
                ->where('testcase_id', $testcase->testcaseID)
                ->pluck('testplan_id')
                ->toArray();

            $testplanCount = count($testplans);

            if ($testplanCount > 0) {
                $coveredTestcases++;
            }

            $coverage[] = [
                'testcase_id' => $testcase->testcaseID,
                'testplan_count' => $testplanCount,
                'testplans' => $testplans,
            ];
        }

        // Calculate coverage percentage
        $coveragePercentage = $totalTestcases > 0 ? ($coveredTestcases / $totalTestcases) * 100 : 0;
        $notcoveredPercentage = 100 - $coveragePercentage;

        $coveragePercentageFormatted = number_format($coveragePercentage, 2);
        $notcoveredPercentageFormatted = number_format($notcoveredPercentage, 2);

        // Get the actual project ID from the projects table
        $project = DB::table('projects')->where('projectID', $projectID)->first();

        // If the project is not found, return an error message
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $actualProjectID = $project->id;

        // Get project members
        $projectMemberIDs = DB::table('project_members')
            ->where('projectID', $actualProjectID)
            ->pluck('userID')
            ->toArray();

        $projectMembers = DB::table('users')
            ->whereIn('id', $projectMemberIDs)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'project_id' => $projectID,
            'total_testcases' => $totalTestcases,
            'covered_testcases' => $coveredTestcases,
            'coverage_percentage' => (float) $coveragePercentageFormatted,
            'notcovered_percentage' => (float) $notcoveredPercentageFormatted,
            'coverage' => $coverage,
            'project_members' => $projectMembers,
        ]);
    }

    public function getTestplanTestexecutionCoverage($projectID)
    {
        // Get all testplan IDs for the project
        $testplans = TestPlan::where('project_id', $projectID)->get();

        // Get the count of associated test executions for each testplan
        $coverage = [];
        $totalTestplans = $testplans->count();
        $coveredTestplans = 0;
        foreach ($testplans as $testplan) {
            $testexecutions = DB::table('test_executions')
                ->where('testplanID', $testplan->testplanID)
                ->pluck('testexecutionID')
                ->toArray();

            $testexecutionCount = count($testexecutions);

            if ($testexecutionCount > 0) {
                $coveredTestplans++;
            }

            $coverage[] = [
                'testplanID' => $testplan->testplanID,
                'testexecution_count' => $testexecutionCount,
                'testexecutions' => $testexecutions,
            ];
        }

        // Calculate coverage percentage
        $coveragePercentage = $totalTestplans > 0 ? ($coveredTestplans / $totalTestplans) * 100 : 0;
        $notcoveredPercentage = 100 - $coveragePercentage;

        $coveragePercentageFormatted = number_format($coveragePercentage, 2);
        $notcoveredPercentageFormatted = number_format($notcoveredPercentage, 2);

        // Get the actual project ID from the projects table
        $project = DB::table('projects')->where('projectID', $projectID)->first();

        // If the project is not found, return an error message
        if (!$project) {
            return response()->json(['message' => 'Project not found'], 404);
        }

        $actualProjectID = $project->id;

        // Get project members
        $projectMemberIDs = DB::table('project_members')
            ->where('projectID', $actualProjectID)
            ->pluck('userID')
            ->toArray();

        $projectMembers = DB::table('users')
            ->whereIn('id', $projectMemberIDs)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'project_id' => $projectID,
            'total_testplans' => $totalTestplans,
            'covered_testplans' => $coveredTestplans,
            'coverage_percentage' => (float) $coveragePercentageFormatted,
            'notcovered_percentage' => (float) $notcoveredPercentageFormatted,
            'coverage' => $coverage,
            'project_members' => $projectMembers,
        ]);
    }
}
