<?php

namespace App\Http\Controllers;

use App\Models\Subject;
use App\Models\SubjectsEnrollment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class SubjectController extends Controller
{
    /**
     * Admin: View all subjects (including inactive)
     */
    public function allSubjects()
    {
        $subjects = Subject::latest()->get();

        return response()->json([
            'subjects' => $subjects,
        ]);
    }

    /**
     * Create new subject (Admin)
     */

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'banner' => 'required|image|mimes:jpg,jpeg,png|max:2048',

            'departments' => 'required|array|min:1',
            'departments.*' => 'string|max:100',

            'courses' => 'nullable|array',
            'courses.*' => 'exists:courses,id',

            'status' => 'required|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {

            /*
            |--------------------------------------------------------------------------
            | 1. Prevent Duplicate Subject (same name + departments)
            |--------------------------------------------------------------------------
            */

            $existing = Subject::where('name', $request->name)
                ->whereJsonContains('departments', $request->departments)
                ->first();

            if ($existing) {
                return response()->json([
                    'message' => 'Subject already exists for selected departments',
                ], 409);
            }

            /*
            |--------------------------------------------------------------------------
            | 2. Upload Banner
            |--------------------------------------------------------------------------
            */

            $bannerPath = $request->file('banner')->store('subject_banners', 'public');

            /*
            |--------------------------------------------------------------------------
            | 3. Create Subject
            |--------------------------------------------------------------------------
            */

            $subject = Subject::create([
                'name' => $request->name,
                'description' => $request->description,
                'banner' => $bannerPath,
                'departments' => array_values($request->departments),
                'status' => $request->status,
            ]);

            /*
            |--------------------------------------------------------------------------
            | 4. Attach Courses (Many-to-Many)
            |--------------------------------------------------------------------------
            */

            if ($request->filled('courses')) {
                $subject->courses()->sync($request->courses);
            }

            DB::commit();

            return response()->json([
                'message' => 'Subject created successfully.',
                'subject' => $subject->load('courses'),
            ], 201);

        } catch (\Throwable $e) {

            DB::rollBack();

            return response()->json([
                'message' => 'Failed to create subject.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    // public function store(Request $request)
    // {
    //     $validator = Validator::make($request->all(), [
    //         'name' => 'required|string|max:255',
    //         'description' => 'required|string',
    //         'banner' => 'required|image|mimes:jpg,jpeg,png|max:2048',
    //         'department' => 'required|array',
    //         'status' => 'required|in:active,inactive',
    //     ]);

    //     if ($validator->fails()) {
    //         return response()->json([
    //             'errors' => $validator->errors(),
    //         ], 422);
    //     }

    //     DB::beginTransaction();

    //     try {
    //         $bannerPath = $request->file('banner')->store('subject_banners', 'public');

    //         $subject = Subject::create([
    //             'name' => $request->name,
    //             'description' => $request->description,
    //             'banner' => $bannerPath,
    //             'department' => $request->department,
    //             'status' => $request->status,
    //         ]);

    //         DB::commit();

    //         return response()->json([
    //             'message' => 'Subject created successfully.',
    //             'subject' => $subject,
    //         ], 201);

    //     } catch (\Throwable $e) {
    //         DB::rollBack();

    //         return response()->json([
    //             'message' => 'Failed to create subject.',
    //             'error' => config('app.debug') ? $e->getMessage() : null,
    //         ], 500);
    //     }
    // }

    /**
     * Update subject (Admin)
     */
    public function update(Request $request, $id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'message' => 'Subject not found.',
            ], 404);
        }

        $validator = Validator::make($request->all(), [
            'name' => 'nullable|string|max:255',
            'description' => 'nullable|string',
            'banner' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
            'department' => 'nullable|in:science,commercial,art,general',
            'status' => 'nullable|in:active,inactive',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        DB::beginTransaction();

        try {
            $data = $validator->validated();

            if ($request->hasFile('banner')) {
                $data['banner'] = $request->file('banner')->store('subject_banners', 'public');
            }

            $subject->update($data);

            DB::commit();

            return response()->json([
                'message' => 'Subject updated successfully.',
                'subject' => $subject->fresh(),
            ]);

        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'message' => 'Failed to update subject.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /**
     * Soft delete subject (Admin)
     */
    public function destroy($id)
    {
        $subject = Subject::find($id);

        if (!$subject) {
            return response()->json([
                'message' => 'Subject not found.',
            ], 404);
        }

        $subject->delete();

        return response()->json([
            'message' => 'Subject deleted successfully.',
        ]);
    }

    /**
     * Restore soft-deleted subject (Admin)
     */
    public function restore($id)
    {
        $subject = Subject::onlyTrashed()->find($id);

        if (!$subject) {
            return response()->json([
                'message' => 'Subject not found or not deleted.',
            ], 404);
        }

        $subject->restore();

        return response()->json([
            'message' => 'Subject restored successfully.',
            'subject' => $subject,
        ]);
    }

    /*
     * Public Method: List all active subjects
     */
    public function index()
    {
        $subjects = Subject::where('status', 'active')->get();

        return response()->json([
            'subjects' => $subjects,
        ]);
    }

    /*
     * Public Method: List subjects by course
     */
    public function subjectsByCourse(int $courseId)
    {
        try {
            $subjects = Subject::where('status', 'active')
                ->whereJsonContains('courses', $courseId)
                ->get();

            return response()->json([
                'message' => 'Subjects fetched successfully.',
                'subjects' => $subjects,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve subjects.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }


    /*
     * Public Method: List subjects by course and department
     */
    public function subjectsByCourseAndDepartment(int $courseId, string $department)
    {
        try {
            $subjects = Subject::query()
                ->where('status', 'active')
                ->whereJsonContains('courses', $courseId)
                ->whereJsonContains('departments', $department)
                ->get();

            return response()->json([
                'message' => 'Subjects fetched successfully.',
                'subjects' => $subjects,
            ], 200);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to retrieve subjects.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }

    /*
     * Public Method: Subject enrollment
     */
    public function subjectEnroll(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'course_enrollment_id' => 'required|exists:courses_enrollments,id',
            'subject_id' => 'required|exists:subjects,id',
            'student_id' => 'required|exists:students,id',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'errors' => $validator->errors(),
            ], 422);
        }

        try {
            // Verify subject enrollment exists already for that course and that student
            $existingEnrollment = SubjectsEnrollment::where('course_enrollment_id', $request->course_enrollment_id)
                ->where('subject_id', $request->subject_id)
                ->where('student_id', $request->student_id)
                ->first();
            if ($existingEnrollment) {
                return response()->json([
                    'message' => 'Subject already enrolled for this course and student.',
                ], 409);
            }

            // Create subject enrollment
            SubjectsEnrollment::create([
                'course_enrollment_id' => $request->course_enrollment_id,
                'subject_id' => $request->subject_id,
                'student_id' => $request->student_id,
            ]);

            return response()->json([
                'message' => 'Subject enrolled successfully.',
            ], 201);

        } catch (\Throwable $e) {
            return response()->json([
                'message' => 'Failed to enroll subject.',
                'error' => config('app.debug') ? $e->getMessage() : null,
            ], 500);
        }
    }
}
