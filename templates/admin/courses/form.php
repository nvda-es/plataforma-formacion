<?php
/** @var array|null $course @var array $teachers @var array $old @var array $errors */
$isEdit = $course !== null;
$action = $isEdit ? url('/admin/cursos/' . $course['id']) : url('/admin/cursos');
$oldTeacher = old($old, 'teacher_id');
?>
<?= partial('admin_nav') ?>
<nav aria-label="Ruta de navegación" class="breadcrumbs">
    <ol>
        <li><a href="<?= e(url('/admin/cursos')) ?>">Cursos</a></li>
        <?php if ($isEdit): ?>
            <li><a href="<?= e(url('/admin/cursos/' . $course['id'])) ?>"><?= e($course['title']) ?></a></li>
            <li aria-current="page">Editar</li>
        <?php else: ?>
            <li aria-current="page">Nuevo curso</li>
        <?php endif; ?>
    </ol>
</nav>

<h1><?= $isEdit ? 'Editar el curso' : 'Crear un curso' ?></h1>

<?= partial('errors', ['errors' => $errors]) ?>

<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>

    <div class="field">
        <label for="title">Título</label>
        <?= field_error($errors, 'title') ?>
        <input type="text" id="title" name="title" required maxlength="200"
               value="<?= e(old($old, 'title')) ?>"<?= field_attrs($errors, 'title') ?>>
    </div>

    <div class="field">
        <label for="slug">Dirección del curso (opcional)</label>
        <p class="hint" id="slug-hint">
            Aparece en la URL: <?= e(absolute_url('/cursos/')) ?><strong>direccion-del-curso</strong>.
            Solo minúsculas sin tilde, números y guiones. Si la dejas en blanco se genera a partir del título.
        </p>
        <?= field_error($errors, 'slug') ?>
        <input type="text" id="slug" name="slug" maxlength="120" pattern="[a-z0-9]+(-[a-z0-9]+)*" spellcheck="false"
               value="<?= e(old($old, 'slug')) ?>"<?= field_attrs($errors, 'slug', 'slug-hint') ?>>
    </div>

    <div class="field">
        <label for="teacher_id">Profesor/a</label>
        <p class="hint" id="teacher-hint">Recibirá los avisos de ejercicios pendientes de corrección. Sin profesor asignado, los avisos van a los administradores.</p>
        <?= field_error($errors, 'teacher_id') ?>
        <select id="teacher_id" name="teacher_id"<?= field_attrs($errors, 'teacher_id', 'teacher-hint') ?>>
            <option value="">Sin asignar</option>
            <?php foreach ($teachers as $t): ?>
                <option value="<?= (int) $t['id'] ?>"<?= selected($oldTeacher === (string) $t['id']) ?>><?= e($t['name']) ?> (<?= e($t['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="field">
        <label for="summary">Resumen (Markdown)</label>
        <p class="hint" id="summary-hint">Se muestra en el catálogo y en la ficha del curso.</p>
        <?= field_error($errors, 'summary') ?>
        <textarea id="summary" name="summary" rows="8" maxlength="20000" class="code"
            <?= field_attrs($errors, 'summary', 'summary-hint') ?>><?= e(old($old, 'summary')) ?></textarea>
    </div>

    <div class="checkbox">
        <input type="checkbox" id="is_published" name="is_published" value="1"<?= checked(!empty($old['is_published'])) ?>>
        <label for="is_published">Publicado (visible en el catálogo y disponible para matricularse)</label>
    </div>

    <button type="submit" class="button"><?= $isEdit ? 'Guardar cambios' : 'Crear curso' ?></button>
</form>

<?php if ($isEdit): ?>
    <section class="panel danger-zone" aria-labelledby="delete-title">
        <h2 id="delete-title">Eliminar el curso</h2>
        <p>Se eliminarán sus módulos, contenidos, matrículas y todas las respuestas de los alumnos. Esta acción no se puede deshacer.</p>
        <form method="post" action="<?= e(url('/admin/cursos/' . $course['id'] . '/eliminar')) ?>"
              data-confirm="¿Eliminar definitivamente el curso «<?= e($course['title']) ?>» con todo su contenido y las respuestas de los alumnos?">
            <?= csrf_field() ?>
            <button type="submit" class="button button-danger">Eliminar el curso</button>
        </form>
    </section>
<?php endif; ?>
