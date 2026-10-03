<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Mailer;
use App\Models\Submission;
use App\Models\User;
use App\Progress;

/**
 * Corrección de ejercicios. El profesor solo accede a las entregas de sus cursos;
 * el administrador, a todas.
 */
final class TeacherController
{
    private const FILTERS = [
        'pending' => 'Pendientes de corrección',
        'approved' => 'Aprobadas',
        'failed' => 'Suspendidas',
        'all' => 'Todas',
    ];

    public function index(): void
    {
        $user = Auth::requireRole('teacher', 'admin');
        $status = query_param('estado', 'pending');
        if (!isset(self::FILTERS[$status])) {
            $status = 'pending';
        }
        // Los administradores ven todas las entregas, pero pueden limitarse a los cursos que tienen asignados
        $isAdmin = $user['role'] === 'admin';
        $onlyMine = !$isAdmin || query_param('cursos') === 'mios';

        view('teacher/index', [
            'title' => 'Corrección de ejercicios',
            'submissions' => Submission::listForGrader($user, $status, $onlyMine),
            'status' => $status,
            'filters' => self::FILTERS,
            'isAdmin' => $isAdmin,
            'onlyMine' => $onlyMine,
        ]);
    }

    public function show(string $id): void
    {
        $user = Auth::requireRole('teacher', 'admin');
        $this->renderSubmission($this->load((int) $id, $user));
    }

    public function grade(string $id): void
    {
        $user = Auth::requireRole('teacher', 'admin');
        $submission = $this->load((int) $id, $user);
        $path = '/profesor/entregas/' . $submission['id'];

        if ($submission['status'] !== 'pending') {
            flash('info', 'Esta entrega ya estaba calificada.');
            redirect($path);
        }

        $status = input('status');
        $feedback = trim(str_replace(["\r\n", "\r"], "\n", input('feedback')));
        $errors = [];
        if (!in_array($status, ['approved', 'failed'], true)) {
            $errors['status'] = 'Indica si el ejercicio está aprobado o suspendido.';
        }
        // El comentario es obligatorio al suspender (el alumno debe saber qué corregir) y opcional al aprobar
        if ($feedback === '' && $status === 'failed') {
            $errors['feedback'] = 'Escribe un comentario para explicar al alumno qué debe mejorar.';
        } elseif (mb_strlen($feedback) > 5000) {
            $errors['feedback'] = 'El comentario no puede superar los 5.000 caracteres.';
        }
        if ($errors !== []) {
            $this->renderSubmission($submission, $errors, ['status' => $status, 'feedback' => $feedback], 422);
            return;
        }

        if (!Submission::grade((int) $submission['id'], $status, $feedback !== '' ? $feedback : null, (int) $user['id'])) {
            flash('info', 'Otra persona ha calificado esta entrega mientras la revisabas.');
            redirect($path);
        }

        $student = User::find((int) $submission['user_id']);
        if ($student !== null) {
            Mailer::send($student['email'], $student['name'], 'exercise_graded', [
                'name' => $student['name'],
                'courseTitle' => $submission['course_title'],
                'itemTitle' => $submission['item_title'],
                'approved' => $status === 'approved',
                'feedback' => $feedback,
                'url' => absolute_url('/cursos/' . $submission['course_slug'] . '/contenido/' . $submission['item_id']),
            ]);
        }
        if ($status === 'approved') {
            Progress::checkCompletion((int) $submission['user_id'], (int) $submission['course_id']);
        }

        flash('success', 'Calificación guardada. Se ha avisado a ' . $submission['student_name'] . ' por correo electrónico.');
        redirect('/profesor');
    }

    private function load(int $id, array $user): array
    {
        $submission = Submission::findDetailed($id) ?? abort(404, 'La entrega que buscas no existe.');
        $isCourseTeacher = $submission['teacher_id'] !== null && (int) $submission['teacher_id'] === (int) $user['id'];
        if ($user['role'] !== 'admin' && !$isCourseTeacher) {
            abort(403, 'Solo el profesor del curso puede ver esta entrega.');
        }
        return $submission;
    }

    private function renderSubmission(array $submission, array $errors = [], array $old = [], int $status = 200): void
    {
        $attempts = Submission::history((int) $submission['user_id'], (int) $submission['item_id']);
        $previous = array_values(array_filter(
            $attempts,
            static fn (array $a): bool => (int) $a['id'] !== (int) $submission['id']
        ));
        $attemptNumber = count(array_filter(
            $attempts,
            static fn (array $a): bool => (int) $a['id'] <= (int) $submission['id']
        ));

        view('teacher/submission', [
            'title' => 'Entrega de ' . $submission['student_name'],
            'submission' => $submission,
            'previous' => $previous,
            'attemptNumber' => $attemptNumber,
            'errors' => $errors,
            'old' => $old,
        ], $status);
    }
}
