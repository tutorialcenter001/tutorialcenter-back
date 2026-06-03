<?php

namespace App\Http\Controllers;

use App\Models\ExamYear;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Validator;
use App\Services\AdminNotificationService;

class ExamYearController extends Controller
{
    public function index(Request $request)
    {
        $query = ExamYear::with(['examBody.course', 'subject']);

        if ($request->filled('exam_body_id')) {
            $query->where('exam_body_id', $request->exam_body_id);
        }

        if ($request->filled('subject_id')) {
            $query->where('subject_id', $request->subject_id);
        }

        if ($request->filled('year')) {
            $query->where('year', $request->year);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $examYears = $query->latest()->paginate(20);

        return response()->json($examYears);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'exam_body_id' => ['required', 'exists:exam_bodies,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'year' => [
                'required',
                'integer',
                'digits:4',
                'min:1980',
                'max:' . now()->year,
                Rule::unique('exam_years')->where(function ($query) use ($request) {
                    return $query
                        ->where('exam_body_id', $request->exam_body_id)
                        ->where('subject_id', $request->subject_id)
                        ->where('year', $request->year);
                }),
            ],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $examYear = ExamYear::create([
            'exam_body_id' => $request->exam_body_id,
            'subject_id' => $request->subject_id,
            'year' => $request->year,
            'status' => $request->status ?? 'active',
        ]);

        AdminNotificationService::notify(
            'exam_year_created',
            "Exam year created: {$examYear->year} for subject ID: {$examYear->subject_id} and exam body ID: {$examYear->exam_body_id} by user: {$request->user()->staff_id}, {$request->user()->firstname} {$request->user()->surname}, {$request->user()->email}",
            ['exam_year_id' => $examYear->id]
        );

        return response()->json([
            'message' => 'Exam year created successfully.',
            'data' => $examYear->load(['examBody.course', 'subject']),
        ], 201);
    }

    public function show(ExamYear $examYear, $id)
    {
        try {
            $examYear = ExamYear::findOrFail($id);
            return response()->json(
                $examYear->load(['examBody.course', 'subject'])
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unauthorized to view exam year.',
            ], 403);
        }
    }

    public function update(Request $request, $id)
    {
        $examYear = ExamYear::findOrFail($id);
        $validator = Validator::make($request->all(), [
            'exam_body_id' => ['required', 'exists:exam_bodies,id'],
            'subject_id' => ['required', 'exists:subjects,id'],
            'year' => [
                'required',
                'integer',
                'digits:4',
                'min:1980',
                'max:' . now()->year,
                Rule::unique('exam_years')->where(function ($query) use ($request) {
                    return $query
                        ->where('exam_body_id', $request->exam_body_id)
                        ->where('subject_id', $request->subject_id)
                        ->where('year', $request->year);
                })->ignore($examYear->id),
            ],
            'status' => ['required', 'in:active,inactive'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);
        }

        $examYear->update([
            'exam_body_id' => $request->exam_body_id,
            'subject_id' => $request->subject_id,
            'year' => $request->year,
            'status' => $request->status ?? $examYear->status,
        ]);

        AdminNotificationService::notify(
            'exam_year_updated',
            "Exam year updated: {$examYear->year} for subject ID: {$examYear->subject_id} and exam body ID: {$examYear->exam_body_id} by user: {$request->user()->staff_id}, {$request->user()->firstname} {$request->user()->surname}, {$request->user()->email}",
            ['exam_year_id' => $examYear->id]
        );

        return response()->json([
            'message' => 'Exam year updated successfully.',
            'examYear' => $examYear->load(['examBody.course', 'subject']),
        ]);
    }

    public function destroy(ExamYear $examYear, Request $request, $id)
    {
        try {
            $examYear = ExamYear::findOrFail($id);
            $examYear->delete();

            AdminNotificationService::notify(
                'exam_year_deleted',
                "Exam year deleted: {$examYear->year} for subject ID: {$examYear->subject_id} and exam body ID: {$examYear->exam_body_id} by user: {$request->user()->staff_id}, {$request->user()->firstname} {$request->user()->surname}, {$request->user()->email}",
                ['exam_year_id' => $examYear->id]
            );

            return response()->json([
                'message' => 'Exam year deleted successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'message' => 'Unauthorized to delete exam year.',
            ], 403);
        }
    }
}
