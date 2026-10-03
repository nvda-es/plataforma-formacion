<?php
/**
 * @var string $name
 * @var string $courseTitle
 * @var string $itemTitle
 * @var bool $approved
 * @var string $feedback
 * @var string $url
 */
$subject = ($approved ? 'Ejercicio aprobado: ' : 'Ejercicio calificado: ') . $itemTitle;
?>
<p>Hola, <?= e($name) ?>:</p>
<p>Tu profesor ha calificado el ejercicio «<?= e($itemTitle) ?>» del curso «<?= e($courseTitle) ?>».</p>
<p>Resultado: <strong><?= $approved ? 'Aprobado' : 'Suspendido' ?></strong></p>
<?php if ($feedback !== ''): ?>
    <p>Comentario del profesor:</p>
    <blockquote style="margin:0 0 16px;padding:12px 16px;border-left:4px solid #0b57d0;background:#f4f7fd;white-space:pre-wrap;"><?= e($feedback) ?></blockquote>
<?php endif; ?>
<?php if (!$approved): ?>
    <p>Revisa el comentario y envía una nueva respuesta cuando estés listo.</p>
<?php endif; ?>
<p><a href="<?= e($url) ?>" style="color:#0b57d0;font-weight:bold;">Ir al ejercicio</a></p>
