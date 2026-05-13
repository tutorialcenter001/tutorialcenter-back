<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Student;
use App\Models\Guardian;
use App\Models\Staff;
use Carbon\Carbon;

class DeleteUnverifiedUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'users:delete-unverified';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Delete users who have not verified email or telephone after 24 hours';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $timeLimit = Carbon::now()->subHours(24);

        // Delete Students
        Student::whereNull('email_verified_at')
            ->whereNull('tel_verified_at')
            ->where('created_at', '<=', $timeLimit)
            ->forceDelete();

        // Delete Guardians
        Guardian::whereNull('email_verified_at')
            ->whereNull('tel_verified_at')
            ->where('created_at', '<=', $timeLimit)
            ->forceDelete();

        // Delete Staff
        Staff::whereNull('email_verified_at')
            ->whereNull('tel_verified_at')
            ->where('created_at', '<=', $timeLimit)
            ->forceDelete();

        $this->info('Unverified users deleted successfully.');
    }
}