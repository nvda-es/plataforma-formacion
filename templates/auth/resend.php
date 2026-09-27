<?php
/** @var array $errors @var array $old */
?>
<div class="narrow">
    <h1>Reenviar el enlace de activación</h1>
    <p>Escribe el correo con el que te registraste y te enviaremos un nuevo enlace para activar tu cuenta. El enlace anterior dejará de funcionar.</p>

    <?= partial('errors', ['errors' => $errors]) ?>

    <form method="post" action="<?= e(url('/reenviar-verificacion')) ?>" class="form"<?= recaptcha_attrs('resend_verification') ?>>
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
