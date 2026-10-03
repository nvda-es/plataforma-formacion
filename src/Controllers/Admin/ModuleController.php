<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\CourseExporter;
use App\Support\Str;
use App\Models\Course;
use App\Models\Module;
use App\Validator;

final class ModuleController
{
    public function create(string $id): void
    {
        Auth::requireRole('admin');
        $course = Course::find((int) $id) ?? abort(404, 'El curso no existe.');
        view('admin/modules/form', ['title' => 'Nuevo módulo', 'course' => $course, 'module' => null]);
    }

    public function store(string $id): void
    {
        Auth::requireRole('admin');
        $course = Course::find((int) $id) ?? abort(404, 'El curso no existe.');
        [$data, $errors] = $this->validated();
        if ($errors !== []) {
            view('admin/modules/form', [
                'title' => 'Nuevo módulo',
                'course' => $course,
                'module' => null,
                'old' => $data,
                'errors' => $errors,
            ], 422);
            return;
        }
        $moduleId = Module::create((int) $course['id'], $data['title'], $data['description']);
        flash('success', 'Módulo «' . $data['title'] . '» creado.');
        redirect('/admin/cursos/' . $course['id'] . '#modulo-' . $moduleId);
    }

    public function edit(string $id): void
    {
        Auth::requireRole('admin');
        $module = Module::find((int) $id) ?? abort(404, 'El módulo no existe.');
        view('admin/modules/form', [
            'title' => 'Editar módulo: ' . $module['title'],
            'course' => ['id' => $module['course_id'], 'title' => $module['course_title']],
            'module' => $module,
            'old' => ['title' => $module['title'], 'description' => $module['description'] ?? ''],
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        $module = Module::find((int) $id) ?? abort(404, 'El módulo no existe.');
        [$data, $errors] = $this->validated();
        if ($errors !== []) {
            view('admin/modules/form', [
                'title' => 'Editar módulo: ' . $module['title'],
                'course' => ['id' => $module['course_id'], 'title' => $module['course_title']],
                'module' => $module,
                'old' => $data,
                'errors' => $errors,
            ], 422);
            return;
        }
        Module::update((int) $module['id'], $data['title'], $data['description']);
        flash('success', 'Los cambios del módulo se han guardado.');
        redirect('/admin/cursos/' . $module['course_id'] . '#modulo-' . $module['id']);
    }

    public function destroy(string $id): void
    {
        Auth::requireRole('admin');
        $module = Module::find((int) $id) ?? abort(404, 'El módulo no existe.');
        Module::delete((int) $module['id']);
        flash('success', 'El módulo «' . $module['title'] . '» y sus contenidos se han eliminado.');
        redirect('/admin/cursos/' . $module['course_id']);
    }

    /** Descarga el módulo como sección "## Módulo:" lista para un archivo de curso. */
    public function export(string $id): void
    {
        Auth::requireRole('admin');
        $module = Module::find((int) $id) ?? abort(404, 'El módulo no existe.');
        download(
            $module['course_slug'] . '-modulo-' . Str::slug($module['title']) . '.md',
            CourseExporter::module($module)
        );
    }

    public function move(string $id): void
    {
        Auth::requireRole('admin');
        $module = Module::find((int) $id) ?? abort(404, 'El módulo no existe.');
        $direction = input('direction') === 'up' ? 'up' : 'down';
        Module::move($module, $direction);
        flash('success', 'Módulo «' . $module['title'] . '» movido ' . ($direction === 'up' ? 'hacia arriba.' : 'hacia abajo.'));
        redirect('/admin/cursos/' . $module['course_id'] . '#modulo-' . $module['id']);
    }

    /** @return array{0: array, 1: array} */
    private function validated(): array
    {
        $data = [
            'title' => trim(input('title')),
            'description' => str_replace(["\r\n", "\r"], "\n", input('description')),
        ];
        $v = (new Validator($data))
            ->required('title', 'Título')->maxLength('title', 'Título', 200)
            ->maxLength('description', 'Descripción', 20000);
        return [$data, $v->errors()];
    }
}
