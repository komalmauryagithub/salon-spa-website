<?php
/**
 * Isolated Demo Logout Handler
 * Safely unsets demo session state without affecting production user sessions
 */

declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';

Demo::requireEnabled();

DemoAuth::logout();

$target = trim($_GET['redirect'] ?? 'index.php');
// Ensure local redirect only
if (!in_array($target, ['index.php', 'login.php', 'admin-login.php'], true)) {
    $target = 'index.php';
}

header("Location: {$target}?notice=" . urlencode("You have safely logged out of the demo environment."));
exit;
