<?php
// delete_instructor.php
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
        // Unlink references, then delete the instructor.
        $db->prepare("DELETE FROM Instructor_Train_Type WHERE instructor_id = ?")->execute([$id]);
        $db->prepare("UPDATE Training_Section SET instructor_id = NULL WHERE instructor_id = ?")->execute([$id]);
        $db->prepare("UPDATE Members SET assigned_instructor_id = NULL WHERE assigned_instructor_id = ?")->execute([$id]);
        $db->prepare("DELETE FROM Instructor WHERE instructor_id = ?")->execute([$id]);
        $db->commit();
    } catch (Exception $e) {
        $db->rollBack();
    }
}

header('Location: admin_instructors.php');
exit;
