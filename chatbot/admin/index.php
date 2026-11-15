<?php
/**
 * Administrator Dashboard Redirector
 * Forwards legacy admin dashboard requests to canonical dashboard
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/url.php';

header('Location: ' . url('/dashboard'));
exit;
