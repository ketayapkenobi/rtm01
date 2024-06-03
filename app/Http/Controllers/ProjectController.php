<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Validator;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Project;
use App\Models\User;

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

        // Get all the projectIDs related to the userID in the project_members table
        $projectIds = DB::table('project_members')
                        ->where('userID', $user->id)
                        ->pluck('projectID');

        // Get the projects based on the projectIDs
        $projects = Project::whereIn('id', $projectIds)->get();

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
}
