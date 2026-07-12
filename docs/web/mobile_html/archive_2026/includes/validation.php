<?php
/**
 * GoSiteMe Global Input Validation Library
 * Provides sanitization and validation for all user inputs
 */

class InputValidator {
    
    /**
     * Sanitize string input - remove dangerous characters and encode HTML
     */
    public static function sanitizeString($input, $max_length = 255) {
        if (!is_string($input)) {
            return '';
        }
        
        // Trim whitespace
        $input = trim($input);
        
        // Remove null bytes
        $input = str_replace("\x00", '', $input);
        
        // Strip HTML tags (keep text content)
        $input = strip_tags($input);
        
        // Truncate to max length
        if ($max_length > 0) {
            $input = substr($input, 0, $max_length);
        }
        
        // Encode HTML entities for safe output
        return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
    }
    
    /**
     * Validate and clean email address
     */
    public static function validateEmail($email) {
        $email = trim($email);
        $email = strtolower($email);
        
        // Check length
        if (strlen($email) > 254) {
            return null;
        }
        
        // Validate format
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return null;
        }
        
        return $email;
    }
    
    /**
     * Validate integer input
     */
    public static function validateInteger($input, $min = null, $max = null) {
        if (!is_numeric($input)) {
            return null;
        }
        
        $value = intval($input);
        
        if ($min !== null && $value < $min) {
            return null;
        }
        
        if ($max !== null && $value > $max) {
            return null;
        }
        
        return $value;
    }
    
    /**
     * Validate URL
     */
    public static function validateURL($url) {
        $url = trim($url);
        
        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            return null;
        }
        
        // Ensure it's HTTP or HTTPS only
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) {
            return null;
        }
        
        return $url;
    }
    
    /**
     * Validate phone number (basic format: 10-15 digits with optional formatting)
     */
    public static function validatePhone($phone) {
        $phone = preg_replace('/[^0-9+()-]/', '', $phone);
        
        if (strlen($phone) < 10 || strlen($phone) > 20) {
            return null;
        }
        
        return $phone;
    }
    
    /**
     * Validate domain name
     */
    public static function validateDomain($domain) {
        $domain = trim(strtolower($domain));
        
        // Basic domain validation
        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,}$/', $domain)) {
            return null;
        }
        
        return $domain;
    }
    
    /**
     * Sanitize array of inputs
     */
    public static function sanitizeArray($array, $rules = []) {
        $sanitized = [];
        
        foreach ($array as $key => $value) {
            // Sanitize key (only allow alphanumeric, underscore, hyphen)
            $clean_key = preg_replace('/[^a-zA-Z0-9_-]/', '', $key);
            if (empty($clean_key)) {
                continue;
            }
            
            // Apply specific rule if exists
            if (isset($rules[$key])) {
                $rule = $rules[$key];
                
                if ($rule === 'email') {
                    $sanitized[$clean_key] = self::validateEmail($value);
                } elseif ($rule === 'integer') {
                    $sanitized[$clean_key] = self::validateInteger($value);
                } elseif ($rule === 'url') {
                    $sanitized[$clean_key] = self::validateURL($value);
                } elseif ($rule === 'phone') {
                    $sanitized[$clean_key] = self::validatePhone($value);
                } elseif ($rule === 'domain') {
                    $sanitized[$clean_key] = self::validateDomain($value);
                } elseif (is_array($rule)) {
                    // Custom rule with type and max_length
                    $type = $rule['type'] ?? 'string';
                    $max_length = $rule['max_length'] ?? 255;
                    if ($type === 'string') {
                        $sanitized[$clean_key] = self::sanitizeString($value, $max_length);
                    }
                } else {
                    // Default to string sanitization
                    $sanitized[$clean_key] = self::sanitizeString($value);
                }
            } else {
                // Default to string sanitization
                $sanitized[$clean_key] = self::sanitizeString($value);
            }
        }
        
        return $sanitized;
    }
    
    /**
     * Check if required fields are present
     */
    public static function validateRequired($data, $required_fields) {
        $missing = [];
        
        foreach ($required_fields as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $missing[] = $field;
            }
        }
        
        return empty($missing) ? true : $missing;
    }
    
    /**
     * Escape string for safe SQL usage (use prepared statements instead!)
     * This is a fallback - ALWAYS use prepared statements
     */
    public static function escapeSQLString($input) {
        if (!is_string($input)) {
            return '';
        }
        // This is NOT a replacement for prepared statements
        // It's only here as a last resort
        if (function_exists('mysqli_real_escape_string')) {
            global $db;
            return mysqli_real_escape_string($db, $input);
        }
        return addslashes($input);
    }
    
    /**
     * Log suspicious activity
     */
    public static function logSuspiciousActivity($type, $data = []) {
        $log_file = __DIR__ . '/../logs/security_audit.log';
        $log_entry = json_encode([
            'timestamp' => date('Y-m-d H:i:s'),
            'ip' => $_SERVER['REMOTE_ADDR'] ?? 'unknown',
            'type' => $type,
            'data' => $data,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        ]) . "\n";
        
        // Create logs directory if it doesn't exist
        if (!is_dir(dirname($log_file))) {
            mkdir(dirname($log_file), 0755, true);
        }
        
        // Append to log (prevent log file injection)
        file_put_contents($log_file, $log_entry, FILE_APPEND);
    }
}

/**
 * Helper functions for quick access
 */

if (!function_exists("sanitize")) {
function sanitize($input, $max_length = 255) {
    return InputValidator::sanitizeString($input, $max_length);
}
}

function validate_email($email) {
    return InputValidator::validateEmail($email);
}

function validate_integer($input, $min = null, $max = null) {
    return InputValidator::validateInteger($input, $min, $max);
}

function validate_url($url) {
    return InputValidator::validateURL($url);
}

function validate_domain($domain) {
    return InputValidator::validateDomain($domain);
}

function sanitize_array($array, $rules = []) {
    return InputValidator::sanitizeArray($array, $rules);
}

function validate_required($data, $required_fields) {
    return InputValidator::validateRequired($data, $required_fields);
}

function log_suspicious($type, $data = []) {
    InputValidator::logSuspiciousActivity($type, $data);
}
