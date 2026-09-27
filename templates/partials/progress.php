<?php
/**
 * Barra de progreso accesible con texto equivalente.
 *
 * @var array $progress resultado de App\Progress::summary()
 * @var string $id identificador único para el elemento
 */
?>
<div class="progress">
    <p id="<?= e($id) ?>-label" class="progress-label">
        <?= (int) $progress['done'] ?> de <?= (int) $progress['total'] ?> contenidos completados
        (<?= (int) $progress['percent'] ?> %)
    </p>
    <progress id="<?= e($id) ?>" max="100" value="<?= (int) $progress['percent'] ?>" aria-labelledby="<?= e($id) ?>-label">
        <?= (int) $progress['percent'] ?> %
    </progress>
    <p class="small muted">
        Unidades leídas: <?= (int) $progress['units_read'] ?> de <?= (int) $progress['units'] ?>.
        Ejercicios aprobados: <?= (int) $progress['exercises_approved'] ?> de <?= (int) $progress['exercises'] ?>.
    </p>
</div>
