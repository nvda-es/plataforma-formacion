<?php
/** @var array $enrollments */
?>
<h1>Mis cursos</h1>

<?php if ($enrollments === []): ?>
    <p>Todavía no estás matriculado en ningún curso.</p>
    <p><a class="button" href="<?= e(url('/')) ?>">Ver los cursos disponibles</a></p>
<?php else: ?>
    <ul class="card-list">
        <?php foreach ($enrollments as $enrollment): ?>
            <li class="card">
                <h2 class="card-title">
                    <a href="<?= e(url('/cursos/' . $enrollment['slug'])) ?>"><?= e($enrollment['title']) ?></a>
                </h2>
                <?php if ($enrollment['completed_at'] !== null): ?>
                    <p><span class="badge badge-success">Completado</span> el <?= time_tag($enrollment['completed_at'], false) ?></p>
                <?php else: ?>
                    <p><span class="badge badge-neutral">En curso</span></p>
                <?php endif; ?>
                <?= partial('progress', ['progress' => $enrollment['progress'], 'id' => 'progress-' . (int) $enrollment['course_id']]) ?>
            </li>
        <?php endforeach; ?>
    </ul>
    <p><a href="<?= e(url('/')) ?>">Ver más cursos disponibles</a></p>
<?php endif; ?>
