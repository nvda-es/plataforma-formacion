<?php
/** @var array $teachers @var array $old @var array $errors */
$oldTeacher = old($old, 'teacher_id');
$example = <<<'MD'
# Curso: Introducción a la accesibilidad web
Resumen del curso en Markdown.

## Módulo: Fundamentos
Descripción opcional del módulo.

### Unidad: ¿Qué es la accesibilidad?
Contenido de la unidad. Puedes usar **negritas**, listas,
tablas, bloques de código y encabezados de nivel 4 o inferior.

### Ejercicio: Identifica barreras
Enunciado del ejercicio que el alumno debe responder.
MD;
?>
<?= partial('admin_nav') ?>
<nav aria-label="Ruta de navegación" class="breadcrumbs">
    <ol>
        <li><a href="<?= e(url('/admin/cursos')) ?>">Cursos</a></li>
        <li aria-current="page">Importar desde Markdown</li>
    </ol>
</nav>

<h1>Importar un curso desde Markdown</h1>

<p>Sube un archivo <code>.md</code> con todo el curso. La estructura se define con estos encabezados:</p>
<ul>
    <li><code># Curso: Título</code>: una sola vez, al principio. El texto que sigue es el resumen.</li>
    <li><code>## Módulo: Título</code>: empieza un módulo. El texto que sigue es su descripción.</li>
    <li><code>### Unidad: Título</code>: una unidad de solo texto.</li>
    <li><code>### Ejercicio: Título</code>: un ejercicio; el texto que sigue es el enunciado.</li>
</ul>
<p>Cualquier otro encabezado forma parte del contenido. El curso se crea como borrador salvo que marques «Publicar».</p>

<details class="panel">
    <summary>Ver un ejemplo</summary>
    <pre><code><?= e($example) ?></code></pre>
</details>

<?= partial('errors', ['errors' => $errors]) ?>

<form method="post" action="<?= e(url('/admin/cursos/importar')) ?>" enctype="multipart/form-data" class="form">
    <?= csrf_field() ?>

    <div class="field">
        <label for="course_file">Archivo Markdown del curso</label>
        <p class="hint" id="course_file-hint">Formatos .md, .markdown o .txt, codificado en UTF-8, máximo 1 MB.</p>
        <?= field_error($errors, 'course_file') ?>
        <input type="file" id="course_file" name="course_file" required accept=".md,.markdown,.txt,text/markdown,text/plain"
            <?= field_attrs($errors, 'course_file', 'course_file-hint') ?>>
    </div>

    <div class="field">
        <label for="teacher_id">Profesor/a</label>
        <?= field_error($errors, 'teacher_id') ?>
        <select id="teacher_id" name="teacher_id"<?= field_attrs($errors, 'teacher_id') ?>>
            <option value="">Sin asignar</option>
            <?php foreach ($teachers as $t): ?>
                <option value="<?= (int) $t['id'] ?>"<?= selected($oldTeacher === (string) $t['id']) ?>><?= e($t['name']) ?> (<?= e($t['email']) ?>)</option>
            <?php endforeach; ?>
        </select>
    </div>

    <div class="checkbox">
        <input type="checkbox" id="is_published" name="is_published" value="1"<?= checked(!empty($old['is_published'])) ?>>
        <label for="is_published">Publicar el curso al importarlo</label>
    </div>

    <button type="submit" class="button">Importar curso</button>
</form>
