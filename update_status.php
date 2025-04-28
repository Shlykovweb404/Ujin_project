<?php
session_start();
require_once 'db.php';

if (!isset($_SESSION['admin_logged_in'])) {
    header("HTTP/1.1 403 Forbidden");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("HTTP/1.1 405 Method Not Allowed");
    exit();
}

$id = intval($_POST['id']);
$status = $_POST['status'];
$rejectionReason = isset($_POST['rejection_reason']) ? trim($_POST['rejection_reason']) : null;

if ($status === 'rejected' && empty($rejectionReason)) {
    header("HTTP/1.1 400 Bad Request");
    echo "При отклонении заявки необходимо указать причину";
    exit();
}

$stmt = $db->prepare("UPDATE requests SET status = ?, rejection_reason = ? WHERE id = ?");
$stmt->bind_param("ssi", $status, $rejectionReason, $id);

if ($stmt->execute()) {
    echo "Статус успешно обновлен";
} else {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Ошибка при обновлении статуса";
}

$stmt->close();
?>