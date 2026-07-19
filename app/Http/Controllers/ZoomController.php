<?php

namespace App\Http\Controllers;

use App\Models\ClassSession;
use App\Models\ClassStaff;
use App\Services\ZoomService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ZoomController extends Controller
{
    protected ZoomService $zoomService;

    public function __construct(ZoomService $zoomService)
    {
        $this->zoomService = $zoomService;
    }

    public function generateSignature(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'class_session_id' => 'required|exists:class_sessions,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $session = ClassSession::findOrFail($request->class_session_id);
            $class = $session->class;

            if (!$class->zoom_meeting_id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This class session does not have a Zoom meeting configured.',
                ], 400);
            }

            $user = auth('sanctum')->user() ?: auth('staff')->user();

            if (!$user) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized.',
                ], 401);
            }

            $role = 0;

            if ($user instanceof \App\Models\Student) {
                if (!$user->enrolledInSubject($class->subject_id)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not enrolled in this subject.',
                    ], 403);
                }
                $role = 0;
            } elseif ($user instanceof \App\Models\Staff) {
                $roleName = strtolower($user->role);
                $isHostRole = in_array($roleName, ['admin', 'advisor', 'courseadvisor', 'course_advisor']);
                
                $classStaff = ClassStaff::where('class_id', $class->id)
                    ->where('staff_id', $user->id)
                    ->first();

                if (!$isHostRole && !$classStaff) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Not authorized to access this class.',
                    ], 403);
                }

                $pivotRole = $classStaff ? strtolower($classStaff->role) : null;
                if ($isHostRole || $pivotRole === 'advisor') {
                    $role = 1; // Join as Host (Advisor/Admin)
                } else {
                    $role = 0; // Join as Participant (Tutor/Teacher)
                }
            } else {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized user type.',
                ], 401);
            }

            $signature = $this->zoomService->generateSignature(
                $class->zoom_meeting_id,
                $role
            );

            return response()->json([
                'success' => true,
                'signature' => $signature,
                'meeting_number' => $class->zoom_meeting_id,
                'password' => $class->zoom_meeting_password,
                'sdk_key' => config('services.zoom.sdk_key'),
                'role' => $role,
                'user_name' => $user->firstname . ' ' . $user->surname,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to generate Zoom signature.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}