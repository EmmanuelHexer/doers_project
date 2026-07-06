<?php
// backup_create.php - export the whole database to a .sql file in /backups
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();
$backupPath = __DIR__ . '/backups/';
if (!is_dir($backupPath)) {
    mkdir($backupPath, 0755, true);
}

$file = $backupPath . 'gym_database_' . date('Ymd_His') . '.sql';
$out = "-- USTED-K Gym Center database backup\n-- Generated: " . date('Y-m-d H:i:s') . "\n\n";
$out .= "SET FOREIGN_KEY_CHECKS=0;\n\n";

$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
foreach ($tables as $table) {
    // Structure
    $create = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_NUM);
    $out .= "DROP TABLE IF EXISTS `$table`;\n" . $create[1] . ";\n\n";

    // Data
    $rows = $db->query("SELECT * FROM `$table`");
    while ($row = $rows->fetch(PDO::FETCH_ASSOC)) {
        $cols = array_map(function ($c) { return "`$c`"; }, array_keys($row));
        $vals = array_map(function ($v) use ($db) {
            return $v === null ? 'NULL' : $db->quote($v);
        }, array_values($row));
        $out .= "INSERT INTO `$table` (" . implode(', ', $cols) . ") VALUES (" . implode(', ', $vals) . ");\n";
    }
    $out .= "\n";
}
$out .= "SET FOREIGN_KEY_CHECKS=1;\n";

file_put_contents($file, $out);

header('Location: admin_backup.php');
exit;
