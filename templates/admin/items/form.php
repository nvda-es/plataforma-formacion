<?php
/**
 * @var array $module   módulo actual (id, title, course_id, course_title)
 * @var array|null $item
 * @var array $modules  módulos del curso (solo al editar)
 * @var array $old
 * @var array $errors
 */
$isEdit = $item !== null;
$action = $isEdit ? url('/admin/contenidos/' . $item['id']) : url('/admin/modulos/' . $module['id'] . '/contenidos');
$oldType = old($old, 'type', 'unit');
$oldModule = old($old, 'module_id', (string) $module['id']);
?>
<?= partial('admin_nav') ?>
<nav aria-label="Ruta de navegación" class="breadcrumbs">
    <ol>
        <li><a href="<?= e(url('/admin/cursos')) ?>">Cursos</a></li>
        <li><a href="<?= e(url('/admin/cursos/' . $module['course_id'])) ?>"><?= e($module['course_title']) ?></a></li>
        <li aria-current="page"><?= $isEdit ? 'Editar contenido' : 'Nuevo contenido' ?></li>
    </ol>
</nav>

<h1><?= $isEdit ? 'Editar «' . e($item['title']) . '»' : ($oldType === 'exercise' ? 'Añadir un ejercicio' : 'Añadir una unidad') ?></h1>
<p>Módulo: <?= e($module['title']) ?></p>

<?= partial('errors', ['errors' => $errors]) ?>

<form method="post" action="<?= e($action) ?>" enctype="multipart/form-data" class="form">
    <?= csrf_field() ?>

    <fieldset class="field">
        <legend>Tipo de contenido</legend>
        <?= field_error($errors, 'type') ?>
        <div class="radio">
            <input type="radio" id="type" name="type" value="unit" required<?= checked($oldType === 'unit') ?>>
            <label for="type">Unidad de solo texto</label>
        </div>
        <div class="radio">
            <input type="radio" id="type-exercise" name="type" value="exercise" required<?= checked($oldType === 'exercise') ?>>
            <label for="type-exercise">Ejercicio con respuesta abierta del alumno</label>
        </div>
    </fieldset>

    <?php if ($isEdit && count($modules) > 1): ?>
        <div class="field">
            <label for="module_id">Módulo</label>
            <?= field_error($errors, 'module_id') ?>
            <select id="module_id" name="module_id"<?= field_attrs($errors, 'module_id') ?>>
                <?php foreach ($modules as $m): ?>
                    <option value="<?= (int) $m['id'] ?>"<?= selected($oldModule === (string) $m['id']) ?>><?= e($m['title']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    <?php else: ?>
        <input type="hidden" name="module_id" value="<?= e($oldModule) ?>">
    <?php endif; ?>

    <div class="field">
        <label for="title">Título</label>
        <?= field_error($errors, 'title') ?>
        <input type="text" id="title" name="title" required maxlength="200"
               value="<?= e(old($old, 'title')) ?>"<?= field_attrs($errors, 'title') ?>>
    </div>

    <div class="field">
        <label for="content">Contenido o enunciado (Markdown)</label>
        <p class="hint" id="content-hint">
            Usa <code>##</code> para los apartados principales. Describe las imágenes con texto alternativo:
            <code>![descripción de la imagen](https://…)</code>. El HTML incrustado no se interpreta.
        </p>
        <?= field_error($errors, 'content') ?>
        <textarea id="content" name="content" rows="20" class="code"
            <?= field_attrs($errors, 'content', 'content-hint') ?>><?= e(old($old, 'content')) ?></textarea>
    </div>

    <div class="field">
        <label for="content_file">O bien sube un archivo Markdown</label>
        <p class="hint" id="content_file-hint">Si subes un archivo, su contenido sustituye al del cuadro de texto. Formatos .md, .markdown o .txt en UTF-8, máximo 1 MB.</p>
        <?= field_error($errors, 'content_file') ?>
        <input type="file" id="content_file" name="content_file" accept=".md,.markdown,.txt,text/markdown,text/plain"
            <?= field_attrs($errors, 'content_file', 'content_file-hint') ?>>
    </div>

    <button type="submit" class="button"><?= $isEdit ? 'Guardar cambios' : 'Añadir' ?></button>
    <a href="<?= e(url('/admin/cursos/' . $module['course_id'])) ?>">Cancelar</a>
</form>
