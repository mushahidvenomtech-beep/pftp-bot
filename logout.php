<?php
/**
 * PFTP Admin Portal: Sign Out Handler
 * Route: /pftp-bot/logout
 */

declare(strict_types=1);

require_once __DIR__ . '/chatbot/config/database.php';
require_once __DIR__ . '/chatbot/includes/auth.php';

adminLogout();

header('Location: ' . url('/login'));
exit;

