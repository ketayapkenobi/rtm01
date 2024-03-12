<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\ProjectMemberController;

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

Route::middleware(['auth:sanctum'])->get('/user', function (Request $request) {
    return $request->user();
});

Route::post('login', [AuthController::class, 'login']);
// Route::post('change-password', [AuthController::class, 'changePassword']);
Route::post('logout', [AuthController::class, 'logout'])->middleware('auth:sanctum');
Route::get('me', [AuthController::class, 'me'])->middleware('auth:sanctum');
Route::get('current-user', [AuthController::class, 'getCurrentUser'])->middleware('auth:sanctum');

Route::get('/projects', [ProjectController::class, 'index'])->middleware('auth:sanctum');
Route::post('/projects', [ProjectController::class, 'create']);
Route::get('/projects/{id}', [ProjectController::class, 'show']);
Route::put('/projects/{id}', [ProjectController::class, 'update']);
Route::delete('/projects/{id}', [ProjectController::class, 'destroy']);
Route::get('/projects/check/{id}', [ProjectController::class, 'checkProjectIdExists']);

// Route::post('/projects/assign-user', [ProjectMemberController::class, 'assignUser']);
Route::get('/projects/{id}/members', [ProjectMemberController::class, 'getProjectMembers']);


Route::get('/users', [UserController::class, 'index']);
Route::post('/users', [UserController::class, 'create']);
Route::get('/users/{id}', [UserController::class, 'show']);

Route::get('/roles', [RoleController::class, 'index']);











