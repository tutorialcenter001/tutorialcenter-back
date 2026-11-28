<?php

use App\Models\BootcampCbt;
use App\Models\BootcampCbtAttempt;
use App\Models\BootcampCbtQuestion;
use App\Models\BootcampParticipant;
use App\Models\Staff;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

function staffHeaders(Staff $staff): array
{
    $token = $staff->createToken('test-token')->plainTextToken;

    return ['Authorization' => "Bearer {$token}"];
}

function makeStaff(string $role = 'admin'): Staff
{
    static $seq = 1;
    $seq++;

    return Staff::create([
        'staff_id' => 'TC2611' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
        'firstname' => 'Test',
        'surname' => ucfirst($role),
        'email' => "{$role}{$seq}@tutorialcenter.africa",
        'tel' => '0809000' . str_pad((string) $seq, 4, '0', STR_PAD_LEFT),
        'password' => Hash::make('password123'),
        'gender' => 'male',
        'profile_picture' => 'default.png',
        'location' => 'Lagos, Nigeria',
        'address' => '123 Tutorial Street',
        'role' => $role,
    ]);
}

function sampleQuestions(int $count = 4): array
{
    $list = [];
    for ($i = 1; $i <= $count; $i++) {
        $list[] = [
            'question_text' => "Question {$i}: What is {$i} + {$i}?",
            'options' => [
                ['key' => 'A', 'text' => (string) ($i * 2)],
                ['key' => 'B', 'text' => (string) ($i * 2 + 1)],
                ['key' => 'C', 'text' => (string) ($i * 2 + 2)],
                ['key' => 'D', 'text' => (string) ($i * 2 + 3)],
            ],
            'correct_option' => 'A',
            'explanation' => "{$i} + {$i} = " . ($i * 2),
        ];
    }

    return $list;
}

test('COO has read-only access on bootcamp CBT admin endpoints', function () {
    $coo = makeStaff('coo');
    $cooHeaders = staffHeaders($coo);

    $this->withHeaders($cooHeaders)->getJson('/api/admin/bootcamp/cbts')->assertOk();

    $this->withHeaders($cooHeaders)->postJson('/api/admin/bootcamp/cbts', [
        'title' => 'Week 1 - JAMB Use of English',
        'subject_track' => 'jamb_english',
        'week_start_date' => '2026-11-23',
        'questions' => sampleQuestions(2),
    ])->assertStatus(403);
});

test('admin can create, update, toggle, and inspect bootcamp CBTs', function () {
    $admin = makeStaff('admin');
    $adminHeaders = staffHeaders($admin);

    $createRes = $this->withHeaders($adminHeaders)->postJson('/api/admin/bootcamp/cbts', [
        'title' => 'Week 1 - JAMB Use of English',
        'subject_track' => 'jamb_english',
        'week_start_date' => '2026-11-23',
        'duration_minutes' => 25,
        'questions' => sampleQuestions(3),
    ]);

    $createRes->assertCreated()
        ->assertJsonPath('data.title', 'Week 1 - JAMB Use of English')
        ->assertJsonPath('data.subject_track', 'jamb_english')
        ->assertJsonCount(3, 'data.questions');

    $cbtId = $createRes->json('data.id');

    $this->withHeaders($adminHeaders)->getJson('/api/admin/bootcamp/cbts')
        ->assertOk()
        ->assertJsonPath('data.0.questions_count', 3);

    $this->withHeaders($adminHeaders)->getJson("/api/admin/bootcamp/cbts/{$cbtId}")
        ->assertOk()
        ->assertJsonPath('data.questions.0.correct_option', 'A');

    $existingQuestions = $createRes->json('data.questions');
    $firstQuestionId = $existingQuestions[0]['id'];

    // Update CBT preserving existing question ID
    $updateRes = $this->withHeaders($adminHeaders)->putJson("/api/admin/bootcamp/cbts/{$cbtId}", [
        'title' => 'Week 1 - JAMB Use of English (Updated)',
        'questions' => [
            [
                'id' => $firstQuestionId,
                'question_text' => 'Updated Question 1',
                'options' => [
                    ['key' => 'A', 'text' => 'Choice A'],
                    ['key' => 'B', 'text' => 'Choice B'],
                ],
                'correct_option' => 'B',
            ],
        ],
    ])->assertOk();

    expect($updateRes->json('data.questions.0.id'))->toBe($firstQuestionId);
    expect($updateRes->json('data.questions'))->toHaveCount(1);

    // Toggle active (pause) and confirm public start rejects paused CBT
    $token = $createRes->json('data.access_token');
    $this->withHeaders($adminHeaders)->patchJson("/api/admin/bootcamp/cbts/{$cbtId}/toggle-active")
        ->assertOk()
        ->assertJsonPath('data.is_active', false);

    $this->getJson("/api/public/bootcamp/cbt/{$token}")
        ->assertOk()
        ->assertJsonPath('data.is_open', false)
        ->assertJsonPath('data.closed_reason', 'inactive');

    // Regenerate token invalidates old link
    $regenRes = $this->withHeaders($adminHeaders)->postJson("/api/admin/bootcamp/cbts/{$cbtId}/regenerate-token")
        ->assertOk();
    $newToken = $regenRes->json('data.access_token');
    expect($newToken)->not->toBe($token);

    $this->getJson("/api/public/bootcamp/cbt/{$token}")->assertStatus(404);
    $this->getJson("/api/public/bootcamp/cbt/{$newToken}")->assertOk();

    // Delete CBT
    $this->withHeaders($adminHeaders)->deleteJson("/api/admin/bootcamp/cbts/{$cbtId}")->assertOk();
    $this->getJson("/api/public/bootcamp/cbt/{$newToken}")->assertStatus(404);
});

test('public CBT registers with phone+name, issues 4-digit PIN, prevents retakes, and scopes record to this week and last week', function () {
    $twoWeeksAgoCbt = BootcampCbt::create([
        'title' => 'Older Week - JAMB Maths',
        'subject_track' => 'jamb_maths',
        'week_start_date' => '2026-11-09',
        'access_token' => 'older-token-123456',
        'duration_minutes' => 20,
        'is_active' => true,
    ]);

    $lastWeekCbt = BootcampCbt::create([
        'title' => 'Last Week - JAMB Maths',
        'subject_track' => 'jamb_maths',
        'week_start_date' => '2026-11-16',
        'access_token' => 'last-week-token-123',
        'duration_minutes' => 20,
        'is_active' => true,
    ]);

    $thisWeekCbt = BootcampCbt::create([
        'title' => 'This Week - JAMB Maths',
        'subject_track' => 'jamb_maths',
        'week_start_date' => '2026-11-23',
        'access_token' => 'this-week-token-123',
        'duration_minutes' => 20,
        'is_active' => true,
    ]);

    foreach (sampleQuestions(4) as $idx => $q) {
        BootcampCbtQuestion::create(array_merge($q, [
            'bootcamp_cbt_id' => $thisWeekCbt->id,
            'order_index' => $idx + 1,
        ]));
    }

    // 1. Register new participant
    $regRes = $this->postJson('/api/public/bootcamp/cbt/this-week-token-123/register', [
        'full_name' => 'Chinedu Okafor',
        'phone' => '08012345678',
    ])->assertCreated();

    $pin = $regRes->json('data.secret_pin');
    expect($pin)->toHaveLength(4);

    // 2. Duplicate registration with +234 format is blocked
    $this->postJson('/api/public/bootcamp/cbt/this-week-token-123/register', [
        'full_name' => 'Another Name',
        'phone' => '+2348012345678',
    ])->assertStatus(409);

    // Seed historical attempts for the participant (2 weeks ago + last week)
    $participant = BootcampParticipant::firstOrFail();

    BootcampCbtAttempt::create([
        'bootcamp_cbt_id' => $twoWeeksAgoCbt->id,
        'participant_id' => $participant->id,
        'score' => 5,
        'total_questions' => 20,
        'time_taken_seconds' => 300,
        'started_at' => now()->subDays(14),
        'submitted_at' => now()->subDays(14),
    ]);

    BootcampCbtAttempt::create([
        'bootcamp_cbt_id' => $lastWeekCbt->id,
        'participant_id' => $participant->id,
        'score' => 14,
        'total_questions' => 20,
        'time_taken_seconds' => 420,
        'started_at' => now()->subDays(7),
        'submitted_at' => now()->subDays(7),
    ]);

    // Seed 3 higher-scoring participants on this week's CBT so Chinedu lands at #4
    for ($i = 1; $i <= 3; $i++) {
        $other = BootcampParticipant::create([
            'phone_normalized' => '234809999000' . $i,
            'phone_raw' => '0809999000' . $i,
            'full_name' => "Top Student {$i}",
            'pin_hash' => Hash::make('1234'),
        ]);
        BootcampCbtAttempt::create([
            'bootcamp_cbt_id' => $thisWeekCbt->id,
            'participant_id' => $other->id,
            'score' => 4,
            'total_questions' => 4,
            'time_taken_seconds' => 20 + $i * 10,
            'started_at' => now()->subMinutes(5),
            'submitted_at' => now()->subMinutes(4),
        ]);
    }

    // 3. Wrong PIN fails
    $this->postJson('/api/public/bootcamp/cbt/this-week-token-123/login', [
        'phone' => '+2348012345678',
        'pin' => '0000',
    ])->assertStatus(422);

    // 4. Start CBT & confirm correct_option is NOT leaked
    $startRes = $this->postJson('/api/public/bootcamp/cbt/this-week-token-123/start', [
        'phone' => '08012345678',
        'pin' => $pin,
    ])->assertOk();

    $questions = $startRes->json('data.questions');
    expect($questions)->toHaveCount(4);
    expect($questions[0])->not->toHaveKey('correct_option');

    // 5. Submit 3 correct answers and 1 wrong answer
    $answers = [
        (string) $questions[0]['id'] => 'A',
        (string) $questions[1]['id'] => 'A',
        (string) $questions[2]['id'] => 'A',
        (string) $questions[3]['id'] => 'B',
    ];

    $submitRes = $this->postJson('/api/public/bootcamp/cbt/this-week-token-123/submit', [
        'phone' => '08012345678',
        'pin' => $pin,
        'answers' => $answers,
    ])->assertOk();

    $submitRes->assertJsonPath('data.attempt.score', 3)
        ->assertJsonPath('data.attempt.total_questions', 4)
        ->assertJsonPath('data.record.this_week.score', 3)
        ->assertJsonPath('data.record.last_week.score', 14)
        ->assertJsonCount(3, 'data.leaderboard.top_three')
        ->assertJsonPath('data.leaderboard.my_entry.rank', 4)
        ->assertJsonPath('data.leaderboard.my_entry.full_name', 'Chinedu Okafor');

    // 6. Second start/submit attempt is rejected with 409
    $this->postJson('/api/public/bootcamp/cbt/this-week-token-123/start', [
        'phone' => '08012345678',
        'pin' => $pin,
    ])->assertStatus(409);

    // 7. Admin can view participants list and reset participant PIN
    $admin = makeStaff('admin');
    $adminHeaders = staffHeaders($admin);

    $this->withHeaders($adminHeaders)
        ->getJson('/api/admin/bootcamp/participants?search=Chinedu')
        ->assertOk()
        ->assertJsonPath('data.0.full_name', 'Chinedu Okafor')
        ->assertJsonPath('data.0.completed_cbts_count', 3);

    $resetRes = $this->withHeaders($adminHeaders)
        ->postJson("/api/admin/bootcamp/participants/{$participant->id}/reset-pin")
        ->assertOk();
    $newPin = $resetRes->json('data.new_pin');
    expect($newPin)->toHaveLength(4);

    $this->postJson('/api/public/bootcamp/cbt/this-week-token-123/login', [
        'phone' => '08012345678',
        'pin' => $newPin,
    ])->assertOk()
        ->assertJsonPath('data.record.last_week.score', 14);
});
