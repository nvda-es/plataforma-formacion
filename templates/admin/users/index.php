<?php
/** @var array $result @var string $role @var string $search */
$query = array_filter(['rol' => $role, 'q' => $search], static fn ($v) => $v !== '');
?>
<?= partial('admin_nav') ?>
<h1>Usuarios</h1>

<p><a class="button" href="<?= e(url('/admin/usuarios/nuevo')) ?>">Registrar un usuario</a></p>

<form method="get" action="<?= e(url('/admin/usuarios')) ?>" class="filter-form" role="search" aria-label="Buscar usuarios">
    <div class="field">
        <label for="rol">Rol</label>
        <select id="rol" name="rol">
            <option value="">Todos</option>
            <?php foreach (App\Models\User::ROLES as $value => $label): ?>
                <option value="<?= e($value) ?>"<?= selected($role === $value) ?>><?= e($label) ?></option>
            <?php endforeach; ?>
        </select>
    </div>
    <div class="field">
        <label for="q">Nombre o correo</label>
        <input type="search" id="q" name="q" value="<?= e($search) ?>" maxlength="100">
    </div>
    <button type="submit" class="button button-secondary">Buscar</button>
</form>

<?php if ($result['rows'] === []): ?>
    <p>No hay usuarios que coincidan con la búsqueda.</p>
<?php else: ?>
    <div class="table-wrapper" role="region" aria-labelledby="users-caption" tabindex="0">
        <table>
            <caption id="users-caption">
                <?= (int) $result['total'] ?> usuarios
                <?php if ($result['pages'] > 1): ?>(página <?= (int) $result['page'] ?> de <?= (int) $result['pages'] ?>)<?php endif; ?>
            </caption>
            <thead>
            <tr>
                <th scope="col">Nombre</th>
                <th scope="col">Correo electrónico</th>
                <th scope="col">Rol</th>
                <th scope="col">Estado</th>
                <th scope="col">Alta</th>
                <th scope="col">Acción</th>
            </tr>
            </thead>
            <tbody>
            <?php foreach ($result['rows'] as $u): ?>
                <tr>
                    <th scope="row"><?= e($u['name']) ?></th>
                    <td><?= e($u['email']) ?></td>
                    <td><?= e(role_label($u['role'])) ?></td>
                    <td>
                        <?php if (!(int) $u['is_active']): ?>
                            <span class="badge badge-error">Desactivado</span>
                        <?php elseif ($u['email_verified_at'] === null): ?>
                            <span class="badge badge-warning">Sin verificar</span>
                        <?php else: ?>
                            <span class="badge badge-success">Activo</span>
                        <?php endif; ?>
                    </td>
                    <td><?= time_tag($u['created_at'], false) ?></td>
                    <td>
                        <a href="<?= e(url('/admin/usuarios/' . $u['id'] . '/editar')) ?>">
                            Editar<span class="visually-hidden"> a <?= e($u['name']) ?></span>
                        </a>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <?php if ($result['pages'] > 1): ?>
        <nav aria-label="Paginación" class="pagination">
            <ul>
                <?php for ($p = 1; $p <= $result['pages']; $p++): ?>
                    <li>
                        <?php if ($p === $result['page']): ?>
                            <a href="<?= e(url('/admin/usuarios', $query + ['pagina' => $p])) ?>" aria-current="page">
                                <span class="visually-hidden">Página </span><?= $p ?>
                            </a>
                        <?php else: ?>
                            <a href="<?= e(url('/admin/usuarios', $query + ['pagina' => $p])) ?>">
                                <span class="visually-hidden">Página </span><?= $p ?>
                            </a>
                        <?php endif; ?>
                    </li>
                <?php endfor; ?>
            </ul>
        </nav>
    <?php endif; ?>
<?php endif; ?>
