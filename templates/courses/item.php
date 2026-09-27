<?php
/**
 * @var array $course
 * @var array $item
 * @var bool $preview
 * @var array $history
 * @var array|null $latest
 * @var array|null $previous
 * @var array|null $next
 * @var int|null $position
 * @var int $totalItems
 * @var array $errors
 * @var array $old
 */
$isExercise = $item['type'] === 'exercise';
$courseUrl = url('/cursos/' . $course['slug']);
$itemUrl = static fn (array $i): string => url('/cursos/' . $course['slug'] . '/contenido/' . $i['id']);
$canSubmit = !$preview && $isExercise && ($latest === null || $latest['status'] === 'failed');
?>
<nav aria-label="Ruta de navegación" class="breadcrumbs">
    <ol>
        <li><a href="<?= e(url('/')) ?>">Cursos</a></li>
        <li><a href="<?= e($courseUrl) ?>"><?= e($course['title']) ?></a></li>
        <li><?= e($item['module_title']) ?></li>
        <li aria-current="page"><?= e($item['title']) ?></li>
    </ol>
</nav>

<article class="content-page" aria-labelledby="item-title">
    <header>
        <p class="eyebrow">
            <?= $isExercise ? 'Ejercicio' : 'Unidad' ?>
            <?php if ($position !== null): ?>
                · Contenido <?= (int) $position ?> de <?= (int) $totalItems ?>
            <?php endif; ?>
        </p>
        <h1 id="item-title"><?= e($item['title']) ?></h1>
    </header>

    <?php if ($preview): ?>
        <p class="alert alert-info">
            Vista previa: estás viendo el contenido tal como lo verán los alumnos<?= $isExercise ? '. Solo los alumnos matriculados pueden responder los ejercicios' : '' ?>.
            Tus visitas no cuentan en el progreso.
        </p>
    <?php endif; ?>

    <?php if ($isExercise): ?>
        <h2 class="section-label">Enunciado</h2>
    <?php endif; ?>
    <div class="prose"><?= markdown($item['content'], $isExercise ? 3 : 2) ?></div>
</article>

<?php if ($isExercise && !$preview): ?>
    <section class="panel answer-section" aria-labelledby="answer-title">
        <h2 id="answer-title">Tu respuesta</h2>

        <?php if ($latest !== null && $latest['status'] === 'approved'): ?>
            <div class="alert alert-success">
                <p><strong>Ejercicio aprobado</strong> el <?= time_tag($latest['graded_at']) ?>.</p>
            </div>
        <?php elseif ($latest !== null && $latest['status'] === 'pending'): ?>
            <div class="alert alert-info">
                <p>
                    Enviaste tu respuesta el <?= time_tag($latest['submitted_at']) ?> y está
                    <strong>pendiente de corrección</strong>. Te avisaremos por correo electrónico cuando el profesor la califique.
                </p>
            </div>
        <?php elseif ($latest !== null && $latest['status'] === 'failed'): ?>
            <div class="alert alert-warning">
                <p>
                    Tu respuesta anterior está <strong>suspendida</strong>. Lee el comentario del profesor
                    y envía una nueva respuesta.
                </p>
            </div>
        <?php endif; ?>

        <?php if ($latest !== null): ?>
            <h3><?= $latest['status'] === 'failed' ? 'Tu respuesta suspendida' : 'Respuesta enviada' ?></h3>
            <div class="answer-text"><?= e($latest['answer']) ?></div>
        <?php endif; ?>

        <?php if ($latest !== null && $latest['feedback'] !== null): ?>
            <h3>Comentario del profesor</h3>
            <blockquote class="feedback"><?= e($latest['feedback']) ?></blockquote>
        <?php endif; ?>

        <?php if ($canSubmit): ?>
            <?= partial('errors', ['errors' => $errors]) ?>
            <form method="post" action="<?= e($itemUrl($item) . '/respuesta') ?>" class="form">
                <?= csrf_field() ?>
                <div class="field">
                    <label for="answer"><?= $latest === null ? 'Escribe tu respuesta' : 'Escribe tu nueva respuesta' ?></label>
                    <p class="hint" id="answer-hint">
                        Máximo 20.000 caracteres. Cuando la envíes, el profesor recibirá un aviso para corregirla
                        y ya no podrás modificarla.
                    </p>
                    <?= field_error($errors, 'answer') ?>
                    <textarea id="answer" name="answer" rows="12" required maxlength="20000"
                        <?= field_attrs($errors, 'answer', 'answer-hint') ?>><?= e(old($old, 'answer')) ?></textarea>
                </div>
                <button type="submit" class="button">Enviar respuesta</button>
            </form>
        <?php endif; ?>

        <?php $older = array_slice($history, 1); ?>
        <?php if ($older !== []): ?>
            <details class="history">
                <summary>Intentos anteriores (<?= count($older) ?>)</summary>
                <ol reversed>
                    <?php foreach ($older as $attempt): ?>
                        <li>
                            <p>
                                Enviado el <?= time_tag($attempt['submitted_at']) ?> ·
                                <?= partial('status_badge', ['status' => $attempt['status']]) ?>
                            </p>
                            <div class="answer-text"><?= e($attempt['answer']) ?></div>
                            <?php if ($attempt['feedback'] !== null): ?>
                                <p><strong>Comentario del profesor:</strong></p>
                                <blockquote class="feedback"><?= e($attempt['feedback']) ?></blockquote>
                            <?php endif; ?>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </details>
        <?php endif; ?>
    </section>
<?php endif; ?>

<nav class="pager" aria-label="Contenidos del curso">
    <ul>
        <li>
            <?php if ($previous !== null): ?>
                <a href="<?= e($itemUrl($previous)) ?>" rel="prev">
                    <span aria-hidden="true">←</span> Anterior<span class="visually-hidden">:</span>
                    <span class="pager-title"><?= e($previous['title']) ?></span>
                </a>
            <?php endif; ?>
        </li>
        <li><a href="<?= e($courseUrl) ?>">Índice del curso</a></li>
        <li>
            <?php if ($next !== null): ?>
                <a href="<?= e($itemUrl($next)) ?>" rel="next">
                    Siguiente<span class="visually-hidden">:</span>
                    <span class="pager-title"><?= e($next['title']) ?></span> <span aria-hidden="true">→</span>
                </a>
            <?php endif; ?>
        </li>
    </ul>
</nav>
