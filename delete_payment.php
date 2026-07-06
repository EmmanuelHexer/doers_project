<?php
// delete_payment.php
session_start();
if (!isset($_SESSION['admin_logged_in']) || $_SESSION['admin_logged_in'] !== true) {
    header('Location: admin_login.php');
    exit;
}
require_once __DIR__ . '/config/database.php';

$id = intval($_GET['id'] ?? 0);
if ($id > 0) {
    $db = Database::getInstance()->getConnection();
    $db->prepare("DELETE FROM Payment WHERE payment_id = ?")->execute([$id]);
}

header('Location: admin_payments.php');
exit;
