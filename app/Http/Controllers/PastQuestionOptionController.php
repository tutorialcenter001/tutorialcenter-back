<?php

namespace App\Http\Controllers;

use App\Models\PastQuestionOption;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class PastQuestionOptionController extends Controller
{
    public function update(Request $request, PastQuestionOption $pastQuestionOption)
    {
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
            DB::commit();
            return response()->json([
                'message' => 'Option updated successfully.',
                'data' => $pastQuestionOption,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'An error occurred while updating the option.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function destroy(PastQuestionOption $pastQuestionOption)
    {
        DB::beginTransaction();
        try {
            $pastQuestionOption->delete();
            DB::commit();

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

    public function restore(PastQuestionOption $pastQuestionOption)
    {
        DB::beginTransaction();
        try {
            $pastQuestionOption->restore();
            DB::commit();
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
