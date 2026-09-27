<?php
/** @var array $errors @var string $token */
?>
<div class="narrow">
    <h1>Crear una contraseña nueva</h1>

    <?= partial('errors', ['errors' => $errors]) ?>

    <form method="post" action="<?= e(url('/restablecer-contrasena')) ?>" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="token" value="<?= e($token) ?>">

        <div class="field">
            <label for="password">Contraseña nueva</label>
            <p class="hint" id="password-hint">Al menos <?= App\Password::MIN_LENGTH ?> caracteres.</p>
            <?= field_error($errors, 'password') ?>
            <input type="password" id="password" name="password" required minlength="<?= App\Password::MIN_LENGTH ?>"
                   maxlength="<?= App\Password::MAX_LENGTH ?>" autocomplete="new-password"<?= field_attrs($errors, 'password', 'password-hint') ?>>
        </div>

        <div class="field">
            <label for="password_confirmation">Repite la contraseña nueva</label>
            <?= field_error($errors, 'password_confirmation') ?>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   maxlength="<?= App\Password::MAX_LENGTH ?>" autocomplete="new-password"<?= field_attrs($errors, 'password_confirmation') ?>>
        </div>

        <button type="submit" class="button">Guardar contraseña</button>
    </form>
</div>
