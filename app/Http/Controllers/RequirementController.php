<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Requirement;
use Illuminate\Validation\Rule;

class RequirementController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index($projectId)
    {
        $requirements = Requirement::where('project_id', $projectId)->get();
        return response()->json(['requirements' => $requirements]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $request->validate([
            'requirementID' => 'required',
            'name' => 'required',
            'description' => 'required',
            'priority_id' => ['required', Rule::exists('priority', 'id')],
            'status_id' => ['required', Rule::exists('status', 'id')],
            'project_id' => ['required', Rule::exists('projects', 'projectID')],
        ]);

        $requirement = Requirement::create([
            'requirementID' => $request->requirementID,
            'name' => $request->name,
            'description' => $request->description,
            'priority_id' => $request->priority_id,
            'status_id' => $request->status_id,
            'project_id' => $request->project_id,
        ]);

        return response()->json($requirement, 201);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
