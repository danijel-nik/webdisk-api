<?php
require_once __DIR__ . '/middleware/CorsMiddleware.php';
CorsMiddleware::handle();

require_once __DIR__ . '/config/config.php';
require_once __DIR__ . '/controllers/FileController.php';

$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$endpoint = basename($path);
$method = $_SERVER['REQUEST_METHOD'];

// Simple API key check
if ($_SERVER['HTTP_X_API_KEY'] !== API_KEY) {
  $response = ['error' => 'Forbidden', 'status' => 403];
  require 'views/response.php';
  exit;
}

// Route handling
switch ("$method $endpoint") {
  case 'POST upload':
    $response = FileController::upload();
    break;
  case 'POST delete':
    $response = FileController::delete();
    break;
  case 'POST rename':
    $response = FileController::rename();
    break;
  case 'POST create-folder':
    $response = FileController::createFolder();
    break;
  case 'POST delete-folder':
    $response = FileController::deleteFolder();
    break;
  case 'GET ':
    $response = ['success' => true, 'status' => 200, 'message' => APP_NAME . ' v' . APP_VERSION];
    break;
  default:
    $response = ['error' => 'Not Found', 'status' => 404];
}

require 'views/response.php';