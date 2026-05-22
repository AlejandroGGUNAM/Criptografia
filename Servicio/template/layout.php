<?php
if (!function_exists('ss_template_start')) {
    function ss_template_start()
    {
        if (PHP_SAPI !== 'cli' && session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        if (!headers_sent()) {
            header('X-Content-Type-Options: nosniff');
            header('X-Frame-Options: DENY');
            header('Referrer-Policy: strict-origin-when-cross-origin');
        }
    }
}

if (!function_exists('ss_e')) {
    function ss_e($value)
    {
        return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('ss_current_page_key')) {
    function ss_current_page_key()
    {
        $file = pathinfo($_SERVER['SCRIPT_NAME'] ?? '', PATHINFO_FILENAME);

        if ($file === 'menu' || $file === '') {
            return 'dashboard';
        }

        return $file;
    }
}

if (!function_exists('ss_current_user_name')) {
    function ss_current_user_name($default)
    {
        $sessionKeys = [
            'usuario_nombre',
            'nombre_usuario',
            'nombre',
            'user_name',
            'correo',
            'email',
        ];

        foreach ($sessionKeys as $key) {
            if (!empty($_SESSION[$key])) {
                return $_SESSION[$key];
            }
        }

        if (!empty($_SESSION['usuario']) && is_array($_SESSION['usuario'])) {
            foreach ($sessionKeys as $key) {
                if (!empty($_SESSION['usuario'][$key])) {
                    return $_SESSION['usuario'][$key];
                }
            }
        }

        return $default;
    }
}

if (!function_exists('ss_url')) {
    function ss_url($basePath, $path)
    {
        if ($path === '#') {
            return '#';
        }

        if (preg_match('/^https?:\/\//i', $path)) {
            return $path;
        }

        return rtrim($basePath, '/') . '/' . ltrim($path, '/');
    }
}

if (!function_exists('ss_is_active_item')) {
    function ss_is_active_item($item, $active)
    {
        if (!empty($item['key']) && $item['key'] === $active) {
            return true;
        }

        if (!empty($item['items']) && is_array($item['items'])) {
            foreach ($item['items'] as $child) {
                if (ss_is_active_item($child, $active)) {
                    return true;
                }
            }
        }

        return false;
    }
}

if (!function_exists('ss_render_nav_items')) {
    function ss_render_nav_items($items, $basePath, $active)
    {
        foreach ($items as $item) {
            $isActive = ss_is_active_item($item, $active);

            if (!empty($item['items']) && is_array($item['items'])) {
                $dropdownId = 'nav_' . preg_replace('/[^a-z0-9_]/i', '_', $item['key'] ?? $item['label']);
                ?>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle <?php echo $isActive ? 'active' : ''; ?>" href="#" id="<?php echo ss_e($dropdownId); ?>" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo ss_e($item['label']); ?>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="<?php echo ss_e($dropdownId); ?>">
                        <?php foreach ($item['items'] as $child) : ?>
                            <?php $childActive = ss_is_active_item($child, $active); ?>
                            <li>
                                <a class="dropdown-item <?php echo $childActive ? 'active' : ''; ?>" href="<?php echo ss_e(ss_url($basePath, $child['href'])); ?>">
                                    <?php echo ss_e($child['label']); ?>
                                </a>
                            </li>
                        <?php endforeach; ?>
                    </ul>
                </li>
                <?php
                continue;
            }

            ?>
            <li class="nav-item">
                <a class="nav-link <?php echo $isActive ? 'active' : ''; ?>" href="<?php echo ss_e(ss_url($basePath, $item['href'])); ?>" <?php echo $isActive ? 'aria-current="page"' : ''; ?>>
                    <?php echo ss_e($item['label']); ?>
                </a>
            </li>
            <?php
        }
    }
}

if (!function_exists('ss_render_header')) {
    function ss_render_header(array $options)
    {
        ss_template_start();

        $basePath = $options['base_path'] ?? '../..';
        $GLOBALS['basePath'] = $basePath;
        $roleName = $options['role_name'] ?? 'Usuario';
        $roleCode = $options['role_code'] ?? 'usuario';
        $title = $options['title'] ?? ($roleName . ' | Servicio Social');
        $active = $options['active'] ?? ss_current_page_key();
        $theme = $options['theme'] ?? 'bg-primary';
        $home = $options['home'] ?? '#';
        $navItems = $options['nav'] ?? [];
        $profile = $options['profile'] ?? '#';
        $security = $options['security'] ?? '#';
        $logout = $options['logout'] ?? 'logout.php';
        $userName = ss_current_user_name($roleName);
        ?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo ss_e($title); ?></title>
    <link rel="stylesheet" href="<?php echo ss_e(ss_url($basePath, 'css/bootstrap.min.css')); ?>">
</head>
<body class="min-vh-100 d-flex flex-column bg-body-tertiary">
    <nav class="navbar navbar-expand-xl navbar-dark <?php echo ss_e($theme); ?> shadow-sm">
        <div class="container-fluid px-3">
            <a class="navbar-brand d-flex align-items-center gap-2" href="<?php echo ss_e(ss_url($basePath, $home)); ?>">
                <span class="d-inline-flex align-items-center justify-content-center rounded-2 bg-white text-dark fw-bold" style="width: 2.35rem; height: 2.35rem;">SS</span>
                <span class="lh-sm">
                    <span class="d-block fw-semibold">Servicio Social</span>
                    <small class="d-block opacity-75">Panel <?php echo ss_e($roleName); ?></small>
                </span>
            </a>

            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#nav<?php echo ss_e(ucfirst($roleCode)); ?>" aria-controls="nav<?php echo ss_e(ucfirst($roleCode)); ?>" aria-expanded="false" aria-label="Mostrar menu">
                <span class="navbar-toggler-icon"></span>
            </button>

            <div class="collapse navbar-collapse" id="nav<?php echo ss_e(ucfirst($roleCode)); ?>">
                <ul class="navbar-nav ms-auto mb-2 mb-xl-0">
                    <?php ss_render_nav_items($navItems, $basePath, $active); ?>
                </ul>

                <div class="dropdown ms-xl-3">
                    <button class="btn btn-outline-light btn-sm dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                        <?php echo ss_e($userName); ?>
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><h6 class="dropdown-header"><?php echo ss_e($roleName); ?></h6></li>
                        <li><a class="dropdown-item" href="<?php echo ss_e(ss_url($basePath, $profile)); ?>">Perfil</a></li>
                        <li><a class="dropdown-item" href="<?php echo ss_e(ss_url($basePath, $security)); ?>">Seguridad</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item text-danger" href="<?php echo ss_e(ss_url($basePath, $logout)); ?>">Cerrar sesion</a></li>
                    </ul>
                </div>
            </div>
        </div>
    </nav>

    <main class="container py-4 flex-grow-1">
        <?php
    }
}

if (!function_exists('ss_render_footer')) {
    function ss_render_footer()
    {
        $basePath = $GLOBALS['basePath'] ?? '../..';
        $year = date('Y');
        ?>
    </main>

    <footer class="border-top bg-white mt-auto">
        <div class="container py-3 d-flex flex-column flex-lg-row gap-2 justify-content-between align-items-lg-center">
            <div>
                <strong>Sistema Seguro de Servicio Social Universitario</strong>
                <div class="small text-secondary">Tokens, firmas digitales y auditoria criptografica para el control del proceso.</div>
            </div>
            <div class="small text-secondary">
                &copy; <?php echo ss_e($year); ?> Universidad
            </div>
        </div>
    </footer>

    <script src="<?php echo ss_e(ss_url($basePath, 'js/bootstrap.bundle.min.js')); ?>"></script>
</body>
</html>
        <?php
    }
}
