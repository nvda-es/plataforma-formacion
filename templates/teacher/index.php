<?php
/** @var array $submissions @var string $status @var array $filters @var bool $isAdmin @var bool $onlyMine */
?>
<h1>Corrección de ejercicios</h1>

<form method="get" action="<?= e(url('/profesor')) ?>" class="filter-form">
    <div class="field">
        <label for="estado">Mostrar entregas</label>
        <select id="estado" name="estado">
            <?php foreach ($filters as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= selected($status === $value) ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <?php if (!empty($isAdmin)): ?>
        <div class="field">
            <label for="cursos">Cursos</label>
            <select id="cursos" name="cursos">
                <option value="todos"<?= selected(!$onlyMine) ?>>Todos los cursos</option>
                <option value="mios"<?= selected($onlyMine) ?>>Solo los cursos que tengo asignados</option>
            </select>
        </div>
    <?php endif; ?>
    <button type="submit" class="button button-secondary">Filtrar</button>
</form>

<?php if ($submissions === []): ?>
    <p><?= $status === 'pending' ? 'No hay ejercicios pendientes de corrección.' : 'No hay entregas con este filtro.' ?></p>
<?php else: ?>
    <div class="table-wrapper" role="region" aria-labelledby="submissions-caption" tabindex="0">
        <table>
            <caption id="submissions-caption"><?= e($filters[$status]) ?> (<?= count($submissions) ?>)</caption>
            <thead>
            <tr>
                <th scope="col">Alumno</th>
                <th scope="col">Curso</th>
                <th scope="col">Ejercicio</th>
                <th scope="col">Entregado</th>
                <th scope="col">Estado</th>
                <th scope="col">Acción</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($submissions as $s): ?>
                <tr>
                    <th scope="row"><?= e($s['student_name']) ?></th>
                    <td><?= e($s['course_title']) ?></td>
                    <td><?= e($s['item_title']) ?></td>
                    <td><?= time_tag($s['submitted_at']) ?></td>
                    <td><?= partial('status_badge', ['status' => $s['status']]) ?></td>
                    <td>
                        <a href="<?= e(url('/profesor/entregas/' . $s['id'])) ?>">
                            <?= $s['status'] === 'pending' ? 'Calificar' : 'Ver' ?>
                            <span class="visually-hidden">la entrega de <?= e($s['student_name']) ?> en «<?= e($s['item_title']) ?>»</span>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
<?php endif; ?>
