<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Secure Logout Handler
 */

require_once __DIR__ . '/includes/bootstrap.php';

// Destroy session and expire auth cookies
Auth::logout();

// Redirect with polite flash notification
Response::redirect('login.php', 'You have been safely signed out. We look forward to welcoming you back to L\'Étoile.', 'success');
