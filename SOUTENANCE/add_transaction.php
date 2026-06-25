<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

$error = '';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $type = $_POST['type'];
    $amount = floatval($_POST['amount']);
    $description = $_POST['description'];
    $category = $_POST['category'];
    
    if ($amount <= 0) {
        $error = "Le montant doit être supérieur à 0";
    } else {
        $stmt = $conn->prepare("INSERT INTO transactions (user_id, amount, type, description, category, status) 
                                VALUES (?, ?, ?, ?, ?, 'completed')");
        $stmt->execute([$user_id, $amount, $type, $description, $category]);
        header("Location: finances.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Ajouter transaction - Poulplume</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
        }
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(145deg, #14B53A 0%, #0d8a2f 100%);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform 0.3s;
            box-shadow: 4px 0 20px rgba(0,0,0,0.1);
        }
        .sidebar-header {
            padding: 30px 24px;
            text-align: center;
            border-bottom: 2px solid #FCD116;
        }
        .logo-img {
            width: 80px;
            height: 80px;
            margin: 0 auto 12px;
            background: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .logo-img img { width: 60px; height: 60px; border-radius: 50%; }
        .brand { font-size: 1.6rem; font-weight: 700; }
        .brand .poul { color: #FCD116; }
        .brand .plume { color: white; }
        .sidebar-nav { flex: 1; padding: 20px 16px; display: flex; flex-direction: column; gap: 4px; }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 12px 16px;
            border-radius: 12px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            transition: 0.2s;
            font-weight: 500;
        }
        .nav-item i { width: 22px; font-size: 1rem; }
        .nav-item:hover { background: #FCD116; color: #0f172a; transform: translateX(5px); }
        .nav-item.active { background: white; color: #14B53A; }
        .settings-group { margin-top: 12px; }
        .settings-header { cursor: pointer; justify-content: space-between; display: flex; align-items: center; }
        .settings-sub {
            margin-left: 38px;
            display: none;
            flex-direction: column;
            gap: 4px;
            margin-top: 8px;
        }
        .settings-sub a {
            padding: 8px 12px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.8rem;
            border-radius: 8px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .settings-sub a:hover { background: #FCD116; color: #0f172a; }
        .settings-sub.show { display: flex; }
        .sidebar-footer {
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .user-card {
            display: flex;
            align-items: center;
            gap: 12px;
            background: rgba(255,255,255,0.1);
            padding: 10px;
            border-radius: 12px;
        }
        .user-avatar {
            width: 42px;
            height: 42px;
            background: #FCD116;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #14B53A;
        }
        .user-name { font-weight: 600; font-size: 0.85rem; color: white; }
        .user-role { font-size: 0.7rem; color: #FCD116; }
        .burger-btn {
            display: none;
            position: fixed;
            top: 15px;
            left: 15px;
            z-index: 1100;
            background: #14B53A;
            border: none;
            color: white;
            padding: 10px 14px;
            border-radius: 10px;
            cursor: pointer;
        }
        .main-content {
            margin-left: 280px;
            padding: 30px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .form-container {
            max-width: 500px;
            width: 100%;
            background: white;
            border-radius: 16px;
            border: 1px solid #e2e8f0;
            padding: 30px;
        }
        .form-container h2 {
            font-size: 1.3rem;
            font-weight: 600;
            color: #0f172a;
            margin-bottom: 20px;
            text-align: center;
        }
        .form-group { margin-bottom: 20px; }
        .form-group label {
            display: block;
            font-weight: 600;
            font-size: 0.8rem;
            margin-bottom: 8px;
            color: #1e293b;
        }
        .form-group input, .form-group select, .form-group textarea {
            width: 100%;
            padding: 10px 14px;
            border: 1px solid #e2e8f0;
            border-radius: 10px;
            font-family: inherit;
            font-size: 0.85rem;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: #14B53A;
        }
        .btn {
            width: 100%;
            padding: 12px;
            background: #14B53A;
            color: white;
            border: none;
            border-radius: 10px;
            font-weight: 600;
            cursor: pointer;
            font-size: 0.85rem;
        }
        .btn:hover { background: #0d8a2f; }
        .btn-back {
            display: inline-block;
            margin-top: 15px;
            text-align: center;
            width: 100%;
            color: #64748b;
            text-decoration: none;
            font-size: 0.8rem;
        }
        .error {
            background: #fef2f2;
            color: #dc2626;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.8rem;
        }
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .burger-btn { display: block; }
            .main-content { margin-left: 0; }
        }
    </style>
</head>
<body>

<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo-img"><img src="Images/Logo.png" alt="Poulplume"></div>
        <div class="brand"><span class="poul">Poul</span><span class="plume">plume</span></div>
    </div>
    <div class="sidebar-nav">
        <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="add_lot.php" class="nav-item"><i class="fas fa-plus-circle"></i> Nouveau lot</a>
        <a href="finances.php" class="nav-item"><i class="fas fa-coins"></i> Finances</a>
        <a href="chatbot.php" class="nav-item"><i class="fas fa-robot"></i> Chatbot IA</a>
        <div class="settings-group">
            <div class="nav-item settings-header" onclick="toggleSettings()">
                <i class="fas fa-cog"></i> Paramètres <i class="fas fa-chevron-down"></i>
            </div>
            <div class="settings-sub" id="settingsSub">
                <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                <a href="aide.php"><i class="fas fa-question-circle"></i> Aide</a>
                <a href="change_password.php"><i class="fas fa-key"></i> Sécurité</a>
                <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
            </div>
        </div>
        <?php if($user_role == 'admin'): ?>
        <a href="admin_users.php" class="nav-item"><i class="fas fa-users"></i> Utilisateurs</a>
        <?php endif; ?>
    </div>
    <div class="sidebar-footer">
        <div class="user-card">
            <div class="user-avatar"><i class="fas fa-user-alt"></i></div>
            <div>
                <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                <div class="user-role"><?php echo $user_role == 'admin' ? 'Administrateur' : 'Éleveur'; ?></div>
            </div>
        </div>
    </div>
</div>

<button class="burger-btn" id="burgerBtn"><i class="fas fa-bars"></i></button>

<div class="main-content">
    <div class="form-container">
        <h2>Ajouter une transaction</h2>
        
        <?php if ($error): ?>
            <div class="error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <form method="POST">
            <div class="form-group">
                <label>Type</label>
                <select name="type" required>
                    <option value="revenu">Revenu</option>
                    <option value="depense">Dépense</option>
                </select>
            </div>
            <div class="form-group">
                <label>Montant (FCFA)</label>
                <input type="number" name="amount" step="1" required placeholder="0">
            </div>
            <div class="form-group">
                <label>Catégorie</label>
                <select name="category">
                    <option value="alimentation">Alimentation</option>
                    <option value="vaccins">Vaccins</option>
                    <option value="equipement">Équipement</option>
                    <option value="vente">Vente de poulets</option>
                    <option value="autre">Autre</option>
                </select>
            </div>
            <div class="form-group">
                <label>Description</label>
                <textarea name="description" rows="3" placeholder="Description de la transaction"></textarea>
            </div>
            <button type="submit" class="btn">Enregistrer</button>
            <a href="finances.php" class="btn-back">Retour</a>
        </form>
    </div>
</div>

<script>
    function toggleSettings() {
        document.getElementById('settingsSub').classList.toggle('show');
    }
    const burger = document.getElementById('burgerBtn');
    const sidebar = document.querySelector('.sidebar');
    if(burger && sidebar) {
        burger.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !burger.contains(e.target)) {
                sidebar.classList.remove('open');
            }
        });
    }
</script>
</body>
</html>