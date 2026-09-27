<?php
/**
 * Etiqueta de estado. El estado se indica con texto (no solo con color).
 *
 * @var string|null $status pending | approved | failed | null
 */
$class = match ($status) {
    'approved' => 'badge-success',
    'failed' => 'badge-error',
    'pending' => 'badge-warning',
    default => 'badge-neutral',
};
?>
<span class="badge <?= e($class) ?>"><?= e(submission_status_label($status)) ?></span>
