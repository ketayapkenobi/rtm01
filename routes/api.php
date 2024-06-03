<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProjectMemberController;
use App\Http\Controllers\PriorityController;
use App\Http\Controllers\StatusController;
use App\Http\Controllers\RequirementController;
use App\Http\Controllers\TestCaseController;
use App\Http\Controllers\StepController;
use App\Http\Controllers\TestPlanController;
use App\Http\Controllers\TestExecutionController;
use App\Http\Controllers\ReportController;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [AuthController::class, 'login']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::get('current-user', [AuthController::class, 'getCurrentUser'])->middleware('auth:sanctum');

// Route::prefix('/projects')->middleware(['auth:sanctum'])->group(function () {
//     Route::get('/', [ProjectController::class, 'index'])->middleware(['permission:view projects']);
//     // Route::post('/projects', [ProjectController::class, 'create']);
//     // Route::get('/projects/{id}', [ProjectController::class, 'show']);
//     // Route::put('/projects/{id}', [ProjectController::class, 'update']);
//     // Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
// });

Route::get('/projects', [ProjectController::class, 'index']);
Route::get('/projects/current-user/{userID}', [ProjectController::class, 'getProjectsByUserId']);
Route::post('/projects', [ProjectController::class, 'create']);
Route::get('/projects/{id}', [ProjectController::class, 'show']);
Route::put('/projects/{id}', [ProjectController::class, 'update']);
Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
Route::get('/projects/{projectID}/requirements', [RequirementController::class, 'show']);
Route::get('/projects/{projectID}/testcases', [TestCaseController::class, 'show']);
Route::get('/projects/{projectID}/testplans', [TestPlanController::class, 'show']);
Route::get('/projects/{projectID}/testexecutions', [TestExecutionController::class, 'show']);
Route::get('/projects/check/{id}', [ProjectController::class, 'checkProjectIdExists']);
Route::get('/projects/{projectID}/requirements/report', [ReportController::class, 'generateProjectRequirementsReport']);
Route::get('/projects/{projectID}/testcases/report', [ReportController::class, 'generateProjectTestCasesReport']);
Route::get('/projects/{projectID}/testplans/report', [ReportController::class, 'generateProjectTestPlansReport']);
Route::get('/projects/{projectID}/testexecutions/report', [ReportController::class, 'generateProjectTestExecutionsReport']);

Route::get('/projects/{projectID}/testcaseIDs', [TestCaseController::class, 'showTestCaseID']);
Route::get('/projects/{projectID}/requirementIDs', [RequirementController::class, 'showRequirementID']);

Route::post('/projects/assign-user', [ProjectMemberController::class, 'assignUser']);
Route::get('/projects/{id}/members', [ProjectMemberController::class, 'getProjectMembers']);

Route::get('/priority', [PriorityController::class, 'index']);
Route::get('/status', [StatusController::class, 'index']);

Route::post('/requirements', [RequirementController::class, 'create']);
Route::get('/requirements/check/{requirementID}', [RequirementController::class, 'checkRequirementIDExists']);
// Route::get('/requirements/{projectID}', [RequirementController::class, 'show']);
Route::put('/requirements/{requirementID}', [RequirementController::class, 'update']);
// Route::get('/projects/{id}/requirements', [RequirementController::class, 'index']);
Route::get('/requirements/{requirementID}/testcases', [RequirementController::class, 'getRelatedTestCases']);

Route::post('/testcases', [TestCaseController::class, 'create']);
Route::get('/testcases/check/{testcaseID}', [TestCaseController::class, 'checkTestCaseIDExists']);
Route::put('/testcases/{testcaseID}', [TestCaseController::class, 'update']);
Route::post('/testcases/{testcaseID}/relate-requirements', [TestCaseController::class, 'relateOrUnrelateRequirements']);

Route::post('/steps', [StepController::class, 'create']);
Route::get('/steps/{testcaseID}', [StepController::class, 'show']);
Route::put('/steps/{testcaseID}/{step_order}', [StepController::class, 'update']);
Route::delete('/steps/{testcaseID}/{step_order}', [StepController::class, 'destroy']);

Route::post('/testplans', [TestPlanController::class, 'create']);
Route::get('/testplans/{projectID}/index', [TestPlanController::class, 'index']);
Route::get('/testplans/{projectID}', [TestPlanController::class, 'getLatestTestPlanNumber']);
Route::post('/testplans/{testplanID}/assign-testcases', [TestPlanController::class, 'relateOrUnrelateTestCases']);
Route::delete('/testplans/{testplanID}', [TestPlanController::class, 'destroy']);
Route::get('/testplans/{testplanID}/related-testcases', [TestPlanController::class, 'getRelatedTestCases']);
Route::get('/testplans/{testplanID}/number-of-execution', [TestPlanController::class, 'countTestExecutions']);
Route::get('/testplans/{testplanID}/number-of-execution-per-project', [TestPlanController::class, 'countTestExecutionsByProjectID']);
Route::post('/testplans/{testplanID}', [TestPlanController::class, 'execute']);
Route::get('/testplans/{testplanID}/testexecutions', [TestPlanController::class, 'getRelatedTestExecutions']);

Route::get('/testexecutions/{projectID}', [TestExecutionController::class, 'index']);
Route::put('/testexecutions/{testexecution_id}/{step_id}', [TestExecutionController::class, 'update']);
Route::get('/testexecutions/{testexecutionID}/progress', [TestExecutionController::class, 'getProgress']);

Route::get('/coverage/{projectID}/requirements', [ReportController::class, 'getRequirementTestcaseCoverage']);
Route::get('/coverage/{projectID}/testcases', [ReportController::class, 'getTestcaseTestplanCoverage']);
Route::get('/coverage/{projectID}/testplans', [ReportController::class, 'getTestplanTestexecutionCoverage']);


// Route::post('/testcases/{testcaseID}/unrelate-requirements', [TestCaseController::class, 'unrelateRequirements']);
// Route::get('/testcases/{testcaseID}/requirements', [TestCaseController::class, 'getRelatedRequirements']);

Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'create']);
Route::get('/users/{id}', [UserController::class, 'show']);
Route::get('/users/check/{userID}', [UserController::class, 'checkUserIDExists']);
Route::get('/users/check/email/{email}', [UserController::class, 'checkEmailExists']);

Route::get('/roles', [RoleController::class, 'index']);











