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

$stmt = $db->prepare("DELETE FROM requests WHERE id = ?");
$stmt->bind_param("i", $id);

if ($stmt->execute()) {
    echo "Заявка успешно удалена";
} else {
    header("HTTP/1.1 500 Internal Server Error");
    echo "Ошибка при удалении заявки";
}

$stmt->close();
?>