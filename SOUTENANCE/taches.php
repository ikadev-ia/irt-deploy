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
if ($batch) {
    $start_date = new DateTime($batch['start_date']);
    $today = new DateTime();
    $batch_age = $start_date->diff($today)->days;
}

// Calcul des horaires adaptés (température et âge)
$temp = $weather['temperature'];
if ($temp > 33) {
    $feeding_times = ['06:00', '10:00', '14:00', '18:00', '22:00'];
    $feeding_frequency = "5 repas légers";
} elseif ($temp > 28) {
    $feeding_times = ['07:00', '12:00', '17:00', '21:00'];
    $feeding_frequency = "4 repas";
} else {
    $feeding_times = ['08:00', '16:00'];
    $feeding_frequency = "2 repas";
}
if ($batch_age < 10) {
    $feeding_times = ['07:00', '11:00', '15:00', '19:00', '23:00'];
    $feeding_frequency = "5 repas (poussins)";
}

if ($temp > 33) {
    $water_times = ['06:00', '10:00', '14:00', '18:00', '22:00'];
    $water_frequency = "5 changements";
} elseif ($temp > 28) {
    $water_times = ['07:00', '12:00', '17:00', '21:00'];
    $water_frequency = "4 changements";
} else {
    $water_times = ['08:00', '16:00'];
    $water_frequency = "2 changements";
}

// Construction des tâches quotidiennes avec horaires
$raw_tasks = [
    ['hour' => '06:00', 'title' => '🌅 Lever & observation', 'desc' => 'Compter les morts, comportement général', 'icon' => 'sun'],
    ['hour' => $feeding_times[0], 'title' => '🍽️ Distribution aliment', 'desc' => "{$feeding_frequency} – 1er repas", 'icon' => 'utensils'],
    ['hour' => $water_times[0], 'title' => '💧 Changement eau', 'desc' => "{$water_frequency} – eau fraîche", 'icon' => 'tint'],
    ['hour' => $feeding_times[1] ?? '12:00', 'title' => '🍽️ Distribution aliment', 'desc' => 'Deuxième repas', 'icon' => 'utensils'],
    ['hour' => $water_times[1] ?? '14:00', 'title' => '💧 Changement eau', 'desc' => 'Eau propre', 'icon' => 'tint'],
    ['hour' => '16:00', 'title' => '📝 Enregistrement', 'desc' => 'Température, mortalité, observations', 'icon' => 'edit'],
    ['hour' => $feeding_times[2] ?? '18:00', 'title' => '🍽️ Distribution aliment', 'desc' => 'Dernier repas', 'icon' => 'utensils'],
    ['hour' => $water_times[2] ?? '20:00', 'title' => '💧 Changement eau', 'desc' => 'Eau pour la nuit', 'icon' => 'tint'],
    ['hour' => '21:00', 'title' => '🌙 Inspection nocturne', 'desc' => 'Température, ventilation, calme', 'icon' => 'moon'],
];

// Trier les tâches par heure croissante (de 06:00 à 21:00)
usort($raw_tasks, function($a, $b) {
    return strcmp($a['hour'], $b['hour']);
});
$dailyTasks = $raw_tasks;

// Tâches hebdomadaires (inchangées)
$weeklyTasks = [
    'Lundi' => ['icon' => 'broom', 'tasks' => ['Nettoyage des mangeoires', 'Vérification litière', 'Planification']],
    'Mardi' => ['icon' => 'weight-hanging', 'tasks' => ['Pesée échantillon', 'Contrôle stocks', 'Désinfection abreuvoirs']],
    'Mercredi' => ['icon' => 'stethoscope', 'tasks' => ['Inspection sanitaire', 'Observation fientes', 'Aération']],
    'Jeudi' => ['icon' => 'soap', 'tasks' => ['Nettoyage complet', 'Changement litière', 'Contrôle parasites']],
    'Vendredi' => ['icon' => 'clipboard-list', 'tasks' => ['Bilan semaine', 'Vérification vaccins', 'Planification']],
    'Samedi' => ['icon' => 'eye', 'tasks' => ['Observation prolongée', 'Pesée contrôle', 'Entretien parcours']],
    'Dimanche' => ['icon' => 'coffee', 'tasks' => ['Repos / surveillance', 'Préparation outils']],
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Planificateur - Poulplume</title>
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

        /* Sidebar identique */
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

        /* Main content - organisation moderne */
        .main-content {
            margin-left: 280px;
            padding: 40px 30px;
            min-height: 100vh;
        }

        /* En-tête */
        .page-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            margin-bottom: 30px;
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            padding: 20px 30px;
            border: 1px solid var(--border-light);
        }
        .page-title h1 {
            font-size: 1.6rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .page-title h1 i {
            color: var(--yellow);
        }
        .batch-selector {
            display: flex;
            align-items: center;
            gap: 12px;
        }
        .batch-select {
            background: var(--white-glass);
            border: 1px solid var(--border-light);
            padding: 8px 18px;
            border-radius: 40px;
            font-family: inherit;
            font-size: 0.9rem;
            cursor: pointer;
        }

        /* Deux colonnes */
        .two-columns {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 30px;
            margin-bottom: 30px;
        }
        .column {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 40px;
            padding: 25px;
            border: 1px solid var(--border-light);
        }
        .column-header {
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 20px;
            padding-bottom: 12px;
            border-bottom: 2px solid var(--yellow);
        }
        .column-header i {
            font-size: 1.6rem;
            color: var(--yellow);
        }
        .column-header h2 {
            font-size: 1.3rem;
            font-weight: 700;
        }

        /* Liste des tâches du jour - style chronologique */
        .tasks-list {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .task-card {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--white-glass);
            border-radius: 28px;
            padding: 12px 18px;
            border: 1px solid var(--border-light);
            transition: all 0.2s;
        }
        .task-card:hover {
            transform: translateX(5px);
            box-shadow: var(--shadow-md);
        }
        .task-time {
            font-weight: 800;
            font-size: 0.9rem;
            background: var(--yellow);
            color: #1e293b;
            padding: 4px 12px;
            border-radius: 30px;
            min-width: 70px;
            text-align: center;
        }
        .task-icon {
            width: 40px;
            height: 40px;
            background: rgba(20,181,58,0.1);
            border-radius: 30px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: var(--green);
            font-size: 1.2rem;
        }
        .task-content {
            flex: 1;
        }
        .task-title {
            font-weight: 700;
            font-size: 1rem;
        }
        .task-desc {
            font-size: 0.75rem;
            color: var(--text-gray);
        }

        /* Planning hebdomadaire */
        .weekly-grid {
            display: flex;
            flex-direction: column;
            gap: 14px;
        }
        .week-day-card {
            background: var(--white-glass);
            border-radius: 28px;
            padding: 14px 18px;
            border: 1px solid var(--border-light);
            transition: 0.2s;
        }
        .week-day-card:hover {
            transform: translateX(5px);
        }
        .week-day-header {
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 10px;
            font-weight: 700;
            font-size: 1rem;
            color: var(--yellow);
        }
        .week-tasks {
            padding-left: 28px;
        }
        .week-tasks li {
            list-style: none;
            margin: 6px 0;
            font-size: 0.8rem;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .week-tasks li i {
            color: var(--green);
            font-size: 0.7rem;
        }

        /* Bouton Accueil réduit */
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
            margin-top: 20px;
        }
        .home-btn:hover {
            background: #e6b800;
            transform: translateY(-1px);
        }
        .text-center {
            text-align: center;
        }

        /* Empty state */
        .empty-state {
            text-align: center;
            padding: 40px;
        }
        .empty-state i {
            font-size: 3rem;
            color: var(--yellow);
            margin-bottom: 15px;
        }
        .btn-primary {
            background: var(--green);
            color: white;
            padding: 10px 24px;
            border-radius: 40px;
            text-decoration: none;
            display: inline-block;
            margin-top: 15px;
        }

        @media (max-width: 1024px) {
            .two-columns {
                grid-template-columns: 1fr;
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
            .main-content {
                padding: 20px 16px;
            }
            .page-header {
                flex-direction: column;
                align-items: stretch;
            }
            .task-card {
                flex-wrap: wrap;
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
        <a href="add_lot.php" class="nav-item"><i class="fas fa-plus-circle"></i> Nouveau lot</a>
        <a href="finances.php" class="nav-item"><i class="fas fa-coins"></i> Finances</a>
        <a href="chatbot.php" class="nav-item"><i class="fas fa-robot"></i> Chatbot </a>
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
    <div class="page-header">
        <div class="page-title">
            <h1><i class="-alt"></i> Planificateur</h1>
            <p style="font-size: 0.85rem; margin-top: 5px;">Tâches personnalisées selon météo et âge du lot</p>
        </div>
        <div class="batch-selector">
            <label for="batchSelect">Lot actif :</label>
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
        <div class="two-columns">
            <!-- Colonne gauche : Aujourd'hui (ordre chronologique) -->
            <div class="column">
                <div class="column-header">
                    <i class="fas fa-sun"></i>
                    <h2>Aujourd'hui</h2>
                </div>
                <div class="tasks-list">
                    <?php foreach ($dailyTasks as $task): ?>
                        <div class="task-card">
                            <div class="task-time"><?php echo $task['hour']; ?></div>
                            <div class="task-icon"><i class="fas fa-<?php echo $task['icon']; ?>"></i></div>
                            <div class="task-content">
                                <div class="task-title"><?php echo $task['title']; ?></div>
                                <div class="task-desc"><?php echo $task['desc']; ?></div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- Colonne droite : Semaine -->
            <div class="column">
                <div class="column-header">
                    <i class="fas fa-calendar-week"></i>
                    <h2>Semaine</h2>
                </div>
                <div class="weekly-grid">
                    <?php foreach ($weeklyTasks as $day => $data): ?>
                        <div class="week-day-card">
                            <div class="week-day-header">
                                <i class="fas fa-<?php echo $data['icon']; ?>"></i>
                                <span><?php echo $day; ?></span>
                            </div>
                            <ul class="week-tasks">
                                <?php foreach ($data['tasks'] as $task): ?>
                                    <li><i class="fas fa-check-circle"></i> <?php echo $task; ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="empty-state">
            <i class="fas fa-chicken"></i>
            <h3>Aucun lot actif</h3>
            <p>Créez un lot pour générer un planning personnalisé.</p>
            <a href="add_lot.php" class="btn-primary">Créer un lot</a>
        </div>
    <?php endif; ?>

    <div class="text-center">
        <a href="dashboard.php" class="home-btn"><i class="fas fa-home"></i> Accueil</a>
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
    document.getElementById('batchSelect').addEventListener('change', function() {
        window.location.href = 'taches.php?batch_id=' + this.value;
    });
</script>
</body>
</html>