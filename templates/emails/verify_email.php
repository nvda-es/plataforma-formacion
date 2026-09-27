<?php
/** @var string $name @var string $url @var int $hours */
$subject = 'Confirma tu correo electrónico para activar tu cuenta';
?>
<p>Hola, <?= e($name) ?>:</p>
<p>Gracias por registrarte en <?= e(config('app.name')) ?>. Para terminar de activar tu cuenta, confirma tu dirección de correo electrónico:</p>
<p><a href="<?= e($url) ?>" style="color:#0b57d0;font-weight:bold;">Confirmar mi correo electrónico</a></p>
<p>Si el enlace no funciona, copia esta dirección en tu navegador:<br><?= e($url) ?></p>
<p>El enlace caduca en <?= (int) $hours ?> horas. Si no has creado esta cuenta, ignora este mensaje.</p>
