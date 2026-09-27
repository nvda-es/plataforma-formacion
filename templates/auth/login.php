<?php
/** @var array $errors @var array $old @var bool|null $unverified */
?>
<div class="narrow">
    <h1>Iniciar sesión</h1>

    <?= partial('errors', ['errors' => $errors]) ?>

    <?php if (!empty($unverified)): ?>
        <p><a href="<?= e(url('/reenviar-verificacion')) ?>">Enviarme un nuevo enlace de activación</a></p>
    <?php endif; ?>

    <form method="post" action="<?= e(url('/login')) ?>" class="form"<?= recaptcha_attrs('login') ?>>
        <?= csrf_field() ?>

        <div class="field">
            <label for="email">Correo electrónico</label>
            <input type="email" id="email" name="email" required maxlength="190" autocomplete="username" spellcheck="false"
                   value="<?= e(old($old, 'email')) ?>">
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <input type="password" id="password" name="password" required maxlength="<?= App\Password::MAX_LENGTH ?>"
                   autocomplete="current-password">
        </div>

        <?= partial('recaptcha') ?>

        <button type="submit" class="button">Iniciar sesión</button>
    </form>

    <ul class="link-list">
        <li><a href="<?= e(url('/recuperar-contrasena')) ?>">He olvidado mi contraseña</a></li>
        <li><a href="<?= e(url('/reenviar-verificacion')) ?>">No he recibido el correo de activación</a></li>
        <li><a href="<?= e(url('/registro')) ?>">Crear una cuenta nueva</a></li>
    </ul>
</div>
