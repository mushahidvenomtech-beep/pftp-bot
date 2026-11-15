<?php
/**
 * Administrator Logout Handler
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/auth.php';

adminLogout();
header('Location: ' . url('/login'));
exit;

