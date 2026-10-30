<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Contact Inquiry Handler API
 * 
 * Validates guest messages, prevents CSRF, and stores entries in MySQL contact_enquiries table.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Only accept POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    Response::error("Method not allowed. Please submit via POST.", [], 405);
}

// Support raw JSON or form-urlencoded POST
$input = $_POST;
if (empty($input)) {
    $raw = file_get_contents('php://input');
    if (!empty($raw)) {
        $decoded = json_decode($raw, true);
        if (is_array($decoded)) {
            $input = $decoded;
        }
    }
}

// 1. Validate CSRF Token if present
$submittedToken = $input['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
if (!empty($submittedToken) && !Csrf::verify($submittedToken)) {
    Response::error("Security token validation failed. Please refresh and try again.", ['csrf_token' => 'Invalid token'], 403);
}

// 2. Strict Input Validation
$validator = Validator::make($input)
    ->required('name', 'Please provide your full name.')
    ->lengthBetween('name', 2, 80, 'Full name must be between 2 and 80 characters.')
    ->required('email', 'Please provide a valid email address.')
    ->email('email')
    ->phone('phone', 'Please provide a valid telephone number.', false)
    ->in('contact-pref', ['Email', 'Phone Call', 'WhatsApp'], 'Please select a valid contact method.')
    ->required('subject', 'Please select or enter the subject of your inquiry.')
    ->lengthBetween('subject', 3, 150, 'Subject must be between 3 and 150 characters.')
    ->required('message', 'Please enter your message or inquiry.')
    ->lengthBetween('message', 10, 3000, 'Your message must be at least 10 characters.');

if ($validator->fails()) {
    Response::error($validator->firstError() ?: "Validation failed.", $validator->errors(), 422);
}

// 3. Database Insertion via Prepared Statements
try {
    $pdo = Database::getConnection();

    $stmt = $pdo->prepare("
        INSERT INTO contact_enquiries (
            full_name,
            email,
            phone,
            preferred_contact_method,
            subject,
            message,
            status,
            created_at
        ) VALUES (
            :full_name,
            :email,
            :phone,
            :pref,
            :subject,
            :message,
            'new',
            NOW()
        )
    ");

    $pref = $validator->value('contact-pref', 'Email');
    $phone = $validator->value('phone') ?: null;

    $stmt->execute([
        'full_name' => $validator->value('name'),
        'email'     => $validator->value('email'),
        'phone'     => $phone,
        'pref'      => $pref,
        'subject'   => $validator->value('subject'),
        'message'   => $validator->value('message'),
    ]);

    $enquiryId = (int)$pdo->lastInsertId();

    AppLogger::info("New contact enquiry #{$enquiryId} recorded from {$validator->value('email')}");

    Response::success("Thank you! Your concierge inquiry has been securely received. Our guest relations team will respond via {$pref} promptly.", [
        'enquiry_id' => $enquiryId,
        'guest_name' => $validator->value('name'),
        'subject'    => $validator->value('subject'),
    ], 201);

} catch (DatabaseException $e) {
    AppLogger::error("Database unavailable during contact submission: " . $e->getMessage());
    Response::error("The salon concierge database is currently offline. Please contact our reception desk by phone.", [
        'detail' => $e->getMessage()
    ], 503);
} catch (Exception $e) {
    AppLogger::error("Contact form error: " . $e->getMessage());
    Response::error("An unexpected error occurred while saving your inquiry.", [], 500);
}
