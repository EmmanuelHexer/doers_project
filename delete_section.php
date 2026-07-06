<?php
// delete_section.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $db = Database::getInstance()->getConnection();
    try {
        $db->beginTransaction();
        $db->prepare("DELETE FROM Member_Section WHERE section_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM Training_Section WHERE section_id = ?")->execute([$id]);
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
    }
}

header('Location: admin_training_sections.php');
exit;
