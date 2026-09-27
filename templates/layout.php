<?php
/**
 * @var string $content
 * @var string|null $title
 * @var bool|null $recaptcha
 */
$authUser = current_user();
$appName = (string) config('app.name');
$pageTitle = isset($title) && $title !== '' ? $title . ' · ' . $appName : $appName;
$useRecaptcha = !empty($recaptcha) && App\Recaptcha::enabled();
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($pageTitle) ?></title>
    <link rel="stylesheet" href="<?= e(asset('css/app.css')) ?>">
    <script nonce="<?= e(csp_nonce()) ?>" src="<?= e(asset('js/app.js')) ?>" defer></script>
    <?php if ($useRecaptcha): ?>
        <script nonce="<?= e(csp_nonce()) ?>" src="https://www.google.com/recaptcha/api.js?render=<?= e(rawurlencode(App\Recaptcha::siteKey())) ?>&amp;hl=es" async defer></script>
    <?php endif; ?>
</head>
<body>
<a class="skip-link" href="#contenido">Saltar al contenido principal</a>

<header class="site-header">
    <div class="container header-inner">
        <a class="brand" href="<?= e(url('/')) ?>"><?= e($appName) ?></a>
        <nav aria-label="Principal">
            <ul class="nav-list">
                <li><a href="<?= e(url('/')) ?>"<?= nav_current('/') ?>>Cursos</a></li>
                <?php if ($authUser !== null && $authUser['role'] === 'student'): ?>
                    <li><a href="<?= e(url('/mis-cursos')) ?>"<?= nav_current('/mis-cursos') ?>>Mis cursos</a></li>
                <?php endif; ?>
                <?php if ($authUser !== null && in_array($authUser['role'], ['teacher', 'admin'], true)): ?>
                    <li><a href="<?= e(url('/profesor')) ?>"<?= nav_current('/profesor') ?>>Corrección</a></li>
                <?php endif; ?>
                <?php if ($authUser !== null && $authUser['role'] === 'admin'): ?>
                    <li><a href="<?= e(url('/admin')) ?>"<?= nav_current('/admin') ?>>Administración</a></li>
                <?php endif; ?>
                <?php if ($authUser === null): ?>
                    <li><a href="<?= e(url('/login')) ?>"<?= nav_current('/login') ?>>Iniciar sesión</a></li>
                    <li><a href="<?= e(url('/registro')) ?>"<?= nav_current('/registro') ?>>Crear cuenta</a></li>
                <?php else: ?>
                    <li>
                        <form method="post" action="<?= e(url('/logout')) ?>" class="inline-form">
                            <?= csrf_field() ?>
                            <button type="submit" class="link-button">
                                Cerrar sesión<span class="visually-hidden"> de <?= e($authUser['name']) ?></span>
                            </button>
                        </form>
                    </li>
                <?php endif; ?>
            </ul>
        </nav>
    </div>
</header>

<main id="contenido" class="container main-content" tabindex="-1">
    <?= partial('flash') ?>
    <?= $content ?>
</main>

<footer class="site-footer">
    <div class="container">
        <p>&copy; <?= e(date('Y')) ?> <?= e($appName) ?></p>
        <?php if ($useRecaptcha): ?>
            <p class="small">
                Este sitio está protegido por reCAPTCHA y se aplican la
                <a href="https://policies.google.com/privacy?hl=es" hreflang="es">política de privacidad</a> y los
                <a href="https://policies.google.com/terms?hl=es" hreflang="es">términos del servicio</a> de Google.
            </p>
        <?php endif; ?>
    </div>
</footer>
</body>
</html>
