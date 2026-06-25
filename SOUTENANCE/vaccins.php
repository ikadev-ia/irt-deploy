<?php
require_once 'config/database_sqlite.php';
require_once 'weather_api.php';
require_once 'config/mail.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
$weather = getMaliWeather();

// Récupérer lots actifs
if ($user_role == 'admin') {
    $stmt = $conn->prepare("SELECT id, name FROM batches WHERE status = 'active' ORDER BY start_date DESC");
    $stmt->execute();
} else {
    $stmt = $conn->prepare("SELECT id, name FROM batches WHERE user_id = ? AND status = 'active' ORDER BY start_date DESC");
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

$batch_age = 0;
$vaccines = [];
$due_today = false;
$overdue = false;
$next_vaccine = null;

if ($batch) {
    $start_date = new DateTime($batch['start_date']);
    $today = new DateTime();
    $batch_age = $start_date->diff($today)->days;

    $stmt = $conn->prepare("SELECT v.*, bv.is_done, bv.administered_date 
                            FROM vaccines v 
                            LEFT JOIN batch_vaccines bv ON v.id = bv.vaccine_id AND bv.batch_id = ?
                            ORDER BY v.recommended_day");
    $stmt->execute([$batch['id']]);
    $vaccines = $stmt->fetchAll();

    foreach ($vaccines as $v) {
        if (!$v['is_done']) {
            if ($v['recommended_day'] == $batch_age) $due_today = true;
            if ($v['recommended_day'] < $batch_age) { $overdue = true; $next_vaccine = $v; break; }
        }
    }

    // Envoi d'email si rappel du jour
    if ($due_today) {
        $stmt = $conn->prepare("SELECT email FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user_email = $stmt->fetchColumn();
        if ($user_email) {
            $subject = "Rappel vaccination - Lot " . $batch['name'];
            $body = "<h2>Vaccination due aujourd'hui</h2>
                     <p>Le lot <strong>{$batch['name']}</strong> doit recevoir le(s) vaccin(s) prévu(s) aujourd'hui.</p>
                     <p>Connectez-vous à PoultryTracker pour gérer le calendrier vaccinal.</p>";
            sendEmail($user_email, $subject, $body);
        }
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Calendrier vaccinal - Poulplume</title>
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
        body.dark::before {
            background: linear-gradient(135deg, rgba(0,0,0,0.6) 0%, rgba(0,0,0,0.4) 100%);
        }

        /* ========== SIDEBAR IDENTIQUE ========== */
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
            justify-content: center;
        }

        /* Conteneur principal (largeur confortable) */
        .vaccin-container {
            max-width: 1200px;
            width: 100%;
        }

        /* Carte d'en-tête (sélecteur de lot) */
        .header-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            border: 1px solid var(--border-light);
        }
        .batch-select {
            background: var(--white-glass);
            border: 1px solid var(--border-light);
            padding: 10px 18px;
            border-radius: 40px;
            font-family: inherit;
            font-size: 0.9rem;
            color: var(--text-dark);
            cursor: pointer;
        }

        /* Alertes */
        .alert-card {
            background: rgba(252,209,22,0.15);
            border-left: 4px solid var(--yellow);
            border-radius: 28px;
            padding: 16px 25px;
            margin-bottom: 30px;
            display: flex;
            align-items: center;
            gap: 15px;
            backdrop-filter: blur(4px);
        }
        .alert-card.critical {
            background: rgba(239,68,68,0.15);
            border-left-color: #ef4444;
        }
        .alert-card i { font-size: 1.5rem; color: var(--yellow); }
        .alert-card.critical i { color: #ef4444; }

        /* Grille des vaccins */
        .vaccines-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 28px;
            margin-top: 10px;
        }
        .vaccine-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 24px;
            border: 1px solid var(--border-light);
            transition: all 0.2s ease;
        }
        .vaccine-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        .vaccine-name {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 5px;
            color: var(--text-dark);
        }
        .vaccine-desc {
            font-size: 0.85rem;
            color: var(--text-gray);
            margin-bottom: 15px;
        }
        .vaccine-day {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.75rem;
            font-weight: 600;
            background: rgba(0,0,0,0.05);
            margin-bottom: 12px;
        }
        .vaccine-day.due {
            background: var(--yellow);
            color: #1e293b;
        }
        .vaccine-day.overdue {
            background: #ef4444;
            color: white;
        }
        .vaccine-status {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
            margin-bottom: 15px;
        }
        .status-done {
            background: var(--green);
            color: white;
        }
        .status-pending {
            background: var(--yellow);
            color: #1e293b;
        }
        .status-overdue {
            background: #ef4444;
            color: white;
        }
        .vaccine-date {
            font-size: 0.7rem;
            color: var(--text-gray);
            margin-bottom: 15px;
        }
        .btn-action {
            background: none;
            border: none;
            cursor: pointer;
            padding: 6px 16px;
            border-radius: 40px;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
        }
        .btn-done {
            background: var(--green);
            color: white;
        }
        .btn-undo {
            background: #ef4444;
            color: white;
        }
        .btn-action:hover {
            transform: scale(1.02);
            filter: brightness(0.95);
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 60px 30px;
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            border: 1px solid var(--border-light);
        }
        .empty-state i {
            font-size: 3.5rem;
            color: var(--yellow);
            margin-bottom: 20px;
        }
        .btn-primary {
            background: var(--green);
            color: white;
            padding: 12px 28px;
            border-radius: 40px;
            text-decoration: none;
            display: inline-block;
            margin-top: 20px;
            font-weight: 600;
        }

        /* Bouton Accueil réduit (home-btn) */
        .home-btn {
            display: inline-flex;
            align-items: center;
            gap: 6px;
            background: var(--yellow);
            color: #1e293b;
            padding: 6px 14px;
            border-radius: 40px;
            text-decoration: none;
            font-size: 0.75rem;
            font-weight: 600;
            transition: all 0.2s;
            margin-top: 30px;
        }
        .home-btn i {
            font-size: 0.7rem;
        }
        .home-btn:hover {
            background: #e6b800;
            transform: translateY(-1px);
        }
        .text-center {
            text-align: center;
        }

        @media (max-width: 1024px) {
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
            .header-card {
                flex-direction: column;
                align-items: stretch;
            }
            .vaccines-grid {
                grid-template-columns: 1fr;
            }
            .main-content {
                padding: 20px 16px;
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR VERTE IDENTIQUE -->
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
        <a href="dashboard.php" class="nav-item">
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
        <a href="logout.php" class="logout-btn">
            <i class="fas fa-sign-out-alt"></i> Déconnexion
        </a>
    </div>
</div>

<button class="burger-btn" id="burgerBtn">
    <i class="fas fa-bars"></i>
</button>

<div class="main-content">
    <div class="vaccin-container">
        <!-- En‑tête sélecteur de lot (style glass) -->
        <div class="header-card">
            <div><i class="" style="color: var(--green);"></i> <strong>Calendrier vaccinal</strong></div>
            <div>
                <label for="batchSelect" style="margin-right: 8px;">Lot :</label>
                <select id="batchSelect" class="batch-select">
                    <?php foreach($all_batches as $b): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo ($selected_batch_id == $b['id']) ? 'selected' : ''; ?>>
                            <?php echo htmlspecialchars($b['name']); ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($batch): ?>
            <!-- Alertes -->
            <?php if ($due_today): ?>
                <div class="alert-card">
                    <i class="fas fa-bell"></i>
                    <div><strong> Vaccination due aujourd'hui !</strong> Le lot a <?php echo $batch_age; ?> jours.</div>
                </div>
            <?php elseif ($overdue && $next_vaccine): ?>
                <div class="alert-card critical">
                    <i class="fas fa-exclamation-triangle"></i>
                    <div><strong> Vaccination en retard !</strong> Vaccin "<?php echo htmlspecialchars($next_vaccine['name']); ?>" prévu au jour <?php echo $next_vaccine['recommended_day']; ?> (actuellement jour <?php echo $batch_age; ?>).</div>
                </div>
            <?php endif; ?>

            <!-- Grille des vaccins -->
            <div class="vaccines-grid">
                <?php foreach ($vaccines as $v):
                    $is_done = $v['is_done'];
                    $day = $v['recommended_day'];
                    $day_class = '';
                    $status_class = '';
                    $status_text = '';
                    if ($is_done) {
                        $status_class = 'status-done';
                        $status_text = '✓ Fait';
                    } else {
                        if ($day < $batch_age) {
                            $status_class = 'status-overdue';
                            $status_text = ' En retard';
                            $day_class = 'overdue';
                        } elseif ($day == $batch_age) {
                            $status_class = 'status-pending';
                            $status_text = ' À faire aujourd\'hui';
                            $day_class = 'due';
                        } else {
                            $status_class = 'status-pending';
                            $status_text = ' À faire';
                        }
                    }
                ?>
                    <div class="vaccine-card">
                        <div class="vaccine-name"><?php echo htmlspecialchars($v['name']); ?></div>
                        <div class="vaccine-desc"><?php echo htmlspecialchars($v['description']); ?></div>
                        <div class="vaccine-day <?php echo $day_class; ?>">Jour <?php echo $day; ?></div>
                        <div class="vaccine-status <?php echo $status_class; ?>"><?php echo $status_text; ?></div>
                        <?php if ($is_done && $v['administered_date']): ?>
                            <div class="vaccine-date">Administré le <?php echo date('d/m/Y', strtotime($v['administered_date'])); ?></div>
                        <?php endif; ?>
                        <?php if ($is_done): ?>
                            <form method="POST" action="toggle_vaccine.php">
                                <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                <input type="hidden" name="vaccine_id" value="<?php echo $v['id']; ?>">
                                <input type="hidden" name="action" value="undo">
                                <button type="submit" class="btn-action btn-undo" onclick="return confirm('Marquer comme non fait ?')"><i class="fas fa-undo-alt"></i> Annuler</button>
                            </form>
                        <?php else: ?>
                            <form method="POST" action="toggle_vaccine.php">
                                <input type="hidden" name="batch_id" value="<?php echo $batch['id']; ?>">
                                <input type="hidden" name="vaccine_id" value="<?php echo $v['id']; ?>">
                                <input type="hidden" name="action" value="done">
                                <button type="submit" class="btn-action btn-done"><i class="fas fa-check"></i> Marquer fait</button>
                            </form>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="empty-state">
                <i class="fas fa-chicken"></i>
                <h3>Aucun lot actif</h3>
                <p>Créez un lot pour consulter le planning vaccinal.</p>
                <a href="add_lot.php" class="btn-primary">Créer un lot</a>
            </div>
        <?php endif; ?>

        <!-- Bouton Accueil réduit -->
        <div class="text-center">
            <a href="dashboard.php" class="home-btn">
                <i class="fas fa-home"></i> Accueil
            </a>
        </div>
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

    // Changement de lot
    document.getElementById('batchSelect').addEventListener('change', function() {
        window.location.href = 'vaccins.php?batch_id=' + this.value;
    });
</script>
</body>
</html>