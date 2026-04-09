<?php

use App\Http\Controllers\AttendanceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\StaffController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\SubjectController;
use App\Http\Controllers\ClassesController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\GuardianController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
*/
Route::get('/courses', [CourseController::class, 'index']); // Public: List all active courses
Route::get('/subjects', [SubjectController::class, 'index']); // Public: List all active subjects
Route::post('/course/enrollment', [CourseController::class, 'courseEnroll']); // Public: Enroll in a course
Route::post('/subject/enrollment', [SubjectController::class, 'subjectEnroll']); // Public: Enroll in a subject
Route::get('/courses/{courseId}/subjects', [SubjectController::class, 'subjectsByCourse']); // Public: List subjects by course
Route::get('/courses/{courseId}/subjects/{department}', [SubjectController::class, 'subjectsByCourseAndDepartment']); // Public: List subjects by course and department
Route::post('payments', [PaymentController::class, 'store']); // Public: Process payment

/*
|--------------------------------------------------------------------------
| Student Public Routes
|--------------------------------------------------------------------------
*/
Route::prefix('students')->group(function () {
    Route::post('/login', [StudentController::class, 'login']); // Login Method
    // Route::post('/register', [StudentController::class, 'store']); // Registration
    // Route::post('/biodata', [StudentController::class, 'biodata']); // Biodata completion (NO AUTH REQUIRED, but verification enforced)
    Route::post('/verify-email', [StudentController::class, 'verifyEmail']); // Email verification
    Route::post('/enroll-course', [CourseController::class, 'courseEnroll']); // Course enrollment
    Route::post('/verify-phone', [StudentController::class, 'verifyPhoneOtp']); // Phone OTP verification
    Route::post('/register', [StudentController::class, 'registerWithBiodata']); // Registration
    Route::post('/forget-password', [StudentController::class, 'forgetPassword']); // Forget Password
    Route::post('/change-password', [StudentController::class, 'changePassword']); // Change Password with OTP
    Route::post('/resend-phone-otp', [StudentController::class, 'resendPhoneOtp']); // Resend Phone Verification
    Route::post('/resend-email-verification', [StudentController::class, 'resendEmailVerification']); // Resend Email Verification
});

/*
|--------------------------------------------------------------------------
| Student Protected Routes
|--------------------------------------------------------------------------
*/
Route::prefix('students')->middleware('auth:sanctum')->group(function () {
    Route::post('/logout', [StudentController::class, 'logout']); // Logout Method
    Route::put('/profile/update', [StudentController::class, 'update']); // Update student profile
    Route::get('/payments', [PaymentController::class, 'myPayments']); // Listing out all payments
    Route::post('/attendance', [AttendanceController::class, 'store']); // Record attendance for a class session
    Route::get('/courses', [CourseController::class, 'getActiveCourses']); // Get Active Courses and Subject 
    Route::get('/class/schedule', [ClassesController::class, 'studentClassSchedule']); // Get student schedule with attendance status
    Route::get('/calendar/schedule', [ClassesController::class, 'studentCalenderSchedule']); // Get student schedule (classes and sessions)
    Route::post('/courses/disenroll/{courseId}', [CourseController::class, 'disenrollCourse']); // Course disenrollment
    Route::post('/contact/change/request', [StudentController::class, 'requestContactChange']); // Request contact change (phone or email)
    Route::post('/contact/change/confirm', [StudentController::class, 'confirmContactChange']); // Verify contact change with OTP
    // Route::post('/phone/change/resend-otp', [StudentController::class, 'resendPhoneChangeOtp']); // Resend OTP for phone number change
});

Route::prefix('students')->group(function () {
    Route::post('/phone/change/confirm', [StudentController::class, 'confirmPhoneNumberChange']); // Confirm phone number change with OTP verification
});

/*
|--------------------------------------------------------------------------
| Guardian Registration & Verification
|--------------------------------------------------------------------------
*/
Route::prefix('guardians')->group(function () {
    Route::post('/register', [GuardianController::class, 'store']);
    Route::post('/verify-email', [GuardianController::class, 'verifyEmail']);

    Route::post('/verify-phone', [GuardianController::class, 'verifyPhoneOtp']);
    Route::post('/resend-phone-otp', [GuardianController::class, 'resendPhoneOtp']);

    Route::post('/resend-email', [GuardianController::class, 'resendEmailVerification']);
});

/*
|--------------------------------------------------------------------------
| Staff Registration Verification
|--------------------------------------------------------------------------
*/
Route::prefix('staffs')->group(function () {
    // Login (restricted until verified)
    Route::post('/login', [StaffController::class, 'login']);

    // Email verification
    Route::post('/verify-email', [StaffController::class, 'verifyEmail']);
    Route::post('/resend-email-verification', [StaffController::class, 'resendEmailVerification']);

    // Phone OTP verification
    Route::post('/verify-phone', [StaffController::class, 'verifyPhoneOtp']);
    Route::post('/resend-phone-otp', [StaffController::class, 'resendPhoneOtp']);

    Route::middleware('auth:staff')->group(function () {
        // Logout
        Route::post('/logout', [StaffController::class, 'logout']);

        // Classes
        Route::post('/classes/session/recording', [ClassesController::class, 'updateSessionRecording']); // Update recording link for a session
    });

});

/*
 * Admin Only Protected Routes (enforced in controller)
 */
Route::prefix('admin')->middleware(['auth:sanctum', 'auth:staff', 'staff.role:admin'])->group(function () {
    //Staffs Management
    Route::prefix('staffs')->group(function () {
        Route::get('/all', [StaffController::class, 'index']); // List all staff members 
        Route::get('/{id}', [StaffController::class, 'show']); // View a specific staff member's details
        Route::post('/register', [StaffController::class, 'store']); // Registration a new Staff
        Route::put('/update/{id}', [StaffController::class, 'update']); // Update staff member details
        Route::post('/restore/{id}', [StaffController::class, 'restore']); // Restore a soft-deleted staff member
        Route::delete('/destroy/{id}', [StaffController::class, 'destroy']); // Soft delete a staff member
        Route::post('/active', [StaffController::class, 'activeStaffs']); // Test Route
    }); 

    // Course Management
    Route::prefix('courses')->group(function () {
        Route::post('/', [CourseController::class, 'store']);
        Route::put('/update/{id}', [CourseController::class, 'update']);
        Route::delete('/destroy/{id}', [CourseController::class, 'destroy']);
        Route::post('/restore/{id}', [CourseController::class, 'restore']);
        Route::get('/disenrollments', [CourseController::class, 'getDisenrolledCourses']); // List all course disenrollments
    });

    // Subject Management
    Route::prefix('subjects')->group(function () {
        Route::get('/all', [SubjectController::class, 'allSubjects']); // View all subjects (including inactive)
        Route::post('/', [SubjectController::class, 'store']); // Create new subject
        Route::put('/update/{id}', [SubjectController::class, 'update']); // Update subject
        Route::delete('/destroy/{id}', [SubjectController::class, 'destroy']); // Soft delete subject
        Route::post('/restore/{id}', [SubjectController::class, 'restore']); // Restore soft-deleted subject
    });

    // Classes Management
    Route::prefix('classes')->group(function () {
        Route::post('/create', [ClassesController::class, 'store']); // Create class, class schedule, assign staff to class and class sessions
        Route::get('/all', [ClassesController::class, 'allClassesSchedule']); // List all classes
    });

});

/*
 * Tutor Only Protected Routes (enforced in controller)
 */
Route::prefix('tutor')->middleware(['auth:sanctum', 'auth:staff', 'staff.role:tutor'])->group(function () {
    Route::prefix('classes')->group(function () {
        Route::get('/schedule', [ClassesController::class, 'tutorClassesSchedule']); // Get tutor schedule with attendance status     
    });
});

/*
 * Tutor Only Protected Routes (enforced in controller)
 */
Route::prefix('advisor')->middleware(['auth:sanctum', 'auth:staff', 'staff.role:advisor'])->group(function () {
    Route::prefix('classes')->group(function () {
        Route::get('/schedule', [ClassesController::class, 'advisorClassesSchedule']); // Get advisor schedule with attendance status     
    });
});




