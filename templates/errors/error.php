<?php
/**
 * @var int $status
 * @var string $title
 * @var string $message
 * @var Throwable|null $exception
 */
?>
<h1><?= e($title) ?></h1>
<p><?= e($message) ?></p>
<p class="muted small">Código de error: <?= (int) $status ?></p>
<p><a href="<?= e(url('/')) ?>">Volver a la página de inicio</a></p>

<?php if (!empty($exception)): ?>
    <details class="debug">
        <summary>Detalles técnicos (solo visibles en modo depuración)</summary>
        <pre><?= e(get_class($exception) . ': ' . $exception->getMessage() . "\n\n" . $exception->getTraceAsString()) ?></pre>
    </details>
<?php endif; ?>
