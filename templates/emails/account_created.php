<?php
/**
 * @var string $name
 * @var string $roleLabel
 * @var string|null $setPasswordUrl
 * @var string $loginUrl
 * @var int $days
 */
$subject = 'Tu cuenta en ' . config('app.name');
?>
<p>Hola, <?= e($name) ?>:</p>
<p>La administración de <?= e(config('app.name')) ?> te ha dado acceso a la plataforma con el perfil de <strong><?= e(mb_strtolower($roleLabel)) ?></strong>.</p>
<?php if ($setPasswordUrl !== null): ?>
    <p>Para empezar, crea tu contraseña:</p>
    <p><a href="<?= e($setPasswordUrl) ?>" style="color:#0b57d0;font-weight:bold;">Crear mi contraseña</a></p>
    <p>Si el enlace no funciona, copia esta dirección en tu navegador:<br><?= e($setPasswordUrl) ?></p>
    <p>El enlace caduca en <?= (int) $days ?> días. Si caduca, pide uno nuevo desde «He olvidado mi contraseña» en la página de inicio de sesión.</p>
<?php else: ?>
    <p>Ya puedes <a href="<?= e($loginUrl) ?>" style="color:#0b57d0;">iniciar sesión</a> con la contraseña que te haya facilitado la administración.
        Te recomendamos cambiarla desde «He olvidado mi contraseña».</p>
<?php endif; ?>
