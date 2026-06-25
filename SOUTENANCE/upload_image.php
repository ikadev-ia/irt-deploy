<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['diagnosis' => 'Non authentifié.']);
    exit();
}

if (!isset($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['diagnosis' => 'Aucune image reçue ou erreur de téléchargement.']);
    exit();
}

$uploadDir = 'uploads/';
if (!is_dir($uploadDir)) mkdir($uploadDir, 0777, true);
$filename = uniqid() . '.jpg';
$path = $uploadDir . $filename;
move_uploaded_file($_FILES['image']['tmp_name'], $path);

// ICI, vous pouvez appeler une API externe comme Google Cloud Vision ou un modèle TensorFlow.js.
// Pour l'exemple, on simule un diagnostic basé sur la couleur dominante (très simpliste).

// Simuler une analyse (à remplacer par une vraie IA)
$diagnosis = "📸 **Analyse visuelle (simulation)**\n\nL'image semble montrer des fientes anormales (couleur suspecte).\n\n🔍 **Diagnostic possible** : Coccidiose ou infection intestinale.\n💊 **Recommandation** : Surveillez l'apparition de sang dans les fientes. Traitez avec Amprolium si nécessaire.\n\n⚠️ Cette analyse est automatisée. Pour un diagnostic fiable, consultez un vétérinaire.";

// Supprimer l'image après analyse (optionnel)
// unlink($path);

echo json_encode(['diagnosis' => $diagnosis]);
?>