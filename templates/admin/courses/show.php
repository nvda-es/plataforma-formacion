<?php
/** @var array $course @var array $outline */
$moveForm = static function (string $action, string $direction, string $label, bool $disabled): string {
    if ($disabled) {
        return '';
    }
    return '<form method="post" action="' . e($action) . '" class="inline-form">'
        . csrf_field()
        . '<input type="hidden" name="direction" value="' . e($direction) . '">'
        . '<button type="submit" class="button button-small button-secondary">' . $label . '</button>'
        . '</form>';
};
$moduleCount = count($outline);
?>
<?= partial('admin_nav') ?>
<nav aria-label="Ruta de navegación" class="breadcrumbs">
    <ol>
        <li><a href="<?= e(url('/admin/cursos')) ?>">Cursos</a></li>
        <li aria-current="page"><?= e($course['title']) ?></li>
    </ol>
</nav>

<h1><?= e($course['title']) ?></h1>

<p>
    <?php if ((int) $course['is_published']): ?>
        <span class="badge badge-success">Publicado</span>
    <?php else: ?>
        <span class="badge badge-neutral">Borrador</span>
    <?php endif; ?>
    Profesor/a: <?= $course['teacher_name'] !== null ? e($course['teacher_name']) : 'sin asignar' ?>
</p>

<ul class="button-row">
    <li><a class="button" href="<?= e(url('/admin/cursos/' . $course['id'] . '/modulos/nuevo')) ?>">Añadir un módulo</a></li>
    <li><a class="button button-secondary" href="<?= e(url('/admin/cursos/' . $course['id'] . '/editar')) ?>">Editar datos del curso</a></li>
    <li><a class="button button-secondary" href="<?= e(url('/cursos/' . $course['slug'])) ?>">Ver como alumno</a></li>
</ul>

<?php if ($outline === []): ?>
    <p>El curso no tiene módulos. Empieza añadiendo uno.</p>
<?php endif; ?>

<?php foreach ($outline as $mIndex => $module): ?>
    <?php $mLabel = e($module['title']); ?>
    <section class="panel module-admin" id="modulo-<?= (int) $module['id'] ?>" aria-labelledby="module-title-<?= (int) $module['id'] ?>" tabindex="-1">
        <h2 id="module-title-<?= (int) $module['id'] ?>">Módulo <?= $mIndex + 1 ?>: <?= $mLabel ?></h2>

        <div class="toolbar" role="group" aria-label="Acciones del módulo <?= $mLabel ?>">
            <?= $moveForm(url('/admin/modulos/' . $module['id'] . '/mover'), 'up', 'Subir<span class="visually-hidden"> el módulo ' . $mLabel . '</span>', $mIndex === 0) ?>
            <?= $moveForm(url('/admin/modulos/' . $module['id'] . '/mover'), 'down', 'Bajar<span class="visually-hidden"> el módulo ' . $mLabel . '</span>', $mIndex === $moduleCount - 1) ?>
            <a class="button button-small button-secondary" href="<?= e(url('/admin/modulos/' . $module['id'] . '/editar')) ?>">Editar<span class="visually-hidden"> el módulo <?= $mLabel ?></span></a>
            <a class="button button-small" href="<?= e(url('/admin/modulos/' . $module['id'] . '/contenidos/nuevo', ['tipo' => 'unit'])) ?>">Añadir unidad<span class="visually-hidden"> al módulo <?= $mLabel ?></span></a>
            <a class="button button-small" href="<?= e(url('/admin/modulos/' . $module['id'] . '/contenidos/nuevo', ['tipo' => 'exercise'])) ?>">Añadir ejercicio<span class="visually-hidden"> al módulo <?= $mLabel ?></span></a>
            <form method="post" action="<?= e(url('/admin/modulos/' . $module['id'] . '/eliminar')) ?>" class="inline-form"
                  data-confirm="¿Eliminar el módulo «<?= $mLabel ?>» con todos sus contenidos y las respuestas de los alumnos?">
                <?= csrf_field() ?>
                <button type="submit" class="button button-small button-danger">Eliminar<span class="visually-hidden"> el módulo <?= $mLabel ?></span></button>
            </form>
        </div>

        <?php if ($module['items'] === []): ?>
            <p class="muted">Este módulo no tiene contenidos.</p>
        <?php else: ?>
            <ol class="admin-item-list">
                <?php $itemCount = count($module['items']); ?>
                <?php foreach ($module['items'] as $iIndex => $item): ?>
                    <?php $iLabel = e($item['title']); ?>
                    <li id="contenido-<?= (int) $item['id'] ?>" tabindex="-1">
                        <p class="admin-item-title">
                            <span class="badge <?= $item['type'] === 'exercise' ? 'badge-warning' : 'badge-neutral' ?>">
                                <?= $item['type'] === 'exercise' ? 'Ejercicio' : 'Unidad' ?>
                            </span>
                            <?= $iLabel ?>
                        </p>
                        <div class="toolbar" role="group" aria-label="Acciones de <?= $iLabel ?>">
                            <?= $moveForm(url('/admin/contenidos/' . $item['id'] . '/mover'), 'up', 'Subir<span class="visually-hidden"> ' . $iLabel . '</span>', $iIndex === 0) ?>
                            <?= $moveForm(url('/admin/contenidos/' . $item['id'] . '/mover'), 'down', 'Bajar<span class="visually-hidden"> ' . $iLabel . '</span>', $iIndex === $itemCount - 1) ?>
                            <a class="button button-small button-secondary" href="<?= e(url('/admin/contenidos/' . $item['id'] . '/editar')) ?>">Editar<span class="visually-hidden"> <?= $iLabel ?></span></a>
                            <a class="button button-small button-secondary" href="<?= e(url('/cursos/' . $course['slug'] . '/contenido/' . $item['id'])) ?>">Vista previa<span class="visually-hidden"> de <?= $iLabel ?></span></a>
                            <form method="post" action="<?= e(url('/admin/contenidos/' . $item['id'] . '/eliminar')) ?>" class="inline-form"
                                  data-confirm="¿Eliminar «<?= $iLabel ?>»?<?= $item['type'] === 'exercise' ? ' También se borrarán las respuestas de los alumnos.' : '' ?>">
                                <?= csrf_field() ?>
                                <button type="submit" class="button button-small button-danger">Eliminar<span class="visually-hidden"> <?= $iLabel ?></span></button>
                            </form>
                        </div>
                    </li>
                <?php endforeach; ?>
            </ol>
        <?php endif; ?>
    </section>
<?php endforeach; ?>
