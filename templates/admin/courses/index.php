<?php
/** @var array $courses */
?>
<?= partial('admin_nav') ?>
<h1>Cursos</h1>

<ul class="button-row">
    <li><a class="button" href="<?= e(url('/admin/cursos/nuevo')) ?>">Crear un curso</a></li>
    <li><a class="button button-secondary" href="<?= e(url('/admin/cursos/importar')) ?>">Importar desde Markdown</a></li>
</ul>

<?php if ($courses === []): ?>
    <p>Todavía no hay cursos.</p>
<?php else: ?>
    <div class="table-wrapper" role="region" aria-labelledby="courses-caption" tabindex="0">
        <table>
            <caption id="courses-caption"><?= count($courses) ?> cursos</caption>
            <thead>
            <tr>
                <th scope="col">Curso</th>
                <th scope="col">Profesor/a</th>
                <th scope="col">Estado</th>
                <th scope="col">Módulos</th>
                <th scope="col">Contenidos</th>
                <th scope="col">Matrículas</th>
                <th scope="col">Acciones</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($courses as $c): ?>
                <tr>
                    <th scope="row"><a href="<?= e(url('/admin/cursos/' . $c['id'])) ?>"><?= e($c['title']) ?></a></th>
                    <td><?= $c['teacher_name'] !== null ? e($c['teacher_name']) : '<span class="muted">Sin asignar</span>' ?></td>
                    <td>
                        <?php if ((int) $c['is_published']): ?>
                            <span class="badge badge-success">Publicado</span>
                        <?php else: ?>
                            <span class="badge badge-neutral">Borrador</span>
                        <?php endif; ?>
                    </td>
                    <td><?= (int) $c['modules_count'] ?></td>
                    <td><?= (int) $c['items_count'] ?></td>
                    <td><?= (int) $c['enrollments_count'] ?></td>
                    <td>
                        <ul class="actions">
                            <li><a href="<?= e(url('/admin/cursos/' . $c['id'])) ?>">Contenidos<span class="visually-hidden"> de <?= e($c['title']) ?></span></a></li>
                            <li><a href="<?= e(url('/admin/cursos/' . $c['id'] . '/exportar')) ?>" download>Exportar<span class="visually-hidden"> <?= e($c['title']) ?> en Markdown</span></a></li>
                            <li><a href="<?= e(url('/admin/cursos/' . $c['id'] . '/editar')) ?>">Editar<span class="visually-hidden"> <?= e($c['title']) ?></span></a></li>
                            <li><a href="<?= e(url('/cursos/' . $c['slug'])) ?>">Ver<span class="visually-hidden"> <?= e($c['title']) ?> como alumno</span></a></li>
                        </ul>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
