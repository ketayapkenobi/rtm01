<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use App\Models\Step;

class StepController extends Controller
{
    public function index()
    {
        //
    }

    public function create(Request $request)
    {
        $request->validate([
            'testcase_id' => ['required', Rule::exists('test_cases', 'testcaseID')],
            'action' => 'required',
            'input' => 'required',
            'expected_result' => 'required',
            'step_order' => 'required',
        ]);

        $step = Step::create([
            'testcase_id' => $request->testcase_id,
            'action' => $request->action,
            'input' => $request->input,
            'expected_result' => $request->expected_result,
            'step_order' => $request->step_order,
        ]);

        return response()->json($step, 201);
    }

    public function show($testcaseID)
    {
        // Fetch the steps ordered by step_order
        $steps = Step::where('testcase_id', $testcaseID)->orderBy('step_order')->get();

        // Get the maximum step_order value
        $maxStepOrder = $steps->max('step_order');

        // Return both steps and maxStepOrder in the response
        return response()->json([
            'steps' => $steps,
            'maxStepOrder' => $maxStepOrder
        ]);
    }

    public function update(Request $request, $testcaseID, $step_order)
    {
        $request->validate([
            'testcase_id' => ['required', Rule::exists('test_cases', 'testcaseID')],
            'action' => 'required',
            'input' => 'required',
            'expected_result' => 'required',
            'step_order' => 'required',
        ]);

        $step = Step::where('testcase_id', $testcaseID)
                    ->where('step_order', $step_order)
                    ->firstOrFail();

        $step->update([
            'testcase_id' => $request->testcase_id,
            'action' => $request->action,
            'input' => $request->input,
            'expected_result' => $request->expected_result,
            'step_order' => $request->step_order,
        ]);

        return response()->json($step, 200);
    }

    public function destroy(string $testcaseID, int $step_order)
    {
        try {
            $step = Step::where('testcase_id', $testcaseID)
                        ->where('step_order', $step_order)
                        ->firstOrFail();

            $step->delete();

            return response()->json(['message' => 'Step deleted successfully'], 200);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Step not found'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => 'Failed to delete step'], 500);
        }
    }
}
