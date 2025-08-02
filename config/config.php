<?php
// Load .env manually (no composer)
$env = file(__DIR__ . '/../.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
foreach ($env as $line) {
  if (str_starts_with(trim($line), '#'))
    continue;
  [$key, $val] = explode('=', $line, 2);
  $_ENV[trim($key)] = trim($val);
}

// Load configuration
define('APP_NAME', 'WebDisk API');
define('APP_VERSION', '1.0.0');
define('UPLOAD_DIR', rtrim($_ENV['UPLOAD_DIR'], '/') . '/');
define('ALLOWED_MIME_TYPES', explode(',', $_ENV['ALLOWED_MIME_TYPES']));
define('MAX_FILE_SIZE', (int) $_ENV['MAX_FILE_SIZE']);
define('API_KEY', $_ENV['API_KEY']);
