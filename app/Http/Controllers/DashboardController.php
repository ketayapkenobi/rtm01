<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Project;
use App\Models\Requirement;
use App\Models\TestCase;
use App\Models\TestPlan;
use App\Models\TestExecution;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function getStats()
    {
        $projectsCount = DB::table('projects')->count();
        $usersCount = DB::table('users')->count();

        return response()->json([
            'projects_count' => $projectsCount,
            'users_count' => $usersCount
        ]);
    }

    public function getStatsForBarChart()
    {
        // Get all distinct project IDs from the projects table
        $projectIDs = Project::pluck('projectID')->toArray();

        $stats = [];

        foreach ($projectIDs as $projectID) {
            // Find all the test plan IDs for the current project
            $testPlanIDs = TestPlan::where('project_id', $projectID)->pluck('testplanID')->toArray();

            // Find all the test execution IDs related to the test plan IDs
            $testExecutionIDs = TestExecution::whereIn('testplanID', $testPlanIDs)->pluck('id')->toArray();

            // Calculate the total number of test executions for the current project
            $totalTestExecutions = count($testExecutionIDs);

            // Find the total number of requirements for the current project
            $totalRequirements = Requirement::where('project_id', $projectID)->count();

            // Find the total number of test cases for the current project
            $totalTestCases = TestCase::where('project_id', $projectID)->count();

            // Find the total number of test plans for the current project
            $totalTestPlans = count($testPlanIDs);

            // Calculate the total artifacts for the current project
            $totalArtifacts = $totalRequirements + $totalTestCases + $totalTestPlans + $totalTestExecutions;

            // Add the project stats to the array
            $stats[] = [
                'projectID' => $projectID,
                'totalRequirements' => $totalRequirements,
                'totalTestCases' => $totalTestCases,
                'totalTestPlans' => $totalTestPlans,
                'totalTestExecutions' => $totalTestExecutions,
                'totalArtifacts' => $totalArtifacts,
            ];
        }

        return [
            'projects' => $stats,
        ];
    }

    public function getRolesStats()
    {
        // Get the total number of users for each role
        $userStats = DB::table('users')
            ->select('roles.name as roleName', DB::raw('COUNT(users.id) as total'))
            ->join('roles', 'users.role_id', '=', 'roles.id')
            ->groupBy('role_id', 'roleName')
            ->get();

        // Calculate the total number of users
        $totalUsers = DB::table('users')->count();

        // Calculate the percentage of users for each role
        foreach ($userStats as $userStat) {
            $userStat->percentage = ($userStat->total / $totalUsers) * 100;
        }

        return response()->json([
            'userStats' => $userStats,
        ]);
    }
}
