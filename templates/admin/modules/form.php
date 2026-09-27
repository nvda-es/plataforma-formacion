<?php
/** @var array $course @var array|null $module @var array $old @var array $errors */
$isEdit = $module !== null;
$action = $isEdit ? url('/admin/modulos/' . $module['id']) : url('/admin/cursos/' . $course['id'] . '/modulos');
?>
<?= partial('admin_nav') ?>
<nav aria-label="Ruta de navegación" class="breadcrumbs">
    <ol>
        <li><a href="<?= e(url('/admin/cursos')) ?>">Cursos</a></li>
        <li><a href="<?= e(url('/admin/cursos/' . $course['id'])) ?>"><?= e($course['title']) ?></a></li>
        <li aria-current="page"><?= $isEdit ? 'Editar módulo' : 'Nuevo módulo' ?></li>
    </ol>
</nav>

<h1><?= $isEdit ? 'Editar el módulo' : 'Añadir un módulo' ?></h1>

<?= partial('errors', ['errors' => $errors]) ?>

<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>

    <div class="field">
        <label for="title">Título del módulo</label>
        <?= field_error($errors, 'title') ?>
        <input type="text" id="title" name="title" required maxlength="200"
               value="<?= e(old($old, 'title')) ?>"<?= field_attrs($errors, 'title') ?>>
    </div>

    <div class="field">
        <label for="description">Descripción (Markdown, opcional)</label>
        <?= field_error($errors, 'description') ?>
        <textarea id="description" name="description" rows="6" maxlength="20000" class="code"
            <?= field_attrs($errors, 'description') ?>><?= e(old($old, 'description')) ?></textarea>
    </div>

    <button type="submit" class="button"><?= $isEdit ? 'Guardar cambios' : 'Añadir módulo' ?></button>
    <a href="<?= e(url('/admin/cursos/' . $course['id'])) ?>">Cancelar</a>
</form>
