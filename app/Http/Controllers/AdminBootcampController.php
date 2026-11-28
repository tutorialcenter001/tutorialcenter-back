<?php

namespace App\Http\Controllers;

use App\Models\BootcampCbt;
use App\Models\BootcampCbtAttempt;
use App\Models\BootcampCbtQuestion;
use App\Models\BootcampParticipant;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class AdminBootcampController extends Controller
{
    private const VALID_TRACKS = [
        'jamb_english',
        'olevel_english',
        'jamb_maths',
        'olevel_maths',
    ];

    /**
     * List all Bootcamp CBTs with question/attempt summary counts.
     */
    public function index(): JsonResponse
    {
        $cbts = BootcampCbt::query()
            ->withCount([
                'questions',
                'attempts as submitted_attempts_count' => function ($q) {
                    $q->whereNotNull('submitted_at');
                },
            ])
            ->orderByDesc('week_start_date')
            ->orderByDesc('id')
            ->get()
            ->map(function (BootcampCbt $cbt) {
                $topAttempt = BootcampCbtAttempt::query()
                    ->with('participant:id,full_name')
                    ->where('bootcamp_cbt_id', $cbt->id)
                    ->whereNotNull('submitted_at')
                    ->orderByDesc('score')
                    ->orderBy('time_taken_seconds')
                    ->orderBy('submitted_at')
                    ->first();

                return [
                    'id' => $cbt->id,
                    'title' => $cbt->title,
                    'subject_track' => $cbt->subject_track,
                    'subject_label' => $cbt->subject_label,
                    'week_start_date' => optional($cbt->week_start_date)->format('Y-m-d'),
                    'access_token' => $cbt->access_token,
                    'duration_minutes' => $cbt->duration_minutes,
                    'starts_at' => $cbt->starts_at?->toIso8601String(),
                    'expires_at' => $cbt->expires_at?->toIso8601String(),
                    'is_active' => $cbt->is_active,
                    'is_open' => $cbt->isCurrentlyOpen(),
                    'questions_count' => $cbt->questions_count,
                    'submitted_attempts_count' => $cbt->submitted_attempts_count,
                    'top_scorer' => $topAttempt ? [
                        'full_name' => $topAttempt->participant?->full_name,
                        'score' => $topAttempt->score,
                        'total_questions' => $topAttempt->total_questions,
                    ] : null,
                    'created_at' => $cbt->created_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => $cbts,
        ]);
    }

    /**
     * Show a single Bootcamp CBT with full editable questions and submitted results.
     */
    public function show(int $id): JsonResponse
    {
        $cbt = BootcampCbt::with('questions')->findOrFail($id);
        $cbt->questions->each->makeVisible(['correct_option', 'explanation']);

        $attempts = BootcampCbtAttempt::query()
            ->with('participant:id,full_name,phone_raw')
            ->where('bootcamp_cbt_id', $cbt->id)
            ->whereNotNull('submitted_at')
            ->orderByDesc('score')
            ->orderBy('time_taken_seconds')
            ->orderBy('submitted_at')
            ->orderBy('id')
            ->get()
            ->values()
            ->map(function (BootcampCbtAttempt $attempt, int $index) {
                return [
                    'rank' => $index + 1,
                    'id' => $attempt->id,
                    'participant_id' => $attempt->participant_id,
                    'full_name' => $attempt->participant?->full_name ?? 'Participant',
                    'phone' => $attempt->participant?->phone_raw ?? '',
                    'score' => $attempt->score,
                    'total_questions' => $attempt->total_questions,
                    'time_taken_seconds' => $attempt->time_taken_seconds,
                    'submitted_at' => $attempt->submitted_at?->toIso8601String(),
                ];
            });

        return response()->json([
            'success' => true,
            'data' => [
                'cbt' => $cbt,
                'questions' => $cbt->questions,
                'attempts' => $attempts,
            ],
        ]);
    }

    /**
     * Create a new Bootcamp CBT with optional questions.
     */
    public function store(Request $request): JsonResponse
    {
        $validated = $this->validateCbtPayload($request, false);

        $cbt = DB::transaction(function () use ($validated, $request) {
            $cbt = BootcampCbt::create([
                'title' => trim($validated['title']),
                'subject_track' => $validated['subject_track'],
                'week_start_date' => $validated['week_start_date'],
                'access_token' => BootcampCbt::generateUniqueToken(),
                'duration_minutes' => $validated['duration_minutes'] ?? 20,
                'starts_at' => $validated['starts_at'] ?? null,
                'expires_at' => $validated['expires_at'] ?? null,
                'is_active' => $validated['is_active'] ?? true,
                'created_by_staff_id' => $request->user()?->id,
            ]);

            if (!empty($validated['questions'])) {
                $this->syncQuestions($cbt, $validated['questions']);
            }

            return $cbt;
        });

        $cbt->load('questions');
        $cbt->questions->each->makeVisible(['correct_option', 'explanation']);

        return response()->json([
            'success' => true,
            'message' => 'Bootcamp CBT created successfully.',
            'data' => $cbt,
        ], 201);
    }

    /**
     * Update a Bootcamp CBT and optionally replace its questions.
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $cbt = BootcampCbt::findOrFail($id);
        $validated = $this->validateCbtPayload($request, true);

        DB::transaction(function () use ($cbt, $validated) {
            $updateData = [];
            foreach (['title', 'subject_track', 'week_start_date', 'duration_minutes', 'starts_at', 'expires_at', 'is_active'] as $field) {
                if (array_key_exists($field, $validated)) {
                    $updateData[$field] = is_string($validated[$field]) ? trim($validated[$field]) : $validated[$field];
                }
            }

            if (!empty($updateData)) {
                $cbt->update($updateData);
            }

            if (array_key_exists('questions', $validated)) {
                $this->syncQuestions($cbt, $validated['questions'] ?? []);
            }
        });

        $cbt->refresh()->load('questions');
        $cbt->questions->each->makeVisible(['correct_option', 'explanation']);

        return response()->json([
            'success' => true,
            'message' => 'Bootcamp CBT updated successfully.',
            'data' => $cbt,
        ]);
    }

    /**
     * Toggle active status of a Bootcamp CBT.
     */
    public function toggleActive(int $id): JsonResponse
    {
        $cbt = BootcampCbt::findOrFail($id);
        $cbt->update(['is_active' => !$cbt->is_active]);

        return response()->json([
            'success' => true,
            'message' => $cbt->is_active ? 'CBT link activated.' : 'CBT link paused.',
            'data' => $cbt,
        ]);
    }

    /**
     * Regenerate the unlisted access token (invalidating the previous link).
     */
    public function regenerateToken(int $id): JsonResponse
    {
        $cbt = BootcampCbt::findOrFail($id);
        $cbt->update(['access_token' => BootcampCbt::generateUniqueToken()]);

        return response()->json([
            'success' => true,
            'message' => 'A new unlisted CBT link has been generated.',
            'data' => $cbt,
        ]);
    }

    /**
     * Delete a Bootcamp CBT.
     */
    public function destroy(int $id): JsonResponse
    {
        $cbt = BootcampCbt::findOrFail($id);
        $cbt->delete();

        return response()->json([
            'success' => true,
            'message' => 'Bootcamp CBT deleted.',
        ]);
    }

    /**
     * List registered Bootcamp participants with search and attempt counts.
     */
    public function participants(Request $request): JsonResponse
    {
        $search = trim((string) $request->query('search', ''));

        $query = BootcampParticipant::query()
            ->withCount([
                'attempts as completed_cbts_count' => function ($q) {
                    $q->whereNotNull('submitted_at');
                },
            ])
            ->withSum([
                'attempts as total_points' => function ($q) {
                    $q->whereNotNull('submitted_at');
                },
            ], 'score')
            ->orderByDesc('last_seen_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $normalized = BootcampParticipant::normalizePhone($search);
            $query->where(function ($q) use ($search, $normalized) {
                $q->where('full_name', 'LIKE', "%{$search}%")
                  ->orWhere('phone_raw', 'LIKE', "%{$search}%");
                if ($normalized !== '') {
                    $q->orWhere('phone_normalized', 'LIKE', "%{$normalized}%");
                }
            });
        }

        $participants = $query->limit(200)->get()->map(function (BootcampParticipant $p) {
            return [
                'id' => $p->id,
                'full_name' => $p->full_name,
                'phone_raw' => $p->phone_raw,
                'completed_cbts_count' => (int) ($p->completed_cbts_count ?? 0),
                'total_points' => (int) ($p->total_points ?? 0),
                'last_seen_at' => $p->last_seen_at?->toIso8601String(),
                'created_at' => $p->created_at?->toIso8601String(),
            ];
        });

        return response()->json([
            'success' => true,
            'data' => $participants,
        ]);
    }

    /**
     * Reset a participant's 4-digit secret PIN so an admin can share it via WhatsApp.
     */
    public function resetParticipantPin(int $id): JsonResponse
    {
        $participant = BootcampParticipant::findOrFail($id);
        $newPin = str_pad((string) random_int(1000, 9999), 4, '0', STR_PAD_LEFT);

        $participant->update([
            'pin_hash' => Hash::make($newPin),
        ]);

        return response()->json([
            'success' => true,
            'message' => "New secret PIN generated for {$participant->full_name}.",
            'data' => [
                'participant_id' => $participant->id,
                'full_name' => $participant->full_name,
                'phone_raw' => $participant->phone_raw,
                'new_pin' => $newPin,
            ],
        ]);
    }

    /**
     * Upload an optional question diagram image for Bootcamp CBTs.
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $request->validate([
            'image' => ['required', 'file', 'image', 'mimes:jpeg,png,jpg,webp,gif,svg', 'max:5120'],
        ]);

        $path = $request->file('image')->store('bootcamp_cbt_images', 'public');
        $relativeUrl = Storage::url($path);
        $fullUrl = asset($relativeUrl);

        return response()->json([
            'success' => true,
            'path' => $path,
            'relative_url' => $relativeUrl,
            'url' => $fullUrl,
        ], 201);
    }

    private function validateCbtPayload(Request $request, bool $isUpdate): array
    {
        $reqRule = $isUpdate ? 'sometimes' : 'required';

        return $request->validate([
            'title' => [$reqRule, 'string', 'max:255'],
            'subject_track' => [$reqRule, 'string', Rule::in(self::VALID_TRACKS)],
            'week_start_date' => [$reqRule, 'date'],
            'duration_minutes' => ['nullable', 'integer', 'min:1', 'max:180'],
            'starts_at' => ['nullable', 'date'],
            'expires_at' => ['nullable', 'date'],
            'is_active' => ['nullable', 'boolean'],
            'questions' => ['nullable', 'array'],
            'questions.*.id' => ['nullable', 'integer'],
            'questions.*.passage_text' => ['nullable', 'string'],
            'questions.*.question_text' => ['required_with:questions', 'string'],
            'questions.*.image_url' => ['nullable', 'string', 'max:500'],
            'questions.*.options' => ['required_with:questions', 'array', 'min:2', 'max:6'],
            'questions.*.options.*.key' => ['required', 'string', 'max:10'],
            'questions.*.options.*.text' => ['required', 'string'],
            'questions.*.correct_option' => ['required_with:questions', 'string', 'max:10'],
            'questions.*.explanation' => ['nullable', 'string'],
        ]);
    }

    private function syncQuestions(BootcampCbt $cbt, array $questions): void
    {
        $existingQuestions = $cbt->questions()->get()->keyBy('id');
        $retainedIds = [];

        foreach (array_values($questions) as $index => $q) {
            $options = array_values(array_map(function ($opt) {
                return [
                    'key' => strtoupper(trim((string) ($opt['key'] ?? ''))),
                    'text' => trim((string) ($opt['text'] ?? '')),
                ];
            }, $q['options'] ?? []));

            $attributes = [
                'passage_text' => isset($q['passage_text']) && trim((string) $q['passage_text']) !== ''
                    ? trim((string) $q['passage_text'])
                    : null,
                'question_text' => trim((string) $q['question_text']),
                'image_url' => isset($q['image_url']) && trim((string) $q['image_url']) !== ''
                    ? trim((string) $q['image_url'])
                    : null,
                'options' => $options,
                'correct_option' => strtoupper(trim((string) $q['correct_option'])),
                'explanation' => isset($q['explanation']) && trim((string) $q['explanation']) !== ''
                    ? trim((string) $q['explanation'])
                    : null,
                'order_index' => $index + 1,
            ];

            $incomingId = isset($q['id']) ? (int) $q['id'] : null;
            if ($incomingId && $existingQuestions->has($incomingId)) {
                $existingQuestions->get($incomingId)->update($attributes);
                $retainedIds[] = $incomingId;
            } else {
                $created = BootcampCbtQuestion::create(array_merge($attributes, [
                    'bootcamp_cbt_id' => $cbt->id,
                ]));
                $retainedIds[] = $created->id;
            }
        }

        $idsToDelete = $existingQuestions->keys()->diff($retainedIds)->all();
        if (!empty($idsToDelete)) {
            $cbt->questions()->whereIn('id', $idsToDelete)->delete();
        }
    }
}
