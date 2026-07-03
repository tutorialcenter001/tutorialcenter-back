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
}