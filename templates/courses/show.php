<?php
/**
 * @var array $course
 * @var array|null $user
 * @var array|null $enrollment
 * @var array $outline
 * @var array|null $progress
 * @var array|null $nextItem
 * @var bool $preview
 */
$canOpen = $enrollment !== null || $preview;
$itemUrl = static fn (array $item): string => url('/cursos/' . $course['slug'] . '/contenido/' . $item['id']);
?>
<nav aria-label="Ruta de navegación" class="breadcrumbs">
    <ol>
        <li><a href="<?= e(url('/')) ?>">Cursos</a></li>
        <li aria-current="page"><?= e($course['title']) ?></li>
    </ol>
</nav>

<h1><?= e($course['title']) ?></h1>

<?php if (!empty($course['teacher_name'])): ?>
    <p class="meta">Profesor/a: <?= e($course['teacher_name']) ?></p>
<?php endif; ?>

<?php if (!(int) $course['is_published']): ?>
    <p class="alert alert-warning">Este curso no está publicado: solo lo ven la administración, su profesor y los alumnos ya matriculados.</p>
<?php endif; ?>

<div class="prose"><?= markdown($course['summary'] ?? '') ?></div>

<section class="panel" aria-labelledby="enrollment-title">
    <h2 id="enrollment-title">Tu matrícula</h2>
    <?php if ($enrollment !== null): ?>
        <?php if ($enrollment['completed_at'] !== null): ?>
            <p><span class="badge badge-success">Curso completado</span> el <?= time_tag($enrollment['completed_at'], false) ?>. ¡Enhorabuena!</p>
        <?php else: ?>
            <p>Estás matriculado desde el <?= time_tag($enrollment['enrolled_at'], false) ?>.</p>
        <?php endif; ?>
        <?= partial('progress', ['progress' => $progress, 'id' => 'course-progress']) ?>
        <?php if ($nextItem !== null): ?>
            <p>
                <a class="button" href="<?= e($itemUrl($nextItem)) ?>">
                    <?= $progress['done'] > 0 ? 'Continuar' : 'Empezar el curso' ?>
                    <span class="visually-hidden">: <?= e($nextItem['title']) ?></span>
                </a>
            </p>
        <?php endif; ?>
    <?php elseif ($preview): ?>
        <p>Estás viendo el curso como <?= e(role_label($user['role'])) ?>. Puedes abrir los contenidos en modo de vista previa.</p>
    <?php elseif ($user === null): ?>
        <p>
            Para matricularte, <a href="<?= e(url('/login')) ?>">inicia sesión</a>
            o <a href="<?= e(url('/registro')) ?>">crea una cuenta</a>.
        </p>
    <?php elseif ($user['role'] === 'student'): ?>
        <form method="post" action="<?= e(url('/cursos/' . $course['slug'] . '/matricula')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="button">Matricularme en este curso</button>
        </form>
    <?php else: ?>
        <p>Solo los usuarios con rol de alumno pueden matricularse en los cursos.</p>
    <?php endif; ?>
</section>

<section aria-labelledby="syllabus-title">
    <h2 id="syllabus-title">Contenido del curso</h2>
    <?php if ($outline === []): ?>
        <p>Este curso todavía no tiene contenidos.</p>
    <?php endif; ?>

    <?php foreach ($outline as $mIndex => $module): ?>
        <section class="module" aria-labelledby="module-<?= (int) $module['id'] ?>">
            <h3 id="module-<?= (int) $module['id'] ?>">
                Módulo <?= $mIndex + 1 ?>: <?= e($module['title']) ?>
            </h3>
            <?php if (!empty($module['description'])): ?>
                <div class="prose"><?= markdown($module['description'], 4) ?></div>
            <?php endif; ?>

            <?php if ($module['items'] === []): ?>
                <p class="muted">Sin contenidos.</p>
            <?php else: ?>
                <ol class="item-list">
                    <?php foreach ($module['items'] as $item): ?>
                        <li>
                            <span class="item-type"><?= $item['type'] === 'exercise' ? 'Ejercicio' : 'Unidad' ?>:</span>
                            <?php if ($canOpen): ?>
                                <a href="<?= e($itemUrl($item)) ?>"><?= e($item['title']) ?></a>
                            <?php else: ?>
                                <?= e($item['title']) ?>
                            <?php endif; ?>
                            <?php if ($enrollment !== null): ?>
                                <?php if ($item['type'] === 'unit'): ?>
                                    <?php if ($item['viewed']): ?>
                                        <span class="badge badge-success">Leída</span>
                                    <?php else: ?>
                                        <span class="badge badge-neutral">Sin leer</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <?= partial('status_badge', ['status' => $item['last_status']]) ?>
                                <?php endif; ?>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            <?php endif; ?>
        </section>
    <?php endforeach; ?>
</section>
