<?php
class FileModel
{
  public static function saveFile(array $file, $path = ''): string|false
  {
    try {
      // $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
      // $filename = bin2hex(random_bytes(8)) . '.' . $ext;
      $filename = $file['name'];
      $destination = UPLOAD_DIR . "$path/" . $filename;

      // Check upload dir
      $uploadDir = UPLOAD_DIR . $path;
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }

      return move_uploaded_file($file['tmp_name'], $destination) ? $filename : false;
    } catch (Exception $e) {
      return false;
    }
  }

  public static function deleteFile(string $filename, $path = ''): bool
  {
    $filepath = UPLOAD_DIR . $path . basename($filename);
    return file_exists($filepath) && unlink($filepath);
  }

  public static function isValidMime(string $tmpPath): bool
  {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);
    return in_array($mimeType, ALLOWED_MIME_TYPES);
  }

  public static function createFolder($path = ''): bool
  {
    $newDir = UPLOAD_DIR . $path;
    if (is_dir($newDir)) {
      $newDir .= ' copy';
    }
    return mkdir($newDir, 0755, true);
  }

  public static function deleteFolder($path = ''): bool
  {
    if ($path === '') {
      return false;
    }
    $dir = UPLOAD_DIR . $path;
    if (!is_dir($dir)) {
      return false;
    }

    $realBase = realpath(UPLOAD_DIR);
    $realDir = realpath($dir);

    if ($realDir === false || strpos($realDir, $realBase) !== 0) {
      // Prevent directory traversal attack
      return false;
    }

    // Recursive delete - delete everything in directory first (files and subfolders)
    $it = new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator(
      $it,
      RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
      if ($file->isDir()) {
        rmdir($file->getRealPath());
      } else {
        unlink($file->getRealPath());
      }
    }

    // finally remove the now-empty folder
    return rmdir($dir);
  }
}