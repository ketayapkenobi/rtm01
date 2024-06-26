<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\User;
use App\Models\Requirement;
use App\Models\TestCase;
use App\Models\TestPlan;

class ProjectController extends Controller
{
    public function index()
    {
        $projects = Project::all();
        return response()->json($projects);
    }

    public function getProjectsByUserId($userId)
    {
        // Find the id of the user in the users table
        $user = DB::table('users')
                    ->where('userID', $userId)
                    ->first();

        if (!$user) {
            return response()->json(['error' => 'User not found'], 404);
        }

        if ($user->role_id == 1) {
            // If the role is 1, return all projects
            $projects = Project::all();
        } else {
            // Get all the projectIDs related to the userID in the project_members table
            $projectIds = DB::table('project_members')
                            ->where('userID', $user->id)
                            ->pluck('projectID');
    
            // Get the projects based on the projectIDs
            $projects = Project::whereIn('id', $projectIds)->get();
        }

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
            'selectedUsers' => 'required|array', // Add validation for selectedUsers
        ]);

        // Check if projectID already exists
        $existingProject = Project::where('projectID', $request->projectID)->first();
        if ($existingProject) {
            return response()->json(['error' => 'Project ID already exists'], 400);
        }

        $project = Project::create($request->all());

        // Assign selected users to the project
        $project->members()->attach($request->selectedUsers);

        // Insert rows into max_number_of table
        $data = [
            ['projectID' => $request->projectID, 'maxNumberOf' => 0, 'category' => 'requirement'],
            ['projectID' => $request->projectID, 'maxNumberOf' => 0, 'category' => 'testcase'],
            ['projectID' => $request->projectID, 'maxNumberOf' => 0, 'category' => 'testplan'],
        ];

        DB::table('max_number_of')->insert($data);

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

    // public function destroy($id)
    // {
    //     $project = Project::find($id);

    //     if (!$project) {
    //         return response()->json([
    //             'message' => 'Project not found'
    //         ], 404);
    //     }

    //     try {
    //         DB::beginTransaction();

    //         // Delete related requirements
    //         DB::table('requirements')->where('project_id', $project->projectID)->delete();

    //         // Delete related test cases
    //         DB::table('testcases')->where('project_id', $project->projectID)->delete();

    //         // Then delete the project
    //         $project->delete();

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Successfully deleted'
    //         ], 200);
    //     } catch (\Exception $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'message' => 'Failed to delete project, requirements, and test cases.'
    //         ], 500);
    //     }
    // }

    public function destroy($id)
    {
        $project = Project::find($id);

        if (!$project) {
            return response()->json([
                'message' => 'Project not found'
            ], 404);
        }

        // Find the IDs of requirements and test cases that would be deleted
        $deletedRequirements = DB::table('requirements')->where('project_id', $project->projectID)->pluck('requirementID');
        $deletedTestCases = DB::table('test_cases')->where('project_id', $project->projectID)->pluck('testcaseID');
        $deletedTestPlans = DB::table('test_plans')->where('project_id', $project->projectID)->pluck('testplanID');

        // Find the IDs of steps related to all the test cases
        $steps = DB::table('steps')
            ->whereIn('testcase_id', $deletedTestCases)
            ->select('id')
            ->pluck('id');

        // Find the IDs in testplan_testcase table that have either the testplanID or testcaseID
        $testplanTestCaseIds = DB::table('testplan_testcase')
            ->where(function ($query) use ($deletedTestCases, $deletedTestPlans) {
                $query->whereIn('testcase_id', $deletedTestCases)
                    ->orWhereIn('testplan_id', $deletedTestPlans);
            })
            ->pluck('id');

        // Find the IDs in testcase_requirement table that have either the testcaseID or requirementID
        $testcaseRequirementIds = DB::table('testcase_requirement')
            ->whereIn('testcase_id', $deletedTestCases)
            ->orWhereIn('requirement_id', $deletedRequirements)
            ->pluck('id');

        // Find the IDs in test_executions table related to the project's test plans
        $testExecutionIds = DB::table('test_executions')
            ->whereIn('testplanID', $deletedTestPlans)
            ->pluck('testexecutionID');

        // Find the IDs in test_results table related to the project's test executions
        $testResultIds = DB::table('test_results')
            ->whereIn('testexecution_id', $testExecutionIds)
            ->pluck('id');

        // Delete the records in the appropriate order to avoid foreign key constraint issues
        DB::table('test_results')->whereIn('id', $testResultIds)->delete();
        DB::table('test_executions')->whereIn('testexecutionID', $testExecutionIds)->delete();
        DB::table('testcase_requirement')->whereIn('id', $testcaseRequirementIds)->delete();
        DB::table('testplan_testcase')->whereIn('id', $testplanTestCaseIds)->delete();
        DB::table('steps')->whereIn('id', $steps)->delete();
        DB::table('test_plans')->whereIn('testplanID', $deletedTestPlans)->delete();
        DB::table('test_cases')->whereIn('testcaseID', $deletedTestCases)->delete();
        DB::table('requirements')->whereIn('requirementID', $deletedRequirements)->delete();
        DB::table('project_members')->where('projectID', $project->id)->delete();

        // Finally, delete the project itself
        $project->delete();

        return response()->json([
            'message' => 'Project and related records deleted successfully.',
            'project_id' => $project->projectID,
            'requirements' => $deletedRequirements,
            'testcases' => $deletedTestCases,
            'testplans' => $deletedTestPlans,
            'steps' => $steps,
            'testplan_testcase_ids' => $testplanTestCaseIds,
            'testcase_requirement_ids' => $testcaseRequirementIds,
            'testexecution_ids' => $testExecutionIds,
            'testresult_ids' => $testResultIds,
        ], 200);
    }

    public function checkProjectIdExists($projectId)
    {
        $project = Project::where('projectID', $projectId)->first();

        return response()->json(['exists' => !!$project]);
    }

    public function getProjectStats($projectID)
    {
        // Find the project by projectID
        $project = Project::where('projectID', $projectID)->first();

        if (!$project) {
            return response()->json(['error' => 'Project not found'], 404);
        }

        // Count the number of related requirements, test cases, test plans, and test executions
        $requirementsCount = DB::table('requirements')->where('project_id', $projectID)->count();
        $testCasesCount = DB::table('test_cases')->where('project_id', $projectID)->count();
        $testPlansCount = DB::table('test_plans')->where('project_id', $projectID)->count();
        $testExecutionsCount = DB::table('test_executions')
            ->whereIn('testplanID', function ($query) use ($projectID) {
                $query->select('testplanID')->from('test_plans')->where('project_id', $projectID);
            })
            ->count();

        // Get requirement-testcase coverage
        $requirements = Requirement::where('project_id', $projectID)->get();
        $totalRequirements = $requirements->count();
        $coveredRequirements = 0;
        $coveredRequirementsList = [];
        $nonCoveredRequirementsList = [];
        foreach ($requirements as $requirement) {
            $testcaseCount = DB::table('testcase_requirement')
                ->where('requirement_id', $requirement->requirementID)
                ->count();

            if ($testcaseCount > 0) {
                $coveredRequirements++;
                $coveredRequirementsList[] = $requirement->requirementID;
            } else {
                $nonCoveredRequirementsList[] = $requirement->requirementID;
            }
        }

        // Get testcase-testplan coverage
        $testcases = TestCase::where('project_id', $projectID)->get();
        $totalTestcases = $testcases->count();
        $coveredTestcases = 0;
        $coveredTestcasesList = [];
        $nonCoveredTestcasesList = [];
        foreach ($testcases as $testcase) {
            $testplanCount = DB::table('testplan_testcase')
                ->where('testcase_id', $testcase->testcaseID)
                ->count();

            if ($testplanCount > 0) {
                $coveredTestcases++;
                $coveredTestcasesList[] = $testcase->testcaseID;
            } else {
                $nonCoveredTestcasesList[] = $testcase->testcaseID;
            }
        }

        // Get testplan-testexecution coverage
        $testplans = TestPlan::where('project_id', $projectID)->get();
        $totalTestplans = $testplans->count();
        $coveredTestplans = 0;
        $coveredTestplansList = [];
        $nonCoveredTestplansList = [];
        foreach ($testplans as $testplan) {
            $testexecutionCount = DB::table('test_executions')
                ->where('testplanID', $testplan->testplanID)
                ->count();

            if ($testexecutionCount > 0) {
                $coveredTestplans++;
                $coveredTestplansList[] = $testplan->testplanID;
            } else {
                $nonCoveredTestplansList[] = $testplan->testplanID;
            }
        }

        // Get project members
        $projectMemberIDs = DB::table('project_members')
            ->where('projectID', $project->id)
            ->pluck('userID')
            ->toArray();

        $projectMembers = DB::table('users')
            ->whereIn('id', $projectMemberIDs)
            ->select('id', 'name')
            ->get();

        return response()->json([
            'projectID' => $projectID,
            'requirements_count' => $requirementsCount,
            'test_cases_count' => $testCasesCount,
            'test_plans_count' => $testPlansCount,
            'test_executions_count' => $testExecutionsCount,
            'covered_requirements' => $coveredRequirements,
            'covered_requirements_list' => $coveredRequirementsList,
            'non_covered_requirements_list' => $nonCoveredRequirementsList,
            'covered_testcases' => $coveredTestcases,
            'covered_testcases_list' => $coveredTestcasesList,
            'non_covered_testcases_list' => $nonCoveredTestcasesList,
            'covered_testplans' => $coveredTestplans,
            'covered_testplans_list' => $coveredTestplansList,
            'non_covered_testplans_list' => $nonCoveredTestplansList,
            'project_members' => $projectMembers,
        ], 200);
    }

}
