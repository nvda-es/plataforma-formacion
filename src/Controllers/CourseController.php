<?php
declare(strict_types=1);

namespace App\Controllers;

use App\Auth;
use App\Mailer;
use App\Models\Course;
use App\Models\Enrollment;
use App\Models\Submission;
use App\Models\User;
use App\Progress;

/**
 * Ficha del curso, matrícula, lectura de contenidos y entrega de ejercicios.
 */
final class CourseController
{
    private const ANSWER_MAX = 20000;

    public function show(string $slug): void
    {
        $user = Auth::user();
        $course = Course::findBySlug($slug) ?? abort(404, 'El curso que buscas no existe.');
        $preview = Auth::canManageCourse($user, $course);
        $enrollment = ($user !== null && $user['role'] === 'student')
            ? Enrollment::find((int) $user['id'], (int) $course['id'])
            : null;

        if (!(int) $course['is_published'] && !$preview && $enrollment === null) {
            abort(404, 'El curso que buscas no existe.');
        }

        $outline = Course::outline((int) $course['id'], $enrollment !== null ? (int) $user['id'] : 0);

        view('courses/show', [
            'title' => $course['title'],
            'course' => $course,
            'user' => $user,
            'enrollment' => $enrollment,
            'outline' => $outline,
            'progress' => $enrollment !== null ? Progress::summary((int) $user['id'], (int) $course['id']) : null,
            'nextItem' => $enrollment !== null ? $this->nextPending($outline) : null,
            'preview' => $preview,
        ]);
    }

    public function enroll(string $slug): void
    {
        $user = Auth::requireRole('student');
        $course = Course::findBySlug($slug);
        if ($course === null || !(int) $course['is_published']) {
            abort(404, 'El curso que buscas no existe.');
        }
        if (Enrollment::enroll((int) $user['id'], (int) $course['id'])) {
            flash('success', 'Te has matriculado en «' . $course['title'] . '». Ya puedes empezar el curso.');
        } else {
            flash('info', 'Ya estabas matriculado en este curso.');
        }
        redirect('/cursos/' . $course['slug']);
    }

    public function item(string $slug, string $id): void
    {
        $user = Auth::requireLogin();
        [$course, $item, $enrollment, $preview] = $this->resolve($slug, (int) $id, $user);

        if ($enrollment !== null) {
            Progress::markViewed((int) $user['id'], (int) $item['id']);
            if ($item['type'] === 'unit') {
                Progress::checkCompletion((int) $user['id'], (int) $course['id']);
            }
        }
        $this->renderItem($course, $item, $user, $enrollment, $preview);
    }

    public function submit(string $slug, string $id): void
    {
        $user = Auth::requireRole('student');
        [$course, $item, $enrollment] = $this->resolve($slug, (int) $id, $user);
        $itemPath = '/cursos/' . $course['slug'] . '/contenido/' . $item['id'];

        if ($enrollment === null || $item['type'] !== 'exercise') {
            abort(403, 'Solo puedes responder ejercicios de cursos en los que estás matriculado.');
        }

        $latest = Submission::latest((int) $user['id'], (int) $item['id']);
        if ($latest !== null && $latest['status'] !== 'failed') {
            flash('info', $latest['status'] === 'approved'
                ? 'Este ejercicio ya está aprobado.'
                : 'Ya has enviado una respuesta que está pendiente de corrección.');
            redirect($itemPath);
        }

        $answer = trim(str_replace(["\r\n", "\r"], "\n", input('answer')));
        $errors = [];
        if ($answer === '') {
            $errors['answer'] = 'Escribe tu respuesta antes de enviarla.';
        } elseif (mb_strlen($answer) > self::ANSWER_MAX) {
            $errors['answer'] = 'La respuesta no puede superar los 20.000 caracteres.';
        }
        if ($errors !== []) {
            $this->renderItem($course, $item, $user, $enrollment, false, $errors, ['answer' => $answer], 422);
            return;
        }

        $submissionId = Submission::create((int) $user['id'], (int) $item['id'], $answer);
        $this->notifyGraders($course, $item, $user, $submissionId);

        flash('success', 'Tu respuesta se ha enviado al profesor. Recibirás un correo electrónico cuando la califique.');
        redirect($itemPath);
    }

    /**
     * Comprueba el acceso a un contenido.
     *
     * @return array{0: array, 1: array, 2: ?array, 3: bool} curso, contenido, matrícula y si es vista previa
     */
    private function resolve(string $slug, int $itemId, array $user): array
    {
        $course = Course::findBySlug($slug) ?? abort(404, 'El curso que buscas no existe.');
        $item = Course::findItem((int) $course['id'], $itemId) ?? abort(404, 'El contenido que buscas no existe.');
        $canManage = Auth::canManageCourse($user, $course);
        $enrollment = $user['role'] === 'student' ? Enrollment::find((int) $user['id'], (int) $course['id']) : null;

        if ($enrollment === null && !$canManage) {
            if (!(int) $course['is_published']) {
                abort(404, 'El curso que buscas no existe.');
            }
            flash('info', 'Matricúlate en el curso para acceder a sus contenidos.');
            redirect('/cursos/' . $course['slug']);
        }
        return [$course, $item, $enrollment, $enrollment === null];
    }

    private function renderItem(
        array $course,
        array $item,
        array $user,
        ?array $enrollment,
        bool $preview,
        array $errors = [],
        array $old = [],
        int $status = 200
    ): void {
        $items = Course::flatItems((int) $course['id']);
        $ids = array_map('intval', array_column($items, 'id'));
        $index = array_search((int) $item['id'], $ids, true);

        $history = ($enrollment !== null && $item['type'] === 'exercise')
            ? Submission::history((int) $user['id'], (int) $item['id'])
            : [];

        view('courses/item', [
            'title' => $item['title'] . ' · ' . $course['title'],
            'course' => $course,
            'item' => $item,
            'preview' => $preview,
            'history' => $history,
            'latest' => $history[0] ?? null,
            'previous' => ($index !== false && $index > 0) ? $items[$index - 1] : null,
            'next' => ($index !== false && isset($items[$index + 1])) ? $items[$index + 1] : null,
            'position' => $index !== false ? $index + 1 : null,
            'totalItems' => count($items),
            'errors' => $errors,
            'old' => $old,
        ], $status);
    }

    /** Primer contenido sin completar (o el primero, si todo está completado). */
    private function nextPending(array $outline): ?array
    {
        $first = null;
        foreach ($outline as $module) {
            foreach ($module['items'] as $item) {
                $first ??= $item;
                if (!Course::itemDone($item)) {
                    return $item;
                }
            }
        }
        return $first;
    }

    /** Avisa al profesor del curso; si no tiene profesor asignado, a los administradores. */
    private function notifyGraders(array $course, array $item, array $student, int $submissionId): void
    {
        $recipients = [];
        if ($course['teacher_id'] !== null) {
            $teacher = User::find((int) $course['teacher_id']);
            if ($teacher !== null && (int) $teacher['is_active'] === 1) {
                $recipients[] = $teacher;
            }
        }
        if ($recipients === []) {
            $recipients = User::activeByRole('admin');
        }
        foreach ($recipients as $recipient) {
            Mailer::send($recipient['email'], $recipient['name'], 'exercise_submitted', [
                'name' => $recipient['name'],
                'studentName' => $student['name'],
                'courseTitle' => $course['title'],
                'itemTitle' => $item['title'],
                'url' => absolute_url('/profesor/entregas/' . $submissionId),
            ]);
        }
    }
}
