<?php
// backup_delete.php - delete a backup .sql file
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}

$backupPath = __DIR__ . '/backups/';
$file = basename($_GET['file'] ?? '');   // basename() blocks path traversal
$full = $backupPath . $file;

if ($file !== '' && pathinfo($file, PATHINFO_EXTENSION) === 'sql' && is_file($full)) {
    unlink($full);
}

header('Location: admin_backup.php');
exit;
