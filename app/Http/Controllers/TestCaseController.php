<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\TestCase;
use Illuminate\Validation\Rule;

class TestCaseController extends Controller
{
    
    public function index()
    {
        //
    }

    public function create(Request $request)
    {
        $request->validate([
            'testcaseID' => 'required',
            'name' => 'required',
            'description' => 'required',
            'priority_id' => ['required', Rule::exists('priority', 'id')],
            'status_id' => ['required', Rule::exists('status', 'id')],
            'project_id' => ['required', Rule::exists('projects', 'projectID')],
        ]);

        $testcase = TestCase::create([
            'testcaseID' => $request->testcaseID,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'project_id' => $request->project_id,
        ]);

        return response()->json($testcase, 201);
    }

    public function show($projectID)
    {
        $testcases = TestCase::with('priority', 'status')
            ->where('project_id', $projectID)
            ->get()
            ->map(function ($testcase) {
                return [
                    'id' => $testcase->id,
                    'testcaseID' => $testcase->testcaseID,
                    'name' => $testcase->name,
                    'description' => $testcase->description,
                    'priority_id' => $testcase->priority_id,
                    'priority_name' => $testcase->priority_name,
                    'status_id' => $testcase->status_id,
                    'status_name' => $testcase->status_name,
                    'project_id' => $testcase->project_id,
                    'created_at' => $testcase->created_at,
                    'updated_at' => $testcase->updated_at,
                ];
            });

        return response()->json(['testcases' => $testcases], 200);
    }

    public function update(Request $request, $testcaseID)
    {
        $request->validate([
            'testcaseID' => 'required',
            'name' => 'required',
            'description' => 'required',
            'priority_id' => ['required', Rule::exists('priority', 'id')],
            'status_id' => ['required', Rule::exists('status', 'id')],
            'project_id' => ['required', Rule::exists('projects', 'projectID')],
        ]);

        $testcase = TestCase::where('testcaseID', $testcaseID)->firstOrFail();

        $testcase->update([
            'testcaseID' => $request->testcaseID,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'project_id' => $request->project_id,
        ]);

        return response()->json($testcase, 200);
    }

    public function destroy(string $id)
    {
        //
    }

    public function checkTestCaseIDExists($testcaseID)
    {
        $testcase = TestCase::where('testcaseID', $testcaseID)->first();

        return response()->json(['exists' => !!$testcase]);
    }
}
