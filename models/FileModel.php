<?php
class FileModel
{
  public static function saveFile(array $file): string|false
  {
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = bin2hex(random_bytes(8)) . '.' . $ext;
    $destination = UPLOAD_DIR . $filename;

    return move_uploaded_file($file['tmp_name'], $destination) ? $filename : false;
  }

  public static function deleteFile(string $filename): bool
  {
    $filepath = UPLOAD_DIR . basename($filename);
    return file_exists($filepath) && unlink($filepath);
  }

  public static function isValidMime(string $tmpPath): bool
  {
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mimeType = $finfo->file($tmpPath);
    return in_array($mimeType, ALLOWED_MIME_TYPES);
  }
}