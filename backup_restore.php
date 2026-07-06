<?php
// backup_restore.php - restore the database from a backup .sql file (destructive; POST only)
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: admin_backup.php');
    exit;
}

$backupPath = __DIR__ . '/backups/';
$file = basename($_POST['file'] ?? '');   // basename() blocks path traversal
$full = $backupPath . $file;

if ($file === '' || pathinfo($file, PATHINFO_EXTENSION) !== 'sql' || !is_file($full)) {
    header('Location: admin_backup.php?restore=invalid');
    exit;
}

$db = Database::getInstance()->getConnection();
$sql = file_get_contents($full);

// Drop comment lines, then split into individual statements (each ends with ";\n").
$lines = array_filter(explode("\n", $sql), function ($l) { return !preg_match('/^\s*--/', $l); });
$sql = implode("\n", $lines);
$statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql)));

$db->exec("SET FOREIGN_KEY_CHECKS=0");
try {
    foreach ($statements as $st) {
        if ($st !== '' && stripos($st, 'SET FOREIGN_KEY_CHECKS') === false) {
            $db->exec($st);
        }
    }
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    header('Location: admin_backup.php?restore=ok');
} catch (Exception $e) {
    $db->exec("SET FOREIGN_KEY_CHECKS=1");
    header('Location: admin_backup.php?restore=error');
}
exit;
