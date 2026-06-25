<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$batch_id = (int)($_POST['batch_id'] ?? 0);
$vaccine_id = (int)($_POST['vaccine_id'] ?? 0);
$action = $_POST['action'] ?? '';

if (!$batch_id || !$vaccine_id || !in_array($action, ['done', 'undo'])) {
    header("Location: vaccins.php?error=missing");
    exit();
}

// Vérifier l'accès au lot
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
if ($user_role != 'admin') {
    $stmt = $conn->prepare("SELECT id FROM batches WHERE id = ? AND user_id = ?");
    $stmt->execute([$batch_id, $user_id]);
    if (!$stmt->fetch()) {
        header("Location: vaccins.php?error=unauthorized");
        exit();
    }
}

if ($action == 'done') {
    // Marquer comme fait
    $today = date('Y-m-d');
    $stmt = $conn->prepare("UPDATE batch_vaccines SET is_done = 1, administered_date = ? WHERE batch_id = ? AND vaccine_id = ?");
    $stmt->execute([$today, $batch_id, $vaccine_id]);
    $message = "Vaccin marqué comme effectué.";
} else {
    // Annuler (marquer comme non fait)
    $stmt = $conn->prepare("UPDATE batch_vaccines SET is_done = 0, administered_date = NULL WHERE batch_id = ? AND vaccine_id = ?");
    $stmt->execute([$batch_id, $vaccine_id]);
    $message = "Vaccin marqué comme non effectué.";
}

// Créer une notification
$stmt = $conn->prepare("SELECT name FROM vaccines WHERE id = ?");
$stmt->execute([$vaccine_id]);
$vaccine_name = $stmt->fetchColumn();

$stmt = $conn->prepare("INSERT INTO notifications (user_id, batch_id, type, message, severity) VALUES (?, ?, 'vaccine', ?, 'info')");
$stmt->execute([$user_id, $batch_id, $message . " : " . $vaccine_name]);

header("Location: vaccins.php?batch_id=$batch_id&success=1");
exit();
?>