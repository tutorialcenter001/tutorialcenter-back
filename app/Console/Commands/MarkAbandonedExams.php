<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\ExamAttempt;

class MarkAbandonedExams extends Command
{
    protected $signature =
        'exam:mark-abandoned';

    protected $description =
        'Mark stale exam attempts abandoned';

    public function handle()
    {
        ExamAttempt::where(
            'status',
            ExamAttempt::IN_PROGRESS
        )
        ->where(
            'started_at',
            '<',
            now()->subHours(2)
        )
        ->update([
            'status'=>ExamAttempt::ABANDONED
        ]);

        $this->info(
            'Expired attempts updated'
        );
    }
}