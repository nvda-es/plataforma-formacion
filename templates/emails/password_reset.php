<?php
/** @var string $name @var string $url @var int $minutes */
$subject = 'Crea una contraseña nueva';
?>
<p>Hola, <?= e($name) ?>:</p>
<p>Hemos recibido una solicitud para crear una contraseña nueva para tu cuenta.</p>
<p><a href="<?= e($url) ?>" style="color:#0b57d0;font-weight:bold;">Crear una contraseña nueva</a></p>
<p>Si el enlace no funciona, copia esta dirección en tu navegador:<br><?= e($url) ?></p>
<p>El enlace caduca en <?= (int) $minutes ?> minutos y solo se puede usar una vez. Si no lo has solicitado tú, ignora este mensaje: tu contraseña no cambiará.</p>
