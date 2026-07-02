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

// Type du lot
$batch_type = 'chair';
if ($batch) {
    $result = $conn->query("PRAGMA table_info(batches)");
    $has_type = false;
    while ($row = $result->fetch()) {
        if ($row['name'] == 'type_poulet') $has_type = true;
    }
    if ($has_type && isset($batch['type_poulet'])) {
        $batch_type = $batch['type_poulet'];
    }
}

// Données des races (sans couleurs spécifiques par race, car on va uniformiser les en-têtes)
$breeds = [
    'chair' => [
        'name' => 'Poulet de chair',
        'duration' => '45 jours',
        'weight' => '2,2 – 2,5 kg',
        'feeding' => 'Démarrage (0-10j) : protéines 22-24%<br>Croissance (11-30j) : protéines 20-22%<br>Finition (31-45j) : protéines 18-20%',
        'temperature' => 'Semaine 1 : 32-35°C → baisse progressive jusqu’à 21°C',
        'vaccines' => 'Gumboro (J7), Newcastle (J14), Bronchite (J21)',
        'health' => 'Coccidiose, stress thermique, colibacillose',
        'advice' => 'Éviter le surpeuplement. Litière toujours sèche. Eau fraîche en été.',
        'description' => 'Croissance rapide, viande tendre.',
        'icon' => 'drumstick-bite'
    ],
    'pondeuse' => [
        'name' => 'Pondeuse',
        'duration' => '126 jours (début ponte vers 18 sem.)',
        'weight' => '1,6 – 1,8 kg',
        'feeding' => 'Démarrage (0-6 sem.) : protéines 20%<br>Croissance (7-18 sem.) : protéines 16-18%<br>Ponte : calcium 3,5-4%',
        'temperature' => '18-24°C (température idéale)',
        'vaccines' => 'Gumboro (J7), Newcastle (J14), Bronchite (J21), Variole (J28)',
        'health' => 'Coccidiose, prolapsus, carence en calcium',
        'advice' => 'Fournir coquilles d’huîtres. Lumière 14-16h/jour.',
        'description' => 'Spécialisée œufs. Besoin constant en calcium.',
        'icon' => 'egg'
    ],
    'bleu_hollande' => [
        'name' => 'Bleu de Hollande',
        'duration' => '70 jours (fermier)',
        'weight' => '2,0 – 2,3 kg',
        'feeding' => 'Démarrage (0-10j) : protéines 22%<br>Croissance : 20%<br>Finition : 18% + parcours',
        'temperature' => 'Rustique, supporte bien les variations',
        'vaccines' => 'Gumboro (J7), Newcastle (J14), Bronchite (J21)',
        'health' => 'Coccidiose, parasites externes',
        'advice' => 'Accès à un parcours herbeux. Complément maïs.',
        'description' => 'Race rustique, chair savoureuse.',
        'icon' => 'feather-alt'
    ],
    'goliath' => [
        'name' => 'Goliath',
        'duration' => '42 jours',
        'weight' => '3,5 – 4,0 kg',
        'feeding' => 'Démarrage : protéines 24%<br>Croissance : 22%<br>Finition : 20%',
        'temperature' => 'Chauffage important (34°C au début)',
        'vaccines' => 'Gumboro (J7), Newcastle (J14), Bronchite (J21)',
        'health' => 'Problèmes de pattes, stress thermique',
        'advice' => 'Surveiller les pattes. Densité limitée.',
        'description' => 'Poulet très lourd, croissance rapide.',
        'icon' => 'weight-hanging'
    ],
    'cou_nu' => [
        'name' => 'Cou nu',
        'duration' => '70-80 jours',
        'weight' => '2,5 – 3,0 kg',
        'feeding' => 'Démarrage : 22%<br>Croissance : 20%<br>Finition : 18% + maïs',
        'temperature' => 'Support chaleur, sensible au froid',
        'vaccines' => 'Gumboro (J7), Newcastle (J14), Bronchite (J21)',
        'health' => 'Parasites, coccidiose',
        'advice' => 'Protéger du froid en hiver. Accès herbeux.',
        'description' => 'Très rustique, excellente chair.',
        'icon' => 'bone'
    ],
    'faverolles' => [
        'name' => 'Faverolles',
        'duration' => '120 jours (ponte)',
        'weight' => '3,0 – 3,5 kg',
        'feeding' => 'Démarrage 20%, croissance 16%, ponte/finition adaptée',
        'temperature' => 'Rustique, supporte variations',
        'vaccines' => 'Gumboro, Newcastle, Bronchite, Variole',
        'health' => 'Coccidiose, problèmes de pattes',
        'advice' => 'Élevage en liberté recommandé.',
        'description' => 'Double finition (chair et œufs).',
        'icon' => 'dove'
    ],
    'marans' => [
        'name' => 'Marans',
        'duration' => '120 jours (ponte)',
        'weight' => '3,5 – 4,0 kg',
        'feeding' => 'Démarrage 20%, croissance 16-18%, ponte riche calcium',
        'temperature' => 'Rustique, résiste au froid',
        'vaccines' => 'Gumboro (J7), Newcastle (J14), Bronchite (J21)',
        'health' => 'Coccidiose, parasites',
        'advice' => 'Reconnue pour œufs brun foncé.',
        'description' => 'Œufs chocolat, chair fine.',
        'icon' => 'crown'
    ],
    'bresse' => [
        'name' => 'Bresse',
        'duration' => '120 jours',
        'weight' => '2,2 – 2,5 kg',
        'feeding' => 'Démarrage 20%, croissance 18%, finition lait caillé + maïs',
        'temperature' => 'Supporte hivers rigoureux',
        'vaccines' => 'Gumboro (J7), Newcastle (J14), Bronchite (J21)',
        'health' => 'Coccidiose, mycoplasmose',
        'advice' => 'Élevage en plein air indispensable.',
        'description' => 'Race de luxe, chair mondialement réputée.',
        'icon' => 'crown'
    ]
];

// Conseil personnalisé pour le lot
$batch_advice = null;
if ($batch && isset($breeds[$batch_type])) {
    $start = new DateTime($batch['start_date']);
    $age = $start->diff(new DateTime())->days;
    $batch_advice = [
        'name' => $batch['name'],
        'breed_name' => $breeds[$batch_type]['name'],
        'age' => $age,
        'advice' => $breeds[$batch_type]['advice']
    ];
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Conseils préventifs - Poulplume</title>
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
            padding: 40px 30px;
            min-height: 100vh;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
        }

        /* Barre de sélection */
        .selector-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            padding: 15px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            border: 1px solid var(--border-light);
        }
        .selector-group {
            display: flex;
            align-items: center;
            gap: 15px;
            background: var(--white-glass);
            padding: 5px 20px;
            border-radius: 50px;
        }
        .selector-group label {
            font-weight: 600;
            font-size: 0.85rem;
        }
        .selector-group select {
            background: transparent;
            border: none;
            font-family: 'Inter', sans-serif;
            padding: 8px 0;
            font-size: 0.9rem;
            color: var(--text-dark);
            cursor: pointer;
            outline: none;
        }

        /* Carte conseil personnalisé du lot */
        .personal-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 40px;
            padding: 25px 35px;
            margin-bottom: 40px;
            border-left: 6px solid var(--green);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
        }
        .personal-text h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 8px;
        }
        .advice-badge {
            background: var(--green);
            color: white;
            padding: 8px 20px;
            border-radius: 60px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        /* Fiche race détaillée (affichée uniquement sur sélection) */
        .breed-detail {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            overflow: hidden;
            border: 1px solid var(--border-light);
            margin-top: 20px;
        }
        .breed-header {
            padding: 20px 30px;
            color: white;
            display: flex;
            align-items: center;
            gap: 15px;
            background: linear-gradient(135deg, var(--green), var(--green-dark));
        }
        .breed-header i {
            font-size: 2rem;
            color: var(--yellow);
        }
        .breed-header h2 {
            font-size: 1.5rem;
            font-weight: 700;
            color: white;
        }
        .breed-body {
            padding: 30px;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 25px;
        }
        .info-block {
            background: var(--white-glass);
            border-radius: 28px;
            padding: 18px;
            border: 1px solid var(--border-light);
        }
        .info-block h4 {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.95rem;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--green);
            border-bottom: 1px solid var(--border-light);
            padding-bottom: 8px;
        }
        .info-block p, .info-block ul {
            font-size: 0.85rem;
            line-height: 1.5;
            color: var(--text-dark);
        }
        .info-block ul {
            padding-left: 20px;
            margin-top: 5px;
        }
        .info-block li {
            margin: 5px 0;
        }
        .empty-selection {
            text-align: center;
            padding: 60px 30px;
            background: var(--white-glass-card);
            border-radius: 48px;
            color: var(--text-gray);
        }

        /* Bouton accueil réduit */
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
            transition: 0.2s;
            margin-top: 40px;
        }
        .home-btn i { font-size: 0.7rem; }
        .home-btn:hover {
            background: #e6b800;
            transform: translateY(-1px);
        }
        .text-center { text-align: center; }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open { transform: translateX(0); }
            .burger-btn { display: block; }
            .main-content { margin-left: 0; }
            .breed-body { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .selector-card { flex-direction: column; align-items: stretch; }
            .selector-group { justify-content: space-between; }
            .personal-card { flex-direction: column; align-items: flex-start; }
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
    <div class="container">
        <!-- Barre de sélection lot + race -->
        <div class="selector-card">
            <div class="selector-group">
                <label><i class="fas fa-chicken"></i> Lot :</label>
                <select id="batchSelect">
                    <?php foreach($all_batches as $b): ?>
                        <option value="<?php echo $b['id']; ?>" <?php echo ($selected_batch_id == $b['id']) ? 'selected' : ''; ?>><?php echo htmlspecialchars($b['name']); ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="selector-group">
                <label><i class=""></i> Race :</label>
                <select id="breedSelect">
                    <option value=""> Sélectionnez une race </option>
                    <?php foreach ($breeds as $key => $breed): ?>
                        <option value="<?php echo $key; ?>"><?php echo $breed['name']; ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <!-- Conseil personnalisé du lot actif -->
        <?php if ($batch_advice): ?>
        <div class="personal-card">
            <div class="personal-text">
                <h3><i class="" style="color: var(--yellow); margin-right: 8px;"></i> Conseil pour <?php echo htmlspecialchars($batch_advice['name']); ?></h3>
                <p>Race <?php echo $batch_advice['breed_name']; ?> – Âge : <?php echo $batch_advice['age']; ?> jours</p>
                <p><?php echo $batch_advice['advice']; ?></p>
            </div>
            <div class="advice-badge">Recommandation Poulplume</div>
        </div>
        <?php elseif (empty($all_batches)): ?>
        <div class="empty-selection" style="margin-bottom: 30px;">
            <i class="fas fa-chicken" style="font-size: 2.5rem; color: var(--yellow); margin-bottom: 10px; display: block;"></i>
            <p>Aucun lot actif. Créez un lot pour des conseils personnalisés.</p>
            <a href="add_lot.php" class="btn-primary" style="background: var(--green); display: inline-block; margin-top: 10px; padding: 8px 20px; border-radius: 40px; color: white; text-decoration: none;">Créer un lot</a>
        </div>
        <?php endif; ?>

        <!-- Zone d'affichage de la fiche race sélectionnée -->
        <div id="breedDetailContainer"></div>

        <!-- Bouton Accueil -->
        <div class="text-center">
            <a href="dashboard.php" class="home-btn"><i class="fas fa-home"></i> Accueil</a>
        </div>
    </div>
</div>

<script>
    const breeds = <?php echo json_encode($breeds, JSON_UNESCAPED_UNICODE); ?>;
    const breedSelect = document.getElementById('breedSelect');
    const container = document.getElementById('breedDetailContainer');

    function displayBreedDetail(breedKey) {
        const b = breeds[breedKey];
        if (!b) {
            container.innerHTML = '<div class="empty-selection">Sélectionnez une race pour afficher ses conseils.</div>';
            return;
        }

        // Construction de la chaîne d'alimentation (remplacement des <br>)
        const feedingHtml = b.feeding.replace(/<br>/g, '<br>');

        const html = `
            <div class="breed-detail">
                <div class="breed-header">
                    <i class="-${b.icon}"></i>
                    <h2>${b.name}</h2>
                </div>
                <div class="breed-body">
                    <div class="info-block">
                        <h4><i class=""></i> Durée & poids</h4>
                        <p><strong>Durée :</strong> ${b.duration}</p>
                        <p><strong>Poids moyen :</strong> ${b.weight}</p>
                    </div>
                    <div class="info-block">
                        <h4><i class="-half"></i> Température ambiante</h4>
                        <p>${b.temperature}</p>
                    </div>
                    <div class="info-block">
                        <h4><i class=""></i> Vaccins clés</h4>
                        <p>${b.vaccines}</p>
                    </div>
                    <div class="info-block">
                        <h4><i class=""></i> Problèmes fréquents</h4>
                        <p>${b.health}</p>
                    </div>
                    <div class="info-block">
                        <h4><i class="-alt"></i> Alimentation</h4>
                        <p>${feedingHtml}</p>
                    </div>
                    <div class="info-block">
                        <h4><i class=""></i> Conseils spécifiques</h4>
                        <p>${b.advice}</p>
                        <p style="margin-top: 10px;"><strong>Description :</strong> ${b.description}</p>
                    </div>
                </div>
            </div>
        `;
        container.innerHTML = html;
    }

    breedSelect.addEventListener('change', function() {
        if (this.value) {
            displayBreedDetail(this.value);
        } else {
            container.innerHTML = '<div class="empty-selection">Sélectionnez une race pour afficher ses conseils.</div>';
        }
    });

    // Au chargement, si aucune race sélectionnée
    if (!breedSelect.value) {
        container.innerHTML = '<div class="empty-selection">Sélectionnez une race pour afficher ses conseils.</div>';
    }

    // Gestion sidebar et changement de lot
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
    document.getElementById('batchSelect').addEventListener('change', function() {
        window.location.href = 'conseils.php?batch_id=' + this.value;
    });
</script>
</body>
</html>