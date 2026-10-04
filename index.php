<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/init.php';
require_once __DIR__ . '/includes/auth.php';

if (current_user_id() !== null) {
    if (current_role() === 'admin') {
        redirect(url('admin/index.php'));
    }
    redirect(url('employee/index.php'));
}

redirect(url('login.php'));
