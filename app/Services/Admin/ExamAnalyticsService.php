<?php

namespace App\Services\Admin;

use App\Models\ExamAttempt;
use App\Models\Student;
use App\Models\Subject;

class ExamAnalyticsService
{
    /**
     * Main analytics endpoint
     */
    public function overview(): array
    {
        return [
            'average_score' => $this->averageScore(),
            'pass_rate' => $this->passRate(),
            'completion_rate' => $this->completionRate(),
            'average_attempts_per_student' => $this->averageAttemptsPerStudent(),

            'students' => [
                'above_80' => $this->studentsAbove80(),
                'below_40' => $this->studentsBelow40(),
            ],

            'subjects' => [
                'most_attempted' => $this->mostAttemptedSubjects(),
                'least_attempted' => $this->leastAttemptedSubjects(),
                'highest_average' => $this->highestAverageSubjects(),
                'lowest_average' => $this->lowestAverageSubjects(),
            ],
        ];
    }

    /**
     * Average Percentage Score
     */
    private function averageScore(): float
    {
        return round(
            ExamAttempt::where(
                'status',
                ExamAttempt::COMPLETED
            )->avg('percentage') ?? 0,
            2
        );
    }

    /**
     * Pass Rate (50% pass mark)
     */
    private function passRate(): float
    {
        $total = ExamAttempt::where(
            'status',
            ExamAttempt::COMPLETED
        )->count();

        if ($total === 0) {
            return 0;
        }

        $passed = ExamAttempt::where(
            'status',
            ExamAttempt::COMPLETED
        )
            ->where('percentage', '>=', 50)
            ->count();

        return round(
            ($passed / $total) * 100,
            2
        );
    }

    /**
     * Completion Rate
     */
    private function completionRate(): float
    {
        $started = ExamAttempt::whereIn(
            'status',
            [
                ExamAttempt::COMPLETED,
                ExamAttempt::ABANDONED,
            ]
        )->count();

        if ($started === 0) {
            return 0;
        }

        $completed = ExamAttempt::where(
            'status',
            ExamAttempt::COMPLETED
        )->count();

        return round(
            ($completed / $started) * 100,
            2
        );
    }

    /**
     * Average Attempts Per Student
     */
    private function averageAttemptsPerStudent(): float
    {
        $students = Student::count();

        if ($students === 0) {
            return 0;
        }

        $attempts = ExamAttempt::where(
            'status',
            ExamAttempt::COMPLETED
        )->count();

        return round(
            $attempts / $students,
            2
        );
    }

    /**
     * Students Above 80%
     */
    private function studentsAbove80(): int
    {
        return ExamAttempt::where(
            'status',
            ExamAttempt::COMPLETED
        )
            ->where('percentage', '>=', 80)
            ->distinct('student_id')
            ->count('student_id');
    }

    /**
     * Students Below 40%
     */
    private function studentsBelow40(): int
    {
        return ExamAttempt::where(
            'status',
            ExamAttempt::COMPLETED
        )
            ->where('percentage', '<', 40)
            ->distinct('student_id')
            ->count('student_id');
    }

    /**
     * Most Attempted Subjects
     */
    private function mostAttemptedSubjects(int $limit = 5)
    {
        return Subject::query()
            ->join(
                'exam_years',
                'subjects.id',
                '=',
                'exam_years.subject_id'
            )
            ->join(
                'exam_attempts',
                'exam_years.id',
                '=',
                'exam_attempts.exam_year_id'
            )
            ->where(
                'exam_attempts.status',
                ExamAttempt::COMPLETED
            )
            ->select(
                'subjects.id',
                'subjects.name'
            )
            ->selectRaw(
                'COUNT(exam_attempts.id) as attempts'
            )
            ->groupBy(
                'subjects.id',
                'subjects.name'
            )
            ->orderByDesc('attempts')
            ->limit($limit)
            ->get();
    }

    /**
     * Least Attempted Subjects
     */
    private function leastAttemptedSubjects(int $limit = 5)
    {
        return Subject::query()
            ->join(
                'exam_years',
                'subjects.id',
                '=',
                'exam_years.subject_id'
            )
            ->join(
                'exam_attempts',
                'exam_years.id',
                '=',
                'exam_attempts.exam_year_id'
            )
            ->where(
                'exam_attempts.status',
                ExamAttempt::COMPLETED
            )
            ->select(
                'subjects.id',
                'subjects.name'
            )
            ->selectRaw(
                'COUNT(exam_attempts.id) as attempts'
            )
            ->groupBy(
                'subjects.id',
                'subjects.name'
            )
            ->orderBy('attempts')
            ->limit($limit)
            ->get();
    }

    /**
     * Highest Average Subjects
     */
    private function highestAverageSubjects(int $limit = 5)
    {
        return Subject::query()
            ->join(
                'exam_years',
                'subjects.id',
                '=',
                'exam_years.subject_id'
            )
            ->join(
                'exam_attempts',
                'exam_years.id',
                '=',
                'exam_attempts.exam_year_id'
            )
            ->where(
                'exam_attempts.status',
                ExamAttempt::COMPLETED
            )
            ->select(
                'subjects.id',
                'subjects.name'
            )
            ->selectRaw(
                'AVG(exam_attempts.percentage) as average_score'
            )
            ->groupBy(
                'subjects.id',
                'subjects.name'
            )
            ->orderByDesc('average_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Lowest Average Subjects
     */
    private function lowestAverageSubjects(int $limit = 5)
    {
        return Subject::query()
            ->join(
                'exam_years',
                'subjects.id',
                '=',
                'exam_years.subject_id'
            )
            ->join(
                'exam_attempts',
                'exam_years.id',
                '=',
                'exam_attempts.exam_year_id'
            )
            ->where(
                'exam_attempts.status',
                ExamAttempt::COMPLETED
            )
            ->select(
                'subjects.id',
                'subjects.name'
            )
            ->selectRaw(
                'AVG(exam_attempts.percentage) as average_score'
            )
            ->groupBy(
                'subjects.id',
                'subjects.name'
            )
            ->orderBy('average_score')
            ->limit($limit)
            ->get();
    }

    /**
     * Leaderboard of students based on average score and total attempts
     */
    public function leaderboard(int $limit = 20)
    {
        return Student::query()
            ->leftJoin(
                'exam_attempts',
                function ($join) {
                    $join->on(
                        'students.id',
                        '=',
                        'exam_attempts.student_id'
                    )
                        ->where(
                            'exam_attempts.status',
                            ExamAttempt::COMPLETED
                        );
                }
            )
            ->select(
                'students.id',
                'students.firstname',
                'students.surname',
                'students.profile_picture'
            )
            ->selectRaw("
            COUNT(exam_attempts.id) AS total_attempts,
            ROUND(AVG(exam_attempts.percentage),2) AS average_score,
            MAX(exam_attempts.percentage) AS highest_score,
            COALESCE(SUM(exam_attempts.correct_answers),0) AS total_correct_answers,
            COALESCE(SUM(exam_attempts.score),0) AS total_score
        ")
            ->groupBy(
                'students.id',
                'students.firstname',
                'students.surname',
                'students.profile_picture'
            )
            ->orderByDesc('total_correct_answers')
            ->orderByDesc('highest_score')
            ->orderByDesc('total_attempts')
            ->orderByDesc('average_score')
            ->limit($limit)
            ->get()
            ->values()
            ->map(function ($student, $index) {

                return [

                    'rank' => $index + 1,

                    'student_id' => $student->id,

                    'name' => trim(
                        $student->firstname . ' ' . $student->surname
                    ),

                    'profile_picture' => $student->profile_picture,

                    'total_correct_answers' => (int) $student->total_correct_answers,

                    'total_attempts' => (int) $student->total_attempts,

                    'highest_score' => (int) $student->highest_score,

                    'average_score' => (float) $student->average_score,

                    'total_score' => (int) $student->total_score,

                ];
            });
    }
    
    // public function leaderboard(int $limit = 20)
    // {
    //     return Student::query()
    //         ->leftJoin(
    //             'exam_attempts',
    //             function ($join) {
    //                 $join->on(
    //                     'students.id',
    //                     '=',
    //                     'exam_attempts.student_id'
    //                 )
    //                     ->where(
    //                         'exam_attempts.status',
    //                         ExamAttempt::COMPLETED
    //                     );
    //             }
    //         )
    //         ->select(
    //             'students.id',
    //             'students.firstname',
    //             'students.surname',
    //             'students.profile_picture'
    //         )
    //         ->selectRaw("
    //         COUNT(exam_attempts.id) AS total_attempts,
    //         ROUND(AVG(exam_attempts.percentage),2) AS average_score,
    //         MAX(exam_attempts.percentage) AS highest_score,
    //         SUM(exam_attempts.correct_answers) AS total_correct_answers,
    //         SUM(exam_attempts.score) AS total_score
    //     ")
    //         ->groupBy(
    //             'students.id',
    //             'students.firstname',
    //             'students.surname',
    //             'students.profile_picture'
    //         )
    //         ->orderByDesc('average_score')
    //         ->orderByDesc('total_attempts')
    //         ->limit($limit)
    //         ->get()
    //         ->values()
    //         ->map(function ($student, $index) {

    //             return [

    //                 'rank' => $index + 1,

    //                 'student_id' => $student->id,

    //                 'name' =>
    //                 trim(
    //                     $student->firstname .
    //                         ' ' .
    //                         $student->surname
    //                 ),

    //                 'profile_picture' =>
    //                 $student->profile_picture,

    //                 'average_score' =>
    //                 (float) ($student->average_score ?? 0),

    //                 'highest_score' =>
    //                 (int) ($student->highest_score ?? 0),

    //                 'total_attempts' =>
    //                 (int) $student->total_attempts,

    //                 'total_correct_answers' =>
    //                 (int) ($student->total_correct_answers ?? 0),

    //                 'total_score' =>
    //                 (int) ($student->total_score ?? 0),
    //             ];
    //         });
    // }
}
