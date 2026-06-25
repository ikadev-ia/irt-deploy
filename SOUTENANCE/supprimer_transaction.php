<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$id = (int)($_GET['id'] ?? 0);
$batch_id = (int)($_GET['batch_id'] ?? 0);
if ($id && $batch_id) {
    $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ?");
    $stmt->execute([$id]);
}
header("Location: finances.php?batch_id=$batch_id");
exit();
?>