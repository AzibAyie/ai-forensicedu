<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class PruneInactiveStudents extends Command
{
    protected $signature = 'students:prune-inactive';

    protected $description = 'Delete student accounts (and all their data) after 4 months with no login';

    public function handle(): void
    {
        $cutoff = now()->subMonths(4);

        $students = User::where('role', 'student')
            ->where('email', '!=', 'student@forensicedu.test') // seeded demo account, kept for marking/evaluation
            ->whereRaw('COALESCE(
                (SELECT MAX(created_at) FROM activity_logs WHERE activity_logs.user_id = users.id AND activity_logs.action = ?),
                users.created_at
            ) < ?', ['LOGIN_SUCCESS', $cutoff])
            ->get();

        foreach ($students as $student) {
            Log::info('PRUNE_INACTIVE_STUDENT', ['user_id' => $student->id, 'email' => $student->email]);
            $student->delete();
        }

        $this->info("Pruned {$students->count()} inactive student account(s).");
    }
}
