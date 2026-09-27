<nav aria-label="Administración" class="subnav">
    <ul>
        <li><a href="<?= e(url('/admin')) ?>"<?= current_path() === '/admin' ? ' aria-current="page"' : '' ?>>Resumen</a></li>
        <li><a href="<?= e(url('/admin/usuarios')) ?>"<?= nav_current('/admin/usuarios') ?>>Usuarios</a></li>
        <li><a href="<?= e(url('/admin/cursos')) ?>"<?= nav_current('/admin/cursos') ?>>Cursos</a></li>
        <li><a href="<?= e(url('/profesor')) ?>">Corrección</a></li>
    </ul>
</nav>
