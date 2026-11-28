<?php

namespace App\Http\Controllers;

use App\Models\BootcampCbt;
use App\Models\BootcampCbtAttempt;
use App\Models\BootcampParticipant;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PublicBootcampCbtController extends Controller
{
    /**
     * Public metadata for an unlisted Bootcamp CBT link (never exposes questions or answers).
     */
    public function show(string $token): JsonResponse
    {
        $cbt = BootcampCbt::withCount('questions')->where('access_token', $token)->first();

        if (!$cbt) {
            return response()->json([
                'success' => false,
                'message' => 'This CBT link is invalid or no longer exists.',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $this->formatCbtMeta($cbt),
        ]);
    }

    /**
     * First-time registration using Full Name + Phone Number.
     * Generates and returns a 4-digit secret PIN once for the student to keep.
     */
    public function register(Request $request, string $token): JsonResponse
    {
        $cbt = BootcampCbt::withCount('questions')->where('access_token', $token)->first();
        if (!$cbt) {
            return response()->json(['success' => false, 'message' => 'Invalid CBT link.'], 404);
        }

        $validated = $request->validate([
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:32'],
        ]);

        $phoneNormalized = BootcampParticipant::normalizePhone($validated['phone']);
        if (strlen($phoneNormalized) < 7) {
            return response()->json([
                'success' => false,
                'message' => 'Please enter a valid phone number.',
                'errors' => ['phone' => ['Please enter a valid phone number.']],
            ], 422);
        }

        $existing = BootcampParticipant::where('phone_normalized', $phoneNormalized)->first();
        if ($existing) {
            return response()->json([
                'success' => false,
                'code' => 'ALREADY_REGISTERED',
                'message' => 'This phone number is already registered. Please sign in as a returning student with your 4-digit secret PIN.',
            ], 409);
        }

        $secretPin = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        $participant = BootcampParticipant::create([
            'phone_normalized' => $phoneNormalized,
            'phone_raw' => trim($validated['phone']),
            'full_name' => preg_replace('/\s+/', ' ', trim($validated['full_name'])),
            'pin_hash' => Hash::make($secretPin),
            'last_seen_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Registration successful. Save your 4-digit secret PIN to access future CBTs.',
            'data' => [
                'cbt' => $this->formatCbtMeta($cbt),
                'participant' => $this->formatParticipant($participant),
                'secret_pin' => $secretPin,
                'attempt' => null,
                'record' => $this->buildSubjectRecord($participant, $cbt),
                'leaderboard' => $this->buildLeaderboard($cbt, $participant->id),
                'review' => [],
            ],
        ], 201);
    }

    /**
     * Returning student login using Phone Number + 4-digit secret PIN.
     */
    public function login(Request $request, string $token): JsonResponse
    {
        $cbt = BootcampCbt::withCount('questions')->where('access_token', $token)->first();
        if (!$cbt) {
            return response()->json(['success' => false, 'message' => 'Invalid CBT link.'], 404);
        }

        $participant = $this->authenticateParticipant($request);
        if ($participant instanceof JsonResponse) {
            return $participant;
        }

        $attempt = BootcampCbtAttempt::where('bootcamp_cbt_id', $cbt->id)
            ->where('participant_id', $participant->id)
            ->first();

        $review = ($attempt && $attempt->submitted_at)
            ? $this->buildReview($cbt, $attempt)
            : [];

        return response()->json([
            'success' => true,
            'data' => [
                'cbt' => $this->formatCbtMeta($cbt),
                'participant' => $this->formatParticipant($participant),
                'attempt' => $attempt ? $this->formatAttempt($attempt) : null,
                'record' => $this->buildSubjectRecord($participant, $cbt),
                'leaderboard' => $this->buildLeaderboard($cbt, $participant->id),
                'review' => $review,
            ],
        ]);
    }

    /**
     * Start or resume an in-progress CBT attempt. Returns questions WITHOUT answers.
     */
    public function start(Request $request, string $token): JsonResponse
    {
        $cbt = BootcampCbt::with('questions')->where('access_token', $token)->first();
        if (!$cbt) {
            return response()->json(['success' => false, 'message' => 'Invalid CBT link.'], 404);
        }

        if (!$cbt->isCurrentlyOpen()) {
            return response()->json([
                'success' => false,
                'message' => 'This CBT is currently closed or has expired.',
            ], 403);
        }

        if ($cbt->questions->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'Questions have not been published for this CBT yet.',
            ], 422);
        }

        $participant = $this->authenticateParticipant($request);
        if ($participant instanceof JsonResponse) {
            return $participant;
        }

        $attempt = BootcampCbtAttempt::where('bootcamp_cbt_id', $cbt->id)
            ->where('participant_id', $participant->id)
            ->first();

        if ($attempt && $attempt->submitted_at !== null) {
            return response()->json([
                'success' => false,
                'code' => 'ALREADY_COMPLETED',
                'message' => 'You have already completed this CBT. Multiple attempts are not allowed.',
            ], 409);
        }

        $totalDurationSeconds = max(60, ((int) $cbt->duration_minutes) * 60);

        if (!$attempt) {
            $attempt = BootcampCbtAttempt::create([
                'bootcamp_cbt_id' => $cbt->id,
                'participant_id' => $participant->id,
                'answers' => [],
                'score' => 0,
                'total_questions' => $cbt->questions->count(),
                'time_taken_seconds' => 0,
                'started_at' => now(),
            ]);
            $remainingSeconds = $totalDurationSeconds;
        } else {
            $elapsed = $attempt->started_at ? max(0, (int) $attempt->started_at->diffInSeconds(now())) : 0;
            $remainingSeconds = max(0, $totalDurationSeconds - $elapsed);
        }

        $questionsPayload = $cbt->questions->map(function ($q, $idx) {
            return [
                'id' => $q->id,
                'number' => $idx + 1,
                'passage_text' => $q->passage_text,
                'question_text' => $q->question_text,
                'image_url' => $q->image_url,
                'options' => $q->options,
            ];
        })->values();

        return response()->json([
            'success' => true,
            'data' => [
                'attempt' => $this->formatAttempt($attempt),
                'remaining_seconds' => $remainingSeconds,
                'questions' => $questionsPayload,
            ],
        ]);
    }

    /**
     * Submit CBT answers, grade strictly server-side, and return score + record + leaderboard.
     */
    public function submit(Request $request, string $token): JsonResponse
    {
        $cbt = BootcampCbt::with('questions')->where('access_token', $token)->first();
        if (!$cbt) {
            return response()->json(['success' => false, 'message' => 'Invalid CBT link.'], 404);
        }

        $participant = $this->authenticateParticipant($request);
        if ($participant instanceof JsonResponse) {
            return $participant;
        }

        $validated = $request->validate([
            'answers' => ['nullable', 'array'],
        ]);

        $submittedAnswers = $validated['answers'] ?? [];

        $result = DB::transaction(function () use ($cbt, $participant, $submittedAnswers) {
            $attempt = BootcampCbtAttempt::where('bootcamp_cbt_id', $cbt->id)
                ->where('participant_id', $participant->id)
                ->lockForUpdate()
                ->first();

            if ($attempt && $attempt->submitted_at !== null) {
                return ['already_submitted' => true, 'attempt' => $attempt];
            }

            $questions = $cbt->questions;
            $totalQuestions = $questions->count();
            $correctCount = 0;
            $normalizedAnswers = [];

            foreach ($questions as $question) {
                $qKey = (string) $question->id;
                $selected = $submittedAnswers[$qKey] ?? $submittedAnswers[$question->id] ?? null;
                $selectedNorm = $selected !== null ? strtoupper(trim((string) $selected)) : null;

                if ($selectedNorm !== null && $selectedNorm !== '') {
                    $normalizedAnswers[$qKey] = $selectedNorm;
                    if ($selectedNorm === strtoupper(trim((string) $question->correct_option))) {
                        $correctCount++;
                    }
                }
            }

            $now = now();
            $maxSeconds = max(60, ((int) $cbt->duration_minutes) * 60);
            $startedAt = $attempt?->started_at ?? $now;
            $elapsedSeconds = max(1, min($maxSeconds, (int) $startedAt->diffInSeconds($now)));

            if (!$attempt) {
                $attempt = BootcampCbtAttempt::create([
                    'bootcamp_cbt_id' => $cbt->id,
                    'participant_id' => $participant->id,
                    'answers' => $normalizedAnswers,
                    'score' => $correctCount,
                    'total_questions' => $totalQuestions,
                    'time_taken_seconds' => $elapsedSeconds,
                    'started_at' => $startedAt,
                    'submitted_at' => $now,
                ]);
            } else {
                $attempt->update([
                    'answers' => $normalizedAnswers,
                    'score' => $correctCount,
                    'total_questions' => $totalQuestions,
                    'time_taken_seconds' => $elapsedSeconds,
                    'submitted_at' => $now,
                ]);
            }

            return ['already_submitted' => false, 'attempt' => $attempt->fresh()];
        });

        if ($result['already_submitted']) {
            return response()->json([
                'success' => false,
                'code' => 'ALREADY_COMPLETED',
                'message' => 'This CBT has already been submitted.',
            ], 409);
        }

        $attempt = $result['attempt'];

        return response()->json([
            'success' => true,
            'message' => 'CBT submitted successfully.',
            'data' => [
                'cbt' => $this->formatCbtMeta($cbt),
                'participant' => $this->formatParticipant($participant),
                'attempt' => $this->formatAttempt($attempt),
                'record' => $this->buildSubjectRecord($participant, $cbt),
                'leaderboard' => $this->buildLeaderboard($cbt, $participant->id),
                'review' => $this->buildReview($cbt, $attempt),
            ],
        ]);
    }

    private function authenticateParticipant(Request $request): BootcampParticipant|JsonResponse
    {
        $validated = $request->validate([
            'phone' => ['required', 'string', 'min:7', 'max:32'],
            'pin' => ['required', 'string', 'size:4'],
        ]);

        $phoneNormalized = BootcampParticipant::normalizePhone($validated['phone']);
        $participant = BootcampParticipant::where('phone_normalized', $phoneNormalized)->first();

        if (!$participant || !Hash::check(trim($validated['pin']), $participant->pin_hash)) {
            return response()->json([
                'success' => false,
                'message' => 'Phone number or 4-digit secret PIN is incorrect.',
            ], 422);
        }

        $participant->update(['last_seen_at' => now()]);

        return $participant;
    }

    private function formatCbtMeta(BootcampCbt $cbt): array
    {
        $closedReason = null;
        if (!$cbt->is_active) {
            $closedReason = 'inactive';
        } elseif ($cbt->starts_at && now()->lt($cbt->starts_at)) {
            $closedReason = 'not_started';
        } elseif ($cbt->expires_at && now()->gt($cbt->expires_at)) {
            $closedReason = 'expired';
        }

        return [
            'id' => $cbt->id,
            'title' => $cbt->title,
            'subject_track' => $cbt->subject_track,
            'subject_label' => $cbt->subject_label,
            'week_start_date' => optional($cbt->week_start_date)->format('Y-m-d'),
            'duration_minutes' => $cbt->duration_minutes,
            'questions_count' => $cbt->questions_count ?? $cbt->questions()->count(),
            'starts_at' => $cbt->starts_at?->toIso8601String(),
            'expires_at' => $cbt->expires_at?->toIso8601String(),
            'is_open' => $cbt->isCurrentlyOpen(),
            'closed_reason' => $closedReason,
        ];
    }

    private function formatParticipant(BootcampParticipant $participant): array
    {
        return [
            'id' => $participant->id,
            'full_name' => $participant->full_name,
            'phone' => $participant->phone_raw,
        ];
    }

    private function formatAttempt(BootcampCbtAttempt $attempt): array
    {
        return [
            'id' => $attempt->id,
            'score' => $attempt->score,
            'total_questions' => $attempt->total_questions,
            'time_taken_seconds' => $attempt->time_taken_seconds,
            'started_at' => $attempt->started_at?->toIso8601String(),
            'submitted_at' => $attempt->submitted_at?->toIso8601String(),
            'is_completed' => $attempt->submitted_at !== null,
        ];
    }

    /**
     * Strictly returns ONLY "This Week" and "Last Week" scores for this CBT's subject track.
     */
    private function buildSubjectRecord(BootcampParticipant $participant, BootcampCbt $cbt): array
    {
        $thisWeekStart = Carbon::parse($cbt->week_start_date)->startOfDay();
        $thisWeekEnd = $thisWeekStart->copy()->addDays(6)->endOfDay();
        $lastWeekStart = $thisWeekStart->copy()->subDays(7)->startOfDay();
        $lastWeekEnd = $thisWeekStart->copy()->subDay()->endOfDay();

        $attempts = BootcampCbtAttempt::query()
            ->with('cbt:id,title,subject_track,week_start_date')
            ->where('participant_id', $participant->id)
            ->whereNotNull('submitted_at')
            ->whereHas('cbt', function ($q) use ($cbt, $lastWeekStart, $thisWeekEnd) {
                $q->where('subject_track', $cbt->subject_track)
                  ->whereBetween('week_start_date', [
                      $lastWeekStart->toDateString(),
                      $thisWeekEnd->toDateString(),
                  ]);
            })
            ->orderByDesc('submitted_at')
            ->get();

        $thisWeekAttempt = $attempts->first(function (BootcampCbtAttempt $a) use ($thisWeekStart, $thisWeekEnd, $cbt) {
            if ($a->bootcamp_cbt_id === $cbt->id) {
                return true;
            }
            $wDate = $a->cbt?->week_start_date;
            return $wDate && $wDate->between($thisWeekStart, $thisWeekEnd);
        });

        $lastWeekAttempt = $attempts->first(function (BootcampCbtAttempt $a) use ($lastWeekStart, $lastWeekEnd) {
            $wDate = $a->cbt?->week_start_date;
            return $wDate && $wDate->between($lastWeekStart, $lastWeekEnd);
        });

        $formatRecord = static function (?BootcampCbtAttempt $a): ?array {
            if (!$a) {
                return null;
            }
            return [
                'cbt_id' => $a->bootcamp_cbt_id,
                'title' => $a->cbt?->title,
                'week_start_date' => optional($a->cbt?->week_start_date)->format('Y-m-d'),
                'score' => $a->score,
                'total_questions' => $a->total_questions,
                'time_taken_seconds' => $a->time_taken_seconds,
                'submitted_at' => $a->submitted_at?->toIso8601String(),
            ];
        };

        return [
            'subject_track' => $cbt->subject_track,
            'subject_label' => $cbt->subject_label,
            'this_week' => $formatRecord($thisWeekAttempt),
            'last_week' => $formatRecord($lastWeekAttempt),
        ];
    }

    /**
     * Builds Top 3 leaderboard + caller's own ranked entry (so #4+ users see themselves right after Top 3).
     */
    private function buildLeaderboard(BootcampCbt $cbt, ?int $participantId = null): array
    {
        $ranked = BootcampCbtAttempt::query()
            ->with('participant:id,full_name')
            ->where('bootcamp_cbt_id', $cbt->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('score')
            ->orderBy('time_taken_seconds')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(function (BootcampCbtAttempt $attempt, int $index) use ($participantId) {
                return [
                    'rank' => $index + 1,
                    'participant_id' => $attempt->participant_id,
                    'full_name' => $attempt->participant?->full_name ?? 'Participant',
                    'score' => $attempt->score,
                    'total_questions' => $attempt->total_questions,
                    'time_taken_seconds' => $attempt->time_taken_seconds,
                    'is_me' => $participantId !== null && $attempt->participant_id === $participantId,
                ];
            });

        $myEntry = $participantId !== null
            ? $ranked->firstWhere('participant_id', $participantId)
            : null;

        return [
            'top_three' => $ranked->take(3)->values()->all(),
            'my_entry' => $myEntry,
            'total_participants' => $ranked->count(),
        ];
    }

    private function buildReview(BootcampCbt $cbt, BootcampCbtAttempt $attempt): array
    {
        $cbt->loadMissing('questions');
        $answers = $attempt->answers ?? [];

        return $cbt->questions->values()->map(function ($question, $idx) use ($answers) {
            $qKey = (string) $question->id;
            $selected = $answers[$qKey] ?? null;
            $correct = strtoupper(trim((string) $question->correct_option));

            return [
                'id' => $question->id,
                'number' => $idx + 1,
                'passage_text' => $question->passage_text,
                'question_text' => $question->question_text,
                'image_url' => $question->image_url,
                'options' => $question->options,
                'selected_option' => $selected,
                'correct_option' => $correct,
                'is_correct' => $selected !== null && strtoupper(trim((string) $selected)) === $correct,
                'explanation' => $question->explanation,
            ];
        })->all();
    }
}
