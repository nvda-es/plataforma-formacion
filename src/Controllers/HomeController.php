<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Models\Course;
use App\Models\Enrollment;

final class HomeController
{
    public function index(): void
    {
        $user = Auth::user();
        $enrolledIds = ($user !== null && $user['role'] === 'student')
            ? array_flip(Enrollment::courseIds((int) $user['id']))
            : [];

        view('home', [
            'title' => 'Cursos disponibles',
            'courses' => Course::published(),
            'enrolledIds' => $enrolledIds,
            'user' => $user,
        ]);
    }
}
