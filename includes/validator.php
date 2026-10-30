<?php
/**
 * L'ÉTOILE LUXURY UNISEX HAIR SALON & SPA
 * Input Validation & Sanitization Engine
 * 
 * Enforces strict backend validation rules without trusting frontend inputs.
 */

class Validator {
    private array $data;
    private array $errors = [];
    private array $sanitized = [];

    public function __construct(array $data) {
        $this->data = $data;
    }

    /**
     * Factory creation
     */
    public static function make(array $data): self {
        return new self($data);
    }

    /**
     * Add an error for a specific field
     */
    public function addError(string $field, string $message): void {
        if (!isset($this->errors[$field])) {
            $this->errors[$field] = $message;
        }
    }

    /**
     * Validate required field
     */
    public function required(string $field, ?string $customMessage = null): self {
        $val = $this->data[$field] ?? null;
        if ($val === null || (is_string($val) && trim($val) === '') || (is_array($val) && empty($val))) {
            $this->addError($field, $customMessage ?: "The " . str_replace('_', ' ', $field) . " field is required.");
        } else {
            $this->sanitized[$field] = is_string($val) ? self::sanitizeString($val) : $val;
        }
        return $this;
    }

    /**
     * Validate email format
     */
    public function email(string $field, ?string $customMessage = null): self {
        $val = $this->data[$field] ?? null;
        if (!empty($val)) {
            $sanitized = filter_var(trim($val), FILTER_SANITIZE_EMAIL);
            if (!filter_var($sanitized, FILTER_VALIDATE_EMAIL)) {
                $this->addError($field, $customMessage ?: "Please provide a valid email address.");
            } else {
                $this->sanitized[$field] = strtolower($sanitized);
            }
        }
        return $this;
    }

    /**
     * Validate telephone format (international or local)
     */
    public function phone(string $field, ?string $customMessage = null, bool $required = false): self {
        $val = $this->data[$field] ?? null;
        if (empty($val)) {
            if ($required) {
                $this->addError($field, $customMessage ?: "A valid telephone number is required.");
            }
            return $this;
        }

        $cleaned = preg_replace('/[^\d+]/', '', (string)$val);
        $digitCount = strlen(preg_replace('/[^\d]/', '', (string)$val));

        if ($digitCount < 7 || $digitCount > 18) {
            $this->addError($field, $customMessage ?: "Please provide a valid telephone number with area/country code.");
        } else {
            $this->sanitized[$field] = self::sanitizeString((string)$val);
        }

        return $this;
    }

    /**
     * Validate date format (YYYY-MM-DD) and ensure not in the past
     */
    public function date(string $field, bool $disallowPast = true, ?string $customMessage = null): self {
        $val = $this->data[$field] ?? null;
        if (empty($val)) return $this;

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$val)) {
            $this->addError($field, $customMessage ?: "Date must follow the format YYYY-MM-DD.");
            return $this;
        }

        [$year, $month, $day] = explode('-', (string)$val);
        if (!checkdate((int)$month, (int)$day, (int)$year)) {
            $this->addError($field, $customMessage ?: "The provided appointment date is invalid.");
            return $this;
        }

        if ($disallowPast) {
            $today = date('Y-m-d');
            if ($val < $today) {
                $this->addError($field, $customMessage ?: "Appointment date cannot be in the past.");
                return $this;
            }
        }

        $this->sanitized[$field] = $val;
        return $this;
    }

    /**
     * Validate time format (HH:MM or HH:MM:SS, 12-hour or 24-hour)
     */
    public function time(string $field, ?string $customMessage = null): self {
        $val = $this->data[$field] ?? null;
        if (empty($val)) return $this;

        // Matches 09:30, 14:00, or 09:30 AM/PM
        if (!preg_match('/^(?:[01]?\d|2[0-3]):[0-5]\d(?:\s*(?:AM|PM|am|pm))?$/', trim((string)$val))) {
            $this->addError($field, $customMessage ?: "Please select a valid appointment time slot.");
        } else {
            // Normalize to 24-hour format HH:MM:00 for MySQL TIME column
            $parsed = strtotime((string)$val);
            $this->sanitized[$field] = $parsed ? date('H:i:00', $parsed) : self::sanitizeString((string)$val);
        }

        return $this;
    }

    /**
     * Validate whitelist options (ENUM)
     */
    public function in(string $field, array $allowedValues, ?string $customMessage = null): self {
        $val = $this->data[$field] ?? null;
        if (!empty($val) && !in_array($val, $allowedValues, true)) {
            $this->addError($field, $customMessage ?: "The selected option for " . str_replace('_', ' ', $field) . " is invalid.");
        } else {
            $this->sanitized[$field] = $val;
        }
        return $this;
    }

    /**
     * Validate min / max string length
     */
    public function lengthBetween(string $field, int $min, int $max, ?string $customMessage = null): self {
        $val = $this->data[$field] ?? null;
        if (!empty($val)) {
            $len = mb_strlen(trim((string)$val), 'UTF-8');
            if ($len < $min || $len > $max) {
                $this->addError($field, $customMessage ?: "Field length must be between {$min} and {$max} characters.");
            } else {
                $this->sanitized[$field] = self::sanitizeString((string)$val);
            }
        }
        return $this;
    }

    /**
     * Validate integer within range
     */
    public function integerBetween(string $field, int $min, int $max, ?string $customMessage = null): self {
        $val = $this->data[$field] ?? null;
        if ($val !== null && $val !== '') {
            $intVal = filter_var($val, FILTER_VALIDATE_INT);
            if ($intVal === false || $intVal < $min || $intVal > $max) {
                $this->addError($field, $customMessage ?: "Value must be an integer between {$min} and {$max}.");
            } else {
                $this->sanitized[$field] = $intVal;
            }
        }
        return $this;
    }

    /**
     * Check if validation passed
     */
    public function isValid(): bool {
        return empty($this->errors);
    }

    /**
     * Check if validation failed
     */
    public function fails(): bool {
        return !empty($this->errors);
    }

    /**
     * Get all validation errors
     */
    public function errors(): array {
        return $this->errors;
    }

    /**
     * Get first validation error message
     */
    public function firstError(): ?string {
        return !empty($this->errors) ? reset($this->errors) : null;
    }

    /**
     * Get sanitized values
     */
    public function values(): array {
        return array_merge($this->data, $this->sanitized);
    }

    /**
     * Get single sanitized value
     */
    public function value(string $field, $default = null) {
        return $this->sanitized[$field] ?? $this->data[$field] ?? $default;
    }

    /**
     * Sanitize string against XSS and control characters
     */
    public static function sanitizeString(string $value): string {
        // Strip null bytes and control chars (except normal whitespace)
        $clean = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value);
        return trim($clean);
    }
}

/**
 * Global helper function for HTML output escaping
 * 
 * @param string|null $string
 * @return string
 */
function e(?string $string): string {
    return htmlspecialchars((string)$string, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}
