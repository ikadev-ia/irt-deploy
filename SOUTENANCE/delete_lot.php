<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Vérifier l'appartenance
if ($user_role == 'admin') {
    $stmt = $conn->prepare("SELECT id FROM batches WHERE id = ?");
    $stmt->execute([$id]);
} else {
    $stmt = $conn->prepare("SELECT id FROM batches WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
}
if (!$stmt->fetch()) { header("Location: dashboard.php"); exit(); }

// Supprimer les dépendances
$conn->exec("DELETE FROM daily_tracking WHERE batch_id = $id");
$conn->exec("DELETE FROM batch_vaccines WHERE batch_id = $id");
$conn->exec("DELETE FROM notifications WHERE batch_id = $id");

// Enfin supprimer le lot
$conn->exec("DELETE FROM batches WHERE id = $id");

// Réinitialiser le lot sélectionné en session
if (isset($_SESSION['selected_batch_id']) && $_SESSION['selected_batch_id'] == $id) {
    unset($_SESSION['selected_batch_id']);
}

header("Location: dashboard.php?msg=lot_supprime");
exit();
?>