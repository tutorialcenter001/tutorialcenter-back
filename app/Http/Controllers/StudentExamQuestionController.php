<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\ExamAttempt;
use App\Models\PastQuestion;
use App\Models\PastQuestionOption;
use App\Services\ExamService;

class StudentExamQuestionController extends Controller
{
    protected $examService;

    public function __construct(
        ExamService $examService
    ) {
        $this->examService = $examService;
    }

    public function questions(
        ExamAttempt $attempt
    ) {
        $questions = $attempt
            ->examYear
            ->pastQuestions()
            ->with([
                'options:id,past_question_id,label,option_text', 'group'
            ])
            ->get();

        return response()->json([
            'success' => true,
            'questions' => $questions
        ]);
    }

    public function submitAnswer(
        Request $request,
        ExamAttempt $attempt
    ) {
        $request->validate([
            'question_id' => 'required|exists:past_questions,id',
            'option_id' => 'required|exists:past_question_options,id'
        ]);

        $question = PastQuestion::findOrFail(
            $request->question_id
        );

        $option = PastQuestionOption::findOrFail(
            $request->option_id
        );

        $answer = $this->examService
            ->submitAnswer(
                $attempt,
                $question,
                $option
            );

        return response()->json([
            'success' => true,
            'data' => $answer
        ]);
    }
}
