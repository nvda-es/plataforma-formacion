<?php
/** @var array $errors @var array $old */
?>
<div class="narrow">
    <h1>Crear una cuenta</h1>
    <p>Regístrate como alumno para matricularte en los cursos. Te enviaremos un correo para verificar tu dirección y activar la cuenta.</p>

    <?= partial('errors', ['errors' => $errors]) ?>

    <form method="post" action="<?= e(url('/registro')) ?>" class="form"<?= recaptcha_attrs('register') ?>>
        <?= csrf_field() ?>

        <div class="field">
            <label for="name">Nombre y apellidos</label>
            <?= field_error($errors, 'name') ?>
            <input type="text" id="name" name="name" required maxlength="120" autocomplete="name"
                   value="<?= e(old($old, 'name')) ?>"<?= field_attrs($errors, 'name') ?>>
        </div>

        <div class="field">
            <label for="email">Correo electrónico</label>
            <?= field_error($errors, 'email') ?>
            <input type="email" id="email" name="email" required maxlength="190" autocomplete="email" spellcheck="false"
                   value="<?= e(old($old, 'email')) ?>"<?= field_attrs($errors, 'email') ?>>
        </div>

        <div class="field">
            <label for="password">Contraseña</label>
            <p class="hint" id="password-hint">Al menos <?= App\Password::MIN_LENGTH ?> caracteres. Te recomendamos usar una frase fácil de recordar.</p>
            <?= field_error($errors, 'password') ?>
            <input type="password" id="password" name="password" required minlength="<?= App\Password::MIN_LENGTH ?>"
                   maxlength="<?= App\Password::MAX_LENGTH ?>" autocomplete="new-password"<?= field_attrs($errors, 'password', 'password-hint') ?>>
        </div>

        <div class="field">
            <label for="password_confirmation">Repite la contraseña</label>
            <?= field_error($errors, 'password_confirmation') ?>
            <input type="password" id="password_confirmation" name="password_confirmation" required
                   maxlength="<?= App\Password::MAX_LENGTH ?>" autocomplete="new-password"<?= field_attrs($errors, 'password_confirmation') ?>>
        </div>

        <?= partial('recaptcha') ?>

        <button type="submit" class="button">Crear cuenta</button>
    </form>

    <p>¿Ya tienes cuenta? <a href="<?= e(url('/login')) ?>">Inicia sesión</a>.</p>
</div>
