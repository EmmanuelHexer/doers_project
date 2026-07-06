<?php
// backup_download.php - download a backup .sql file
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$backupPath = __DIR__ . '/backups/';
// basename() strips any directory parts to prevent path traversal.
$file = basename($_GET['file'] ?? '');
$full = $backupPath . $file;

// Only allow .sql files that actually live in the backups directory.
if ($file === '' || pathinfo($file, PATHINFO_EXTENSION) !== 'sql' || !is_file($full)) {
    header('Location: admin_backup.php');
    exit;
}

header('Content-Type: application/octet-stream');
header('Content-Disposition: attachment; filename="' . $file . '"');
header('Content-Length: ' . filesize($full));
header('Pragma: no-cache');
readfile($full);
exit;
