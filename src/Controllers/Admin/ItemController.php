<?php
declare(strict_types=1);

namespace App\Controllers\Admin;

use App\Auth;
use App\CourseExporter;
use App\Support\Str;
use App\Models\Course;
use App\Models\Item;
use App\Models\Module;
use App\Upload;
use App\Validator;

/**
 * Gestión de unidades y ejercicios de un módulo. El contenido se escribe en
 * Markdown o se carga desde un archivo .md.
 */
final class ItemController
{
    private const CONTENT_MAX = 200000;

    public function create(string $id): void
    {
        Auth::requireRole('admin');
        $module = Module::find((int) $id) ?? abort(404, 'El módulo no existe.');
        $type = query_param('tipo') === 'exercise' ? 'exercise' : 'unit';
        view('admin/items/form', [
            'title' => $type === 'exercise' ? 'Nuevo ejercicio' : 'Nueva unidad',
            'module' => $module,
            'item' => null,
            'modules' => [],
            'old' => ['type' => $type, 'module_id' => (string) $module['id']],
        ]);
    }

    public function store(string $id): void
    {
        Auth::requireRole('admin');
        $module = Module::find((int) $id) ?? abort(404, 'El módulo no existe.');
        [$data, $errors] = $this->validated([(int) $module['id']]);
        if ($errors !== []) {
            view('admin/items/form', [
                'title' => $data['type'] === 'exercise' ? 'Nuevo ejercicio' : 'Nueva unidad',
                'module' => $module,
                'item' => null,
                'modules' => [],
                'old' => $data,
                'errors' => $errors,
            ], 422);
            return;
        }
        $itemId = Item::create((int) $module['id'], $data['type'], $data['title'], $data['content']);
        flash('success', 'Se ha añadido «' . $data['title'] . '» al módulo «' . $module['title'] . '».');
        redirect('/admin/cursos/' . $module['course_id'] . '#contenido-' . $itemId);
    }

    public function edit(string $id): void
    {
        Auth::requireRole('admin');
        $item = Item::find((int) $id) ?? abort(404, 'El contenido no existe.');
        $this->renderEdit($item, [
            'type' => $item['type'],
            'title' => $item['title'],
            'content' => $item['content'],
            'module_id' => (string) $item['module_id'],
        ]);
    }

    public function update(string $id): void
    {
        Auth::requireRole('admin');
        $item = Item::find((int) $id) ?? abort(404, 'El contenido no existe.');
        $moduleIds = array_map('intval', array_column(Course::modules((int) $item['course_id']), 'id'));
        [$data, $errors] = $this->validated($moduleIds);
        if ($errors !== []) {
            $this->renderEdit($item, $data, $errors, 422);
            return;
        }
        Item::update($item, (int) $data['module_id'], $data['type'], $data['title'], $data['content']);
        flash('success', 'Los cambios de «' . $data['title'] . '» se han guardado.');
        redirect('/admin/cursos/' . $item['course_id'] . '#contenido-' . $item['id']);
    }

    public function destroy(string $id): void
    {
        Auth::requireRole('admin');
        $item = Item::find((int) $id) ?? abort(404, 'El contenido no existe.');
        Item::delete((int) $item['id']);
        flash('success', '«' . $item['title'] . '» se ha eliminado.');
        redirect('/admin/cursos/' . $item['course_id'] . '#modulo-' . $item['module_id']);
    }

    /** Descarga el contenido Markdown de la unidad o el enunciado del ejercicio. */
    public function export(string $id): void
    {
        Auth::requireRole('admin');
        $item = Item::find((int) $id) ?? abort(404, 'El contenido no existe.');
        $prefix = $item['type'] === 'exercise' ? '-ejercicio-' : '-unidad-';
        download($item['course_slug'] . $prefix . Str::slug($item['title']) . '.md', CourseExporter::item($item));
    }

    public function move(string $id): void
    {
        Auth::requireRole('admin');
        $item = Item::find((int) $id) ?? abort(404, 'El contenido no existe.');
        $direction = input('direction') === 'up' ? 'up' : 'down';
        Item::move($item, $direction);
        flash('success', '«' . $item['title'] . '» movido ' . ($direction === 'up' ? 'hacia arriba.' : 'hacia abajo.'));
        redirect('/admin/cursos/' . $item['course_id'] . '#contenido-' . $item['id']);
    }

    /**
     * @param list<int> $allowedModules módulos válidos como destino
     * @return array{0: array, 1: array}
     */
    private function validated(array $allowedModules): array
    {
        $data = [
            'type' => input('type'),
            'title' => trim(input('title')),
            'content' => str_replace(["\r\n", "\r"], "\n", input('content')),
            'module_id' => input('module_id', (string) ($allowedModules[0] ?? '')),
        ];

        $v = (new Validator($data))
            ->in('type', 'Tipo de contenido', array_keys(Item::TYPES))
            ->required('title', 'Título')->maxLength('title', 'Título', 200);

        if (!in_array((int) $data['module_id'], $allowedModules, true)) {
            $v->addError('module_id', 'Selecciona un módulo válido de este curso.');
        }

        $uploadError = null;
        $uploaded = Upload::markdown('content_file', $uploadError);
        if ($uploadError !== null) {
            $v->addError('content_file', $uploadError);
        } elseif ($uploaded !== null) {
            $data['content'] = $uploaded;
        }

        if (trim($data['content']) === '') {
            $v->addError('content', 'Escribe el contenido en Markdown o sube un archivo .md.');
        } elseif (mb_strlen($data['content']) > self::CONTENT_MAX) {
            $v->addError('content', 'El contenido no puede superar los 200.000 caracteres.');
        }
        return [$data, $v->errors()];
    }

    private function renderEdit(array $item, array $old, array $errors = [], int $status = 200): void
    {
        view('admin/items/form', [
            'title' => 'Editar: ' . $item['title'],
            'module' => ['id' => $item['module_id'], 'title' => $item['module_title'], 'course_id' => $item['course_id'], 'course_title' => $item['course_title']],
            'item' => $item,
            'modules' => Course::modules((int) $item['course_id']),
            'old' => $old,
            'errors' => $errors,
        ], $status);
    }
}
