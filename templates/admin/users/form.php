<?php
/**
 * @var array|null $user
 * @var array $old
 * @var array $errors
 * @var array|null $enrollments
 * @var array|null $availableCourses
 */
$isEdit = $user !== null;
$action = $isEdit ? url('/admin/usuarios/' . $user['id']) : url('/admin/usuarios');
$oldRole = old($old, 'role', 'student');
?>
<?= partial('admin_nav') ?>
<nav aria-label="Ruta de navegación" class="breadcrumbs">
    <ol>
        <li><a href="<?= e(url('/admin/usuarios')) ?>">Usuarios</a></li>
        <li aria-current="page"><?= $isEdit ? e($user['name']) : 'Nuevo usuario' ?></li>
    </ol>
</nav>

<h1><?= $isEdit ? 'Editar usuario' : 'Registrar un usuario' ?></h1>

<?= partial('errors', ['errors' => $errors]) ?>

<form method="post" action="<?= e($action) ?>" class="form">
    <?= csrf_field() ?>

    <div class="field">
        <label for="name">Nombre y apellidos</label>
        <?= field_error($errors, 'name') ?>
        <input type="text" id="name" name="name" required maxlength="120" autocomplete="off"
               value="<?= e(old($old, 'name')) ?>"<?= field_attrs($errors, 'name') ?>>
    </div>

    <div class="field">
        <label for="email">Correo electrónico</label>
        <?= field_error($errors, 'email') ?>
        <input type="email" id="email" name="email" required maxlength="190" autocomplete="off" spellcheck="false"
               value="<?= e(old($old, 'email')) ?>"<?= field_attrs($errors, 'email') ?>>
    </div>

    <div class="field">
        <label for="role">Rol</label>
        <?= field_error($errors, 'role') ?>
        <select id="role" name="role" required<?= field_attrs($errors, 'role') ?>>
            <?php foreach (App\Models\User::ROLES as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= selected($oldRole === $value) ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>

    <fieldset class="field">
        <legend><?= $isEdit ? 'Cambiar la contraseña (opcional)' : 'Contraseña (opcional)' ?></legend>
        <p class="hint" id="password-hint">
            <?php if ($isEdit): ?>
                Déjala en blanco para no cambiarla.
            <?php else: ?>
                Si la dejas en blanco, el usuario recibirá un correo con un enlace para crear su propia contraseña (recomendado).
            <?php endif; ?>
        </p>
        <label for="password">Contraseña</label>
        <?= field_error($errors, 'password') ?>
        <input type="password" id="password" name="password" minlength="<?= App\Password::MIN_LENGTH ?>"
               maxlength="<?= App\Password::MAX_LENGTH ?>" autocomplete="new-password"<?= field_attrs($errors, 'password', 'password-hint') ?>>

        <label for="password_confirmation">Repite la contraseña</label>
        <?= field_error($errors, 'password_confirmation') ?>
        <input type="password" id="password_confirmation" name="password_confirmation"
               maxlength="<?= App\Password::MAX_LENGTH ?>" autocomplete="new-password"<?= field_attrs($errors, 'password_confirmation') ?>>
    </fieldset>

    <div class="checkbox">
        <input type="checkbox" id="verified" name="verified" value="1" aria-describedby="verified-hint"<?= checked(!empty($old['verified'])) ?>>
        <label for="verified">Correo electrónico verificado</label>
        <p class="hint" id="verified-hint">
            Márcalo si ya has comprobado la dirección. Si no lo marcas y asignas una contraseña,
            el usuario recibirá un correo para verificarla antes de poder entrar.
        </p>
    </div>

    <?php if ($isEdit): ?>
        <div class="checkbox">
            <?= field_error($errors, 'is_active') ?>
            <input type="checkbox" id="is_active" name="is_active" value="1"<?= checked(!empty($old['is_active'])) ?><?= field_attrs($errors, 'is_active') ?>>
            <label for="is_active">Cuenta activa (si la desactivas, el usuario no podrá iniciar sesión)</label>
        </div>
    <?php endif; ?>

    <button type="submit" class="button"><?= $isEdit ? 'Guardar cambios' : 'Registrar usuario' ?></button>
</form>

<?php if ($isEdit): ?>
    <section class="panel" aria-labelledby="access-title">
        <h2 id="access-title">Acceso</h2>
        <p>Envía al usuario un enlace, válido durante 7 días, para que establezca una contraseña nueva.</p>
        <form method="post" action="<?= e(url('/admin/usuarios/' . $user['id'] . '/enviar-acceso')) ?>">
            <?= csrf_field() ?>
            <button type="submit" class="button button-secondary">Enviar enlace de acceso</button>
        </form>
    </section>

    <?php if ($user['role'] === 'student'): ?>
        <section class="panel" id="matriculas" aria-labelledby="enrollments-title" tabindex="-1">
            <h2 id="enrollments-title">Matrículas</h2>

            <?php if ($enrollments === []): ?>
                <p>No está matriculado en ningún curso.</p>
            <?php else: ?>
                <div class="table-wrapper" role="region" aria-labelledby="enrollments-caption" tabindex="0">
                    <table>
                        <caption id="enrollments-caption">Cursos en los que está matriculado</caption>
                        <thead>
                        <tr>
                            <th scope="col">Curso</th>
                            <th scope="col">Progreso</th>
                            <th scope="col">Estado</th>
                            <th scope="col">Acción</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($enrollments as $enrollment): ?>
                            <tr>
                                <th scope="row"><?= e($enrollment['title']) ?></th>
                                <td><?= (int) $enrollment['progress']['done'] ?> de <?= (int) $enrollment['progress']['total'] ?> (<?= (int) $enrollment['progress']['percent'] ?> %)</td>
                                <td>
                                    <?php if ($enrollment['completed_at'] !== null): ?>
                                        Completado el <?= time_tag($enrollment['completed_at'], false) ?>
                                    <?php else: ?>
                                        En curso
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <form method="post" data-confirm="¿Dar de baja a <?= e($user['name']) ?> del curso «<?= e($enrollment['title']) ?>»?"
                                          action="<?= e(url('/admin/usuarios/' . $user['id'] . '/matriculas/' . $enrollment['course_id'] . '/eliminar')) ?>">
                                        <?= csrf_field() ?>
                                        <button type="submit" class="button button-small button-danger">
                                            Dar de baja<span class="visually-hidden"> de <?= e($enrollment['title']) ?></span>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>

            <?php if (!empty($availableCourses)): ?>
                <form method="post" action="<?= e(url('/admin/usuarios/' . $user['id'] . '/matriculas')) ?>" class="filter-form">
                    <?= csrf_field() ?>
                    <div class="field">
                        <label for="course_id">Matricular en el curso</label>
                        <select id="course_id" name="course_id" required>
                            <?php foreach ($availableCourses as $c): ?>
                                <option value="<?= (int) $c['id'] ?>"><?= e($c['title']) ?><?= (int) $c['is_published'] ? '' : ' (no publicado)' ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" class="button button-secondary">Matricular</button>
                </form>
            <?php endif; ?>
        </section>
    <?php endif; ?>
<?php endif; ?>
