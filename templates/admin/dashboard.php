<?php
/** @var array $stats */
?>
<?= partial('admin_nav') ?>
<h1>Administración</h1>

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
