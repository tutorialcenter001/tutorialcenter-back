<?php

namespace App\Http\Controllers;

use App\Models\PastQuestionGroup;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PastQuestionGroupController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = PastQuestionGroup::with(['examYear.examBody', 'examYear.subject']);

            if ($request->filled('exam_year_id')) {
                $query->where('exam_year_id', $request->exam_year_id);
            }

            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            $groups = $query->latest()->paginate(20);

            return response()->json([
                'groups' => $groups
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'An error occurred while fetching past question groups.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_year_id' => ['required', 'exists:exam_years,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'type' => ['nullable', 'in:comprehension,instruction,diagram,case_study'],
            'image' => ['nullable', 'image', 'max:2048'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        DB::beginTransaction();
        try {

            $imagePath = null;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('past-question-groups', 'public');
            }

            $group = PastQuestionGroup::create([
                'exam_year_id' => $request->exam_year_id,
                'title' => $request->title,
                'content' => $request->content,
                'type' => $request->type ?? 'comprehension',
                'image' => $imagePath,
                'sort_order' => $request->sort_order ?? 0,
            ]);
            DB::commit();
            return response()->json([
                'message' => 'Past question group created successfully.',
                'data' => $group,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while creating the past question group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function show(PastQuestionGroup $pastQuestionGroup)
    {
        return response()->json(
            $pastQuestionGroup->load(['examYear.examBody', 'examYear.subject', 'questions.options', 'questions.files'])
        );
    }

    public function update(Request $request, PastQuestionGroup $pastQuestionGroup)
    {
        $validator = Validator::make($request->all(), [
            'exam_year_id' => ['required', 'exists:exam_years,id'],
            'title' => ['nullable', 'string', 'max:255'],
            'content' => ['nullable', 'string'],
            'type' => ['nullable', 'in:comprehension,instruction,diagram,case_study'],
            'image' => ['nullable', 'image', 'max:2048'],
            'sort_order' => ['nullable', 'integer'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }
        DB::beginTransaction();
        try {

            $imagePath = $pastQuestionGroup->image;

            if ($request->hasFile('image')) {
                $imagePath = $request->file('image')->store('past-question-groups', 'public');
            }

            $pastQuestionGroup->update([
                'exam_year_id' => $request->exam_year_id,
                'title' => $request->title,
                'content' => $request->content,
                'type' => $request->type ?? $pastQuestionGroup->type,
                'image' => $imagePath,
                'sort_order' => $request->sort_order ?? $pastQuestionGroup->sort_order,
            ]);

            DB::commit();
            return response()->json([
                'message' => 'Past question group updated successfully.',
                'data' => $pastQuestionGroup,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while updating the past question group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(PastQuestionGroup $pastQuestionGroup)
    {
        DB::beginTransaction();
        try {
            $pastQuestionGroup->delete();
            DB::commit();
            return response()->json([
                'message' => 'Past question group deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while deleting the past question group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function restore(PastQuestionGroup $pastQuestionGroup)
    {
        DB::beginTransaction();
        try {
            $pastQuestionGroup->restore();
            DB::commit();
            return response()->json([
                'message' => 'Past question group restored successfully.',
                'data' => $pastQuestionGroup,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while restoring the past question group.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
