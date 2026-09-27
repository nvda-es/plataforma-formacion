<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\DB;
use App\Models\Submission;
use App\Models\User;

final class DashboardController
{
    public function index(): void
    {
        Auth::requireRole('admin');

        view('admin/dashboard', [
            'title' => 'Administración',
            'stats' => [
                'students' => User::countByRole('student'),
                'teachers' => User::countByRole('teacher'),
                'courses' => (int) DB::value('SELECT COUNT(*) FROM courses'),
                'published' => (int) DB::value('SELECT COUNT(*) FROM courses WHERE is_published = 1'),
                'enrollments' => (int) DB::value('SELECT COUNT(*) FROM enrollments'),
                'completed' => (int) DB::value('SELECT COUNT(*) FROM enrollments WHERE completed_at IS NOT NULL'),
                'pending' => Submission::countPending(),
            ],
        ]);
    }
}
