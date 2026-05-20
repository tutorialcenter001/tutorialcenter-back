<?php

namespace App\Services;

use App\Models\Student;
use App\Models\ExamAttempt;
use App\Models\PastQuestion;
use App\Models\ExamAttemptAnswer;
use App\Models\PastQuestionOption;
use Illuminate\Support\Facades\DB;

class ExamService
{

    public function startExam(Student $student, $examYearId)
    {
        return DB::transaction(function () use (
            $student,
            $examYearId
        ) {

            if (!$student->canAccessExam($examYearId)) {
                abort(403, 'Not eligible');
            }

            /*
        |------------------------------------------
        | Ignore old dead sessions
        |------------------------------------------
        */

            $existingAttempt = ExamAttempt::lockForUpdate()
                ->where(
                    'student_id',
                    $student->id
                )
                ->where(
                    'exam_year_id',
                    $examYearId
                )
                ->where(
                    'status',
                    ExamAttempt::IN_PROGRESS
                )
                ->where(
                    'started_at',
                    '>=',
                    now()->subHours(2)
                )
                ->first();

            if ($existingAttempt) {
                return $existingAttempt;
            }

            $questionsCount = PastQuestion::where(
                'exam_year_id',
                $examYearId
            )->count();

            return ExamAttempt::create([
                'student_id' => $student->id,
                'exam_year_id' => $examYearId,
                'total_questions' => $questionsCount,
                'started_at' => now(),
                'status' => ExamAttempt::IN_PROGRESS
            ]);
        });
    }

    public function submitAnswer(
        ExamAttempt $attempt,
        PastQuestion $question,
        PastQuestionOption $option
    ) {
        if (
            $attempt->status !== ExamAttempt::IN_PROGRESS
        ) {
            abort(403, 'Exam already completed');
        }

        if (
            $question->exam_year_id !==
            $attempt->exam_year_id
        ) {
            abort(422, 'Invalid question');
        }

        if (
            $option->past_question_id !==
            $question->id
        ) {
            abort(422, 'Invalid option');
        }

        return ExamAttemptAnswer::updateOrCreate(
            [
                'exam_attempt_id' => $attempt->id,
                'past_question_id' => $question->id,
            ],
            [
                'past_question_option_id' => $option->id,
            ]
        );
    }

    public function finalizeAttempt(ExamAttempt $attempt)
    {
        if (
            $attempt->status !== ExamAttempt::IN_PROGRESS
        ) {
            return $attempt;
        }

        $answers = $attempt->answers()
            ->with('option:id,is_correct')
            ->get();

        $correct = $answers
            ->filter(fn($answer) => $answer->is_correct)
            ->count();

        $wrong = $answers
            ->filter(fn($answer) => !$answer->is_correct)
            ->count();

        $total = $attempt->total_questions;

        $unanswered = $total - ($correct + $wrong);

        $percentage = $total > 0
            ? ($correct / $total) * 100
            : 0;

        $attempt->update([
            'correct_answers' => $correct,
            'wrong_answers' => $wrong,
            'unanswered' => $unanswered,
            'score' => $correct,
            'percentage' => round($percentage, 2),
            'submitted_at' => now(),
            'status' => ExamAttempt::COMPLETED,
        ]);

        return $attempt;
    }
}
