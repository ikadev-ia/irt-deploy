<?php
require_once 'config/database_sqlite.php';
require_once 'weather_api.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$weather = getMaliWeather();

// Récupérer tous les lots actifs
if ($user_role == 'admin') {
    $stmt = $conn->prepare("SELECT id, name, current_birds, initial_birds, start_date, status FROM batches WHERE status = 'active' ORDER BY start_date DESC");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT id, name, current_birds, initial_birds, start_date, status FROM batches WHERE user_id = ? AND status = 'active' ORDER BY start_date DESC");
    $stmt->execute([$user_id]);
}
$all_batches = $stmt->fetchAll();

$selected_batch_id = isset($_GET['batch_id']) ? (int)$_GET['batch_id'] : (isset($_SESSION['selected_batch_id']) ? $_SESSION['selected_batch_id'] : ($all_batches[0]['id'] ?? null));
if ($selected_batch_id) {
    $_SESSION['selected_batch_id'] = $selected_batch_id;
    $stmt = $conn->prepare("SELECT * FROM batches WHERE id = ?");
    $stmt->execute([$selected_batch_id]);
    $batch = $stmt->fetch();
} else {
    $batch = null;
}

$total_birds = 0;
$total_mortality = 0;
$batch_age = 0;
$chart_labels = [];
$chart_mortality = [];
$last_mortality_value = 0;
$alert_mortality = false;

if ($batch) {
    $total_birds = $batch['initial_birds'];
    $total_mortality = $batch['initial_birds'] - $batch['current_birds'];
    $start_date = new DateTime($batch['start_date']);
    $today = new DateTime();
    $batch_age = $start_date->diff($today)->days;
    
    // Dernière mortalité
    $stmt = $conn->prepare("SELECT mortality FROM daily_tracking WHERE batch_id = ? ORDER BY tracking_date DESC LIMIT 1");
    $stmt->execute([$batch['id']]);
    $last_m = $stmt->fetch();
    $last_mortality_value = $last_m ? (int)$last_m['mortality'] : 0;
    
    // Alerte si mortalité > 5
    $alert_mortality = ($last_mortality_value > 5);
    
    // Données graphique
    $stmt = $conn->prepare("SELECT tracking_date, mortality FROM daily_tracking WHERE batch_id = ? ORDER BY tracking_date DESC LIMIT 7");
    $stmt->execute([$batch['id']]);
    $data = $stmt->fetchAll();
    $chart_labels = array_reverse(array_map(function($d) { return date('d/m', strtotime($d['tracking_date'])); }, $data));
    $chart_mortality = array_reverse(array_column($data, 'mortality'));
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Poulplume · Dashboard Pro</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        :root {
            --green: #14B53A;
            --green-dark: #0d8a2f;
            --green-light: #a8e6b5;
            --yellow: #FCD116;
            --white: #ffffff;
            --white-glass: rgba(255, 255, 255, 0.94);
            --white-glass-card: rgba(255, 255, 255, 0.9);
            --text-dark: #0a0f1c;
            --text-gray: #475569;
            --border-light: rgba(203, 213, 225, 0.4);
            --shadow-sm: 0 8px 20px rgba(0,0,0,0.04);
            --shadow-md: 0 20px 35px -12px rgba(0,0,0,0.12);
            --shadow-hover: 0 25px 40px -15px rgba(20,181,58,0.25);
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
            background: url('Images/AR100.png') no-repeat center center fixed;
            background-size: cover;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
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
        body.dark::before {
            background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.4) 100%);
        }

        /* ========== SIDEBAR VERT PREMIUM ========== */
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
        .logo-img:hover {
            transform: scale(1.05);
        }
        .logo-img img {
            width: 68px;
            height: auto;
            border-radius: 50%;
        }
        .brand {
            font-size: 2rem;
            font-weight: 800;
            letter-spacing: -0.5px;
        }
        .brand .poul {
            color: var(--yellow);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .brand .plume {
            color: var(--white);
            text-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }

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
        .nav-item i {
            width: 24px;
            font-size: 1.15rem;
        }
        .nav-item:hover {
            background: var(--yellow);
            color: #1e293b;
            transform: translateX(6px);
        }
        .nav-item:hover i {
            color: #1e293b;
        }
        .nav-item.active {
            background: var(--white);
            color: var(--green);
            box-shadow: 0 6px 14px rgba(0,0,0,0.15);
        }
        .nav-item.active i {
            color: var(--green);
        }

        .settings-group {
            margin-top: 12px;
        }
        .settings-header {
            cursor: pointer;
            justify-content: space-between;
        }
        .settings-header i:last-child {
            width: auto;
            font-size: 0.7rem;
            transition: transform 0.2s;
        }
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
            transition: 0.2s;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .settings-sub a i {
            width: 20px;
            font-size: 0.85rem;
        }
        .settings-sub a:hover {
            background: var(--yellow);
            color: #1e293b;
            transform: translateX(5px);
        }
        .settings-sub.show {
            display: flex;
        }

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
            transition: 0.2s;
        }
        .user-card:hover {
            background: rgba(255,255,255,0.2);
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
        .user-name {
            font-weight: 700;
            font-size: 0.95rem;
            color: var(--white);
        }
        .user-role {
            font-size: 0.7rem;
            color: var(--yellow);
            font-weight: 600;
        }

        /* Burger menu */
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
            padding: 30px;
            flex: 1;
        }

        /* Cartes */
        .card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 24px;
            border: 1px solid var(--border-light);
            transition: all 0.3s ease;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }

        /* Sélecteur lot */
        .lot-card {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 16px;
            margin-bottom: 28px;
        }
        .lot-select {
            background: var(--white-glass-card);
            backdrop-filter: blur(8px);
            border: 1px solid var(--border-light);
            padding: 10px 22px;
            border-radius: 50px;
            color: var(--text-dark);
            font-weight: 500;
            cursor: pointer;
            font-family: inherit;
        }
        .lot-select:focus {
            outline: none;
            border-color: var(--green);
        }
        .badge {
            padding: 5px 16px;
            border-radius: 40px;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 8px;
        }
        .badge-age {
            background: var(--yellow);
            color: #1e293b;
        }
        .badge-birds {
            background: var(--green);
            color: white;
        }
        .action-btns a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: rgba(0,0,0,0.05);
            padding: 6px 16px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 0.7rem;
            color: var(--text-gray);
            margin-left: 8px;
            transition: 0.2s;
        }
        .action-btns a:hover {
            background: var(--yellow);
            color: #1e293b;
        }
        .action-btns .danger {
            color: #ef4444;
            background: rgba(239,68,68,0.1);
        }
        .action-btns .danger:hover {
            background: rgba(239,68,68,0.2);
        }

        /* KPI - TOUS VERTS */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 24px;
            margin-bottom: 32px;
        }

        .stat-card {
            background: linear-gradient(135deg, var(--green) 0%, var(--green-dark) 100%);
            border-radius: 32px;
            padding: 24px;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            color: white;
            box-shadow: 0 10px 25px rgba(20,181,58,0.25);
        }
        .stat-card::before {
            content: '';
            position: absolute;
            top: -30%;
            right: -30%;
            width: 150%;
            height: 150%;
            background: radial-gradient(circle, rgba(255,255,255,0.15) 0%, transparent 70%);
            pointer-events: none;
        }
        .stat-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 35px rgba(20,181,58,0.35);
        }

        /* Carte alerte spéciale */
        .stat-card.alert-critical {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            animation: pulse-border 1.5s infinite;
        }
        @keyframes pulse-border {
            0% { box-shadow: 0 0 0 0 rgba(239,68,68,0.5); }
            70% { box-shadow: 0 0 0 12px rgba(239,68,68,0); }
            100% { box-shadow: 0 0 0 0 rgba(239,68,68,0); }
        }

        .stat-title {
            font-size: 0.7rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            font-weight: 700;
            margin-bottom: 12px;
            display: flex;
            align-items: center;
            justify-content: space-between;
            color: rgba(255,255,255,0.9);
        }
        .stat-icon {
            width: 38px;
            height: 38px;
            border-radius: 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.1rem;
            background: rgba(255,255,255,0.2);
        }
        .stat-value {
            font-size: 2.4rem;
            font-weight: 800;
            line-height: 1.1;
            margin-bottom: 8px;
        }
        .stat-trend {
            font-size: 0.7rem;
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            color: rgba(255,255,255,0.85);
        }
        .alert-badge {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: rgba(255,255,255,0.25);
            padding: 4px 12px;
            border-radius: 40px;
            font-size: 0.65rem;
            font-weight: 600;
            color: white;
        }

        /* Grille fonctionnalités */
        .features-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 24px;
            margin-bottom: 32px;
        }
        .feature-card {
            cursor: pointer;
            text-align: center;
            transition: all 0.3s;
        }
        .feature-card:hover {
            transform: translateY(-6px);
        }
.feature-icon {
    width: 70px;
    height: 70px;
    background: linear-gradient(135deg, #FCD116, #e6b800); /* Jaune */
    border-radius: 35px;
    display: flex;
    align-items: center;
    justify-content: center;
    margin: 0 auto 20px;
    transition: all 0.3s;
}
.feature-card:hover .feature-icon {
    transform: scale(1.05);
    background: #14B53A; /* devient vert au survol */
}
.feature-icon i {
    font-size: 1.8rem;
    color: #1e293b; /* texte foncé pour contraster avec jaune */
}
.feature-card:hover .feature-icon i {
    color: white;
}
        .feature-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        .feature-card p {
            color: var(--text-gray);
            font-size: 0.85rem;
        }

        /* Graphique */
        .chart-container {
            height: 260px;
            margin-top: 16px;
        }

        /* Theme toggle */
        .theme-toggle {
            position: fixed;
            top: 20px;
            right: 20px;
            z-index: 1100;
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border: 1px solid var(--border-light);
            border-radius: 50px;
            padding: 8px 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 500;
            transition: 0.2s;
        }
        .theme-toggle:hover {
            transform: scale(1.02);
        }
        .theme-toggle i:first-child {
            color: var(--yellow);
        }
        .theme-toggle i:last-child {
            color: var(--green);
        }

        /* Footer */
        .footer {
            background: var(--white-glass);
            backdrop-filter: blur(12px);
            border-top: 1px solid var(--border-light);
            padding: 20px 30px;
            text-align: center;
            font-size: 0.75rem;
            color: var(--text-gray);
        }

        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(25px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        .stats-grid .stat-card,
        .features-grid .feature-card,
        .lot-card {
            animation: fadeInUp 0.45s ease-out forwards;
        }
        .stats-grid .stat-card:nth-child(1) { animation-delay: 0s; }
        .stats-grid .stat-card:nth-child(2) { animation-delay: 0.05s; }
        .stats-grid .stat-card:nth-child(3) { animation-delay: 0.1s; }
        .stats-grid .stat-card:nth-child(4) { animation-delay: 0.15s; }

        @media (max-width: 1024px) {
            .stats-grid {
                grid-template-columns: repeat(2, 1fr);
            }
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open {
                transform: translateX(0);
            }
            .burger-btn {
                display: block;
            }
            .main-content {
                margin-left: 0;
            }
        }
        @media (max-width: 640px) {
            .stats-grid {
                grid-template-columns: 1fr;
            }
            .lot-card {
                flex-direction: column;
                align-items: stretch;
            }
            .action-btns {
                display: flex;
                flex-wrap: wrap;
                gap: 8px;
                margin-top: 12px;
            }
            .action-btns a {
                margin-left: 0;
            }
            .main-content {
                padding: 20px 16px;
            }
            .theme-toggle {
                top: 12px;
                right: 12px;
                padding: 6px 14px;
                font-size: 0.7rem;
            }
            .burger-btn {
                top: 12px;
                left: 12px;
                padding: 8px 12px;
            }
        }
    </style>
</head>
<body>

    <div class="sidebar">
        <div class="sidebar-header">
            <div class="logo-img">
                <img src="Images/Logo.png" alt="Poulplume">
            </div>
            <div class="brand">
                <span class="poul">Poul</span><span class="plume">plume</span>
            </div>
        </div>
        <div class="sidebar-nav">
            <a href="dashboard.php" class="nav-item active">
                <i class="fas fa-tachometer-alt"></i> Tableau de bord
            </a>
            <a href="add_lot.php" class="nav-item">
                <i class="fas fa-plus-circle"></i> Nouveau lot
            </a>
            <a href="finances.php" class="nav-item">
                <i class="fas fa-coins"></i> Finances
            </a>
            <a href="chatbot.php" class="nav-item">
                <i class="fas fa-robot"></i> Chatbot 
            </a>
            <div class="settings-group">
                <div class="nav-item settings-header" onclick="toggleSettings()">
                    <i class="fas fa-cog"></i> Paramètres
                    <i class="fas fa-chevron-down"></i>
                </div>
                <div class="settings-sub" id="settingsSub">
                    <a href="notifications.php"><i class="fas fa-bell"></i> Notifications</a>
                    <a href="aide.php"><i class="fas fa-question-circle"></i> Aide</a>
                    <a href="change_password.php"><i class="fas fa-key"></i> Sécurité</a>
                    <a href="logout.php"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
                </div>
            </div>
            <?php if($user_role == 'admin'): ?>
            <a href="admin_users.php" class="nav-item">
                <i class="fas fa-users"></i> Utilisateurs
            </a>
            <?php endif; ?>
        </div>
        <div class="sidebar-footer">
            <div class="user-card">
                <div class="user-avatar">
                    <i class="fas fa-user-alt"></i>
                </div>
                <div>
                    <div class="user-name"><?php echo htmlspecialchars($user_name); ?></div>
                    <div class="user-role"><?php echo $user_role == 'admin' ? 'Administrateur' : 'Éleveur'; ?></div>
                </div>
            </div>
        </div>
    </div>

    <button class="burger-btn" id="burgerBtn">
        <i class="fas fa-bars"></i>
    </button>

    <div class="theme-toggle" onclick="toggleTheme()">
        <i class="fas fa-sun"></i> <i class="fas fa-moon"></i>
        <span id="themeLabel">Clair</span>
    </div>

    <div class="main-content">
        <?php if (!empty($all_batches) && $batch): ?>
            <!-- Sélecteur lot -->
            <div class="lot-card card">
                <div>
                    <i class="fas fa-chicken" style="color: var(--green); margin-right: 8px;"></i>
                    <strong>Lot actif :</strong>
                    <select id="batchSelect" class="lot-select">
                        <?php foreach ($all_batches as $b): ?>
                            <option value="<?php echo $b['id']; ?>" <?php echo ($selected_batch_id == $b['id']) ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($b['name']); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="action-btns">
                    <span class="badge badge-age">
                        <i class="fas fa-calendar"></i> <?php echo $batch_age; ?> jours
                    </span>
                    <span class="badge badge-birds">
                        <i class="fas fa-chicken"></i> <?php echo number_format($batch['current_birds']); ?>
                    </span>
                    <a href="edit_lot.php?id=<?php echo $batch['id']; ?>">
                        <i class="fas fa-edit"></i> Modifier
                    </a>
                    <a href="delete_lot.php?id=<?php echo $batch['id']; ?>" class="danger" onclick="return confirm('⚠️ Supprimer définitivement ce lot ?')">
                        <i class="fas fa-trash"></i> Supprimer
                    </a>
                </div>
            </div>

            <script>
                document.getElementById('batchSelect').addEventListener('change', function() {
                    window.location.href = 'dashboard.php?batch_id=' + this.value;
                });
            </script>

            <!-- KPI - TOUS VERTS -->
            <div class="stats-grid">
                <!-- Carte 1 -->
                <div class="stat-card">
                    <div class="stat-title">
                        <span><i class="fas fa-chicken"></i> POULETS RESTANTS</span>
                        <span class="stat-icon"><i class="fas fa-warehouse"></i></span>
                    </div>
                    <div class="stat-value"><?php echo number_format($batch['current_birds']); ?></div>
                    <div class="stat-trend">
                         <b>-<?php echo $total_mortality; ?> morts</b>
                    </div>
                </div>

                <!-- Carte 2 -->
                <div class="stat-card">
                    <div class="stat-title">
                        <span><i class=""></i> TAUX DE SURVIE</span>
                        <span class="stat-icon"><i class="fas fa-chart-line"></i></span>
                    </div>
                    <div class="stat-value"><?php echo round(($batch['current_birds'] / max(1, $total_birds)) * 100, 1); ?><span style="font-size: 1rem;">%</span></div>
                    <div class="stat-trend">
                         <b>Objectif >95%</b>
                    </div>
                </div>

                <!-- Carte 3 -->
                <div class="stat-card">
                    <div class="stat-title">
                        <span><i class=""></i> ÂGE DU LOT</span>
                        <span class="stat-icon"><i class="fas fa-hourglass-half"></i></span>
                    </div>
                    <div class="stat-value"><?php echo $batch_age; ?><span style="font-size: 1rem;"> jours</span></div>
                    <div class="stat-trend">
                         <b>Début : <?php echo date('d/m/Y', strtotime($batch['start_date'])); ?></b>
                    </div>
                </div>

                <!-- Carte 4 avec alerte intégrée -->
                <div class="stat-card <?php echo $alert_mortality ? 'alert-critical' : ''; ?>">
                    <div class="stat-title">
                        <span><i class=""></i> DERNIÈRE MORTALITÉ</span>
                        <span class="stat-icon"><i class="fas fa-heartbeat"></i></span>
                    </div>
                    <div class="stat-value"><?php echo $last_mortality_value; ?><span style="font-size: 1rem;"> morts</span></div>
                    <div class="stat-trend">
                        <b>Dernier relevé</b>
                        <?php if ($alert_mortality): ?>
                            <span class="alert-badge">
                                <i class=""></i> Alerte → Notifications
                            </span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- Grille fonctionnalités -->
            <div class="features-grid">
                <div class="card feature-card" onclick="window.location.href='enregistrement.php'">
                    <div class="feature-icon"><i class="fas fa-edit"></i></div>
                    <h3>Enregistrement quotidien</h3>
                    <p>Température, alimentation, observations terrain</p>
                </div>
                <div class="card feature-card" onclick="window.location.href='diagnostic.php'">
                    <div class="feature-icon"><i class="fas fa-stethoscope"></i></div>
                    <h3>Diagnostic </h3>
                    <p>Symptômes → diagnostic précis et traitements</p>
                </div>
                <div class="card feature-card" onclick="window.location.href='vaccins.php'">
                    <div class="feature-icon"><i class="fas fa-syringe"></i></div>
                    <h3>Calendrier vaccinal</h3>
                    <p>Planification, rappels et suivi</p>
                </div>
                <div class="card feature-card" onclick="window.location.href='taches.php'">
                    <div class="feature-icon"><i class="fas fa-clock"></i></div>
                    <h3>Tâches recommandées</h3>
                    <p>Alimentation, eau, nettoyage, pesée</p>
                </div>
                <div class="card feature-card" onclick="window.location.href='conseils.php'">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h3>Conseils préventifs</h3>
                    <p>Biosécurité, bien-être animal</p>
                </div>
                <div class="card feature-card" onclick="window.location.href='historique.php'">
                    <div class="feature-icon"><i class="fas fa-history"></i></div>
                    <h3>Historique complet</h3>
                    <p>Tous les enregistrements passés</p>
                </div>
            </div>

            <!-- Graphique -->
            <?php if (count($chart_labels) > 0): ?>
            <div class="card">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                    <span style="font-weight: 700;">
                        <i class="fas fa-chart-line" style="color: var(--green);"></i> Évolution mortalité (7 derniers jours)
                    </span>
                    <span style="font-size: 0.7rem; color: var(--text-gray);"> </span>
                </div>
                <div class="chart-container">
                    <canvas id="mortalityChart"></canvas>
                </div>
            </div>
            <?php endif; ?>

        <?php else: ?>
            <div class="card" style="text-align: center; padding: 60px; max-width: 550px; margin: 80px auto;">
                <i class="" style="font-size: 3.8rem; color: var(--yellow);"></i>
                <h2 style="margin: 20px 0;">Bienvenue sur Poulplume</h2>
                <p style="color: var(--text-gray);">Créez votre premier lot pour démarrer le suivi professionnel.</p>
                <a href="add_lot.php" style="display: inline-block; margin-top: 28px; background: var(--green); color: white; padding: 14px 32px; border-radius: 50px; text-decoration: none; font-weight: 600;">
                    <i class="fas fa-plus-circle"></i> Créer mon lot
                </a>
            </div>
        <?php endif; ?>
    </div>

    <div class="footer">
        <p> @ 2025 copyright tous droits réservé | Poulplume L'innovation au service de l'élevage</p>
    </div>

    <script>
        function toggleTheme() {
            document.body.classList.toggle('dark');
            const label = document.getElementById('themeLabel');
            if(label) label.textContent = document.body.classList.contains('dark') ? 'Sombre' : 'Clair';
            localStorage.setItem('theme', document.body.classList.contains('dark') ? 'dark' : 'light');
        }
        
        if (localStorage.getItem('theme') === 'dark') {
            document.body.classList.add('dark');
            const label = document.getElementById('themeLabel');
            if(label) label.textContent = 'Sombre';
        }

        function toggleSettings() {
            const sub = document.getElementById('settingsSub');
            if(sub) sub.classList.toggle('show');
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

        <?php if (isset($chart_labels) && count($chart_labels) > 0): ?>
        const ctx = document.getElementById('mortalityChart').getContext('2d');
        new Chart(ctx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode($chart_labels); ?>,
                datasets: [{
                    label: 'Mortalité journalière',
                    data: <?php echo json_encode($chart_mortality); ?>,
                    borderColor: '#14B53A',
                    backgroundColor: 'rgba(20,181,58,0.05)',
                    borderWidth: 3,
                    pointBackgroundColor: '#FCD116',
                    pointBorderColor: '#ffffff',
                    pointRadius: 5,
                    pointHoverRadius: 8,
                    pointBorderWidth: 2,
                    tension: 0.3,
                    fill: true
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: true,
                plugins: {
                    legend: { display: false },
                    tooltip: { backgroundColor: 'rgba(0,0,0,0.75)', titleColor: '#fff', bodyColor: '#ddd' }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.05)' },
                        title: { display: true, text: 'Nombre de morts', color: getComputedStyle(document.body).getPropertyValue('--text-gray') }
                    },
                    x: { grid: { display: false } }
                }
            }
        });
        <?php endif; ?>
    </script>
</body>
</html>