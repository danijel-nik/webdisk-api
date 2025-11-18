<?php
class FileModel
{
  public static function saveFile(array $file, $path = ''): string|false
  {
    try {
      // ✅ Validate file array
      if (!isset($file['tmp_name'], $file['name']) || !is_uploaded_file($file['tmp_name'])) {
        return false;
      }

      // ✅ Sanitize folder path
      $path = preg_replace('/[^a-zA-Z0-9_@.\-\/ ]/', '', $path);
      if ($path === '') {
        return false;
      }

      // ✅ Extract safe extension
      $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));

      if (!in_array($ext, ALLOWED_EXTENSIONS, true)) {
        return false;
      }

      // ✅ Generate safe filename (random or sanitized original)
      $baseName = pathinfo($file['name'], PATHINFO_FILENAME);
      $baseName = preg_replace('/[^a-zA-Z0-9_\-\(\) ]/', '_', $baseName); // safe chars only
      if ($baseName === '') {
        $baseName = 'file';
      }

      // If you want random names, uncomment:
      // $baseName = bin2hex(random_bytes(8));

      $filename = $baseName . '.' . $ext;

      // ✅ Ensure upload directory exists
      $uploadDir = rtrim(UPLOAD_DIR . '/' . $path, '/');
      if (!is_dir($uploadDir)) {
        mkdir($uploadDir, 0755, true);
      }

      // ✅ Prevent overwriting existing files → add (1), (2), ...
      $destination = $uploadDir . '/' . $filename;
      // $counter = 1;
      // while (file_exists($destination)) {
      //   $filename = $baseName . " ($counter)." . $ext;
      //   $destination = $uploadDir . '/' . $filename;
      //   $counter++;
      // }

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
    // Sanitize folder name: allow only letters, numbers, dashes, underscores
    $path = preg_replace('/[^a-zA-Z0-9_@.\-\/ ]/', '', $path);

    if ($path === '') {
      return false;
    }

    $newDir = UPLOAD_DIR . $path;

    // Normalize paths
    $realBase = realpath(UPLOAD_DIR);
    $realTarget = realpath(dirname($newDir));

    // Ensure parent exists
    if ($realTarget === false) {
      $realTarget = $realBase;
    }

    // Prevent directory traversal attacks
    if ($realBase === false || strpos($realTarget, $realBase) !== 0) {
      return false;
    }

    // If folder already exists, add " copy", " copy 2", etc.
    $counter = 1;
    $candidate = $newDir;
    while (is_dir($candidate)) {
      $candidate = $newDir . ' copy' . ($counter > 1 ? " $counter" : '');
      $counter++;
    }

    return mkdir($newDir, 0755, true);
  }

  public static function deleteFolder($path = ''): bool | string
  {
    if ($path === '') {
      return false;
    }

    $realBase = realpath(UPLOAD_DIR);
    if ($realBase === false) {
      return false;
    }

    $target = UPLOAD_DIR . $path;
    $realTarget = realpath($target);

    // making sure that $realTarget is into $realBase
    if ($realTarget === false || strpos($realTarget, $realBase) !== 0) {
      // Prevent directory traversal attack
      return false;
    }

    // Recursive delete - delete everything in directory first (files and subfolders)
    $it = new RecursiveDirectoryIterator($realTarget, FilesystemIterator::SKIP_DOTS);
    $files = new RecursiveIteratorIterator(
      $it,
      RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($files as $file) {
      $filePath = $file->getPathname();
      $realFilePath = realpath($filePath);
      $checkPath = $realFilePath !== false ? $realFilePath : $filePath;

      // security check: don't delete anything outside of root
      if (strpos($checkPath, $realBase) !== 0) {
        return false;
      }

      if ($file->isLink()) {
        if (!@unlink($filePath)) {
          return false;
        }
        continue;
      }

      if ($file->isDir()) {
        if (!@rmdir($filePath)) {
          return false;
        }
      } else {
        if (!@unlink($filePath)) {
          return false;
        }
      }
    }

    // finally remove the now-empty folder
    return @rmdir($realTarget);
  }

  /**
   * Rename files and folders
   * @param string $oldPath
   * @param string $newPath
   * @return bool
   */
  public static function rename(string $oldPath, string $newPath): bool
  {
    if ($oldPath === '' || $newPath === '') {
      return false;
    }

    $old = UPLOAD_DIR . $oldPath;
    $new = UPLOAD_DIR . $newPath;

    if (!file_exists($old)) {
      return false; // nothing to rename
    }

    if (file_exists($new)) {
      $new .= ' copy';
    }

    // Prevent directory traversal attacks
    $realBase = realpath(UPLOAD_DIR);
    $realOld = realpath($old);
    $realNew = realpath(dirname($new)); // check parent dir of new path

    if ($realOld === false || $realNew === false) {
      return false;
    }

    if (strpos($realOld, $realBase) !== 0 || strpos($realNew, $realBase) !== 0) {
      return false; // outside upload dir
    }

    return rename($old, $new);
  }

  /**
   * Lists folder: files and subfolders in results
   * @param string $path
   * @return array{modified: string, name: bool|string, size: bool|int|null, type: string[]|bool}
   */
  public static function listFolder(string $path = ''): array|false
  {
    // 🛡️ Sanitize input path
    $path = preg_replace('/[^a-zA-Z0-9_@.\-\/ ]/', '', $path);

    $dir = rtrim(UPLOAD_DIR . '/' . $path, '/');

    if (!is_dir($dir)) {
      return false;
    }

    // 🛡️ Prevent directory traversal
    $realBase = realpath(UPLOAD_DIR);
    $realDir = realpath($dir);

    if ($realBase === false || $realDir === false || strpos($realDir, $realBase) !== 0) {
      return false;
    }

    $items = [];
    $handle = opendir($dir);
    if ($handle === false) {
      return false;
    }

    while (($entry = readdir($handle)) !== false) {
      if ($entry === '.' || $entry === '..') {
        continue;
      }

      $fullPath = $dir . '/' . $entry;
      $items[] = [
        'name' => $entry,
        'type' => is_dir($fullPath) ? 'folder' : 'file',
        'size' => is_file($fullPath) ? filesize($fullPath) : null,
        'modified' => date('Y-m-d H:i:s', filemtime($fullPath)),
      ];
    }
    closedir($handle);

    return $items;
  }
}