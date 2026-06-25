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
$success = '';

$durees = [
    'chair' => 45,
    'pondeuse' => 126,
    'goliath' => 42,
    'bleu_hollande' => 70,
    'cou_nu' => 75,
    'faverolles' => 120,
    'marans' => 110,
    'bresse' => 120
];

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $name = trim($_POST['name']);
    $start_date = $_POST['start_date'];
    $initial_birds = (int)$_POST['initial_birds'];
    $type_poulet = $_POST['type_poulet'];
    $farmer_id = ($user_role == 'admin' && isset($_POST['farmer_id'])) ? (int)$_POST['farmer_id'] : $user_id;

    if (!isset($durees[$type_poulet])) {
        $error = "Type de poulet invalide.";
    } elseif (empty($name) || empty($start_date) || $initial_birds < 1) {
        $error = "Veuillez remplir tous les champs.";
    } else {
        $date_sortie = date('Y-m-d', strtotime($start_date . ' + ' . $durees[$type_poulet] . ' days'));
        try {
            $stmt = $conn->prepare("INSERT INTO batches (user_id, name, start_date, initial_birds, current_birds, type_poulet, date_sortie, status) 
                                    VALUES (?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([$farmer_id, $name, $start_date, $initial_birds, $initial_birds, $type_poulet, $date_sortie]);
            $batch_id = $conn->lastInsertId();

            // Vaccins par défaut
            $stmt = $conn->query("SELECT id FROM vaccines");
            $vaccines = $stmt->fetchAll();
            $insert = $conn->prepare("INSERT INTO batch_vaccines (batch_id, vaccine_id) VALUES (?, ?)");
            foreach ($vaccines as $v) {
                $insert->execute([$batch_id, $v['id']]);
            }

            $success = "Lot créé avec succès ! Date de sortie : " . date('d/m/Y', strtotime($date_sortie));
            echo "<script>setTimeout(function(){ window.location.href='dashboard.php'; }, 1500);</script>";
        } catch (PDOException $e) {
            $error = "Erreur SQL : " . $e->getMessage();
        }
    }
}

$farmers = [];
if ($user_role == 'admin') {
    $stmt = $conn->query("SELECT id, name FROM users WHERE role = 'farmer'");
    $farmers = $stmt->fetchAll();
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Nouveau lot - Poulplume</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --green: #14B53A;
            --green-dark: #0d8a2f;
            --yellow: #FCD116;
            --white: #ffffff;
            --white-glass: rgba(255, 255, 255, 0.94);
            --white-glass-card: rgba(255, 255, 255, 0.92);
            --text-dark: #0a0f1c;
            --text-gray: #475569;
            --border-light: rgba(203, 213, 225, 0.4);
            --shadow-md: 0 20px 35px -12px rgba(0,0,0,0.15);
        }
        body.dark {
            --white-glass: rgba(15, 23, 42, 0.94);
            --white-glass-card: rgba(30, 41, 59, 0.92);
            --text-dark: #f1f5f9;
            --text-gray: #94a3b8;
            --border-light: rgba(51, 65, 85, 0.5);
        }

        body {
            font-family: 'Inter', sans-serif;
            background: url('Images/AR10.png') no-repeat center center fixed;
            background-size: cover;
            color: var(--text-dark);
            min-height: 100vh;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.2) 0%, rgba(0,0,0,0.08) 100%);
            z-index: -1;
        }

        /* Sidebar */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            width: 280px;
            height: 100vh;
            background: linear-gradient(145deg, var(--green) 0%, var(--green-dark) 100%);
            backdrop-filter: blur(2px);
            display: flex;
            flex-direction: column;
            z-index: 1000;
            transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
            box-shadow: 4px 0 25px rgba(0,0,0,0.15);
        }
        .sidebar-header {
            padding: 36px 24px 28px;
            text-align: center;
            border-bottom: 2px solid var(--yellow);
        }
        .logo-img {
            width: 100px;
            height: 100px;
            margin: 0 auto 15px;
            background: var(--white);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            transition: transform 0.3s;
        }
        .logo-img img {
            width: 68px;
            height: auto;
            border-radius: 50%;
        }
        .brand {
            font-size: 2rem;
            font-weight: 800;
        }
        .brand .poul { color: var(--yellow); }
        .brand .plume { color: var(--white); }
        .sidebar-nav {
            flex: 1;
            padding: 30px 16px;
            display: flex;
            flex-direction: column;
            gap: 8px;
        }
        .nav-item {
            display: flex;
            align-items: center;
            gap: 14px;
            padding: 12px 18px;
            border-radius: 50px;
            color: rgba(255,255,255,0.85);
            text-decoration: none;
            transition: all 0.25s;
            font-weight: 500;
        }
        .nav-item i { width: 24px; font-size: 1.15rem; }
        .nav-item:hover {
            background: var(--yellow);
            color: #1e293b;
            transform: translateX(6px);
        }
        .nav-item.active {
            background: var(--white);
            color: var(--green);
        }
        .settings-group { margin-top: 12px; }
        .settings-header { cursor: pointer; justify-content: space-between; }
        .settings-sub {
            margin-left: 48px;
            display: none;
            flex-direction: column;
            gap: 4px;
            margin-top: 8px;
        }
        .settings-sub a {
            padding: 10px 14px;
            color: rgba(255,255,255,0.8);
            text-decoration: none;
            font-size: 0.85rem;
            border-radius: 40px;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .settings-sub a:hover { background: var(--yellow); color: #1e293b; }
        .settings-sub.show { display: flex; }
        .sidebar-footer {
            padding: 20px 20px 30px;
            border-top: 1px solid rgba(255,255,255,0.2);
        }
        .user-card {
            display: flex;
            align-items: center;
            gap: 14px;
            background: rgba(255,255,255,0.12);
            padding: 10px 14px;
            border-radius: 60px;
            margin-bottom: 15px;
        }
        .user-avatar {
            width: 46px;
            height: 46px;
            background: var(--yellow);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--green-dark);
            font-size: 1.2rem;
        }
        .user-name { font-weight: 700; font-size: 0.95rem; color: var(--white); }
        .user-role { font-size: 0.7rem; color: var(--yellow); font-weight: 600; }
        .logout-btn {
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            background: rgba(255,255,255,0.1);
            color: #ffcccc;
            padding: 10px;
            border-radius: 50px;
            text-decoration: none;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.2); color: white; }
        .burger-btn {
            display: none;
            position: fixed;
            top: 18px;
            left: 18px;
            z-index: 1100;
            background: var(--green);
            border: none;
            color: white;
            font-size: 1.2rem;
            padding: 10px 14px;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Main content */
        .main-content {
            margin-left: 280px;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Conteneur formulaire */
        .form-container {
            width: 650px;
            max-width: 95%;
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            box-shadow: 0 25px 45px -12px rgba(0,0,0,0.25);
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        .form-header {
            background: var(--yellow);
            color: #1e293b;
            padding: 20px 25px;
            text-align: center;
            position: relative;
        }
        .form-header i {
            font-size: 2.2rem;
            color: var(--green);
            margin-bottom: 8px;
        }
        .form-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .form-header p {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        .home-btn {
            position: absolute;
            top: 18px;
            right: 20px;
            background: rgba(30,41,59,0.12);
            color: #1e293b;
            padding: 5px 12px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: 0.2s;
        }
        .home-btn i {
            font-size: 0.7rem;
            margin: 0;
        }
        .home-btn:hover {
            background: rgba(30,41,59,0.25);
        }

        .form-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 22px;
        }
        .form-group label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        .form-group label i {
            color: var(--green);
            width: 20px;
        }
        .form-group input, .form-group select {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-light);
            border-radius: 30px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            background: var(--white-glass);
            color: var(--text-dark);
            transition: 0.15s;
            -webkit-appearance: none;
            appearance: none;
        }
        .form-group input:focus, .form-group select:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 2px rgba(20,181,58,0.2);
        }
        .form-group select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23475569' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }

        .btn-submit {
            width: 100%;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white;
            border: none;
            padding: 14px;
            border-radius: 40px;
            font-size: 1rem;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            margin-top: 10px;
        }
        .btn-submit:hover {
            background: var(--green-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(20,181,58,0.3);
        }

        .alert-success {
            background: rgba(20,181,58,0.15);
            border-left: 4px solid var(--green);
            padding: 12px 18px;
            border-radius: 24px;
            margin-bottom: 20px;
            color: var(--green-dark);
            font-size: 0.85rem;
        }
        .alert-error {
            background: rgba(239,68,68,0.15);
            border-left: 4px solid #ef4444;
            padding: 12px 18px;
            border-radius: 24px;
            margin-bottom: 20px;
            color: #ef4444;
            font-size: 0.85rem;
        }
        .info-box {
            background: rgba(20,181,58,0.08);
            border-radius: 24px;
            padding: 12px 15px;
            margin-top: 20px;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* ============================================
                   RESPONSIVITÉ - COMME ADMIN_USERS
                   ============================================ */

        /* Tablettes et petits écrans */
        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
                width: 280px;
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .burger-btn {
                display: block;
            }
            .main-content {
                margin-left: 0;
                padding: 20px 10px;
            }
            .form-container {
                max-width: 98%;
                border-radius: 32px;
            }
        }

        /* Téléphones moyens */
        @media (max-width: 768px) {
            .form-header {
                padding: 16px 18px;
            }
            .form-header h1 {
                font-size: 1.2rem;
            }
            .form-header p {
                font-size: 0.7rem;
            }
            .form-header i {
                font-size: 1.8rem;
            }
            .home-btn {
                position: static;
                display: inline-block;
                margin-top: 8px;
                font-size: 0.65rem;
                padding: 4px 10px;
            }
            .form-body {
                padding: 18px 16px;
            }
            .form-group {
                margin-bottom: 16px;
            }
            .form-group label {
                font-size: 0.75rem;
            }
            .form-group label i {
                width: 16px;
                font-size: 0.8rem;
            }
            .form-group input, .form-group select {
                padding: 10px 14px;
                font-size: 0.8rem;
                border-radius: 24px;
            }
            .btn-submit {
                padding: 12px;
                font-size: 0.85rem;
                border-radius: 32px;
            }
            .info-box {
                font-size: 0.7rem;
                padding: 10px 12px;
                border-radius: 20px;
            }
            .alert-success, .alert-error {
                font-size: 0.75rem;
                padding: 10px 14px;
                border-radius: 20px;
            }
        }

        /* Très petits téléphones (< 450px) */
        @media (max-width: 450px) {
            body {
                padding: 0;
            }
            .main-content {
                padding: 10px 5px;
            }
            .form-container {
                max-width: 100%;
                border-radius: 24px;
            }
            .form-header {
                padding: 14px 12px;
            }
            .form-header h1 {
                font-size: 1rem;
            }
            .form-header p {
                font-size: 0.6rem;
            }
            .form-header i {
                font-size: 1.5rem;
                margin-bottom: 4px;
            }
            .home-btn {
                font-size: 0.55rem;
                padding: 3px 8px;
                gap: 3px;
            }
            .home-btn i {
                font-size: 0.55rem;
            }
            .form-body {
                padding: 14px 12px;
            }
            .form-group {
                margin-bottom: 12px;
            }
            .form-group label {
                font-size: 0.65rem;
                gap: 5px;
            }
            .form-group label i {
                width: 14px;
                font-size: 0.7rem;
            }
            .form-group input, .form-group select {
                padding: 8px 12px;
                font-size: 0.7rem;
                border-radius: 20px;
            }
            .btn-submit {
                padding: 10px;
                font-size: 0.75rem;
                border-radius: 28px;
                gap: 5px;
            }
            .info-box {
                font-size: 0.6rem;
                padding: 8px 10px;
                border-radius: 16px;
                gap: 5px;
                flex-wrap: wrap;
            }
            .info-box i {
                font-size: 0.7rem;
            }
            .alert-success, .alert-error {
                font-size: 0.65rem;
                padding: 8px 10px;
                border-radius: 16px;
            }
            .burger-btn {
                top: 10px;
                left: 10px;
                font-size: 0.9rem;
                padding: 8px 12px;
            }
        }

        /* Orientation paysage sur téléphone */
        @media (max-height: 500px) and (orientation: landscape) {
            .main-content {
                padding: 10px 8px;
                align-items: flex-start;
                padding-top: 15px;
            }
            .form-container {
                max-width: 100%;
                border-radius: 20px;
            }
            .form-header {
                padding: 10px 14px;
            }
            .form-header h1 {
                font-size: 0.9rem;
            }
            .form-header p {
                display: none;
            }
            .form-header i {
                font-size: 1.2rem;
                margin-bottom: 2px;
            }
            .home-btn {
                font-size: 0.5rem;
                padding: 2px 8px;
            }
            .form-body {
                padding: 10px 14px;
            }
            .form-group {
                margin-bottom: 8px;
            }
            .form-group label {
                font-size: 0.6rem;
                margin-bottom: 3px;
            }
            .form-group input, .form-group select {
                padding: 6px 10px;
                font-size: 0.65rem;
                border-radius: 16px;
            }
            .btn-submit {
                padding: 8px;
                font-size: 0.7rem;
                border-radius: 24px;
                margin-top: 5px;
            }
            .info-box {
                font-size: 0.55rem;
                padding: 6px 10px;
                margin-top: 10px;
                border-radius: 14px;
            }
            .alert-success, .alert-error {
                font-size: 0.6rem;
                padding: 6px 10px;
                margin-bottom: 10px;
                border-radius: 14px;
            }
            .sidebar {
                width: 240px;
            }
            .burger-btn {
                top: 8px;
                left: 8px;
                font-size: 0.8rem;
                padding: 6px 10px;
            }
        }

        /* Dark mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: url('Images/AR10.png') no-repeat center center fixed;
                background-size: cover;
            }
            .form-container {
                background: var(--white-glass-card);
            }
            .form-group input, .form-group select {
                background: rgba(30, 41, 59, 0.6);
                color: #f1f5f9;
                border-color: rgba(51, 65, 85, 0.5);
            }
            .form-group input::placeholder {
                color: #94a3b8;
            }
            .form-group select {
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394a3b8' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            }
            .info-box {
                background: rgba(20,181,58,0.15);
            }
            .alert-success {
                background: rgba(20,181,58,0.2);
            }
            .alert-error {
                background: rgba(239,68,68,0.2);
            }
        }
    </style>
</head>
<body>

<!-- Sidebar -->
<div class="sidebar">
    <div class="sidebar-header">
        <div class="logo-img"><img src="Images/Logo.png" alt="Poulplume"></div>
        <div class="brand"><span class="poul">Poul</span><span class="plume">plume</span></div>
    </div>
    <div class="sidebar-nav">
        <a href="dashboard.php" class="nav-item"><i class="fas fa-tachometer-alt"></i> Tableau de bord</a>
        <a href="add_lot.php" class="nav-item active"><i class="fas fa-plus-circle"></i> Nouveau lot</a>
        <a href="finances.php" class="nav-item"><i class="fas fa-coins"></i> Finances</a>
        <a href="chatbot.php" class="nav-item"><i class="fas fa-robot"></i> Chatbot</a>
        <div class="settings-group">
            <div class="nav-item settings-header" onclick="toggleSettings()">
                <i class="fas fa-cog"></i> Paramètres <i class="fas fa-chevron-down"></i>
            </div>
            <div class="settings-sub" id="settingsSub">
                <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                <a href="aide.php"><i class="fas fa-question-circle"></i> Aide</a>
                <a href="change_password.php"><i class="fas fa-key"></i> Sécurité</a>
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
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<button class="burger-btn" id="burgerBtn"><i class="fas fa-bars"></i></button>

<div class="main-content">
    <div class="form-container">
        <div class="form-header">
            <i class="fas fa-plus-circle"></i>
            <h1>Créer un nouveau lot</h1>
            <p>Sélectionnez le type de poulet et les informations de départ</p>
            <a href="dashboard.php" class="home-btn"><i class="fas fa-home"></i> Accueil</a>
        </div>
        <div class="form-body">
            <?php if($success): ?>
                <div class="alert-success"><?php echo $success; ?></div>
            <?php endif; ?>
            <?php if($error): ?>
                <div class="alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            <form method="POST">
                <?php if($user_role == 'admin' && !empty($farmers)): ?>
                <div class="form-group">
                    <label><i class="fas fa-user"></i> Éleveur</label>
                    <select name="farmer_id" required>
                        <?php foreach($farmers as $f): ?>
                            <option value="<?php echo $f['id']; ?>"><?php echo htmlspecialchars($f['name']); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="form-group">
                    <label><i class=""></i> Nom du lot</label>
                    <input type="text" name="name" placeholder="Ex: Lot A - Mars 2025" required>
                </div>
                <div class="form-group">
                    <label><i class="r"></i> Date de début</label>
                    <input type="date" name="start_date" value="<?php echo date('Y-m-d'); ?>" required>
                </div>
                <div class="form-group">
                    <label><i class=""></i> Type de poulet</label>
                    <select name="type_poulet" required>
                        <option value="chair"> Poulet de chair (45 jours)</option>
                        <option value="pondeuse"> Pondeuse (126 jours)</option>
                        <option value="goliath"> Goliath (42 jours)</option>
                        <option value="bleu_hollande"> Bleu de Hollande (70 jours)</option>
                        <option value="cou_nu"> Cou nu (75 jours)</option>
                        <option value="faverolles"> Faverolles (120 jours)</option>
                        <option value="marans"> Marans (110 jours)</option>
                        <option value="bresse"> Bresse (120 jours)</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class="fas fa-chicken"></i> Nombre initial d'animaux</label>
                    <input type="number" name="initial_birds" min="1" placeholder="Ex: 1000" required>
                </div>
                <button type="submit" class="btn-submit">
                    <i class="fas fa-plus-circle"></i> Créer le lot
                </button>
            </form>
            <div class="info-box">
                <i class="fas fa-info-circle"></i> La date de sortie est automatiquement calculée en fonction du type de poulet.
            </div>
        </div>
    </div>
</div>

<script>
    function toggleSettings() {
        document.getElementById('settingsSub').classList.toggle('show');
    }
    const burger = document.getElementById('burgerBtn');
    const sidebar = document.querySelector('.sidebar');
    if (burger && sidebar) {
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