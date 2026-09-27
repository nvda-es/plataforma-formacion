<?php
/** @var string $title @var string $message @var string $actionUrl @var string $actionLabel */
?>
<div class="narrow">
    <h1><?= e($title) ?></h1>
    <p><?= e($message) ?></p>
    <p><a class="button" href="<?= e(url($actionUrl)) ?>"><?= e($actionLabel) ?></a></p>
</div>
