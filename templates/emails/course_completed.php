<?php
/** @var string $name @var string $courseTitle @var string $courseUrl */
$subject = '¡Enhorabuena! Has completado el curso «' . $courseTitle . '»';
?>
<p>Hola, <?= e($name) ?>:</p>
<p>¡Enhorabuena! Has leído todas las unidades y has aprobado todos los ejercicios del curso <strong>«<?= e($courseTitle) ?>»</strong>, así que el curso queda marcado como completado.</p>
<p>Gracias por tu esfuerzo y dedicación. Puedes volver a consultar los contenidos siempre que quieras:</p>
<p><a href="<?= e($courseUrl) ?>" style="color:#0b57d0;font-weight:bold;">Ir al curso</a></p>
