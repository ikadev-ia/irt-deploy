<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// FAQ données
$faq_categories = [
    'Démarrage' => [
        ['question' => 'Comment créer un lot ?', 'answer' => 'Allez dans "Nouveau lot" depuis le menu latéral. Remplissez le nom du lot, la date de début, le type de poulet et le nombre initial. La date de sortie est automatiquement calculée.'],
        ['question' => 'Comment accéder au tableau de bord ?', 'answer' => 'Le tableau de bord est la page d’accueil après connexion. Vous y trouverez les indicateurs clés de votre élevage.'],
        ['question' => 'Puis-je avoir plusieurs lots ?', 'answer' => 'Oui, vous pouvez créer autant de lots que nécessaire. Utilisez le sélecteur en haut de chaque page pour basculer entre eux.']
    ],
    'Enregistrement quotidien' => [
        ['question' => 'Quelles données dois-je enregistrer chaque jour ?', 'answer' => 'Température, quantité d’aliment distribuée, nombre de morts et de malades. Ces données sont essentielles pour le suivi de votre élevage.'],
        ['question' => 'Que faire si j’oublie un jour ?', 'answer' => 'Vous pouvez toujours enregistrer les jours manquants en sélectionnant la date dans le formulaire. L’historique garde la trace de tous vos enregistrements.'],
        ['question' => 'Pourquoi ai-je une recommandation alimentaire ?', 'answer' => 'Le système calcule la quantité idéale selon l’âge de vos poulets et la température ambiante, pour optimiser leur croissance.']
    ],
    'Vaccination' => [
        ['question' => 'Quand vacciner mes poulets ?', 'answer' => 'Le calendrier vaccinal vous montre les vaccins recommandés par âge. Suivez le planning et cochez les vaccins réalisés.'],
        ['question' => 'Puis-je ajouter des vaccins personnalisés ?', 'answer' => 'Contactez votre administrateur pour ajouter de nouveaux vaccins dans la base de données.']
    ],
    'Notifications' => [
        ['question' => 'À quoi servent les notifications ?', 'answer' => 'Elles vous rappellent les tâches à faire (alimentation, eau, vaccination) et vous alertent en cas de mortalité anormale ou de température critique.'],
        ['question' => 'Comment supprimer une notification ?', 'answer' => 'Cliquez sur le bouton "Supprimer" à côté de la notification. Vous pouvez aussi toutes les marquer comme lues en un clic.']
    ],
    'Finances' => [
        ['question' => 'Comment ajouter une transaction ?', 'answer' => 'Dans la page Finances, remplissez le formulaire en bas : type (dépense/revenu), catégorie, description, montant et date.'],
        ['question' => 'Puis-je associer une transaction à un lot ?', 'answer' => 'Oui, sélectionnez le lot concerné dans le formulaire. Cela vous permet de suivre la rentabilité par lot.']
    ],
    'Conseils' => [
        ['question' => 'Où trouver des conseils pour ma race ?', 'answer' => 'Dans la page "Conseils préventifs", sélectionnez votre race pour obtenir des informations sur l’alimentation, la température, les vaccins et les problèmes fréquents.'],
        ['question' => 'Les conseils sont-ils personnalisés ?', 'answer' => 'Oui, ils s’adaptent à l’âge de votre lot et à la météo actuelle.']
    ]
];

$quick_help = [
    ['icon' => 'fas fa-chicken', 'title' => 'Guide rapide', 'desc' => 'Téléchargez le manuel utilisateur PDF', 'link' => '#'],
    ['icon' => 'fas fa-video', 'title' => 'Tutoriels vidéo', 'desc' => 'Regardez nos démonstrations', 'link' => '#'],
    ['icon' => 'fas fa-headset', 'title' => 'Support technique', 'desc' => 'Nous contacter 24h/24', 'link' => '#'],
    ['icon' => 'fas fa-comments', 'title' => 'Communauté', 'desc' => 'Échangez avec d’autres éleveurs', 'link' => '#']
];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Aide - Poulplume</title>
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
            transition: transform 0.3s;
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
        .logo-img img { width: 68px; height: auto; border-radius: 50%; }
        .brand { font-size: 2rem; font-weight: 800; }
        .brand .poul { color: var(--yellow); }
        .brand .plume { color: var(--white); }
        .sidebar-nav { flex: 1; padding: 30px 16px; display: flex; flex-direction: column; gap: 8px; }
        .nav-item {
            display: flex; align-items: center; gap: 14px; padding: 12px 18px;
            border-radius: 50px; color: rgba(255,255,255,0.85); text-decoration: none;
            transition: 0.25s; font-weight: 500;
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
        .user-avatar { width: 46px; height: 46px; background: var(--yellow); border-radius: 50%; display: flex; align-items: center; justify-content: center; color: var(--green-dark); font-size: 1.2rem; }
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
            padding: 10px 14px; border-radius: 30px; cursor: pointer;
        }

        /* Main content */
        .main-content {
            margin-left: 280px;
            padding: 40px 30px;
            min-height: 100vh;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }

        /* En-tête */
        .page-header {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            padding: 25px 35px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            border: 1px solid var(--border-light);
        }
        .page-title h1 {
            font-size: 1.6rem;
            font-weight: 800;
            display: flex;
            align-items: center;
            gap: 10px;
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
            transition: 0.2s;
        }
        .home-btn:hover {
            background: #e6b800;
            transform: translateY(-1px);
        }

        /* Section titre */
        .section-title {
            font-size: 1.3rem;
            font-weight: 700;
            margin: 40px 0 20px;
            display: flex;
            align-items: center;
            gap: 10px;
            padding-bottom: 8px;
            border-bottom: 2px solid var(--yellow);
            width: fit-content;
        }

        /* Cartes aide rapide */
        .help-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 25px;
            margin-bottom: 40px;
        }
        .help-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 25px 20px;
            text-align: center;
            border: 1px solid var(--border-light);
            transition: 0.2s;
            text-decoration: none;
            display: block;
        }
        .help-card:hover {
            transform: translateY(-5px);
            box-shadow: var(--shadow-md);
        }
        .help-card i {
            font-size: 2rem;
            color: var(--green);
            margin-bottom: 15px;
        }
        .help-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        .help-card p {
            font-size: 0.8rem;
            color: var(--text-gray);
        }

        /* FAQ Accordéon */
        .faq-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(500px, 1fr));
            gap: 20px;
            margin-bottom: 40px;
        }
        .faq-category {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 20px;
            border: 1px solid var(--border-light);
        }
        .faq-category h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 15px;
            display: flex;
            align-items: center;
            gap: 8px;
            color: var(--green);
            padding-bottom: 10px;
            border-bottom: 1px solid var(--border-light);
        }
        .faq-item {
            margin-bottom: 15px;
            border-bottom: 1px solid rgba(0,0,0,0.05);
            padding-bottom: 12px;
        }
        .faq-question {
            font-weight: 600;
            font-size: 0.9rem;
            cursor: pointer;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            color: var(--text-dark);
        }
        .faq-question:hover {
            color: var(--green);
        }
        .faq-question i {
            transition: transform 0.2s;
        }
        .faq-answer {
            font-size: 0.85rem;
            color: var(--text-gray);
            line-height: 1.5;
            padding: 0 0 8px 0;
            margin-top: 5px;
            display: none;
        }
        .faq-answer.show {
            display: block;
        }

        /* Contact */
        .contact-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 30px;
            text-align: center;
            border: 1px solid var(--border-light);
            margin-top: 20px;
        }
        .contact-card h3 {
            font-size: 1.2rem;
            font-weight: 700;
            margin-bottom: 10px;
        }
        .contact-buttons {
            display: flex;
            justify-content: center;
            gap: 20px;
            margin-top: 20px;
            flex-wrap: wrap;
        }
        .contact-btn {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            background: var(--green);
            color: white;
            padding: 10px 25px;
            border-radius: 40px;
            text-decoration: none;
            font-weight: 600;
            transition: 0.2s;
        }
        .contact-btn:hover {
            background: var(--green-dark);
            transform: translateY(-2px);
        }

        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .burger-btn { display: block; }
            .main-content { margin-left: 0; }
            .faq-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .main-content { padding: 20px 16px; }
            .page-header { flex-direction: column; align-items: stretch; }
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
                <a href="aide.php" class="active"><i class="fas fa-question-circle"></i> Aide</a>
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
                <h1><i class="" style="color: var(--green);"></i> Centre d'aide</h1>
                <p style="font-size: 0.85rem;">Trouvez des réponses à vos questions</p>
            </div>
            <a href="dashboard.php" class="home-btn"><i class="fas fa-home"></i> Accueil</a>
        </div>

        <!-- Cartes aide rapide -->
        <div class="help-grid">
            <?php foreach ($quick_help as $help): ?>
            <a href="<?php echo $help['link']; ?>" class="help-card">
                <i class="<?php echo $help['icon']; ?>"></i>
                <h3><?php echo $help['title']; ?></h3>
                <p><?php echo $help['desc']; ?></p>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- FAQ par catégories -->
        <div class="section-title">
            <i class=""></i>
            <span>Foire aux questions (FAQ)</span>
        </div>
        
        <div class="faq-grid">
            <?php foreach ($faq_categories as $category => $faqs): ?>
            <div class="faq-category">
                <h3><i class="fas fa-folder-open"></i> <?php echo $category; ?></h3>
                <?php foreach ($faqs as $index => $faq): ?>
                <div class="faq-item">
                    <div class="faq-question" onclick="toggleAnswer(this)">
                        <?php echo $faq['question']; ?>
                        <i class="fas fa-chevron-down"></i>
                    </div>
                    <div class="faq-answer"><?php echo nl2br($faq['answer']); ?></div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- Contact support -->
        <div class="contact-card">
            <h3></i> Besoin d'aide supplémentaire ?</h3>
            <p>Notre équipe est disponible pour vous accompagner</p>
            <div class="contact-buttons">
                <a href="mailto:support@poulplume.com" class="contact-btn"><i class="fas fa-envelope"></i>Kfabiendiakaria@gmail.com </a>
                <a href="#" class="contact-btn"><i class="fas fa-phone"></i> +223 91 78 77 83</a>
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

    function toggleAnswer(element) {
        const answer = element.nextElementSibling;
        const icon = element.querySelector('i');
        answer.classList.toggle('show');
        if (answer.classList.contains('show')) {
            icon.style.transform = 'rotate(180deg)';
        } else {
            icon.style.transform = 'rotate(0deg)';
        }
    }
</script>
</body>
</html>