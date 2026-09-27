<?php
/** @var string $name @var string $loginUrl @var string $resetUrl */
$subject = 'Ya tienes una cuenta en ' . config('app.name');
?>
<p>Hola, <?= e($name) ?>:</p>
<p>Alguien ha intentado crear una cuenta nueva con tu dirección de correo electrónico, pero ya tienes una cuenta en <?= e(config('app.name')) ?>.</p>
<p>Puedes <a href="<?= e($loginUrl) ?>" style="color:#0b57d0;">iniciar sesión</a> o, si no recuerdas la contraseña,
    <a href="<?= e($resetUrl) ?>" style="color:#0b57d0;">crear una contraseña nueva</a>.</p>
<p>Si no has sido tú, puedes ignorar este mensaje: tu cuenta no ha cambiado.</p>
