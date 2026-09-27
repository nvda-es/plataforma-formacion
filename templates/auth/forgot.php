<?php
/** @var array $errors @var array $old */
?>
<div class="narrow">
    <h1>Recuperar la contraseña</h1>
    <p>Escribe tu correo electrónico y te enviaremos un enlace para crear una contraseña nueva. El enlace caduca en 1 hora.</p>

    <?= partial('errors', ['errors' => $errors]) ?>

    <form method="post" action="<?= e(url('/recuperar-contrasena')) ?>" class="form"<?= recaptcha_attrs('forgot_password') ?>>
        <?= csrf_field() ?>
        <div class="field">
            <label for="email">Correo electrónico</label>
            <?= field_error($errors, 'email') ?>
            <input type="email" id="email" name="email" required maxlength="190" autocomplete="email" spellcheck="false"
                   value="<?= e(old($old, 'email')) ?>"<?= field_attrs($errors, 'email') ?>>
        </div>
        <?= partial('recaptcha') ?>
        <button type="submit" class="button">Enviar enlace</button>
    </form>

    <p><a href="<?= e(url('/login')) ?>">Volver a iniciar sesión</a></p>
</div>
