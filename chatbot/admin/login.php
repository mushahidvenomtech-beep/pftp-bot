<?php
/**
 * Administrator Login Redirector
 * Forwards legacy admin login requests to canonical login
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/url.php';

header('Location: ' . url('/login'));
exit;
