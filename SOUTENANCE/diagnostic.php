<?php
/**
 * Diagnostic vétérinaire intelligent pour poulets
 * Analyse les symptômes et fournit un diagnostic spécifique avec recommandations.
 * Enregistre l'historique dans un fichier CSV.
 */

// ====== SESSION DÉMARRÉE ICI ======
// Vérifier si la session est déjà démarrée avant de la lancer
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Vérifier si l'utilisateur est connecté
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Inclure la base de données APRÈS la session
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();

// Récupérer les infos utilisateur
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'] ?? 'user';
$user_name = $_SESSION['user_name'] ?? 'Utilisateur';

$csvFile = 'diagnostics.csv';

function saveDiagnostic($data) {
    global $csvFile;
    $file = fopen($csvFile, 'a');
    if ($file) {
        fputcsv($file, $data);
        fclose($file);
    }
}

// Fonction de diagnostic intelligente
function analyzeSymptoms($color, $behavior, $appearance) {
    $severity = 0;
    $issues = [];
    $recommendations = [];

    switch ($color) {
        case 'normal':
            $issues[] = "Excréments normaux.";
            break;
        case 'blanc':
            $issues[] = "Excréments blancs - possible infection bactérienne (Pullorose, Salmonellose).";
            $severity += 2;
            $recommendations[] = "Isolez les sujets touchés. Consultez un vétérinaire pour antibiogramme. Désinfection stricte du poulailler.";
            break;
        case 'vert':
            $issues[] = "Excréments verts - souvent liés à un jeûne ou à une maladie virale (Newcastle).";
            $severity += 3;
            $recommendations[] = "URGENCE : isolement, vaccination si possible, contact vétérinaire immédiat.";
            break;
        case 'noir':
            $issues[] = "Excréments noirs - présence de sang digéré (hémorragie interne).";
            $severity += 3;
            $recommendations[] = "URGENCE VETERINAIRE. Risque de coccidiose sévère ou empoisonnement.";
            break;
        case 'jaune':
            $issues[] = "Excréments jaunes mousseux - infection parasitaire (coccidiose) ou hépatite.";
            $severity += 2;
            $recommendations[] = "Traitez avec un anticoccidien (Amprolium) pendant 5 jours. Maintenez la litière sèche.";
            break;
        case 'liquide':
            $issues[] = "Diarrhée liquide - déshydratation rapide. Causes : stress, alimentation, bactéries.";
            $severity += 1;
            $recommendations[] = "Ajoutez des électrolytes dans l'eau. Vérifiez l'alimentation. Surveillez l'évolution.";
            break;
        case 'sang':
            $issues[] = "Excréments sanglants - signe majeur de coccidiose sévère.";
            $severity += 3;
            $recommendations[] = "URGENCE : traitement anticoccidien immédiat + désinfection totale du bâtiment.";
            break;
        default:
            $issues[] = "Couleur des excréments non spécifiée.";
    }

    switch ($behavior) {
        case 'normal':
            $issues[] = "Comportement normal, actif.";
            break;
        case 'apathique':
            $issues[] = "Apathie, abattement - signe de maladie générale (fièvre, infection).";
            $severity += 2;
            $recommendations[] = "Placez les sujets faibles à part. Vérifiez la température ambiante. Consultez si persistance.";
            break;
        case 'isole':
            $issues[] = "Isolement du groupe - souvent un signe précoce de maladie.";
            $severity += 1;
            $recommendations[] = "Surveillez attentivement. Isolez le poulet malade pour éviter la propagation.";
            break;
        case 'mange_moins':
            $issues[] = "Perte d'appétit - peut indiquer un problème digestif ou infectieux.";
            $severity += 1;
            $recommendations[] = "Proposez une alimentation appétente (mash humide). Vérifiez l'état du jabot.";
            break;
        case 'boit_plus':
            $issues[] = "Consommation excessive d'eau - signe de fièvre, diabète ou insuffisance rénale.";
            $severity += 1;
            $recommendations[] = "Contrôlez la température. Ajoutez des vitamines. Consultez si persistant.";
            break;
        case 'tremblements':
            $issues[] = "Tremblements nerveux - possible maladie de Newcastle, carence, empoisonnement.";
            $severity += 3;
            $recommendations[] = "URGENCE VETERINAIRE. Isolement strict.";
            break;
        default:
            $issues[] = "Comportement non spécifié.";
    }

    switch ($appearance) {
        case 'normal':
            $issues[] = "Apparence normale.";
            break;
        case 'plumes_herissees':
            $issues[] = "Plumes hérissées - frilosité ou maladie (fièvre).";
            $severity += 1;
            $recommendations[] = "Augmentez la température si nécessaire. Surveillez l'état général.";
            break;
        case 'yeux_larmoyants':
            $issues[] = "Yeux larmoyants ou gonflés - infection respiratoire (mycoplasmose, sinusite).";
            $severity += 2;
            $recommendations[] = "Traitez avec un antibiotique adapté (tylosine) dans l'eau. Aérez sans courant d'air.";
            break;
        case 'crete_pale':
            $issues[] = "Crête pâle ou violacée - anémie, insuffisance cardiaque ou infection grave.";
            $severity += 2;
            $recommendations[] = "Contrôle vétérinaire rapide. Vérifiez la présence de parasites (poux, tiques).";
            break;
        case 'respiration_bruyante':
            $issues[] = "Respiration bruyante (râles, sifflements) - atteinte respiratoire avancée.";
            $severity += 3;
            $recommendations[] = "URGENCE : isolement, antibiothérapie, amélioration de la ventilation.";
            break;
        case 'toux':
            $issues[] = "Toux ou éternuements - début d'infection respiratoire.";
            $severity += 2;
            $recommendations[] = "Ajoutez des vitamines A et E. Surveillez l'évolution. Un antibiotique peut être nécessaire.";
            break;
        case 'paralysie':
            $issues[] = "Paralysie, cou tordu - symptôme neurologique grave (Newcastle, botulisme).";
            $severity += 4;
            $recommendations[] = "URGENCE ABSOLUE : isolement, euthanasie si souffrance, analyse de laboratoire.";
            break;
        default:
            $issues[] = "Apparence non spécifiée.";
    }

    if ($severity == 0) {
        $status = "Etat de santé normal";
        $advice = "Aucun problème détecté. Continuez les bonnes pratiques d'élevage : alimentation équilibrée, eau propre, litière sèche, vaccination à jour.";
    } elseif ($severity <= 2) {
        $status = "Anomalies légères à surveiller";
        $advice = implode(" ", $recommendations);
        if (empty($recommendations)) {
            $advice = "Surveillez attentivement les symptômes dans les prochains jours. Améliorez l'hygiène et la nutrition.";
        }
    } else {
        $status = "Alerte sanitaire - Intervention nécessaire";
        $advice = implode(" ", array_unique($recommendations));
    }

    $symptomDetail = implode(" ", $issues);

    return [
        'status' => $status,
        'details' => $symptomDetail,
        'severity' => $severity,
        'advice' => $advice
    ];
}

$result = null;
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $color = $_POST['color'] ?? '';
    $behavior = $_POST['behavior'] ?? '';
    $appearance = $_POST['appearance'] ?? '';

    $validColors = ['normal', 'blanc', 'vert', 'noir', 'jaune', 'liquide', 'sang'];
    $validBehaviors = ['normal', 'apathique', 'isole', 'mange_moins', 'boit_plus', 'tremblements'];
    $validAppearances = ['normal', 'plumes_herissees', 'yeux_larmoyants', 'crete_pale', 'respiration_bruyante', 'toux', 'paralysie'];

    if (!in_array($color, $validColors)) $color = '';
    if (!in_array($behavior, $validBehaviors)) $behavior = '';
    if (!in_array($appearance, $validAppearances)) $appearance = '';

    if (empty($color) || empty($behavior) || empty($appearance)) {
        $error = "Veuillez sélectionner toutes les options.";
    } else {
        $analysis = analyzeSymptoms($color, $behavior, $appearance);
        $date = date('Y-m-d H:i:s');
        saveDiagnostic([$date, $color, $behavior, $appearance, $analysis['status'], $analysis['details'], $analysis['advice']]);
        $result = $analysis;
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Diagnostic - Poulplume</title>
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
        }
        .brand .plume {
            color: var(--white);
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
        .nav-item.active {
            background: var(--white);
            color: var(--green);
            box-shadow: 0 6px 14px rgba(0,0,0,0.15);
        }
        .settings-group {
            margin-top: 12px;
        }
        .settings-header {
            cursor: pointer;
            justify-content: space-between;
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
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .settings-sub a:hover {
            background: var(--yellow);
            color: #1e293b;
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

        /* Main content - centré comme enregistrement */
        .main-content {
            margin-left: 280px;
            padding: 40px 20px;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        /* Conteneur taille fixe (550px comme enregistrement) */
        .diagnostic-container {
            width: 550px;
            max-width: 95%;
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            box-shadow: var(--shadow-md);
            border: 1px solid var(--border-light);
            overflow: hidden;
        }

        /* En-tête jaune */
        .diagnostic-header {
            background: var(--yellow);
            color: #1e293b;
            padding: 25px 25px;
            text-align: center;
            position: relative;
        }
        .diagnostic-header i {
            font-size: 2.2rem;
            color: var(--green);
            margin-bottom: 8px;
        }
        .diagnostic-header h1 {
            font-size: 1.5rem;
            font-weight: 800;
            margin-bottom: 4px;
        }
        .diagnostic-header p {
            font-size: 0.8rem;
            opacity: 0.8;
        }

        /* Bouton Accueil */
        .home-btn {
            position: absolute;
            top: 18px;
            right: 20px;
            background: rgba(30,41,59,0.12);
            color: #1e293b;
            padding: 4px 10px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 0.68rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 4px;
        }
        .home-btn i {
            font-size: 0.65rem;
        }
        .home-btn:hover {
            background: rgba(30,41,59,0.25);
        }

        .diagnostic-body {
            padding: 30px;
        }

        .form-group {
            margin-bottom: 22px;
        }
        label {
            display: flex;
            align-items: center;
            gap: 8px;
            font-weight: 600;
            font-size: 0.85rem;
            margin-bottom: 8px;
            color: var(--text-dark);
        }
        label i {
            color: var(--green);
            width: 20px;
        }
        select, .btn {
            width: 100%;
            padding: 12px 16px;
            border: 1px solid var(--border-light);
            border-radius: 30px;
            font-family: 'Inter', sans-serif;
            font-size: 0.9rem;
            background: var(--white-glass);
            color: var(--text-dark);
            transition: 0.2s;
            -webkit-appearance: none;
            appearance: none;
        }
        select {
            background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%23475569' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            background-repeat: no-repeat;
            background-position: right 16px center;
            padding-right: 40px;
        }
        select:focus {
            outline: none;
            border-color: var(--green);
            box-shadow: 0 0 0 2px rgba(20,181,58,0.1);
        }
        .btn {
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white;
            border: none;
            cursor: pointer;
            font-weight: 700;
            margin-top: 10px;
        }
        .btn:hover {
            background: var(--green-dark);
            transform: translateY(-1px);
            box-shadow: 0 8px 20px rgba(20,181,58,0.3);
        }

        .error {
            background: rgba(239,68,68,0.15);
            border-left: 4px solid #ef4444;
            padding: 12px 18px;
            border-radius: 24px;
            margin-bottom: 25px;
            color: #ef4444;
            font-size: 0.85rem;
        }

        .result-card {
            background: rgba(20,181,58,0.08);
            border-radius: 28px;
            padding: 20px;
            margin-top: 25px;
            border: 1px solid var(--border-light);
        }
        .result-status {
            font-size: 1rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 10px;
            margin-bottom: 12px;
        }
        .status-normal {
            color: #10b981;
        }
        .status-warning {
            color: #f59e0b;
        }
        .status-critical {
            color: #ef4444;
        }
        .result-details {
            color: var(--text-dark);
            font-size: 0.85rem;
            margin: 10px 0;
            line-height: 1.5;
        }
        .result-advice {
            background: rgba(0,0,0,0.04);
            padding: 12px 15px;
            border-radius: 20px;
            margin-top: 12px;
            font-size: 0.85rem;
            border-left: 3px solid var(--green);
        }
        .back-link {
            display: inline-block;
            margin-top: 12px;
            color: var(--green);
            text-decoration: none;
            font-weight: 600;
            font-size: 0.8rem;
        }
        hr {
            margin: 20px 0;
            border: none;
            border-top: 1px solid var(--border-light);
        }
        footer {
            text-align: center;
            font-size: 0.7rem;
            color: var(--text-gray);
            padding: 15px;
            background: var(--white-glass);
            border-top: 1px solid var(--border-light);
        }

        /* ============================================
                   RESPONSIVITÉ RENFORCÉE - RIEN N'EST CHANGÉ
                   ============================================ */

        /* Tablettes et petits écrans */
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
                padding: 20px 12px;
            }
            .diagnostic-container {
                max-width: 98%;
                border-radius: 32px;
            }
        }

        /* Téléphones moyens */
        @media (max-width: 768px) {
            body { padding: 0; }
            .diagnostic-header {
                padding: 16px 18px;
            }
            .diagnostic-header h1 {
                font-size: 1.2rem;
            }
            .diagnostic-header p {
                font-size: 0.7rem;
            }
            .diagnostic-header i {
                font-size: 1.8rem;
            }
            .home-btn {
                position: static;
                display: inline-block;
                margin-top: 8px;
                font-size: 0.65rem;
                padding: 4px 10px;
            }
            .diagnostic-body {
                padding: 18px 16px;
            }
            .form-group {
                margin-bottom: 16px;
            }
            label {
                font-size: 0.75rem;
            }
            label i {
                width: 16px;
                font-size: 0.8rem;
            }
            select, .btn {
                padding: 10px 14px;
                font-size: 0.8rem;
                border-radius: 24px;
            }
            .result-card {
                padding: 16px;
                border-radius: 20px;
            }
            .result-status {
                font-size: 0.9rem;
            }
            .result-details {
                font-size: 0.75rem;
            }
            .result-advice {
                font-size: 0.75rem;
                padding: 10px 12px;
                border-radius: 16px;
            }
            .error {
                font-size: 0.75rem;
                padding: 10px 14px;
                border-radius: 20px;
            }
            footer {
                font-size: 0.6rem;
                padding: 10px;
            }
        }

        /* Très petits téléphones (< 450px) */
        @media (max-width: 450px) {
            .main-content {
                padding: 10px 5px;
            }
            .diagnostic-container {
                max-width: 100%;
                border-radius: 24px;
            }
            .diagnostic-header {
                padding: 14px 12px;
            }
            .diagnostic-header h1 {
                font-size: 1rem;
            }
            .diagnostic-header p {
                font-size: 0.6rem;
            }
            .diagnostic-header i {
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
            .diagnostic-body {
                padding: 14px 12px;
            }
            .form-group {
                margin-bottom: 12px;
            }
            label {
                font-size: 0.65rem;
                gap: 5px;
            }
            label i {
                width: 14px;
                font-size: 0.7rem;
            }
            select, .btn {
                padding: 8px 12px;
                font-size: 0.7rem;
                border-radius: 20px;
            }
            .result-card {
                padding: 12px;
                border-radius: 16px;
                margin-top: 16px;
            }
            .result-status {
                font-size: 0.8rem;
            }
            .result-details {
                font-size: 0.65rem;
            }
            .result-advice {
                font-size: 0.65rem;
                padding: 8px 10px;
                border-radius: 14px;
            }
            .error {
                font-size: 0.65rem;
                padding: 8px 12px;
                border-radius: 16px;
                margin-bottom: 16px;
            }
            .back-link {
                font-size: 0.7rem;
            }
            hr {
                margin: 12px 0;
            }
            .burger-btn {
                top: 10px;
                left: 10px;
                font-size: 0.9rem;
                padding: 8px 12px;
            }
            footer {
                font-size: 0.55rem;
                padding: 8px;
            }
        }

        /* Orientation paysage sur téléphone */
        @media (max-height: 500px) and (orientation: landscape) {
            .main-content {
                padding: 10px 8px;
                align-items: flex-start;
                padding-top: 15px;
            }
            .diagnostic-container {
                max-width: 100%;
                border-radius: 20px;
            }
            .diagnostic-header {
                padding: 10px 14px;
            }
            .diagnostic-header h1 {
                font-size: 0.9rem;
            }
            .diagnostic-header p {
                display: none;
            }
            .diagnostic-header i {
                font-size: 1.2rem;
                margin-bottom: 2px;
            }
            .home-btn {
                font-size: 0.5rem;
                padding: 2px 8px;
                top: 8px;
                right: 10px;
            }
            .diagnostic-body {
                padding: 10px 14px;
            }
            .form-group {
                margin-bottom: 8px;
            }
            label {
                font-size: 0.6rem;
                margin-bottom: 3px;
            }
            select, .btn {
                padding: 6px 10px;
                font-size: 0.65rem;
                border-radius: 16px;
            }
            .result-card {
                padding: 10px;
                border-radius: 14px;
                margin-top: 12px;
            }
            .result-status {
                font-size: 0.75rem;
                margin-bottom: 6px;
            }
            .result-details {
                font-size: 0.6rem;
                margin: 5px 0;
            }
            .result-advice {
                font-size: 0.6rem;
                padding: 6px 10px;
                border-radius: 12px;
                margin-top: 6px;
            }
            .error {
                font-size: 0.6rem;
                padding: 6px 10px;
                border-radius: 14px;
                margin-bottom: 12px;
            }
            hr {
                margin: 10px 0;
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
            footer {
                font-size: 0.5rem;
                padding: 6px;
            }
        }

        /* Dark mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: url('Images/AR10.png') no-repeat center center fixed;
                background-size: cover;
            }
            .diagnostic-container {
                background: var(--white-glass-card);
            }
            select {
                background: rgba(30, 41, 59, 0.6);
                color: #f1f5f9;
                border-color: rgba(51, 65, 85, 0.5);
                background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='8' viewBox='0 0 12 8'%3E%3Cpath d='M1 1l5 5 5-5' stroke='%2394a3b8' stroke-width='1.5' fill='none' stroke-linecap='round'/%3E%3C/svg%3E");
            }
            select option {
                background: #1e293b;
                color: #f1f5f9;
            }
            .result-card {
                background: rgba(20,181,58,0.12);
                border-color: rgba(20,181,58,0.2);
            }
            .result-advice {
                background: rgba(255,255,255,0.05);
            }
            .home-btn {
                background: rgba(255,255,255,0.08);
                color: #94a3b8;
            }
            .home-btn:hover {
                background: rgba(255,255,255,0.15);
            }
            footer {
                background: rgba(30, 41, 59, 0.6);
            }
            .error {
                background: rgba(239,68,68,0.2);
            }
        }
    </style>
</head>
<body>

<!-- SIDEBAR -->
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
    <div class="diagnostic-container">
        <div class="diagnostic-header">
            <i class="fas fa-stethoscope"></i>
            <h1>Diagnostic vétérinaire</h1>
            <p>Analyse des symptômes en temps réel</p>
            <a href="dashboard.php" class="home-btn">
                <i class="fas fa-home"></i> Accueil
            </a>
        </div>
        <div class="diagnostic-body">
            <?php if (isset($error)): ?>
                <div class="error"><i class="fas fa-exclamation-triangle"></i> <?php echo htmlspecialchars($error); ?></div>
            <?php endif; ?>
            <form method="POST">
                <div class="form-group">
                    <label><i class=""></i> Couleur des excréments</label>
                    <select name="color" required>
                        <option value="">Sélectionnez</option>
                        <option value="normal">Normales (brun foncé)</option>
                        <option value="blanc">Blanches</option>
                        <option value="vert">Vertes</option>
                        <option value="noir">Noires</option>
                        <option value="jaune">Jaunes</option>
                        <option value="liquide">Liquides / aqueuses</option>
                        <option value="sang">Sanglantes</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class=""></i> Comportement général</label>
                    <select name="behavior" required>
                        <option value="">Sélectionnez</option>
                        <option value="normal">Normal</option>
                        <option value="apathique">Apathique, abattu</option>
                        <option value="isole">Isolé du groupe</option>
                        <option value="mange_moins">Mange moins</option>
                        <option value="boit_plus">Boit plus que d'habitude</option>
                        <option value="tremblements">Tremblements</option>
                    </select>
                </div>
                <div class="form-group">
                    <label><i class=""></i> Apparence physique</label>
                    <select name="appearance" required>
                        <option value="">Sélectionnez</option>
                        <option value="normal">Normale</option>
                        <option value="plumes_herissees">Plumes hérissées</option>
                        <option value="yeux_larmoyants">Yeux larmoyants</option>
                        <option value="crete_pale">Crête pâle</option>
                        <option value="respiration_bruyante">Respiration bruyante</option>
                        <option value="toux">Toux / éternuements</option>
                        <option value="paralysie">Paralysie / cou tordu</option>
                    </select>
                </div>
                <button type="submit" name="submit" class="btn">
                    <i class=""></i> Analyser
                </button>
            </form>

            <?php if ($result): ?>
                <hr>
                <div class="result-card">
                    <div class="result-status <?php
                        if ($result['severity'] == 0) echo 'status-normal';
                        elseif ($result['severity'] <= 2) echo 'status-warning';
                        else echo 'status-critical';
                    ?>">
                        <?php echo $result['status']; ?>
                    </div>
                    <div class="result-details">
                        <strong> Analyse des symptômes :</strong><br>
                        <?php echo htmlspecialchars($result['details']); ?>
                    </div>
                    <div class="result-advice">
                        <strong> Recommandations :</strong><br>
                        <?php echo nl2br(htmlspecialchars($result['advice'])); ?>
                    </div>
                    <a href="diagnostic.php" class="back-link">
                        <i class="fas fa-redo-alt"></i> Nouvelle analyse
                    </a>
                </div>
            <?php endif; ?>
        </div>
        <footer>
            <i class=""></i> Historique des diagnostics sauvegardé localement
        </footer>
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