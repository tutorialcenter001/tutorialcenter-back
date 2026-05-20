<?php

namespace App\Http\Controllers;

use App\Models\ExamYear;
use Illuminate\Http\Request;
use App\Services\ExamService;

class StudentExamController extends Controller
{
    protected $examService;

    public function __construct(
        ExamService $examService
    ) {
        $this->examService = $examService;
    }

    public function available(Request $request)
    {
        $student = $request->user();

        $exams = ExamYear::with([
            'examBody',
            'subject'
        ])
            ->get()
            ->filter(function ($exam) use ($student) {

                return $student->canAccessExam(
                    $exam->id
                );
            })
            ->values();

        return response()->json([
            'success' => true,
            'data' => $exams
        ]);
    }

    public function start(
        Request $request,
        ExamYear $examYear
    ) {
        $student = $request->user();

        $attempt = $this->examService
            ->startExam(
                $student,
                $examYear->id
            );

        return response()->json([
            'success' => true,
            'attempt' => $attempt
        ]);
    }
}
