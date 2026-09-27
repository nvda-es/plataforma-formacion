<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Enrollment;
use App\Progress;

final class StudentController
{
    public function dashboard(): void
    {
        $user = Auth::requireRole('student');
        $enrollments = Enrollment::forUser((int) $user['id']);
        foreach ($enrollments as &$enrollment) {
            $enrollment['progress'] = Progress::summary((int) $user['id'], (int) $enrollment['course_id']);
        }
        unset($enrollment);

        view('student/dashboard', [
            'title' => 'Mis cursos',
            'enrollments' => $enrollments,
        ]);
    }
}
