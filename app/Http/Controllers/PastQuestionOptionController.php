<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\PastQuestionOption;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use App\Services\AdminNotificationService;

class PastQuestionOptionController extends Controller
{
    // Update an existing past question option
    public function update(Request $request, $id)
    {
        $pastQuestionOption = PastQuestionOption::findOrFail($id);
        DB::beginTransaction();
        try {
            $validator = Validator::make($request->all(), [
                'label' => ['nullable', 'string', 'max:10'],
                'option_text' => ['required', 'string'],
                'is_correct' => ['nullable', 'boolean'],
                'sort_order' => ['nullable', 'integer'],
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'message' => 'Validation failed.',
                    'errors' => $validator->errors(),
                ], 422);
            }

            $pastQuestionOption->update([
                'label' => $request->label,
                'option_text' => $request->option_text,
                'is_correct' => $request->is_correct ?? false,
                'sort_order' => $request->sort_order ?? $pastQuestionOption->sort_order,
            ]);
            $pastQuestionOption->refresh();
            DB::commit();
            AdminNotificationService::notify(
                'Past Question Option Updated',
                "Past question option updated for past question group ID: {$pastQuestionOption->past_question_group_id} by user: {$request->user()->staff_id}, {$request->user()->firstname} {$request->user()->surname}, {$request->user()->email}",
                ['past_question_option_id' => $pastQuestionOption->id]
            );
            return response()->json([
                'message' => 'Option updated successfully.',
                'pastQuestionOption' => $pastQuestionOption,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while updating the option.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Soft delete a past question option
    public function destroy(PastQuestionOption $pastQuestionOption, Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $pastQuestionOption = PastQuestionOption::findOrFail($id);
            $pastQuestionOption->delete();
            DB::commit();
            AdminNotificationService::notify(
                'Past Question Option Deleted',
                "Past question option deleted for past question group ID: {$pastQuestionOption->past_question_group_id} by user: {$request->user()->staff_id}, {$request->user()->firstname} {$request->user()->surname}, {$request->user()->email}",
                ['past_question_option_id' => $pastQuestionOption->id]
            );

            return response()->json([
                'message' => 'Option deleted successfully.',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while deleting the option.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    // Restore a soft-deleted past question option
    public function restore(PastQuestionOption $pastQuestionOption, Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $pastQuestionOption = PastQuestionOption::findOrFail($id);
            $pastQuestionOption->restore();
            DB::commit();
            AdminNotificationService::notify(
                'Past Question Option Restored',
                "Past question option restored for past question group ID: {$pastQuestionOption->past_question_group_id} by user: {$request->user()->staff_id}, {$request->user()->firstname} {$request->user()->surname}, {$request->user()->email}",
                ['past_question_option_id' => $pastQuestionOption->id]
            );
            return response()->json([
                'message' => 'Option restored successfully.',
                'data' => $pastQuestionOption,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while restoring the option.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
