<?php
declare(strict_types=1);
require __DIR__ . '/includes/bootstrap.php';
if (is_post()) {
    verify_csrf();
    $_SESSION = [];
    session_destroy();
}
redirect('login.php');
