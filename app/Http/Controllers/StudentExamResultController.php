<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExamAttempt;
use App\Services\ExamService;

class StudentExamResultController extends Controller
{
    protected $examService;

    public function __construct(
        ExamService $examService
    ) {
        $this->examService = $examService;
    }

    public function submit(
        ExamAttempt $attempt
    ) {
        $attempt = $this->examService
            ->finalizeAttempt(
                $attempt
            );

        return response()->json([
            'success' => true,
            'result' => $attempt
        ]);
    }

    public function history(
        Request $request
    ) {
        return response()->json([
            'success' => true,
            'data' => $request
                ->user()
                ->examAttempts()
                ->latest()
                ->paginate()
        ]);
    }
}
