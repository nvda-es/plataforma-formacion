<?php
/**
 * @var array $submission
 * @var array $previous
 * @var int $attemptNumber
 * @var array $errors
 * @var array $old
 */
$oldStatus = old($old, 'status');
?>
<nav aria-label="Ruta de navegación" class="breadcrumbs">
    <ol>
        <li><a href="<?= e(url('/profesor')) ?>">Corrección de ejercicios</a></li>
        <li aria-current="page">Entrega de <?= e($submission['student_name']) ?></li>
    </ol>
</nav>

<h1>Entrega de <?= e($submission['student_name']) ?></h1>

<dl class="summary-list">
    <div><dt>Curso</dt><dd><?= e($submission['course_title']) ?></dd></div>
    <div><dt>Módulo</dt><dd><?= e($submission['module_title']) ?></dd></div>
    <div><dt>Ejercicio</dt><dd><?= e($submission['item_title']) ?></dd></div>
    <div><dt>Alumno</dt><dd><?= e($submission['student_name']) ?> (<?= e($submission['student_email']) ?>)</dd></div>
    <div><dt>Entregado</dt><dd><?= time_tag($submission['submitted_at']) ?> · Intento <?= (int) $attemptNumber ?></dd></div>
    <div><dt>Estado</dt><dd><?= partial('status_badge', ['status' => $submission['status']]) ?></dd></div>
</dl>

<details class="panel">
    <summary>Enunciado del ejercicio</summary>
    <div class="prose"><?= markdown($submission['item_content'], 3) ?></div>
</details>

<section aria-labelledby="answer-title">
    <h2 id="answer-title">Respuesta del alumno</h2>
    <div class="answer-text"><?= e($submission['answer']) ?></div>
</section>

<?php if ($submission['status'] === 'pending'): ?>
    <section class="panel" aria-labelledby="grade-title">
        <h2 id="grade-title">Calificar</h2>
        <?= partial('errors', ['errors' => $errors]) ?>
        <form method="post" action="<?= e(url('/profesor/entregas/' . $submission['id'])) ?>" class="form">
            <?= csrf_field() ?>
            <fieldset class="field"<?= isset($errors['status']) ? ' aria-describedby="status-error"' : '' ?>>
                <legend>Resultado</legend>
                <?= field_error($errors, 'status') ?>
                <div class="radio">
                    <input type="radio" id="status" name="status" value="approved" required<?= checked($oldStatus === 'approved') ?>>
                    <label for="status">Aprobado</label>
                </div>
                <div class="radio">
                    <input type="radio" id="status-failed" name="status" value="failed" required<?= checked($oldStatus === 'failed') ?>>
                    <label for="status-failed">Suspendido (el alumno podrá enviar una nueva respuesta)</label>
                </div>
            </fieldset>

            <div class="field">
                <label for="feedback">Comentario de feedback para el alumno</label>
                <p class="hint" id="feedback-hint">El alumno lo recibirá por correo electrónico y lo verá junto al ejercicio. Máximo 5.000 caracteres.</p>
                <?= field_error($errors, 'feedback') ?>
                <textarea id="feedback" name="feedback" rows="8" required maxlength="5000"
                    <?= field_attrs($errors, 'feedback', 'feedback-hint') ?>><?= e(old($old, 'feedback')) ?></textarea>
            </div>

            <button type="submit" class="button">Guardar calificación</button>
        </form>
    </section>
<?php else: ?>
    <section class="panel" aria-labelledby="result-title">
        <h2 id="result-title">Calificación</h2>
        <p>
            <?= partial('status_badge', ['status' => $submission['status']]) ?>
            el <?= time_tag($submission['graded_at']) ?>
            <?php if (!empty($submission['grader_name'])): ?>por <?= e($submission['grader_name']) ?><?php endif; ?>.
        </p>
        <?php if ($submission['feedback'] !== null): ?>
            <blockquote class="feedback"><?= e($submission['feedback']) ?></blockquote>
        <?php endif; ?>
    </section>
<?php endif; ?>

<?php if ($previous !== []): ?>
    <details class="history">
        <summary>Otros intentos de este alumno en el ejercicio (<?= count($previous) ?>)</summary>
        <ul>
            <?php foreach ($previous as $attempt): ?>
                <li>
                    <p>
                        <a href="<?= e(url('/profesor/entregas/' . $attempt['id'])) ?>">Entrega del <?= e(format_date($attempt['submitted_at'])) ?></a>
                        · <?= partial('status_badge', ['status' => $attempt['status']]) ?>
                    </p>
                </li>
            <?php endforeach; ?>
        </ul>
    </details>
<?php endif; ?>

<p><a href="<?= e(url('/profesor')) ?>">Volver al listado de entregas</a></p>
