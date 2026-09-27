<?php
/**
 * Resumen de errores de un formulario. Recibe el foco al cargar la página para que
 * los lectores de pantalla lo anuncien; cada error enlaza con su campo.
 *
 * @var array $errors
 */
if (empty($errors)) {
    return;
}
?>
<div class="error-summary" role="alert" tabindex="-1" data-autofocus aria-labelledby="error-summary-title">
    <h2 id="error-summary-title">
        <?= count($errors) === 1 ? 'Hay un problema' : 'Hay ' . count($errors) . ' problemas' ?>
    </h2>
    <ul>
        <?php foreach ($errors as $field => $message): ?>
            <li>
                <?php if (str_starts_with((string) $field, '_')): ?>
                    <?= e($message) ?>
                <?php else: ?>
                    <a href="#<?= e($field) ?>"><?= e($message) ?></a>
                <?php endif; ?>
            </li>
        <?php endforeach; ?>
    </ul>
</div>
