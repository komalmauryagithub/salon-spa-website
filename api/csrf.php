<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Active Session Status & CSRF Token API
 * 
 * Provides an active CSRF token and sanitized session context for frontend navigation.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

$isLoggedIn = Auth::check();
$customer = Auth::customer();

Response::json([
    'success'          => true,
    'csrf_token'       => Csrf::token(),
    'session_id'       => session_id() ? 'active' : 'inactive',
    'is_authenticated' => $isLoggedIn,
    'role'             => Auth::role(),
    'customer'         => $customer ? [
        'id'                => (int)$customer['id'],
        'first_name'        => (string)$customer['first_name'],
        'last_name'         => (string)$customer['last_name'],
        'email'             => (string)$customer['email'],
        'phone'             => (string)$customer['phone'],
        'gender_preference' => (string)$customer['gender_preference'],
        'allergies_notes'   => (string)($customer['allergies_notes'] ?? '')
    ] : null
]);
