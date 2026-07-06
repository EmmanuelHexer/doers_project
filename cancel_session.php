<?php
session_start();

if (!isset($_SESSION['member_logged_in']) || $_SESSION['member_logged_in'] !== true) {
    header('Location: login.php');
    exit;
}

require_once 'config/database.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['section_id'])) {
    $db = Database::getInstance()->getConnection();
    $member_id = $_SESSION['member_id'];
    $sectionId = intval($_POST['section_id']);
    
    $sql = "UPDATE Member_Section SET status = 'dropped' WHERE member_id = ? AND section_id = ? AND status = 'active'";
    $stmt = $db->prepare($sql);
    $stmt->execute([$member_id, $sectionId]);
}

header('Location: my_sessions.php');
exit;
?>