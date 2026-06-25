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

// Récupérer tous les lots actifs
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

// Récupérer tous les enregistrements quotidiens
$daily_records = [];
if ($batch) {
    $stmt = $conn->prepare("SELECT * FROM daily_tracking WHERE batch_id = ? ORDER BY tracking_date DESC");
    $stmt->execute([$batch['id']]);
    $daily_records = $stmt->fetchAll();
}

// Calculs des statistiques supplémentaires
$total_records = count($daily_records);
$first_date = null;
$last_date = null;
$unique_days = 0;
if ($total_records > 0) {
    $first_date = $daily_records[array_key_last($daily_records)]['tracking_date']; // le plus ancien (fin du tableau car tri DESC)
    $last_date = $daily_records[0]['tracking_date'];
    // Compter les jours uniques (par précaution)
    $dates = array_column($daily_records, 'tracking_date');
    $unique_days = count(array_unique($dates));
}

// Calculs des moyennes et totaux
$avg_temp = 0;
$total_feed = 0;
$total_mortality = 0;
if ($daily_records) {
    $avg_temp = round(array_sum(array_column($daily_records, 'temperature')) / $total_records, 1);
    $total_feed = array_sum(array_column($daily_records, 'feed_quantity'));
    $total_mortality = array_sum(array_column($daily_records, 'mortality'));
}

// Données pour le graphique (7 derniers jours)
$last7 = array_slice($daily_records, 0, 7);
$chart_labels = [];
$chart_mortality = [];
$chart_feed = [];
foreach (array_reverse($last7) as $record) {
    $chart_labels[] = date('d/m', strtotime($record['tracking_date']));
    $chart_mortality[] = $record['mortality'];
    $chart_feed[] = $record['feed_quantity'];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Historique - Poulplume</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
            background: linear-gradient(135deg, rgba(0,0,0,0.2), rgba(0,0,0,0.08));
            z-index: -1;
        }

        /* Sidebar identique */
        .sidebar {
            position: fixed; left: 0; top: 0; width: 280px; height: 100vh;
            background: linear-gradient(145deg, var(--green) 0%, var(--green-dark) 100%);
            backdrop-filter: blur(2px); display: flex; flex-direction: column; z-index: 1000;
            transition: transform 0.3s; box-shadow: 4px 0 25px rgba(0,0,0,0.15);
        }
        .sidebar-header {
            padding: 36px 24px 28px; text-align: center; border-bottom: 2px solid var(--yellow);
        }
        .logo-img {
            width: 100px; height: 100px; margin: 0 auto 15px; background: var(--white);
            border-radius: 50%; display: flex; align-items: center; justify-content: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
        }
        .logo-img img { width: 68px; height: auto; border-radius: 50%; }
        .brand { font-size: 2rem; font-weight: 800; }
        .brand .poul { color: var(--yellow); }
        .brand .plume { color: var(--white); }
        .sidebar-nav { flex: 1; padding: 30px 16px; display: flex; flex-direction: column; gap: 8px; }
        .nav-item {
            display: flex; align-items: center; gap: 14px; padding: 12px 18px; border-radius: 50px;
            color: rgba(255,255,255,0.85); text-decoration: none; transition: 0.25s; font-weight: 500;
        }
        .nav-item i { width: 24px; font-size: 1.15rem; }
        .nav-item:hover { background: var(--yellow); color: #1e293b; transform: translateX(6px); }
        .nav-item.active { background: var(--white); color: var(--green); }
        .settings-group { margin-top: 12px; }
        .settings-header { cursor: pointer; justify-content: space-between; }
        .settings-sub { margin-left: 48px; display: none; flex-direction: column; gap: 4px; margin-top: 8px; }
        .settings-sub a { padding: 10px 14px; color: rgba(255,255,255,0.8); text-decoration: none; font-size: 0.85rem; border-radius: 40px; display: flex; align-items: center; gap: 10px; }
        .settings-sub a:hover { background: var(--yellow); color: #1e293b; }
        .settings-sub.show { display: flex; }
        .sidebar-footer { padding: 20px 20px 30px; border-top: 1px solid rgba(255,255,255,0.2); }
        .user-card {
            display: flex; align-items: center; gap: 14px; background: rgba(255,255,255,0.12);
            padding: 10px 14px; border-radius: 60px; margin-bottom: 15px;
        }
        .user-avatar {
            width: 46px; height: 46px; background: var(--yellow); border-radius: 50%;
            display: flex; align-items: center; justify-content: center; color: var(--green-dark);
            font-size: 1.2rem;
        }
        .user-name { font-weight: 700; font-size: 0.95rem; color: var(--white); }
        .user-role { font-size: 0.7rem; color: var(--yellow); font-weight: 600; }
        .logout-btn {
            display: flex; align-items: center; justify-content: center; gap: 8px;
            background: rgba(255,255,255,0.1); color: #ffcccc; padding: 10px; border-radius: 50px;
            text-decoration: none;
        }
        .logout-btn:hover { background: rgba(255,255,255,0.2); color: white; }
        .burger-btn {
            display: none; position: fixed; top: 18px; left: 18px; z-index: 1100;
            background: var(--green); border: none; color: white; font-size: 1.2rem;
            padding: 10px 14px; border-radius: 30px; cursor: pointer; box-shadow: 0 4px 12px rgba(0,0,0,0.2);
        }

        /* Main content */
        .main-content {
            margin-left: 280px;
            padding: 40px 30px;
            min-height: 100vh;
        }
        .container {
            max-width: 1300px;
            margin: 0 auto;
        }

        /* En-tête avec sélecteur et infos */
        .header-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            padding: 18px 25px;
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
            padding: 8px 18px;
            border-radius: 40px;
            font-family: inherit;
            cursor: pointer;
        }
        .stats-badge {
            display: flex;
            gap: 20px;
            font-size: 0.9rem;
        }
        .stats-badge span {
            background: var(--white-glass);
            padding: 5px 12px;
            border-radius: 30px;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        /* Cartes récapitulatives */
        .stats-row {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 20px;
            margin-bottom: 30px;
        }
        .stat-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 28px;
            padding: 18px;
            text-align: center;
            border: 1px solid var(--border-light);
        }
        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: var(--green);
        }
        .stat-label {
            font-size: 0.8rem;
            color: var(--text-gray);
            margin-top: 5px;
        }

        /* Graphique */
        .chart-container {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid var(--border-light);
        }
        canvas {
            max-height: 300px;
            width: 100%;
        }

        /* Tableau */
        .table-wrapper {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 20px;
            overflow-x: auto;
            border: 1px solid var(--border-light);
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.85rem;
        }
        th {
            text-align: left;
            padding: 12px 10px;
            background: rgba(0,0,0,0.03);
            color: var(--green);
            font-weight: 600;
        }
        td {
            padding: 10px;
            border-bottom: 1px solid var(--border-light);
        }
        .badge-notes {
            background: var(--yellow);
            color: #1e293b;
            padding: 2px 8px;
            border-radius: 20px;
            font-size: 0.7rem;
            display: inline-block;
        }

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
            margin-top: 30px;
        }
        .text-center { text-align: center; }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .burger-btn { display: block; }
            .main-content { margin-left: 0; }
            .stats-row { grid-template-columns: repeat(2,1fr); }
        }
        @media (max-width: 640px) {
            .main-content { padding: 20px; }
            .stats-row { grid-template-columns: 1fr; }
            .stats-badge { flex-wrap: wrap; }
            th, td { font-size: 0.75rem; padding: 8px 5px; }
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
    <div class="container">
        <!-- En-tête avec sélecteur + nombre d'enregistrements et période -->
        <div class="header-card">
            <div><i class="" style="color: var(--green);"></i> <strong>Historique des enregistrements</strong></div>
            <div class="stats-badge">
                <?php if ($batch && $total_records > 0): ?>
                <span><i class="fas fa-database"></i> <?php echo $total_records; ?> enregistrements</span>
                <span><i class="fas fa-calendar-alt"></i> <?php echo date('d/m/Y', strtotime($first_date)); ?> → <?php echo date('d/m/Y', strtotime($last_date)); ?></span>
                <span><i class="fas fa-clock"></i> <?php echo $unique_days; ?> jours de suivi</span>
                <?php elseif ($batch): ?>
                <span><i class="fas fa-database"></i> Aucun enregistrement</span>
                <?php endif; ?>
            </div>
            <div>
                <label for="batchSelect" style="margin-right: 8px;">Lot :</label>
                <select id="batchSelect" class="batch-select">
                    <?php foreach($all_batches as $b): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo ($selected_batch_id == $b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <?php if ($batch): ?>
            <?php if (!empty($daily_records)): ?>
            <!-- 4 cartes récapitulatives (température moyenne, alimentation totale, mortalité cumulée, nombre d'enregistrements) -->
            <div class="stats-row">
                <div class="stat-card">
                    <div class="stat-value"><?php echo $avg_temp; ?>°C</div>
                    <div class="stat-label">Température moyenne</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo round($total_feed); ?> kg</div>
                    <div class="stat-label">Alimentation totale</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_mortality; ?></div>
                    <div class="stat-label">Mortalité cumulée</div>
                </div>
                <div class="stat-card">
                    <div class="stat-value"><?php echo $total_records; ?></div>
                    <div class="stat-label">Enregistrements</div>
                </div>
            </div>

            <!-- Graphique d'évolution (7 derniers jours) -->
            <?php if (!empty($chart_labels)): ?>
            <div class="chart-container">
                <div style="display: flex; justify-content: space-between; margin-bottom: 15px;">
                    <span><i class="fas fa-chart-line" style="color: var(--green);"></i> Évolution des 7 derniers jours</span>
                    <span style="font-size:0.7rem;">Mortalité (vert) / Alimentation (jaune)</span>
                </div>
                <canvas id="trendChart" style="height: 260px;"></canvas>
            </div>
            <?php endif; ?>

            <!-- Tableau des enregistrements -->
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Température</th><th>Aliment (kg)</th><th>Morts</th><th>Malades</th><th>Notes</th></tr>
                    </thead>
                    <tbody>
                        <?php foreach ($daily_records as $record): ?>
                        <tr>
                            <td><?php echo date('d/m/Y', strtotime($record['tracking_date'])); ?></td>
                            <td><?php echo $record['temperature']; ?>°C</td>
                            <td><?php echo $record['feed_quantity']; ?> kg</td>
                            <td><?php echo $record['mortality']; ?></td>
                            <td><?php echo $record['sick'] ?? 0; ?></td>
                            <td><?php echo !empty($record['notes']) ? '<span class="badge-notes">' . htmlspecialchars(substr($record['notes'], 0, 50)) . '</span>' : '—'; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php else: ?>
            <div class="chart-container" style="text-align:center; padding:60px;">
                <i class="fas fa-database" style="font-size:3rem; color:var(--yellow);"></i>
                <p>Aucun enregistrement pour ce lot.</p>
                <a href="enregistrement.php" style="background:var(--green); color:white; padding:8px 20px; border-radius:40px; text-decoration:none; display:inline-block; margin-top:15px;">Enregistrer</a>
            </div>
            <?php endif; ?>
        <?php else: ?>
            <div class="chart-container" style="text-align:center; padding:60px;">
                <i class="fas fa-chicken" style="font-size:3rem; color:var(--yellow);"></i>
                <p>Aucun lot actif. Créez un lot pour voir l'historique.</p>
                <a href="add_lot.php" style="background:var(--green); color:white; padding:8px 20px; border-radius:40px; text-decoration:none; display:inline-block; margin-top:15px;">Créer un lot</a>
            </div>
        <?php endif; ?>

        <div class="text-center">
            <a href="dashboard.php" class="home-btn"><i class="fas fa-home"></i> Accueil</a>
        </div>
    </div>
</div>

<script>
    function toggleSettings() { document.getElementById('settingsSub').classList.toggle('show'); }
    const burger = document.getElementById('burgerBtn');
    const sidebar = document.querySelector('.sidebar');
    if (burger && sidebar) {
        burger.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => {
            if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !burger.contains(e.target))
                sidebar.classList.remove('open');
        });
    }
    document.getElementById('batchSelect').addEventListener('change', function() {
        window.location.href = 'historique.php?batch_id=' + this.value;
    });

    <?php if (!empty($chart_labels)): ?>
    new Chart(document.getElementById('trendChart'), {
        type: 'line',
        data: {
            labels: <?php echo json_encode($chart_labels); ?>,
            datasets: [
                {
                    label: 'Mortalité (nombre)',
                    data: <?php echo json_encode($chart_mortality); ?>,
                    borderColor: '#14B53A',
                    backgroundColor: 'rgba(20,181,58,0.05)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#14B53A',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'y'
                },
                {
                    label: 'Alimentation (kg)',
                    data: <?php echo json_encode($chart_feed); ?>,
                    borderColor: '#FCD116',
                    backgroundColor: 'rgba(252,209,22,0.05)',
                    borderWidth: 2,
                    tension: 0.3,
                    fill: true,
                    pointBackgroundColor: '#FCD116',
                    pointBorderColor: '#fff',
                    pointRadius: 4,
                    pointHoverRadius: 6,
                    yAxisID: 'y1'
                }
            ]
        },
        options: {
            responsive: true,
            maintainAspectRatio: true,
            plugins: {
                legend: { position: 'top', labels: { boxWidth: 12, font: { size: 11 } } },
                tooltip: { mode: 'index', intersect: false }
            },
            scales: {
                y: {
                    title: { display: true, text: 'Mortalité (nb)', color: '#14B53A' },
                    beginAtZero: true,
                    grid: { color: 'rgba(0,0,0,0.05)' }
                },
                y1: {
                    position: 'right',
                    title: { display: true, text: 'Aliment (kg)', color: '#FCD116' },
                    beginAtZero: true,
                    grid: { drawOnChartArea: false }
                }
            }
        }
    });
    <?php endif; ?>
</script>
</body>
</html>