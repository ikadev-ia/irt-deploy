<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }

$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];

// Création/mise à jour de la table
$conn->exec("CREATE TABLE IF NOT EXISTS transactions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    type TEXT NOT NULL,
    category TEXT NOT NULL,
    description TEXT,
    amount DECIMAL(10,2) NOT NULL,
    transaction_date DATE NOT NULL,
    batch_id INTEGER,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (batch_id) REFERENCES batches(id)
)");

$result = $conn->query("PRAGMA table_info(transactions)");
$cols = [];
while ($row = $result->fetch()) $cols[] = $row['name'];
if (!in_array('transaction_date', $cols)) {
    $conn->exec("ALTER TABLE transactions ADD COLUMN transaction_date DATE");
}

// Traitement du formulaire
$message = '';
$error = '';
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    if (isset($_POST['action'])) {
        if ($_POST['action'] == 'add') {
            $type = $_POST['type'];
            $category = $_POST['category'];
            $description = trim($_POST['description']);
            $amount = (float)$_POST['amount'];
            $transaction_date = $_POST['transaction_date'];
            $batch_id = !empty($_POST['batch_id']) ? (int)$_POST['batch_id'] : null;
            if ($amount <= 0) $error = "Montant invalide.";
            else {
                try {
                    $stmt = $conn->prepare("INSERT INTO transactions (user_id, type, category, description, amount, transaction_date, batch_id) VALUES (?,?,?,?,?,?,?)");
                    $stmt->execute([$user_id, $type, $category, $description, $amount, $transaction_date, $batch_id]);
                    $message = "Transaction ajoutée !";
                } catch (PDOException $e) { $error = "Erreur SQL : " . $e->getMessage(); }
            }
        } elseif ($_POST['action'] == 'delete') {
            $id = (int)$_POST['id'];
            $stmt = $conn->prepare("DELETE FROM transactions WHERE id = ? AND user_id = ?");
            $stmt->execute([$id, $user_id]);
            $message = "Transaction supprimée.";
        }
    }
}

// Filtres
$annee = isset($_GET['year']) ? (int)$_GET['year'] : date('Y');
$mois = isset($_GET['month']) ? (int)$_GET['month'] : 0;

$sql = "SELECT * FROM transactions WHERE user_id = ? AND strftime('%Y', transaction_date) = ?";
$params = [$user_id, $annee];
if ($mois > 0) {
    $sql .= " AND strftime('%m', transaction_date) = ?";
    $params[] = sprintf('%02d', $mois);
}
$sql .= " ORDER BY transaction_date DESC";
$stmt = $conn->prepare($sql);
$stmt->execute($params);
$transactions = $stmt->fetchAll();

$total_depenses = 0;
$total_revenus = 0;
foreach ($transactions as $t) {
    if ($t['type'] == 'depense') $total_depenses += $t['amount'];
    else $total_revenus += $t['amount'];
}
$solde = $total_revenus - $total_depenses;

// Données pour graphique (12 mois)
$monthly = [];
for ($i = 11; $i >= 0; $i--) {
    $mois_annee = date('Y-m', strtotime("-$i months"));
    $debut = "$mois_annee-01";
    $fin = date('Y-m-t', strtotime($debut));
    $stmt = $conn->prepare("SELECT SUM(CASE WHEN type='depense' THEN amount ELSE 0 END) as dep, SUM(CASE WHEN type='revenu' THEN amount ELSE 0 END) as rev
                            FROM transactions WHERE user_id = ? AND transaction_date BETWEEN ? AND ?");
    $stmt->execute([$user_id, $debut, $fin]);
    $row = $stmt->fetch();
    $monthly[] = [
        'label' => date('M Y', strtotime($debut)),
        'dep' => round($row['dep'] ?? 0, 0),
        'rev' => round($row['rev'] ?? 0, 0)
    ];
}

// Lots pour sélecteur
$batches = [];
if ($user_role == 'admin') $stmt = $conn->prepare("SELECT id, name FROM batches");
else $stmt = $conn->prepare("SELECT id, name FROM batches WHERE user_id = ?");
$stmt->execute($user_role == 'admin' ? [] : [$user_id]);
$batches = $stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Finances - Poulplume</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
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
            padding: 40px 30px;
            min-height: 100vh;
        }
        .container {
            max-width: 1300px;
            margin: 0 auto;
        }

        /* En-tête */
        .page-header {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            padding: 20px 30px;
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 20px;
            border: 1px solid var(--border-light);
        }
        .page-title h1 {
            font-size: 1.5rem;
            font-weight: 700;
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

        /* Filtres */
        .filter-bar {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 60px;
            padding: 12px 25px;
            margin-bottom: 30px;
            display: inline-flex;
            flex-wrap: wrap;
            gap: 15px;
            align-items: center;
            border: 1px solid var(--border-light);
        }
        .filter-bar select, .filter-bar button {
            padding: 8px 18px;
            border-radius: 40px;
            border: 1px solid var(--border-light);
            background: var(--white-glass);
            font-family: inherit;
            cursor: pointer;
        }
        .filter-bar button {
            background: var(--green);
            color: white;
            border: none;
            font-weight: 600;
        }
        .filter-bar button:hover {
            background: var(--green-dark);
        }

        /* KPI */
        .kpi-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 25px;
            margin-bottom: 40px;
        }
        .kpi-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 24px;
            border: 1px solid var(--border-light);
            transition: 0.2s;
        }
        .kpi-card:hover {
            transform: translateY(-4px);
            box-shadow: var(--shadow-md);
        }
        .kpi-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 15px;
        }
        .kpi-header i {
            font-size: 2rem;
            color: var(--yellow);
        }
        .kpi-value {
            font-size: 2.2rem;
            font-weight: 800;
        }
        .depenses .kpi-value { color: #ef4444; }
        .revenus .kpi-value { color: var(--green); }
        .solde .kpi-value { color: var(--yellow); }
        .kpi-label {
            color: var(--text-gray);
            font-size: 0.8rem;
            margin-top: 8px;
        }

        /* Graphique */
        .chart-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 25px;
            margin-bottom: 40px;
            border: 1px solid var(--border-light);
        }
        .chart-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        /* Formulaire */
        .form-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 25px;
            margin-bottom: 40px;
            border: 1px solid var(--border-light);
        }
        .form-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        .form-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            align-items: end;
        }
        .input-group {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }
        .input-group label {
            font-size: 0.7rem;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: var(--text-gray);
        }
        .input-group input, .input-group select {
            padding: 12px 16px;
            border-radius: 40px;
            border: 1px solid var(--border-light);
            background: var(--white-glass);
            font-family: inherit;
            font-size: 0.85rem;
            color: var(--text-dark);
        }
        .input-group input:focus, .input-group select:focus {
            outline: none;
            border-color: var(--green);
        }
        .btn-submit {
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 40px;
            font-weight: 700;
            cursor: pointer;
            transition: 0.2s;
        }
        .btn-submit:hover {
            background: var(--green-dark);
            transform: translateY(-1px);
        }

        /* Tableau */
        .table-card {
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 32px;
            padding: 25px;
            overflow-x: auto;
            border: 1px solid var(--border-light);
        }
        .table-card h3 {
            font-size: 1.1rem;
            font-weight: 700;
            margin-bottom: 20px;
            display: flex;
            align-items: center;
            gap: 8px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
        }
        th, td {
            padding: 12px 10px;
            text-align: left;
            border-bottom: 1px solid var(--border-light);
        }
        th {
            font-weight: 600;
            color: var(--text-gray);
            font-size: 0.75rem;
            text-transform: uppercase;
        }
        .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 30px;
            font-size: 0.7rem;
            font-weight: 600;
        }
        .badge-depense { background: rgba(239,68,68,0.15); color: #ef4444; }
        .badge-revenu { background: rgba(20,181,58,0.15); color: var(--green); }
        .btn-delete {
            background: none;
            border: none;
            color: #ef4444;
            cursor: pointer;
            font-size: 1rem;
        }
        .btn-delete:hover { transform: scale(1.1); }
        .message {
            padding: 12px 20px;
            border-radius: 40px;
            margin-bottom: 20px;
            font-size: 0.85rem;
        }
        .success { background: rgba(20,181,58,0.15); color: var(--green-dark); }
        .error { background: rgba(239,68,68,0.15); color: #ef4444; }
        .text-center { text-align: center; }

        @media (max-width: 1024px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.open { transform: translateX(0); }
            .burger-btn { display: block; }
            .main-content { margin-left: 0; }
            .kpi-grid { grid-template-columns: 1fr; }
        }
        @media (max-width: 640px) {
            .main-content { padding: 20px 16px; }
            .form-grid { grid-template-columns: 1fr; }
            .filter-bar { flex-direction: column; align-items: stretch; }
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
        <a href="finances.php" class="nav-item active"><i class="fas fa-coins"></i> Finances</a>
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
        <!-- En-tête -->
        <div class="page-header">
            <div class="page-title">
                <h1><i class="fas fa-coins" style="color: var(--green);"></i> Gestion financière</h1>
                <p style="font-size: 0.8rem;">Suivez vos dépenses et revenus</p>
            </div>
            <a href="dashboard.php" class="home-btn"><i class="fas fa-home"></i> Accueil</a>
        </div>

        <!-- Filtres -->
        <div class="filter-bar">
            <form method="GET" style="display: flex; gap: 12px; flex-wrap: wrap; align-items: center;">
                <label>Année :</label>
                <select name="year">
                    <?php for($y=date('Y')-2;$y<=date('Y')+1;$y++): ?>
                        <option value="<?php echo $y; ?>" <?php echo ($annee==$y)?'selected':''; ?>><?php echo $y; ?></option>
                    <?php endfor; ?>
                </select>
                <label>Mois :</label>
                <select name="month">
                    <option value="0">Tous</option>
                    <?php for($m=1;$m<=12;$m++): ?>
                        <option value="<?php echo $m; ?>" <?php echo ($mois==$m)?'selected':''; ?>><?php echo date('F', mktime(0,0,0,$m,1)); ?></option>
                    <?php endfor; ?>
                </select>
                <button type="submit"><i class=""></i> Appliquer</button>
            </form>
        </div>

        <!-- KPI -->
        <div class="kpi-grid">
            <div class="kpi-card depenses">
                <div class="kpi-header"><span>Dépenses totales</span><i class=""></i></div>
                <div class="kpi-value"><?php echo number_format($total_depenses,0,',',' '); ?> FCFA</div>
                <div class="kpi-label">Période sélectionnée</div>
            </div>
            <div class="kpi-card revenus">
                <div class="kpi-header"><span>Revenus totaux</span><i class=""></i></div>
                <div class="kpi-value"><?php echo number_format($total_revenus,0,',',' '); ?> FCFA</div>
                <div class="kpi-label">Période sélectionnée</div>
            </div>
            <div class="kpi-card solde">
                <div class="kpi-header"><span>Solde net</span><i class=""></i></div>
                <div class="kpi-value <?php echo $solde>=0?'text-success':'text-danger'; ?>"><?php echo ($solde>=0?'+':''); ?> <?php echo number_format($solde,0,',',' '); ?> FCFA</div>
                <div class="kpi-label">Bénéfice / Perte</div>
            </div>
        </div>

        <!-- Graphique -->
        <div class="chart-card">
            <h3><i class="" style="color: var(--green);"></i> Évolution sur 12 mois</h3>
            <canvas id="financeChart" style="height: 280px;"></canvas>
        </div>

        <!-- Formulaire d'ajout -->
        <div class="form-card">
            <h3><i class="fas fa-plus-circle" style="color: var(--green);"></i> Nouvelle transaction</h3>
            <?php if($message): ?><div class="message success"><?php echo $message; ?></div><?php endif; ?>
            <?php if($error): ?><div class="message error"><?php echo $error; ?></div><?php endif; ?>
            <form method="POST">
                <input type="hidden" name="action" value="add">
                <div class="form-grid">
                    <div class="input-group">
                        <label>Type</label>
                        <select name="type">
                            <option value="depense"> Dépense</option>
                            <option value="revenu">  Revenu</option>
                        </select> 
                    </div>
                    <div class="input-group">
                        <label>Catégorie</label>
                        <select name="category">
                            <optgroup label="Dépenses">
                                <option>Nourriture</option>
                                <option>Vaccins</option>
                                <option>Matériel</option>
                                <option>Achat de poussins</option>
                                <option>Autre</option>
                            </optgroup>
                            <optgroup label="Revenus">
                                <option>Vente de poulets</option>
                                <option>Vente d'œufs</option>
                                <option>Autre</option>
                            </optgroup>
                        </select>
                    </div>
                    <div class="input-group">
                        <label>Description</label>
                        <input type="text" name="description" required>
                    </div>
                    <div class="input-group">
                        <label>Montant (FCFA)</label>
                        <input type="number" step="1" name="amount" required>
                    </div>
                    <div class="input-group">
                        <label>Date</label>
                        <input type="date" name="transaction_date" value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    <div class="input-group">
                        <label>Lot associé</label>
                        <select name="batch_id">
                            <option value=""> Aucun </option>
                            <?php foreach($batches as $b): ?>
                                <option value="<?php echo $b['id']; ?>"><?php echo htmlspecialchars($b['name']); ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="input-group">
                        <button type="submit" class="btn-submit"><i class=""></i> Enregistrer</button>
                    </div>
                </div>
            </form>
        </div>

        <!-- Tableau des transactions -->
        <div class="table-card">
            <h3><i class="fas fa-list-ul" style="color: var(--green);"></i> Historique des transactions</h3>
            <div style="overflow-x: auto; margin-top: 20px;">
                <table>
                    <thead>
                        <tr><th>Date</th><th>Type</th><th>Catégorie</th><th>Description</th><th>Montant</th><th>Lot</th><th></th></tr>
                    </thead>
                    <tbody>
                        <?php if(empty($transactions)): ?>
                            <tr><td colspan="7" class="text-center">Aucune transaction pour cette période</td></tr>
                        <?php else: ?>
                            <?php foreach($transactions as $t): ?>
                            <tr>
                                <td><?php echo date('d/m/Y', strtotime($t['transaction_date'])); ?></td>
                                <td><span class="badge badge-<?php echo $t['type']; ?>"><?php echo $t['type']=='depense'?'Dépense':'Revenu'; ?></span></td>
                                <td><?php echo htmlspecialchars($t['category']); ?></td>
                                <td><?php echo htmlspecialchars($t['description']); ?></td>
                                <td><strong><?php echo number_format($t['amount'],0,',',' '); ?> FCFA</strong></td>
                                <td><?php echo $t['batch_id'] ? 'Lot #'.$t['batch_id'] : '-'; ?></td>
                                <td>
                                    <form method="POST" onsubmit="return confirm('Supprimer cette transaction ?')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="id" value="<?php echo $t['id']; ?>">
                                        <button class="btn-delete"><i class="fas fa-trash"></i></button>
                                    </form>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
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

    const monthlyData = <?php echo json_encode($monthly); ?>;
    const labels = monthlyData.map(m => m.label);
    const depenses = monthlyData.map(m => m.dep);
    const revenus = monthlyData.map(m => m.rev);
    new Chart(document.getElementById('financeChart'), {
        type: 'line',
        data: {
            labels: labels,
            datasets: [
                { label: 'Dépenses', data: depenses, borderColor: '#ef4444', backgroundColor: 'rgba(239,68,68,0.05)', tension: 0.3, fill: true, pointBackgroundColor: '#ef4444', pointBorderColor: '#fff', pointRadius: 4 },
                { label: 'Revenus', data: revenus, borderColor: '#14B53A', backgroundColor: 'rgba(20,181,58,0.05)', tension: 0.3, fill: true, pointBackgroundColor: '#14B53A', pointBorderColor: '#fff', pointRadius: 4 }
            ]
        },
        options: { responsive: true, maintainAspectRatio: true, plugins: { tooltip: { mode: 'index', intersect: false }, legend: { position: 'top' } } }
    });
</script>
</body>
</html>