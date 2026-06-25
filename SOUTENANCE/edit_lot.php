<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$id = (int)($_GET['id'] ?? 0);
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];

// Vérifier que le lot appartient à l'utilisateur ou admin
if ($user_role == 'admin') {
    $stmt = $conn->prepare("SELECT * FROM batches WHERE id = ?");
    $stmt->execute([$id]);
} else {
    $stmt = $conn->prepare("SELECT * FROM batches WHERE id = ? AND user_id = ?");
    $stmt->execute([$id, $user_id]);
}
$batch = $stmt->fetch();
if (!$batch) { header("Location: dashboard.php"); exit(); }

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $start_date = $_POST['start_date'];
    $initial_birds = (int)$_POST['initial_birds'];
    $current_birds = (int)$_POST['current_birds'];
    if ($name && $start_date && $initial_birds >= 0 && $current_birds >= 0) {
        $stmt = $conn->prepare("UPDATE batches SET name=?, start_date=?, initial_birds=?, current_birds=? WHERE id=?");
        $stmt->execute([$name, $start_date, $initial_birds, $current_birds, $id]);
        $success = "Lot modifié avec succès !";
        // Recharger les nouvelles données
        $stmt = $conn->prepare("SELECT * FROM batches WHERE id = ?");
        $stmt->execute([$id]);
        $batch = $stmt->fetch();
    } else {
        $error = "Veuillez remplir tous les champs correctement.";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <title>Modifier le lot - PoultryTracker</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        *{margin:0;padding:0;box-sizing:border-box;}
        body{font-family:'Inter',sans-serif;background:linear-gradient(135deg,#f0f4f8,#e2e8f0);}
        .container{max-width:600px;margin:50px auto;background:white;border-radius:32px;padding:35px;box-shadow:0 25px 50px -12px rgba(0,0,0,0.25);}
        h1{font-size:1.8rem;margin-bottom:20px;color:#0f172a;}
        .form-group{margin-bottom:20px;}
        label{font-weight:600;display:block;margin-bottom:8px;}
        input{width:100%;padding:12px;border:2px solid #e2e8f0;border-radius:20px;font-family:inherit;}
        .btn-save{background:linear-gradient(135deg,#f59e0b,#d97706);color:white;border:none;padding:14px;border-radius:40px;width:100%;font-weight:600;cursor:pointer;}
        .alert-success{background:#d1fae5;color:#065f46;padding:12px;border-radius:20px;margin-bottom:20px;}
        .alert-error{background:#fee2e2;color:#991b1b;padding:12px;border-radius:20px;margin-bottom:20px;}
        .back-link{display:block;text-align:center;margin-top:20px;color:#f59e0b;text-decoration:none;}
    </style>
</head>
<body>
<div class="container">
    <h1><i class="fas fa-edit"></i> Modifier le lot</h1>
    <?php if ($success): ?><div class="alert-success"><?php echo $success; ?></div><?php endif; ?>
    <?php if ($error): ?><div class="alert-error"><?php echo $error; ?></div><?php endif; ?>
    <form method="POST">
        <div class="form-group">
            <label>Nom du lot</label>
            <input type="text" name="name" value="<?php echo htmlspecialchars($batch['name']); ?>" required>
        </div>
        <div class="form-group">
            <label>Date de début</label>
            <input type="date" name="start_date" value="<?php echo $batch['start_date']; ?>" required>
        </div>
        <div class="form-group">
            <label>Nombre initial de poulets</label>
            <input type="number" name="initial_birds" value="<?php echo $batch['initial_birds']; ?>" required min="0">
        </div>
        <div class="form-group">
            <label>Nombre actuel de poulets</label>
            <input type="number" name="current_birds" value="<?php echo $batch['current_birds']; ?>" required min="0">
        </div>
        <button type="submit" class="btn-save"><i class="fas fa-save"></i> Enregistrer les modifications</button>
    </form>
    <a href="dashboard.php" class="back-link"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
</div>
</body>
</html>