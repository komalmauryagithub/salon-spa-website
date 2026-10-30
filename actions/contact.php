<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Synchronous Contact Form Submission Handler (Fallback)
 */

require_once __DIR__ . '/../includes/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::redirect('../contact.html');
}

// 1. Verify CSRF
$token = $_POST['csrf_token'] ?? null;
if (!empty($token) && !Csrf::verify($token)) {
    Response::redirect('../contact.html', 'Security validation expired. Please resubmit your inquiry.', 'error');
}

// 2. Validate
$validator = Validator::make($_POST)
    ->required('name', 'Please provide your full name.')
    ->required('email', 'Please provide your email address.')
    ->email('email')
    ->phone('phone', null, false)
    ->required('subject', 'Please select an inquiry topic.')
    ->required('message', 'Please enter your message.');

if ($validator->fails()) {
    Response::redirect('../contact.html', $validator->firstError() ?: 'Please check your form inputs.', 'error');
}

// 3. Database Insert
try {
    $pdo = Database::getConnection();
    $stmt = $pdo->prepare("
        INSERT INTO contact_enquiries (full_name, email, phone, preferred_contact_method, subject, message, status, created_at)
        VALUES (:name, :email, :phone, :pref, :subject, :message, 'new', NOW())
    ");

    $stmt->execute([
        'name'    => $validator->value('name'),
        'email'   => $validator->value('email'),
        'phone'   => $validator->value('phone') ?: null,
        'pref'    => $validator->value('contact-pref', 'Email'),
        'subject' => $validator->value('subject'),
        'message' => $validator->value('message'),
    ]);

    Response::redirect('../contact.html', 'Thank you! Your concierge message has been securely recorded. Our team will contact you shortly.', 'success');
} catch (Exception $e) {
    AppLogger::error("Synchronous contact error: " . $e->getMessage());
    Response::redirect('../contact.html', 'The reservation database is temporarily unavailable. Please reach our desk via telephone.', 'error');
}
