<?php
class CorsMiddleware
{
  private static $allowedOrigins = [
    'https://formify.me',
    'http://localhost:3000',
  ];

  public static function handle()
  {
    $origin = $_SERVER['HTTP_ORIGIN'] ?? '';

    if (in_array($origin, self::$allowedOrigins)) {
      header("Access-Control-Allow-Origin: $origin");
      header("Vary: Origin");
    }

    header("Access-Control-Allow-Methods: POST, GET, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, X-API-Key");

    // Optional: allow credentials (if using cookies/auth headers)
    // header("Access-Control-Allow-Credentials: true");

    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
      http_response_code(200);
      exit;
    }
  }
}