<?php
/**
 * Environment Variable Loader
 * 
 * Loads configuration values from environment variables with fallback to defaults.
 * Supports .env file loading if available.
 */

// Load .env file if exists (for development)
$env_file = __DIR__ . '/../.env';
if (file_exists($env_file)) {
    $lines = file($env_file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        // Skip comments
        if (strpos(trim($line), '#') === 0) continue;
        
        // Parse KEY=VALUE
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value);
            
            // Remove quotes if present
            $value = trim($value, '"\'');
            
            // Set as environment variable if not already set
            if (!getenv($key)) {
                putenv("$key=$value");
            }
        }
    }
}

/**
 * Get environment variable with fallback
 * 
 * @param string $key Environment variable name
 * @param mixed $default Default value if not found
 * @return mixed
 */
function env($key, $default = null) {
    $value = getenv($key);
    
    if ($value === false) {
        return $default;
    }
    
    // Convert string booleans
    switch (strtolower($value)) {
        case 'true':
        case '(true)':
            return true;
        case 'false':
        case '(false)':
            return false;
        case 'null':
        case '(null)':
            return null;
    }
    
    return $value;
}

/**
 * Require environment variable - throws error if not set
 * 
 * @param string $key Environment variable name
 * @return mixed
 * @throws Exception if not set
 */
function env_required($key) {
    $value = getenv($key);
    
    if ($value === false) {
        throw new Exception("Required environment variable '$key' is not set. Please configure your environment.");
    }
    
    return $value;
}
?>
