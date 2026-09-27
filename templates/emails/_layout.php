<?php
/** @var string $subject @var string $body */
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($subject) ?></title>
</head>
<body style="margin:0;padding:0;background:#f4f4f2;font-family:Arial,Helvetica,sans-serif;font-size:16px;line-height:1.6;color:#1f2328;">
<div role="article" aria-roledescription="correo electrónico" aria-label="<?= e($subject) ?>" lang="es"
     style="max-width:600px;margin:0 auto;padding:24px;background:#ffffff;">
    <p style="margin:0 0 24px;font-size:18px;font-weight:bold;color:#1f2a44;"><?= e(config('app.name')) ?></p>
    <?= $body ?>
    <hr style="border:none;border-top:1px solid #c9ced4;margin:32px 0 16px;">
    <p style="font-size:14px;color:#555d66;margin:0;">
        Este es un mensaje automático de <?= e(config('app.name')) ?>. Por favor, no respondas a este correo.
    </p>
</div>
</body>
</html>
