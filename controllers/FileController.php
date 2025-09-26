<?php
require_once __DIR__ . '/../models/FileModel.php';

class FileController
{
  public static function upload(): array
  {
    header('Content-Type: application/json');
    try {
      // Check if file was uploaded
      if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
        throw new Exception('No file uploaded');
      }
      // Check for path to avoid uploading file to the root of uploads dir
      if (!isset($_POST['path'])) {
        throw new Exception('No path provided');
      }

      // Optional: Check other fields
      $path = $_POST['path'];
      $file = $_FILES['file'];

      if ($file['size'] > MAX_FILE_SIZE) {
        return ['error' => 'File too large', 'status' => 413];
      }

      // if (!FileModel::isValidMime($file['tmp_name'])) {
      //   return ['error' => 'Unsupported file type', 'status' => 415];
      // }

      $filename = FileModel::saveFile($file, $path);
      if (!$filename) {
        return ['error' => 'Failed to save file', 'status' => 500];
      }

      return ['success' => true, 'filename' => $filename, 'filepath' => "$path/$filename", 'status' => 200];

    } catch (Exception $e) {
      return [
        'success' => false,
        'message' => 'Upload error',
        'error' => $e->getMessage(),
        'status' => 500
      ];
    }
  }

  public static function delete(): array
  {
    $input = json_decode(file_get_contents('php://input'), true);
    if (!isset($input['filename'])) {
      return ['error' => 'Missing filename', 'status' => 400];
    }

    $deleted = FileModel::deleteFile($input['filename'], $input['path']);
    if (!$deleted) {
      return ['error' => 'File not found or failed to delete', 'status' => 404];
    }

    return ['success' => true, 'status' => 200];
  }

  public static function createFolder(): array
  {
    $path = $_POST['path'];
    if (FileModel::createFolder($path)) {
      return ['success' => true, 'status' => 200];
    }
    return ['success' => false, 'status' => 400, 'error' => 'Folder wasn\'t created. Please try later.'];
  }

  public static function deleteFolder(): array
  {
    $path = $_POST['path'];
    if (FileModel::deleteFolder($path)) {
      return ['success' => true, 'status' => 200];
    }
    return ['success' => false, 'status' => 400, 'error' => 'Folder wasn\'t created. Please try later.'];
  }
}