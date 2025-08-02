<?php
require_once __DIR__ . '/../models/FileModel.php';

class FileController
{
  public static function upload(): array
  {
    if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
      return ['error' => 'Invalid file upload', 'status' => 400];
    }

    $file = $_FILES['file'];

    if ($file['size'] > MAX_FILE_SIZE) {
      return ['error' => 'File too large', 'status' => 413];
    }

    if (!FileModel::isValidMime($file['tmp_name'])) {
      return ['error' => 'Unsupported file type', 'status' => 415];
    }

    $filename = FileModel::saveFile($file);
    if (!$filename) {
      return ['error' => 'Failed to save file', 'status' => 500];
    }

    return ['success' => true, 'filename' => $filename, 'status' => 200];
  }

  public static function delete()
  {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['filename'])) {
      return ['error' => 'Missing filename', 'status' => 400];
    }

    $deleted = FileModel::deleteFile($input['filename']);
    if (!$deleted) {
      return ['error' => 'File not found or failed to delete', 'status' => 404];
    }

    return ['success' => true, 'status' => 200];
  }
}