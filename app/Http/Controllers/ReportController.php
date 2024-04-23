<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requirement;
use App\Models\TestCase;
use App\Models\TestPlan;
use App\Models\TestExecution;

// use PDF;

class ReportController extends Controller
{
    public function generateProjectRequirementsReport($projectID)
    {
        // Fetch requirements for the given project
        $requirements = Requirement::where('project_id', $projectID)->get();

        // Return requirements as JSON
        return response()->json([
            'project_id' => $projectID,
            'requirements' => $requirements,
        ]);
    }

    public function generateProjectTestCasesReport($projectID)
    {
        $testcases = TestCase::where('project_id', $projectID)->get();

        return response()->json([
            'project_id' => $projectID,
            'testcases' => $testcases,
        ]);
    }

    public function generateProjectTestPlansReport($projectID)
    {
        $testplans = TestPlan::where('project_id', $projectID)->get();

        return response()->json([
            'project_id' => $projectID,
            'testplans' => $testplans,
        ]);
    }

    public function generateProjectTestExecutionsReport($projectID)
    {
        $testexecutions = TestExecution::where('project_id', $projectID)->get();

        return response()->json([
            'project_id' => $projectID,
            'testexecutions' => $testexecutions,
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
