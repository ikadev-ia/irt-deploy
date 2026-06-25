<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();

if (!isset($_SESSION['user_id']) || $_SESSION['user_role'] != 'admin') {
    header("Location: login.php");
    exit();
}

$message = '';
$error = '';

// Supprimer un utilisateur
if (isset($_GET['delete'])) {
    $user_id = (int)$_GET['delete'];
    
    if ($user_id == $_SESSION['user_id']) {
        $error = "Impossible de supprimer votre propre compte.";
    } else {
        try {
            $conn->exec("PRAGMA foreign_keys = OFF");
            
            $tables = ['chat_history', 'notifications', 'transactions', 'subscriptions'];
            foreach ($tables as $table) {
                try {
                    $conn->prepare("DELETE FROM $table WHERE user_id = ?")->execute([$user_id]);
                } catch (Exception $e) {}
            }
            
            $stmt = $conn->prepare("SELECT id FROM batches WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $batches = $stmt->fetchAll();
            
            foreach ($batches as $batch) {
                try {
                    $conn->prepare("DELETE FROM daily_tracking WHERE batch_id = ?")->execute([$batch['id']]);
                } catch (Exception $e) {}
                try {
                    $conn->prepare("DELETE FROM batch_vaccines WHERE batch_id = ?")->execute([$batch['id']]);
                } catch (Exception $e) {}
            }
            
            $conn->prepare("DELETE FROM batches WHERE user_id = ?")->execute([$user_id]);
            $conn->prepare("DELETE FROM users WHERE id = ?")->execute([$user_id]);
            $conn->exec("PRAGMA foreign_keys = ON");
            
            $message = "Utilisateur supprimé avec succès.";
        } catch (Exception $e) {
            $conn->exec("PRAGMA foreign_keys = ON");
            $error = "Erreur lors de la suppression.";
        }
    }
}

// Changer le statut d'un utilisateur (activer/désactiver)
if (isset($_GET['status']) && isset($_GET['id'])) {
    $user_id = (int)$_GET['id'];
    $new_status = $_GET['status'] == 'active' ? 'active' : 'pending';
    
    if ($user_id != $_SESSION['user_id']) {
        $conn->prepare("UPDATE users SET status = ? WHERE id = ?")->execute([$new_status, $user_id]);
        $message = "Statut de l'utilisateur mis à jour.";
    }
}

// Récupérer tous les utilisateurs
$users = $conn->query("SELECT id, name, email, role, status, package, created_at FROM users ORDER BY role DESC, id ASC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=yes">
    <title>Gestion des utilisateurs - Poulplume</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Inter', sans-serif;
            background: #f1f5f9;
            min-height: 100vh;
            padding: 30px;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
        }
        
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
        }
        
        .header h1 {
            font-size: 1.5rem;
            font-weight: 600;
            color: #0f172a;
        }
        
        .header h1 i {
            color: #14B53A;
            margin-right: 10px;
        }
        
        .btn-back {
            background: white;
            color: #475569;
            padding: 8px 16px;
            border-radius: 8px;
            text-decoration: none;
            font-size: 0.875rem;
            font-weight: 500;
            border: 1px solid #e2e8f0;
            transition: all 0.2s;
        }
        
        .btn-back:hover {
            background: #f8fafc;
            border-color: #cbd5e1;
        }
        
        .stats {
            display: flex;
            gap: 20px;
            margin-bottom: 30px;
        }
        
        .stat-card {
            background: white;
            padding: 20px 25px;
            border-radius: 12px;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
            border: 1px solid #e2e8f0;
            flex: 1;
        }
        
        .stat-card .label {
            font-size: 0.75rem;
            text-transform: uppercase;
            color: #64748b;
            font-weight: 600;
            letter-spacing: 0.5px;
        }
        
        .stat-card .value {
            font-size: 1.75rem;
            font-weight: 700;
            color: #0f172a;
            margin-top: 5px;
        }
        
        .card {
            background: white;
            border-radius: 12px;
            border: 1px solid #e2e8f0;
            overflow: hidden;
            box-shadow: 0 1px 3px rgba(0,0,0,0.05);
        }
        
        .card-header {
            padding: 18px 24px;
            border-bottom: 1px solid #e2e8f0;
            background: #f8fafc;
        }
        
        .card-header h2 {
            font-size: 1rem;
            font-weight: 600;
            color: #0f172a;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
        }
        
        th {
            text-align: left;
            padding: 14px 16px;
            background: #f8fafc;
            font-weight: 600;
            font-size: 0.75rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #64748b;
            border-bottom: 1px solid #e2e8f0;
        }
        
        td {
            padding: 14px 16px;
            font-size: 0.875rem;
            color: #334155;
            border-bottom: 1px solid #e2e8f0;
        }
        
        tr:last-child td {
            border-bottom: none;
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.7rem;
            font-weight: 600;
            white-space: nowrap;
        }
        
        .badge-admin {
            background: #14B53A;
            color: white;
        }
        
        .badge-user {
            background: #e2e8f0;
            color: #475569;
        }
        
        .badge-active {
            background: #dcfce7;
            color: #166534;
        }
        
        .badge-pending {
            background: #fed7aa;
            color: #9a3412;
        }
        
        .btn-icon {
            background: none;
            border: none;
            cursor: pointer;
            font-size: 1rem;
            padding: 5px;
            margin: 0 3px;
            border-radius: 6px;
            transition: all 0.2s;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }
        
        .btn-delete {
            color: #dc2626;
        }
        
        .btn-delete:hover {
            background: #fee2e2;
        }
        
        .btn-activate {
            color: #16a34a;
        }
        
        .btn-activate:hover {
            background: #dcfce7;
        }
        
        .btn-suspend {
            color: #f97316;
        }
        
        .btn-suspend:hover {
            background: #fed7aa;
        }
        
        .alert {
            padding: 12px 16px;
            border-radius: 8px;
            margin-bottom: 20px;
            font-size: 0.875rem;
        }
        
        .alert-success {
            background: #dcfce7;
            color: #166534;
            border: 1px solid #bbf7d0;
        }
        
        .alert-error {
            background: #fee2e2;
            color: #991b1b;
            border: 1px solid #fecaca;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-muted {
            color: #94a3b8;
        }

        /* ============================================
                   RESPONSIVITÉ - TABLEAU 100% VISIBLE
                   ============================================ */
        
        /* Bureau et tablettes larges */
        @media (min-width: 769px) {
            .table-col-mobile {
                display: none !important;
            }
            .table-col-desktop {
                display: table-cell !important;
            }
        }
        
        /* Tablettes et téléphones - on cache les colonnes non essentielles */
        @media (max-width: 768px) {
            body {
                padding: 10px;
            }
            
            .header {
                flex-direction: column;
                align-items: flex-start;
                gap: 10px;
                margin-bottom: 15px;
            }
            
            .header h1 {
                font-size: 1.1rem;
            }
            
            .btn-back {
                font-size: 0.75rem;
                padding: 6px 12px;
            }
            
            .stats {
                flex-wrap: wrap;
                gap: 8px;
                margin-bottom: 15px;
            }
            
            .stat-card {
                flex: 1 1 calc(33.33% - 8px);
                padding: 12px 10px;
                min-width: 80px;
            }
            
            .stat-card .value {
                font-size: 1.2rem;
            }
            
            .stat-card .label {
                font-size: 0.6rem;
            }
            
            .card-header {
                padding: 12px 14px;
            }
            
            .card-header h2 {
                font-size: 0.85rem;
            }
            
            /* On cache les colonnes non prioritaires */
            .table-col-email {
                display: none !important;
            }
            
            .table-col-package {
                display: none !important;
            }
            
            .table-col-created {
                display: none !important;
            }
            
            /* Réduction des tailles */
            th {
                padding: 10px 8px;
                font-size: 0.6rem;
            }
            
            td {
                padding: 10px 8px;
                font-size: 0.75rem;
            }
            
            .badge {
                font-size: 0.6rem;
                padding: 2px 8px;
            }
            
            .btn-icon {
                font-size: 0.9rem;
                padding: 8px 6px;
                min-width: 36px;
                min-height: 36px;
            }
            
            .btn-icon i {
                font-size: 0.85rem;
            }
            
            .alert {
                font-size: 0.75rem;
                padding: 8px 12px;
            }
        }
        
        /* Très petits téléphones (< 450px) */
        @media (max-width: 450px) {
            body {
                padding: 5px;
            }
            
            .header h1 {
                font-size: 0.9rem;
            }
            
            .btn-back {
                font-size: 0.6rem;
                padding: 4px 8px;
            }
            
            .stat-card {
                padding: 6px 4px;
                flex: 1 1 100%;
            }
            
            .stat-card .value {
                font-size: 0.9rem;
            }
            
            .stat-card .label {
                font-size: 0.45rem;
            }
            
            /* On cache encore plus pour les très petits écrans */
            .table-col-id {
                display: none !important;
            }
            
            .table-col-role {
                display: none !important;
            }
            
            th {
                padding: 6px 5px;
                font-size: 0.5rem;
            }
            
            td {
                padding: 6px 5px;
                font-size: 0.65rem;
            }
            
            .badge {
                font-size: 0.5rem;
                padding: 1px 5px;
            }
            
            .btn-icon {
                font-size: 0.8rem;
                padding: 6px 4px;
                min-width: 32px;
                min-height: 32px;
            }
            
            .btn-icon i {
                font-size: 0.75rem;
            }
            
            .card-header {
                padding: 6px 8px;
            }
            
            .card-header h2 {
                font-size: 0.65rem;
            }
        }
        
        /* Téléphones moyens (451px - 600px) */
        @media (min-width: 451px) and (max-width: 600px) {
            .table-col-id {
                display: none !important;
            }
            
            th {
                padding: 8px 6px;
                font-size: 0.55rem;
            }
            
            td {
                padding: 8px 6px;
                font-size: 0.7rem;
            }
            
            .stat-card {
                flex: 1 1 calc(50% - 8px);
            }
        }
        
        /* Orientation paysage sur téléphone */
        @media (max-height: 500px) and (orientation: landscape) {
            body {
                padding: 5px;
            }
            
            .header {
                flex-direction: row;
                align-items: center;
                justify-content: space-between;
                margin-bottom: 8px;
            }
            
            .header h1 {
                font-size: 0.85rem;
            }
            
            .btn-back {
                font-size: 0.55rem;
                padding: 3px 8px;
            }
            
            .stats {
                gap: 4px;
                margin-bottom: 8px;
            }
            
            .stat-card {
                padding: 4px 6px;
                flex: 1;
            }
            
            .stat-card .value {
                font-size: 0.8rem;
            }
            
            .stat-card .label {
                font-size: 0.4rem;
            }
            
            .table-col-email {
                display: none !important;
            }
            
            .table-col-package {
                display: none !important;
            }
            
            .table-col-created {
                display: none !important;
            }
            
            th {
                padding: 4px 4px;
                font-size: 0.45rem;
            }
            
            td {
                padding: 4px 4px;
                font-size: 0.55rem;
            }
            
            .badge {
                font-size: 0.45rem;
                padding: 1px 4px;
            }
            
            .btn-icon {
                font-size: 0.7rem;
                padding: 4px 3px;
                min-width: 28px;
                min-height: 28px;
            }
            
            .btn-icon i {
                font-size: 0.65rem;
            }
            
            .card-header {
                padding: 4px 8px;
            }
            
            .card-header h2 {
                font-size: 0.6rem;
            }
        }
        
        /* Dark mode */
        @media (prefers-color-scheme: dark) {
            body {
                background: #0f172a;
            }
            
            .stat-card,
            .card,
            .btn-back {
                background: #1e293b;
                border-color: #334155;
            }
            
            .card-header {
                background: #1a2332;
                border-color: #334155;
            }
            
            .header h1,
            .card-header h2,
            .stat-card .value {
                color: #f1f5f9;
            }
            
            th {
                background: #1a2332;
                color: #94a3b8;
                border-color: #334155;
            }
            
            td {
                color: #cbd5e1;
                border-color: #334155;
            }
            
            .badge-user {
                background: #334155;
                color: #94a3b8;
            }
            
            .btn-back {
                color: #94a3b8;
            }
            
            .btn-back:hover {
                background: #2d3a4f;
            }
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1><i class="fas fa-users"></i> Gestion des utilisateurs</h1>
            <a href="dashboard.php" class="btn-back"><i class="fas fa-arrow-left"></i> Retour au tableau de bord</a>
        </div>
        
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo htmlspecialchars($message); ?></div>
        <?php endif; ?>
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo htmlspecialchars($error); ?></div>
        <?php endif; ?>
        
        <?php
        $total_users = count($users);
        $active_users = count(array_filter($users, function($u) { return $u['status'] == 'active'; }));
        $admin_count = count(array_filter($users, function($u) { return $u['role'] == 'admin'; }));
        ?>
        
        <div class="stats">
            <div class="stat-card">
                <div class="label">Total utilisateurs</div>
                <div class="value"><?php echo $total_users; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Comptes actifs</div>
                <div class="value"><?php echo $active_users; ?></div>
            </div>
            <div class="stat-card">
                <div class="label">Administrateurs</div>
                <div class="value"><?php echo $admin_count; ?></div>
            </div>
        </div>
        
        <div class="card">
            <div class="card-header">
                <h2>Liste des utilisateurs</h2>
            </div>
            <div style="overflow-x: auto; -webkit-overflow-scrolling: touch;">
                <table>
                    <thead>
                        <tr>
                            <th class="table-col-id">ID</th>
                            <th>Nom</th>
                            <th class="table-col-email">Email</th>
                            <th class="table-col-role">Rôle</th>
                            <th class="table-col-package">Forfait</th>
                            <th>Statut</th>
                            <th class="table-col-created">Inscription</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($users)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted" style="padding: 40px;">
                                    Aucun utilisateur enregistré
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($users as $user): ?>
                            <tr>
                                <td class="table-col-id"><?php echo $user['id']; ?></td>
                                <td><strong><?php echo htmlspecialchars($user['name']); ?></strong></td>
                                <td class="table-col-email"><?php echo htmlspecialchars($user['email']); ?></td>
                                <td class="table-col-role">
                                    <span class="badge <?php echo $user['role'] == 'admin' ? 'badge-admin' : 'badge-user'; ?>">
                                        <?php echo $user['role'] == 'admin' ? 'Admin' : 'Éleveur'; ?>
                                    </span>
                                </td>
                                <td class="table-col-package"><?php echo ucfirst($user['package'] ?? 'Standard'); ?></td>
                                <td>
                                    <span class="badge <?php echo $user['status'] == 'active' ? 'badge-active' : 'badge-pending'; ?>">
                                        <?php echo $user['status'] == 'active' ? 'Actif' : 'Attente'; ?>
                                    </span>
                                </td>
                                <td class="table-col-created"><?php echo date('d/m/Y', strtotime($user['created_at'])); ?></td>
                                <td>
                                    <?php if ($user['id'] != $_SESSION['user_id']): ?>
                                        <?php if ($user['status'] == 'active'): ?>
                                            <a href="?status=pending&id=<?php echo $user['id']; ?>" class="btn-icon btn-suspend" title="Désactiver">
                                                <i class="fas fa-ban"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="?status=active&id=<?php echo $user['id']; ?>" class="btn-icon btn-activate" title="Activer">
                                                <i class="fas fa-check-circle"></i>
                                            </a>
                                        <?php endif; ?>
                                        <a href="?delete=<?php echo $user['id']; ?>" class="btn-icon btn-delete" title="Supprimer" onclick="return confirm('Supprimer définitivement cet utilisateur ?')">
                                            <i class="fas fa-trash"></i>
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted" style="font-size: 0.7rem;">Actuel</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</body>
</html>