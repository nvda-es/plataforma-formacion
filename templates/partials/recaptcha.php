<?php
/** Campos necesarios en un formulario protegido con reCAPTCHA v3. */
if (!App\Recaptcha::enabled()) {
    return;
}
?>
<input type="hidden" name="g-recaptcha-response" value="">
<p class="recaptcha-status" data-recaptcha-status role="status" aria-live="polite"></p>
<noscript>
    <p class="alert alert-warning">
        Este formulario necesita JavaScript para la verificación anti-spam (reCAPTCHA).
        Actívalo en tu navegador para poder continuar.
    </p>
</noscript>
