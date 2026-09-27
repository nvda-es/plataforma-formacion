<?php
/** @var array $courses @var array $enrolledIds @var array|null $user */
?>
<h1>Cursos disponibles</h1>

<?php if ($user === null): ?>
    <p class="lead">
        Explora el catálogo. Para matricularte en un curso,
        <a href="<?= e(url('/registro')) ?>">crea una cuenta</a> o
        <a href="<?= e(url('/login')) ?>">inicia sesión</a>.
    </p>
<?php endif; ?>

<?php if ($courses === []): ?>
    <p>Todavía no hay cursos publicados. Vuelve pronto.</p>
<?php else: ?>
    <ul class="card-list">
        <?php foreach ($courses as $course): ?>
            <li class="card">
                <h2 class="card-title">
                    <a href="<?= e(url('/cursos/' . $course['slug'])) ?>"><?= e($course['title']) ?></a>
                </h2>
                <?php if (!empty($course['teacher_name'])): ?>
                    <p class="meta">Profesor/a: <?= e($course['teacher_name']) ?></p>
                <?php endif; ?>
                <?php if (isset($enrolledIds[(int) $course['id']])): ?>
                    <p><span class="badge badge-success">Estás matriculado</span></p>
                <?php endif; ?>
                <div class="prose card-summary"><?= markdown($course['summary'] ?? '', 3) ?></div>
            </li>
        <?php endforeach; ?>
    </ul>
<?php endif; ?>
