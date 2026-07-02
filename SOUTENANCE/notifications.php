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

// Fichier JSON pour stocker les notifications
$notif_file = 'notifications_' . $user_id . '.json';

// Initialiser le fichier s'il n'existe pas
if (!file_exists($notif_file)) {
    file_put_contents($notif_file, json_encode([]));
}

// Charger les notifications
$notifications = json_decode(file_get_contents($notif_file), true) ?: [];

// Générer les notifications du jour
$today = date('Y-m-d');
$today_key = date('Ymd');

// Nettoyer les anciennes notifications (plus de 7 jours)
foreach ($notifications as $key => $notif) {
    if (strtotime($notif['created_at']) < strtotime('-7 days')) {
        unset($notifications[$key]);
    }
}

// Marquer comme lu
if (isset($_GET['read_id'])) {
    $read_id = (int)$_GET['read_id'];
    foreach ($notifications as $key => $notif) {
        if ($notif['id'] == $read_id) {
            $notifications[$key]['is_read'] = 1;
            break;
        }
    }
    file_put_contents($notif_file, json_encode(array_values($notifications)));
    header("Location: notifications.php");
    exit();
}

// Marquer toutes comme lues
if (isset($_GET['read_all'])) {
    foreach ($notifications as $key => $notif) {
        $notifications[$key]['is_read'] = 1;
    }
    file_put_contents($notif_file, json_encode(array_values($notifications)));
    header("Location: notifications.php");
    exit();
}

// Supprimer une notification
if (isset($_GET['delete_id'])) {
    $delete_id = (int)$_GET['delete_id'];
    foreach ($notifications as $key => $notif) {
        if ($notif['id'] == $delete_id) {
            unset($notifications[$key]);
            break;
        }
    }
    file_put_contents($notif_file, json_encode(array_values($notifications)));
    header("Location: notifications.php");
    exit();
}

// Générer les notifications du jour si pas déjà fait
$today_notif_key = 'generated_' . $today;
if (!isset($_SESSION[$today_notif_key])) {
    $_SESSION[$today_notif_key] = true;
    
    // Récupérer les lots actifs
    if ($user_role == 'admin') {
        $stmt = $conn->prepare("SELECT id, name, start_date, current_birds FROM batches WHERE status = 'active'");
        $stmt->execute();
    } else {
        $stmt = $conn->prepare("SELECT id, name, start_date, current_birds FROM batches WHERE user_id = ? AND status = 'active'");
        $stmt->execute([$user_id]);
    }
    $batches = $stmt->fetchAll();
    
    $new_notifs = [];
    $next_id = count($notifications) + 1;
    
    foreach ($batches as $batch) {
        $batch_name = $batch['name'];
        $start = new DateTime($batch['start_date']);
        $age = $start->diff(new DateTime())->days;
        $temp = $weather['temperature'];
        
        // Horaires adaptés
        if ($temp > 33) {
            $feed_times = ['06:00', '10:00', '14:00', '18:00', '22:00'];
            $water_times = ['06:00', '10:00', '14:00', '18:00', '22:00'];
        } elseif ($temp > 28) {
            $feed_times = ['07:00', '12:00', '17:00', '21:00'];
            $water_times = ['07:00', '12:00', '17:00', '21:00'];
        } else {
            $feed_times = ['08:00', '16:00'];
            $water_times = ['08:00', '16:00'];
        }
        if ($age < 10) {
            $feed_times = ['07:00', '11:00', '15:00', '19:00', '23:00'];
            $water_times = ['07:00', '11:00', '15:00', '19:00', '23:00'];
        }
        
        // Distribution aliment
        foreach ($feed_times as $time) {
            $new_notifs[] = [
                'id' => $next_id++,
                'title' => ' Distribution aliment',
                'message' => "Lot {$batch_name} : distribution d'aliment à {$time}",
                'type' => 'task',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'batch_name' => $batch_name
            ];
        }
        
        // Changement eau
        foreach ($water_times as $time) {
            $new_notifs[] = [
                'id' => $next_id++,
                'title' => ' Changement eau',
                'message' => "Lot {$batch_name} : eau fraîche à {$time}",
                'type' => 'task',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'batch_name' => $batch_name
            ];
        }
        
        // Alerte mortalité
        $stmt2 = $conn->prepare("SELECT mortality FROM daily_tracking WHERE batch_id = ? ORDER BY tracking_date DESC LIMIT 1");
        $stmt2->execute([$batch['id']]);
        $last_mort = $stmt2->fetch();
        if ($last_mort && $last_mort['mortality'] > 5) {
            $new_notifs[] = [
                'id' => $next_id++,
                'title' => ' Alerte mortalité élevée',
                'message' => "Lot {$batch_name} : {$last_mort['mortality']} morts aujourd'hui. Consultez le diagnostic.",
                'type' => 'alert',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'batch_name' => $batch_name
            ];
        }
        
        // Rappel vaccinal
        $vaccine_days = [7, 14, 21, 28, 35, 42];
        if (in_array($age, $vaccine_days)) {
            $new_notifs[] = [
                'id' => $next_id++,
                'title' => ' Rappel vaccinal',
                'message' => "Lot {$batch_name} : vaccination prévue aujourd'hui (Jour {$age})",
                'type' => 'vaccine',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'batch_name' => $batch_name
            ];
        }
        
        // Alerte température
        if ($temp > 35) {
            $new_notifs[] = [
                'id' => $next_id++,
                'title' => ' Alerte chaleur',
                'message' => "Température extérieure élevée ({$temp}°C). Augmentez la ventilation et l'eau fraîche.",
                'type' => 'alert',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'batch_name' => null
            ];
        } elseif ($temp < 15 && $age < 21) {
            $new_notifs[] = [
                'id' => $next_id++,
                'title' => ' Alerte froid',
                'message' => "Température basse ({$temp}°C). Vérifiez le chauffage.",
                'type' => 'alert',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'batch_name' => null
            ];
        }
        
        // Rappel enregistrement quotidien
        $stmt2 = $conn->prepare("SELECT COUNT(*) FROM daily_tracking WHERE batch_id = ? AND tracking_date = ?");
        $stmt2->execute([$batch['id'], $today]);
        if ($stmt2->fetchColumn() == 0) {
            $new_notifs[] = [
                'id' => $next_id++,
                'title' => ' Enregistrement quotidien',
                'message' => "Lot {$batch_name} : pensez à enregistrer les données du jour.",
                'type' => 'reminder',
                'is_read' => 0,
                'created_at' => date('Y-m-d H:i:s'),
                'batch_name' => $batch_name
            ];
        }
    }
    
    // Ajouter les nouvelles notifications au début
    $notifications = array_merge($new_notifs, $notifications);
    file_put_contents($notif_file, json_encode($notifications));
}

// Compter les non lues
$unread_count = 0;
foreach ($notifications as $notif) {
    if (!$notif['is_read']) $unread_count++;
}

// Filtres
$filter = isset($_GET['filter']) ? $_GET['filter'] : 'all';
$filtered_notifs = [];
foreach ($notifications as $notif) {
    if ($filter == 'unread' && $notif['is_read']) continue;
    if ($filter != 'all' && $filter != 'unread' && $filter != 'read' && $notif['type'] != $filter) continue;
    $filtered_notifs[] = $notif;
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Notifications - Poulplume</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
        body { font-family: 'Inter', sans-serif; background: url('Images/') no-repeat center center fixed; background-size: cover; color: var(--text-dark); min-height: 100vh; }
        body::before { content: ''; position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: linear-gradient(135deg, rgba(0,0,0,0.2), rgba(0,0,0,0.08)); z-index: -1; }

        /* Sidebar */
        .sidebar { position: fixed; left: 0; top: 0; width: 280px; height: 100vh; background: linear-gradient(145deg, var(--green) 0%, var(--green-dark) 100%); backdrop-filter: blur(2px); display: flex; flex-direction: column; z-index: 1000; transition: transform 0.3s; box-shadow: 4px 0 25px rgba(0,0,0,0.15); }
        .sidebar-header { padding: 36px 24px 28px; text-align: center; border-bottom: 2px solid var(--yellow); }
        .logo-img { width: 100px; height: 100px; margin: 0 auto 15px; background: var(--white); border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 10px 25px rgba(0,0,0,0.2); }
        .logo-img img { width: 68px; height: auto; border-radius: 50%; }
        .brand { font-size: 2rem; font-weight: 800; }
        .brand .poul { color: var(--yellow); }
        .brand .plume { color: var(--white); }
        .sidebar-nav { flex: 1; padding: 30px 16px; display: flex; flex-direction: column; gap: 8px; }
        .nav-item { display: flex; align-items: center; gap: 14px; padding: 12px 18px; border-radius: 50px; color: rgba(255,255,255,0.85); text-decoration: none; transition: 0.25s; font-weight: 500; position: relative; }
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
        .user-card { display: flex; align-items: center; gap: 14px; background: rgba(255,255,255,0.12); padding: 10px 14px; border-radius: 60px; margin-bottom: 15px; }
        .user-avatar { width: 46px; height: 46px; background: var(--yellow); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--green-dark); font-size: 1.2rem; }
        .user-name { font-weight: 700; font-size: 0.95rem; color: var(--white); }
        .user-role { font-size: 0.7rem; color: var(--yellow); font-weight: 600; }
        .logout-btn { display: flex; align-items: center; justify-content: center; gap: 8px; background: rgba(255,255,255,0.1); color: #ffcccc; padding: 10px; border-radius: 50px; text-decoration: none; }
        .logout-btn:hover { background: rgba(255,255,255,0.2); color: white; }
        .burger-btn { display: none; position: fixed; top: 18px; left: 18px; z-index: 1100; background: var(--green); border: none; color: white; font-size: 1.2rem; padding: 10px 14px; border-radius: 30px; cursor: pointer; }

        /* Main content */
        .main-content { margin-left: 280px; padding: 40px 30px; min-height: 100vh; }
        .container { max-width: 1000px; margin: 0 auto; }

        /* En-tête */
        .page-header { background: var(--white-glass-card); backdrop-filter: blur(12px); border-radius: 60px; padding: 20px 30px; margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 20px; border: 1px solid var(--border-light); }
        .page-title h1 { font-size: 1.5rem; font-weight: 700; display: flex; align-items: center; gap: 10px; }
        .unread-badge { background: #ef4444; color: white; border-radius: 30px; padding: 2px 10px; font-size: 0.7rem; margin-left: 10px; }
        .home-btn { display: inline-flex; align-items: center; gap: 6px; background: var(--yellow); color: #1e293b; padding: 6px 14px; border-radius: 40px; text-decoration: none; font-size: 0.75rem; font-weight: 600; transition: 0.2s; }
        .home-btn:hover { background: #e6b800; transform: translateY(-1px); }

        /* Filtres */
        .filter-bar { display: flex; gap: 12px; flex-wrap: wrap; margin-bottom: 25px; background: var(--white-glass); padding: 12px 20px; border-radius: 60px; }
        .filter-btn { padding: 6px 16px; border-radius: 30px; background: transparent; border: 1px solid var(--border-light); color: var(--text-dark); cursor: pointer; transition: 0.2s; font-size: 0.8rem; text-decoration: none; }
        .filter-btn.active, .filter-btn:hover { background: var(--green); color: white; border-color: var(--green); }
        .mark-read-btn { background: var(--yellow); color: #1e293b; padding: 6px 16px; border-radius: 30px; text-decoration: none; font-size: 0.8rem; font-weight: 600; }

        /* Notifications */
        .notifications-list { display: flex; flex-direction: column; gap: 12px; }
        .notification-card { background: var(--white-glass-card); backdrop-filter: blur(12px); border-radius: 28px; padding: 18px 22px; border: 1px solid var(--border-light); transition: 0.2s; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; }
        .notification-card:hover { transform: translateX(5px); }
        .notification-card.unread { border-left: 4px solid #ef4444; background: rgba(239,68,68,0.05); }
        .notification-content { flex: 1; }
        .notification-title { font-weight: 700; font-size: 1rem; display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
        .notification-type { display: inline-block; padding: 2px 10px; border-radius: 20px; font-size: 0.65rem; font-weight: 600; }
        .type-alert { background: #fee2e2; color: #ef4444; }
        .type-task { background: #d1fae5; color: #10b981; }
        .type-vaccine { background: #fef3c7; color: #d97706; }
        .type-reminder { background: #e0e7ff; color: #4f46e5; }
        .notification-message { font-size: 0.85rem; color: var(--text-gray); margin-top: 6px; }
        .notification-date { font-size: 0.7rem; color: var(--text-gray); margin-top: 8px; }
        .notification-actions { display: flex; gap: 8px; }
        .action-btn { background: none; border: 1px solid var(--border-light); padding: 6px 12px; border-radius: 30px; cursor: pointer; transition: 0.2s; font-size: 0.7rem; color: var(--text-dark); text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
        .action-btn:hover { background: var(--yellow); border-color: var(--yellow); }
        .empty-state { background: var(--white-glass-card); border-radius: 48px; padding: 60px 30px; text-align: center; }

        @media (max-width: 1024px) { .sidebar { transform: translateX(-100%); } .sidebar.open { transform: translateX(0); } .burger-btn { display: block; } .main-content { margin-left: 0; } }
        @media (max-width: 640px) { .main-content { padding: 20px 16px; } .page-header { flex-direction: column; align-items: stretch; } .notification-card { flex-direction: column; align-items: stretch; } }
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
                <a href="notifications.php" class="active"><i class="fas fa-bell"></i> Notifications <?php if($unread_count > 0): ?><span style="background:#ef4444; color:white; border-radius:20px; padding:2px 8px; font-size:0.65rem; margin-left:8px;"><?php echo $unread_count; ?></span><?php endif; ?></a>
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
            <div><div class="user-name"><?php echo htmlspecialchars($user_name); ?></div><div class="user-role"><?php echo $user_role == 'admin' ? 'Administrateur' : 'Éleveur'; ?></div></div>
        </div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<button class="burger-btn" id="burgerBtn"><i class="fas fa-bars"></i></button>

<div class="main-content">
    <div class="container">
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-bell" style="color: var(--green);"></i> Notifications <?php if($unread_count > 0): ?><span class="unread-badge"><?php echo $unread_count; ?> non lues</span><?php endif; ?></h1>
            </div>
            <a href="dashboard.php" class="home-btn"><i class="fas fa-home"></i> Accueil</a>
        </div>

        <div class="filter-bar">
            <a href="?filter=all" class="filter-btn <?php echo $filter=='all'?'active':''; ?>">Toutes</a>
            <a href="?filter=unread" class="filter-btn <?php echo $filter=='unread'?'active':''; ?>">Non lues <?php if($unread_count>0): ?><span style="background:#ef4444; color:white; padding:0px 6px; border-radius:20px; margin-left:5px;"><?php echo $unread_count; ?></span><?php endif; ?></a>
            <a href="?filter=alert" class="filter-btn <?php echo $filter=='alert'?'active':''; ?>">Alertes</a>
            <a href="?filter=task" class="filter-btn <?php echo $filter=='task'?'active':''; ?>">Tâches</a>
            <a href="?filter=vaccine" class="filter-btn <?php echo $filter=='vaccine'?'active':''; ?>">Vaccins</a>
            <a href="?filter=reminder" class="filter-btn <?php echo $filter=='reminder'?'active':''; ?>">Rappels</a>
            <div style="flex:1;"></div>
            <a href="?read_all=1" class="mark-read-btn" onclick="return confirm('Marquer toutes les notifications comme lues ?')"><i class="fas fa-check-double"></i> Tout marquer lu</a>
        </div>

        <?php if (empty($filtered_notifs)): ?>
            <div class="empty-state">
                <i class="fas fa-bell-slash" style="font-size: 3rem; color: var(--yellow); margin-bottom: 15px; display: block;"></i>
                <h3>Aucune notification</h3>
                <p>Vous êtes à jour !</p>
            </div>
        <?php else: ?>
            <div class="notifications-list">
                <?php foreach ($filtered_notifs as $notif): ?>
                    <div class="notification-card <?php echo $notif['is_read'] ? '' : 'unread'; ?>">
                        <div class="notification-content">
                            <div class="notification-title">
                                <?php echo htmlspecialchars($notif['title']); ?>
                                <span class="notification-type type-<?php echo $notif['type']; ?>">
                                    <?php if($notif['type']=='alert'): ?> Alerte<?php elseif($notif['type']=='task'): ?> Tâche<?php elseif($notif['type']=='vaccine'): ?> Vaccin<?php elseif($notif['type']=='reminder'): ?> Rappel<?php else: ?> Info<?php endif; ?>
                                </span>
                                <?php if(!$notif['is_read']): ?>
                                    <span style="background:#ef4444; color:white; padding:2px 8px; border-radius:20px; font-size:0.65rem;">Nouveau</span>
                                <?php endif; ?>
                            </div>
                            <div class="notification-message"><?php echo htmlspecialchars($notif['message']); ?></div>
                            <div class="notification-date">
                                <i class="far fa-clock"></i> <?php echo date('d/m/Y H:i', strtotime($notif['created_at'])); ?>
                                <?php if($notif['batch_name']): ?> • Lot: <?php echo htmlspecialchars($notif['batch_name']); ?><?php endif; ?>
                            </div>
                        </div>
                        <div class="notification-actions">
                            <?php if(!$notif['is_read']): ?>
                                <a href="?read_id=<?php echo $notif['id']; ?>" class="action-btn"><i class="fas fa-check"></i> Marquer lu</a>
                            <?php endif; ?>
                            <a href="?delete_id=<?php echo $notif['id']; ?>" class="action-btn" onclick="return confirm('Supprimer cette notification ?')"><i class="fas fa-trash"></i> Supprimer</a>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<script>
    function toggleSettings() { document.getElementById('settingsSub').classList.toggle('show'); }
    const burger = document.getElementById('burgerBtn');
    const sidebar = document.querySelector('.sidebar');
    if (burger && sidebar) {
        burger.addEventListener('click', () => sidebar.classList.toggle('open'));
        document.addEventListener('click', (e) => { if (sidebar.classList.contains('open') && !sidebar.contains(e.target) && !burger.contains(e.target)) sidebar.classList.remove('open'); });
    }
</script>
</body>
</html>