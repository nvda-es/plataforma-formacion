<?php
/** @var array $stats @var int $assigned @var int $assignedPending */
?>
<?= partial('admin_nav') ?>
<h1>Administración</h1>

<?php if ($assigned > 0): ?>
    <p class="alert alert-info">
        Tienes <?= $assigned ?> <?= $assigned === 1 ? 'curso asignado' : 'cursos asignados' ?> como profesor/a,
        con <?= $assignedPending ?> <?= $assignedPending === 1 ? 'ejercicio pendiente' : 'ejercicios pendientes' ?> de corrección.
        <a href="<?= e(url('/profesor', ['cursos' => 'mios'])) ?>">Ver mis entregas</a>
    </p>
<?php endif; ?>

<ul class="stats">
    <li><span class="stat-value"><?= (int) $stats['students'] ?></span> <span class="stat-label">alumnos</span></li>
    <li><span class="stat-value"><?= (int) $stats['teachers'] ?></span> <span class="stat-label">profesores</span></li>
    <li><span class="stat-value"><?= (int) $stats['courses'] ?></span> <span class="stat-label">cursos (<?= (int) $stats['published'] ?> publicados)</span></li>
    <li><span class="stat-value"><?= (int) $stats['enrollments'] ?></span> <span class="stat-label">matrículas (<?= (int) $stats['completed'] ?> completadas)</span></li>
    <li><span class="stat-value"><?= (int) $stats['pending'] ?></span> <span class="stat-label">ejercicios pendientes de corrección</span></li>
</ul>

<h2>Acciones rápidas</h2>
<ul class="link-list">
    <li><a href="<?= e(url('/admin/usuarios/nuevo', ['rol' => 'student'])) ?>">Registrar un alumno</a></li>
    <li><a href="<?= e(url('/admin/usuarios/nuevo', ['rol' => 'teacher'])) ?>">Registrar un profesor</a></li>
    <li><a href="<?= e(url('/admin/cursos/nuevo')) ?>">Crear un curso</a></li>
    <li><a href="<?= e(url('/admin/cursos/importar')) ?>">Importar un curso desde Markdown</a></li>
    <li><a href="<?= e(url('/profesor')) ?>">Corregir ejercicios pendientes</a></li>
</ul>
