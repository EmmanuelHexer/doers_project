<?php
// add_section.php - create a training section (posted from the modal on admin_training_sections.php)
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$db = Database::getInstance()->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $typeId = intval($_POST['training_type_id'] ?? 0);
    $instrId = intval($_POST['instructor_id'] ?? 0);
    $day = $_POST['day_of_week'] ?? '';
    $start = $_POST['start_time'] ?? '';
    $end = $_POST['end_time'] ?? '';
    $capacity = intval($_POST['capacity'] ?? 20);

    if ($typeId > 0 && $instrId > 0 && $day !== '' && $start !== '' && $end !== '') {
        $db->prepare("INSERT INTO Training_Section (training_type_id, instructor_id, day_of_week, start_time, end_time, capacity, status)
                      VALUES (?, ?, ?, ?, ?, ?, 'active')")
           ->execute([$typeId, $instrId, $day, $start, $end, $capacity]);
    }
}

header('Location: admin_training_sections.php');
exit;
