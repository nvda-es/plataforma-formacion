<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\CourseExporter;
use App\CourseImporter;
use App\Models\Course;
use App\Models\User;
use App\Support\Str;
use App\Upload;
use App\Validator;
use RuntimeException;

final class CourseController
{
    public function index(): void
    {
        Auth::requireRole('admin');
        view('admin/courses/index', ['title' => 'Cursos', 'courses' => Course::adminList()]);
    }

    public function show(string $id): void
    {
        Auth::requireRole('admin');
        $course = Course::find((int) $id) ?? abort(404, 'El curso no existe.');
        view('admin/courses/show', [
            'title' => 'Contenidos de «' . $course['title'] . '»',
            'course' => $course,
            'outline' => Course::outline((int) $course['id']),
        ]);
    }

    public function create(): void
    {
        Auth::requireRole('admin');
        view('admin/courses/form', [
            'title' => 'Nuevo curso',
            'course' => null,
            'teachers' => User::assignableTeachers(),
        ]);
    }

    public function store(): void
    {
        Auth::requireRole('admin');
        [$data, $errors] = $this->validated(null);
        if ($errors !== []) {
            view('admin/courses/form', [
                'title' => 'Nuevo curso',
                'course' => null,
                'teachers' => User::assignableTeachers(),
                'old' => $data,
                'errors' => $errors,
            ], 422);
            return;
        }
        $id = Course::create($data);
        flash('success', 'Curso «' . $data['title'] . '» creado. Ahora puedes añadir sus módulos.');
        redirect('/admin/cursos/' . $id);
    }

    public function edit(string $id): void
    {
        Auth::requireRole('admin');
        $course = Course::find((int) $id) ?? abort(404, 'El curso no existe.');
        view('admin/courses/form', [
            'title' => 'Editar curso: ' . $course['title'],
            'course' => $course,
            'teachers' => User::assignableTeachers(),
            'old' => [
                'title' => $course['title'],
                'slug' => $course['slug'],
                'summary' => $course['summary'] ?? '',
                'teacher_id' => (string) ($course['teacher_id'] ?? ''),
                'is_published' => (int) $course['is_published'] === 1,
            ],
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        $course = Course::find((int) $id) ?? abort(404, 'El curso no existe.');
        [$data, $errors] = $this->validated($course);
        if ($errors !== []) {
            view('admin/courses/form', [
                'title' => 'Editar curso: ' . $course['title'],
                'course' => $course,
                'teachers' => User::assignableTeachers(),
                'old' => $data,
                'errors' => $errors,
            ], 422);
            return;
        }
        Course::update((int) $course['id'], $data);
        flash('success', 'Los cambios del curso se han guardado.');
        redirect('/admin/cursos/' . $course['id']);
    }

    public function destroy(string $id): void
    {
        Auth::requireRole('admin');
        $course = Course::find((int) $id) ?? abort(404, 'El curso no existe.');
        Course::delete((int) $course['id']);
        flash('success', 'El curso «' . $course['title'] . '» y todo su contenido se han eliminado.');
        redirect('/admin/cursos');
    }

    /** Descarga el curso completo en el formato de importación. */
    public function export(string $id): void
    {
        Auth::requireRole('admin');
        $course = Course::find((int) $id) ?? abort(404, 'El curso no existe.');
        download($course['slug'] . '.md', CourseExporter::course($course));
    }

    public function importForm(): void
    {
        Auth::requireRole('admin');
        view('admin/courses/import', [
            'title' => 'Importar un curso desde Markdown',
            'teachers' => User::assignableTeachers(),
        ]);
    }

    public function import(): void
    {
        Auth::requireRole('admin');
        $old = ['teacher_id' => input('teacher_id'), 'is_published' => checkbox('is_published')];
        $errors = [];

        $teacherId = $this->teacherId($old['teacher_id'], $errors);
        $uploadError = null;
        $markdown = Upload::markdown('course_file', $uploadError);
        $parsed = null;
        if ($uploadError !== null) {
            $errors['course_file'] = $uploadError;
        } elseif ($markdown === null) {
            $errors['course_file'] = 'Selecciona el archivo Markdown del curso.';
        } else {
            try {
                $parsed = CourseImporter::parse($markdown);
            } catch (RuntimeException $e) {
                $errors['course_file'] = 'El archivo no tiene el formato esperado. ' . $e->getMessage();
            }
        }

        if ($errors !== [] || $parsed === null) {
            view('admin/courses/import', [
                'title' => 'Importar un curso desde Markdown',
                'teachers' => User::assignableTeachers(),
                'old' => $old,
                'errors' => $errors,
            ], 422);
            return;
        }

        $courseId = CourseImporter::save($parsed, $teacherId, $old['is_published']);
        $items = array_sum(array_map(static fn (array $m): int => count($m['items']), $parsed['modules']));
        $modules = count($parsed['modules']);
        flash('success', sprintf(
            'Curso «%s» importado con %d %s y %d %s.',
            $parsed['title'],
            $modules,
            $modules === 1 ? 'módulo' : 'módulos',
            $items,
            $items === 1 ? 'contenido' : 'contenidos'
        ));
        redirect('/admin/cursos/' . $courseId);
    }

    /** @return array{0: array, 1: array} */
    private function validated(?array $existing): array
    {
        $data = [
            'title' => trim(input('title')),
            'slug' => trim(input('slug')),
            'summary' => str_replace(["\r\n", "\r"], "\n", input('summary')),
            'teacher_id' => input('teacher_id'),
            'is_published' => checkbox('is_published'),
        ];
        $v = (new Validator($data))
            ->required('title', 'Título')->maxLength('title', 'Título', 200)
            ->maxLength('summary', 'Resumen', 20000);

        $errors = $v->errors();
        $teacherId = $this->teacherId($data['teacher_id'], $errors);

        $ignoreId = $existing !== null ? (int) $existing['id'] : null;
        if ($data['slug'] === '') {
            $data['slug'] = $data['title'] !== '' ? Course::uniqueSlug($data['title'], $ignoreId) : '';
        } elseif ($data['slug'] !== Str::slug($data['slug']) || strlen($data['slug']) > 120) {
            $errors['slug'] ??= 'La dirección solo puede contener letras minúsculas sin tilde, números y guiones (máximo 120 caracteres).';
        } elseif (Course::slugExists($data['slug'], $ignoreId)) {
            $errors['slug'] ??= 'Ya hay otro curso con esa dirección.';
        }

        $clean = $data;
        $clean['teacher_id'] = $teacherId;
        return [$errors === [] ? $clean : $data, $errors];
    }

    /** Valida el profesor seleccionado (opcional). */
    private function teacherId(string $value, array &$errors): ?int
    {
        if ($value === '') {
            return null;
        }
        $teacher = User::find((int) $value);
        if (!User::canTeach($teacher)) {
            $errors['teacher_id'] ??= 'Selecciona un profesor o administrador válido.';
            return null;
        }
        return (int) $teacher['id'];
    }
}
