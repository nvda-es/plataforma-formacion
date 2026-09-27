<?php
$labels = ['success' => 'Hecho', 'error' => 'Error', 'info' => 'Información', 'warning' => 'Aviso'];
foreach (take_flashes() as $flash):
    $type = isset($labels[$flash['type']]) ? $flash['type'] : 'info';
    ?>
    <div class="alert alert-<?= e($type) ?>" role="<?= $type === 'error' ? 'alert' : 'status' ?>">
        <p><strong><?= e($labels[$type]) ?>:</strong> <?= e($flash['message']) ?></p>
    </div>
<?php endforeach; ?>
