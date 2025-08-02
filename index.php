<?php
require 'config.php';
header('Content-Type: application/json');

// Enforce API key
if ($_SERVER['HTTP_X_API_KEY'] !== API_KEY) {
  http_response_code(403);
  echo json_encode(['error' => 'Forbidden']);
  exit;
}

$method = $_SERVER['REQUEST_METHOD'];
$path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$endpoint = basename($path);

// Route: POST /upload
if ($method === 'POST' && $endpoint === 'upload') {
  if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid file upload']);
    exit;
  }

  $file = $_FILES['file'];

  if ($file['size'] > MAX_FILE_SIZE) {
    http_response_code(413);
    echo json_encode(['error' => 'File too large']);
    exit;
  }

  $finfo = new finfo(FILEINFO_MIME_TYPE);
  $mimeType = $finfo->file($file['tmp_name']);

  if (!in_array($mimeType, ALLOWED_MIME_TYPES)) {
    http_response_code(415);
    echo json_encode(['error' => 'Unsupported file type']);
    exit;
  }

  $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
  $filename = bin2hex(random_bytes(8)) . '.' . $ext;
  $destination = UPLOAD_DIR . $filename;

  if (!move_uploaded_file($file['tmp_name'], $destination)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to save file']);
    exit;
  }

  echo json_encode(['success' => true, 'filename' => $filename]);
  exit;
}

// Route: POST /delete
if ($method === 'POST' && $endpoint === 'delete') {
  $input = json_decode(file_get_contents('php://input'), true);
  if (!isset($input['filename'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing filename']);
    exit;
  }

  $filename = basename($input['filename']);
  $filepath = UPLOAD_DIR . $filename;

  if (!file_exists($filepath)) {
    http_response_code(404);
    echo json_encode(['error' => 'File not found']);
    exit;
  }

  if (!unlink($filepath)) {
    http_response_code(500);
    echo json_encode(['error' => 'Failed to delete file']);
    exit;
  }

  echo json_encode(['success' => true]);
  exit;
}

// Unknown route
http_response_code(404);
echo json_encode(['error' => 'Endpoint not found']);