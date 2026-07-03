<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\Admin\ExamAnalyticsService;

class AdminDashboardAnalyticsController extends Controller
{
    protected ExamAnalyticsService $analytics;

    public function __construct(
        ExamAnalyticsService $analytics
    ) {
        $this->analytics = $analytics;
    }

    /**
     * Exam Practice Analytics
     */
    public function examAnalytics(Request $request)
    {
        try {

            $analytics = $this->analytics->overview();

            return response()->json([
                'success' => true,
                'message' => 'Exam analytics retrieved successfully.',
                'data' => $analytics,
            ]);

        } catch (\Throwable $e) {

            return response()->json([
                'success' => false,
                'message' => 'Unable to retrieve exam analytics.',
                'error' => config('app.debug')
                    ? $e->getMessage()
                    : null,
            ], 500);
        }
    }
}