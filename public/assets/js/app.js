/*
 * Mejoras progresivas: la plataforma funciona sin este script salvo los formularios
 * protegidos por reCAPTCHA v3, que necesitan JavaScript para obtener el token.
 */
(function () {
    'use strict';

    var RECAPTCHA_ERROR = 'No se ha podido cargar la verificación anti-spam (reCAPTCHA). ' +
        'Comprueba tu conexión o desactiva los bloqueadores de contenido para este sitio y vuelve a intentarlo.';
    var RECAPTCHA_TIMEOUT = 10000;

    // Espera a que el script de reCAPTCHA esté disponible (se carga de forma asíncrona)
    function whenRecaptchaReady(callback, onTimeout) {
        var attempts = 0;
        (function check() {
            if (window.grecaptcha && typeof window.grecaptcha.ready === 'function') {
                window.grecaptcha.ready(callback);
                return;
            }
            attempts += 1;
            if (attempts > 50) {
                onTimeout();
                return;
            }
            window.setTimeout(check, 100);
        })();
    }

    function setupRecaptchaForm(form) {
        form.addEventListener('submit', function (event) {
            if (form.dataset.recaptchaDone === '1') {
                return;
            }
            event.preventDefault();

            var status = form.querySelector('[data-recaptcha-status]');
            var button = form.querySelector('[type="submit"]');
            var field = form.querySelector('input[name="g-recaptcha-response"]');

            var settled = false;
            var timer = null;
            var fail = function () {
                if (settled) {
                    return;
                }
                settled = true;
                window.clearTimeout(timer);
                if (button) {
                    button.disabled = false;
                    button.removeAttribute('aria-disabled');
                }
                if (status) {
                    status.textContent = RECAPTCHA_ERROR;
                    status.classList.add('field-error');
                }
            };

            if (button) {
                button.disabled = true;
                button.setAttribute('aria-disabled', 'true');
            }
            if (status) {
                status.classList.remove('field-error');
                status.textContent = 'Verificando…';
            }

            // Con una clave no válida o una red bloqueada, Google puede no responder nunca
            timer = window.setTimeout(fail, RECAPTCHA_TIMEOUT);

            whenRecaptchaReady(function () {
                try {
                    window.grecaptcha
                        .execute(form.dataset.recaptchaSitekey, { action: form.dataset.recaptchaAction })
                        .then(function (token) {
                            if (settled) {
                                return;
                            }
                            settled = true;
                            window.clearTimeout(timer);
                            field.value = token;
                            form.dataset.recaptchaDone = '1';
                            HTMLFormElement.prototype.submit.call(form);
                        }, fail);
                } catch (e) {
                    fail();
                }
            }, fail);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        // Lleva el foco al resumen de errores para que los lectores de pantalla lo anuncien
        var focusTarget = document.querySelector('[data-autofocus]');
        if (focusTarget) {
            focusTarget.focus();
        } else if (window.location.hash.length > 1) {
            // Tras una acción que vuelve a un elemento concreto (#modulo-3, #contenido-8…),
            // el foco va a ese elemento y el mensaje de confirmación, que queda fuera de la
            // vista, se anuncia mediante una región viva.
            var anchor = document.getElementById(decodeURIComponent(window.location.hash.slice(1)));
            var flashes = document.querySelectorAll('.main-content > .alert');
            if (anchor && flashes.length > 0) {
                if (!anchor.hasAttribute('tabindex')) {
                    anchor.setAttribute('tabindex', '-1');
                }
                anchor.focus();
                var live = document.createElement('div');
                live.className = 'visually-hidden';
                live.setAttribute('aria-live', 'polite');
                document.body.appendChild(live);
                window.setTimeout(function () {
                    live.textContent = Array.prototype.map.call(flashes, function (f) {
                        return f.textContent.trim();
                    }).join(' ');
                }, 500);
            }
        }

        // Confirmación antes de acciones destructivas
        document.querySelectorAll('form[data-confirm]').forEach(function (form) {
            form.addEventListener('submit', function (event) {
                if (!window.confirm(form.getAttribute('data-confirm'))) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                }
            });
        });

        document.querySelectorAll('form[data-recaptcha-action]').forEach(setupRecaptchaForm);
    });
})();
