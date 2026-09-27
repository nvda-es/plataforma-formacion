<?php
/**
 * @var string $name
 * @var string $studentName
 * @var string $courseTitle
 * @var string $itemTitle
 * @var string $url
 */
$subject = 'Ejercicio listo para calificar: ' . $itemTitle;
?>
<p>Hola, <?= e($name) ?>:</p>
<p><strong><?= e($studentName) ?></strong> ha enviado su respuesta a un ejercicio y está lista para calificar.</p>
<ul>
    <li>Curso: <?= e($courseTitle) ?></li>
    <li>Ejercicio: <?= e($itemTitle) ?></li>
</ul>
<p><a href="<?= e($url) ?>" style="color:#0b57d0;font-weight:bold;">Ver la respuesta y calificarla</a></p>
