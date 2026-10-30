<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Guest Review & Reflection Submission API
 * 
 * Validates feedback and saves entries into the reviews table with 'pending' moderation status.
 */

require_once __DIR__ . '/../includes/bootstrap.php';

// Handle GET requests to fetch public approved reviews
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    try {
        $pdo = Database::getConnection();
        $limit = isset($_GET['limit']) ? min(50, max(1, (int)$_GET['limit'])) : 20;
        $stmt = $pdo->prepare("
            SELECT 
                r.id, r.guest_name, r.rating, r.review_title, r.review_text, r.is_featured, r.created_at,
                s.name AS service_name, stf.full_name AS staff_name
            FROM reviews r
            LEFT JOIN services s ON r.service_id = s.id
            LEFT JOIN staff stf ON r.staff_id = stf.id
            WHERE r.status = 'approved'
            ORDER BY r.is_featured DESC, r.created_at DESC
            LIMIT :lim
        ");
        $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
        $stmt->execute();
        $approvedReviews = $stmt->fetchAll();
        Response::success("Approved client reflections loaded.", ['reviews' => $approvedReviews]);
    } catch (Exception $e) {
        Response::error("Could not load reviews.", [], 500);
    }
}

// Only accept POST requests for submission
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

// Clean rating integer from string e.g. "5 Stars — Exceptional Sanctuary Experience" -> 5
$rawRating = $input['rating'] ?? $input['review-rating'] ?? 5;
$ratingInt = 5;
if (is_numeric($rawRating)) {
    $ratingInt = (int)$rawRating;
} elseif (preg_match('/^(\d)/', (string)$rawRating, $matches)) {
    $ratingInt = (int)$matches[1];
}

$cleanInput = [
    'guest_name'  => $input['name'] ?? $input['review-name'] ?? '',
    'guest_email' => $input['email'] ?? $input['review-email'] ?? '',
    'service'     => $input['service'] ?? $input['review-service'] ?? '',
    'stylist'     => $input['stylist'] ?? $input['review-stylist'] ?? '',
    'rating'      => $ratingInt,
    'review_text' => $input['review'] ?? $input['review-text'] ?? '',
];

// 2. Strict Input Validation
$validator = Validator::make($cleanInput)
    ->required('guest_name', 'Please provide your name.')
    ->lengthBetween('guest_name', 2, 80)
    ->required('guest_email', 'Please provide a valid email address.')
    ->email('guest_email')
    ->required('service', 'Please select the service experienced.')
    ->integerBetween('rating', 1, 5, 'Rating must be between 1 and 5 stars.')
    ->required('review_text', 'Please provide your review feedback.')
    ->lengthBetween('review_text', 10, 2000, 'Review text must be between 10 and 2000 characters.');

if ($validator->fails()) {
    Response::error($validator->firstError() ?: "Review validation failed.", $validator->errors(), 422);
}

// 3. Database Insertion
try {
    $pdo = Database::getConnection();

    // Check optional link to customer profile
    $custStmt = $pdo->prepare("SELECT id FROM customers WHERE email = :email LIMIT 1");
    $custStmt->execute(['email' => $validator->value('guest_email')]);
    $customerRow = $custStmt->fetch();
    $customerId = $customerRow ? (int)$customerRow['id'] : null;

    // Check optional link to staff
    $staffId = null;
    $stylistInput = $cleanInput['stylist'];
    if (!empty($stylistInput)) {
        $stfStmt = $pdo->prepare("SELECT id FROM staff WHERE full_name LIKE :name OR slug LIKE :slug LIMIT 1");
        $stfStmt->execute(['name' => "%{$stylistInput}%", 'slug' => "%{$stylistInput}%"]);
        $stfRow = $stfStmt->fetch();
        if ($stfRow) $staffId = (int)$stfRow['id'];
    }

    $insertStmt = $pdo->prepare("
        INSERT INTO reviews (
            customer_id,
            service_id,
            staff_id,
            guest_name,
            guest_email,
            rating,
            review_title,
            review_text,
            status,
            is_featured,
            created_at
        ) VALUES (
            :cust_id,
            NULL,
            :staff_id,
            :name,
            :email,
            :rating,
            :title,
            :text,
            'pending',
            0,
            NOW()
        )
    ");

    $reviewTitle = $validator->value('service') . " Reflection";

    $insertStmt->execute([
        'cust_id'  => $customerId,
        'staff_id' => $staffId,
        'name'     => $validator->value('guest_name'),
        'email'    => $validator->value('guest_email'),
        'rating'   => $validator->value('rating'),
        'title'    => $reviewTitle,
        'text'     => $validator->value('review_text'),
    ]);

    $reviewId = (int)$pdo->lastInsertId();
    AppLogger::info("New guest review #{$reviewId} submitted by {$validator->value('guest_email')} (Pending Moderation)");

    Response::success("Thank you for your atelier reflection. Your feedback has been securely submitted and is pending concierge curation before publication.", [
        'review_id'   => $reviewId,
        'guest_name'  => $validator->value('guest_name'),
        'rating'      => $validator->value('rating'),
        'status'      => 'pending'
    ], 201);

} catch (DatabaseException $e) {
    AppLogger::error("Database unavailable during review submission: " . $e->getMessage());
    Response::error("The salon database is currently offline. Your reflection cannot be saved right now.", [
        'detail' => $e->getMessage()
    ], 503);
} catch (Exception $e) {
    AppLogger::error("Review submission error: " . $e->getMessage());
    Response::error("An unexpected error occurred while saving your review.", [], 500);
}
