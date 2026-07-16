<?php

namespace App\Http\Controllers;

use App\Models\Feedback;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\FeedbackService;

class FeedbackController extends Controller
{
    protected $feedbackService;

    public function __construct(
        FeedbackService $feedbackService
    ) {
        $this->feedbackService = $feedbackService;
    }

    /**
     * Submit Feedback
     */
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'feedbackable_type' => [
                'required',
                'string',
            ],

            'feedbackable_id' => [
                'required',
                'integer',
            ],

            'rating' => [
                'required',
                'integer',
                'between:1,5',
            ],

            'title' => [
                'nullable',
                'string',
                'max:255',
            ],

            'comment' => [
                'nullable',
                'string',
            ],

            'ratings' => [
                'nullable',
                'array',
            ],

            'would_recommend' => [
                'boolean',
            ],

            'is_anonymous' => [
                'boolean',
            ],

        ]);

        if ($validator->fails()) {

            return response()->json([
                'message' => 'Validation failed.',
                'errors' => $validator->errors(),
            ], 422);

        }

        try {

            $feedback = $this->feedbackService->create(
                $request->user(),
                $request->all()
            );

            return response()->json([
                'message' => 'Feedback submitted successfully.',
                'data' => $feedback,
            ], 201);

        } catch (\Exception $e) {

            return response()->json([
                'message' => $e->getMessage(),
            ], 400);

        }
    }

    /**
     * My Feedback
     */
    public function index(Request $request)
    {
        $feedbacks = $request->user()
            ->feedbacks()
            ->latest()
            ->paginate(20);

        return response()->json($feedbacks);
    }

    /**
     * View Feedback
     */
    public function show(
        Feedback $feedback,
        Request $request
    ) {

        if (
            $feedback->feedbacker_id != $request->user()->id ||
            $feedback->feedbacker_type != get_class($request->user())
        ) {
            abort(403);
        }

        return response()->json([
            'data' => $feedback->load([
                'feedbackable',
                'feedbacker',
            ]),
        ]);
    }

    /**
     * Update Feedback
     */
    public function update(
        Request $request,
        Feedback $feedback
    ) {
        if (
            $feedback->feedbacker_id != $request->user()->id ||
            $feedback->feedbacker_type != get_class($request->user())
        ) {
            abort(403);
        }

        $validator = Validator::make($request->all(), [

            'rating' => 'sometimes|integer|between:1,5',

            'title' => 'nullable|string|max:255',

            'comment' => 'nullable|string',

            'ratings' => 'nullable|array',

            'would_recommend' => 'boolean',

            'is_anonymous' => 'boolean',

        ]);

        if ($validator->fails()) {

            return response()->json([
                'errors' => $validator->errors(),
            ], 422);

        }

        $feedback = $this->feedbackService->update(
            $feedback,
            $request->all()
        );

        return response()->json([
            'message' => 'Feedback updated successfully.',
            'data' => $feedback,
        ]);
    }

    /**
     * Delete Feedback
     */
    public function destroy(
        Feedback $feedback,
        Request $request
    ) {
        if (
            $feedback->feedbacker_id != $request->user()->id ||
            $feedback->feedbacker_type != get_class($request->user())
        ) {
            abort(403);
        }

        $feedback->delete();

        return response()->json([
            'message' => 'Feedback deleted successfully.',
        ]);
    }
}