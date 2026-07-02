<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();

if (isset($_SESSION['user_id'])) {
    header("Location: dashboard.php");
    exit();
}

$error = '';
$success = '';

$packages = [
    'classique' => [
        'name' => 'Classique',
        'plans' => [
            ['id' => 'classique_mensuel', 'name' => 'Mensuel', 'months' => 1, 'price' => 2000],
            ['id' => 'classique_trimestriel', 'name' => 'Trimestriel', 'months' => 3, 'price' => 6000],
            ['id' => 'classique_annuel', 'name' => 'Annuel', 'months' => 12, 'price' => 24000]
        ]
    ],
    'standard' => [
        'name' => 'Standard',
        'plans' => [
            ['id' => 'standard_mensuel', 'name' => 'Mensuel', 'months' => 1, 'price' => 5000],
            ['id' => 'standard_trimestriel', 'name' => 'Trimestriel', 'months' => 3, 'price' => 20000],
            ['id' => 'standard_annuel', 'name' => 'Annuel', 'months' => 12, 'price' => 60000]
        ]
    ],
    'premium' => [
        'name' => 'Premium',
        'plans' => [
            ['id' => 'premium_mensuel', 'name' => 'Mensuel', 'months' => 1, 'price' => 10000],
            ['id' => 'premium_trimestriel', 'name' => 'Trimestriel', 'months' => 3, 'price' => 30000],
            ['id' => 'premium_annuel', 'name' => 'Annuel', 'months' => 12, 'price' => 120000]
        ]
    ]
];

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['register'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $plan_id = $_POST['plan_id'] ?? '';
    $payment_method = $_POST['payment_method'] ?? '';
    $phone = trim($_POST['phone'] ?? '');
    
    $selected_plan = null;
    $selected_package = null;
    foreach ($packages as $pkg_key => $pkg) {
        foreach ($pkg['plans'] as $plan) {
            if ($plan['id'] == $plan_id) {
                $selected_plan = $plan;
                $selected_package = $pkg_key;
                break;
            }
        }
    }
    
    if ($password !== $confirm_password) {
        $error = "Les mots de passe ne correspondent pas";
    } elseif (strlen($password) < 6) {
        $error = "Le mot de passe doit contenir au moins 6 caractères";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = "Email invalide";
    } elseif (!$selected_plan) {
        $error = "Veuillez sélectionner un forfait";
    } elseif (empty($payment_method)) {
        $error = "Veuillez sélectionner un moyen de paiement";
    } elseif (in_array($payment_method, ['orange', 'wave', 'moov']) && empty($phone)) {
        $error = "Veuillez renseigner votre numéro de téléphone";
    } else {
        $check = $conn->prepare("SELECT id FROM users WHERE email = ?");
        $check->execute([$email]);
        if ($check->fetch()) {
            $error = "Cet email est déjà utilisé";
        } else {
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);
            
            $stmt = $conn->prepare("INSERT INTO users (name, email, password, role, package, status) 
                                    VALUES (?, ?, ?, 'farmer', ?, 'pending')");
            $stmt->execute([$name, $email, $hashed_password, $selected_package]);
            
            $user_id = $conn->lastInsertId();
            $transaction_id = 'POUL-' . date('Ymd') . '-' . strtoupper(substr(uniqid(), -6));
            $expiry_date = date('Y-m-d', strtotime('+' . $selected_plan['months'] . ' months'));
            
            $trans = $conn->prepare("INSERT INTO transactions (user_id, amount, payment_method, transaction_id, package_name, status, phone, expiry_date) 
                                     VALUES (?, ?, ?, ?, ?, 'pending', ?, ?)");
            $trans->execute([$user_id, $selected_plan['price'], $payment_method, $transaction_id, $selected_plan['name'], $phone, $expiry_date]);
            
            $payment_names = ['orange' => 'Orange Money', 'wave' => 'Wave', 'moov' => 'Moov Money', 'card' => 'Carte Bancaire', 'paypal' => 'PayPal'];
            $payment_name = $payment_names[$payment_method] ?? $payment_method;
            
            $success = "
            <div style='background: white; border-radius: 16px; overflow: hidden; border: 1px solid #e2e8f0;'>
                <div style='background: #14B53A; padding: 20px; text-align: center; border-bottom: 1px solid #e2e8f0;'>
                    <div style='width: 60px; height: 60px; background: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 12px;'>
                        <i class='fas fa-clock' style='font-size: 1.8rem; color: #14B53A;'></i>
                    </div>
                    <h2 style='color: white; font-size: 1.3rem; font-weight: 600;'>Paiement en attente</h2>
                    <p style='color: rgba(255,255,255,0.85); font-size: 0.85rem; margin-top: 5px;'>Votre inscription a été enregistrée</p>
                </div>
                
                <div style='padding: 20px;'>
                    <table style='width: 100%; border-collapse: collapse; margin-bottom: 20px;'>
                        <tr style='border-bottom: 1px solid #e2e8f0;'>
                            <td style='padding: 10px 0; color: #64748b; font-size: 0.8rem;'>Transaction</td>
                            <td style='padding: 10px 0; text-align: right; font-weight: 600; color: #1e293b; font-size: 0.85rem;'>$transaction_id</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #e2e8f0;'>
                            <td style='padding: 10px 0; color: #64748b; font-size: 0.8rem;'>Montant</td>
                            <td style='padding: 10px 0; text-align: right; font-weight: 700; color: #14B53A; font-size: 1rem;'>" . number_format($selected_plan['price'], 0, ',', ' ') . " FCFA</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #e2e8f0;'>
                            <td style='padding: 10px 0; color: #64748b; font-size: 0.8rem;'>Forfait</td>
                            <td style='padding: 10px 0; text-align: right; font-weight: 500; color: #1e293b; font-size: 0.85rem;'>" . ucfirst($selected_package) . " - " . $selected_plan['name'] . "</td>
                        </tr>
                        <tr style='border-bottom: 1px solid #e2e8f0;'>
                            <td style='padding: 10px 0; color: #64748b; font-size: 0.8rem;'>Paiement</td>
                            <td style='padding: 10px 0; text-align: right; font-weight: 500; color: #1e293b; font-size: 0.85rem;'>$payment_name</td>
                        </tr>
                    </table>
                    
                    <div style='background: #f0fdf4; padding: 12px; border-radius: 10px; margin-bottom: 15px; text-align: center;'>
                        <i class='fas fa-info-circle' style='color: #14B53A; font-size: 0.85rem; margin-right: 8px;'></i>
                        <span style='color: #166534; font-size: 0.8rem;'>Votre compte sera activé après validation par l'administrateur</span>
                    </div>
                    
                    <div style='background: #f8fafc; padding: 15px; border-radius: 10px; text-align: center; border: 1px solid #e2e8f0;'>
                        <i class='fas fa-phone-alt' style='color: #14B53A; font-size: 1rem; margin-bottom: 8px; display: block;'></i>
                        <p style='color: #1e293b; font-size: 0.85rem; margin-bottom: 5px;'><strong>Aucune confirmation reçue ?</strong></p>
                        <p style='color: #475569; font-size: 0.8rem;'>Contactez le <strong style='color: #14B53A;'>+223 91 78 87 783</strong></p>
                    </div>
                    
                    <div style='margin-top: 20px; text-align: center;'>
                        <a href='login.php' style='display: inline-block; background: #14B53A; color: white; padding: 10px 20px; border-radius: 8px; text-decoration: none; font-size: 0.85rem; font-weight: 500;'>Retour à la connexion</a>
                    </div>
                </div>
            </div>
            ";
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['login'])) {
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    
    $stmt = $conn->prepare("SELECT * FROM users WHERE email = ?");
    $stmt->execute([$email]);
    $user = $stmt->fetch();
    
    if ($user && password_verify($password, $user['password'])) {
        if ($user['status'] != 'active') {
            $error = "Compte en attente de validation. Contactez +223 91 78 87 783";
        } else {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email'] = $user['email'];
            $_SESSION['user_role'] = $user['role'];
            header("Location: dashboard.php");
            exit();
        }
    } else {
        $error = "Email ou mot de passe incorrect";
    }
}
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Connexion · Poulplume</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: 'Inter', sans-serif;
            background: url('Images/AR10.png') no-repeat center center fixed;
            background-size: cover;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 20px;
        }
        .container {
            background: rgba(255,255,255,0.96);
            backdrop-filter: blur(10px);
            border-radius: 32px;
            padding: 40px;
            max-width: 520px;
            width: 100%;
            box-shadow: 0 20px 40px rgba(0,0,0,0.15);
        }
        .logo { text-align: center; margin-bottom: 24px; }
        .logo img { width: 80px; height: 80px; border-radius: 50%; }
        .logo h1 { font-size: 1.8rem; margin-top: 8px; }
        .logo .poul { color: #FCD116; }
        .logo .plume { color: #14B53A; }
        .tabs { display: flex; gap: 10px; margin-bottom: 24px; border-bottom: 2px solid #e2e8f0; }
        .tab { flex: 1; text-align: center; padding: 10px; cursor: pointer; font-weight: 600; color: #64748b; transition: 0.3s; }
        .tab.active { color: #14B53A; border-bottom: 2px solid #14B53A; margin-bottom: -2px; }
        .form-group { margin-bottom: 18px; }
        .form-group label { display: block; margin-bottom: 6px; font-weight: 500; font-size: 0.85rem; color: #1e293b; }
        .form-group input { width: 100%; padding: 12px 15px; border: 1px solid #e2e8f0; border-radius: 12px; font-family: inherit; font-size: 0.9rem; outline: none; background: white; }
        .form-group input:focus { border-color: #14B53A; box-shadow: 0 0 0 2px rgba(20,181,58,0.1); }
        .btn { width: 100%; padding: 14px; background: linear-gradient(135deg, #14B53A, #0d8a2f); color: white; border: none; border-radius: 12px; font-weight: 600; cursor: pointer; transition: 0.3s; font-size: 0.95rem; }
        .btn:hover { transform: translateY(-2px); box-shadow: 0 4px 12px rgba(20,181,58,0.3); }
        .error { background: #fee2e2; color: #dc2626; padding: 12px; border-radius: 10px; margin-bottom: 20px; font-size: 0.85rem; }
        .success { padding: 0; margin-bottom: 20px; }
        
        .package-card { border: 1px solid #e2e8f0; border-radius: 12px; margin-bottom: 12px; overflow: hidden; }
        .package-header { padding: 14px 18px; cursor: pointer; display: flex; justify-content: space-between; align-items: center; background: white; }
        .package-header:hover { background: #f8fafc; }
        .package-title { font-weight: 600; font-size: 0.95rem; color: #1e293b; }
        .package-icon { color: #94a3b8; font-size: 0.8rem; }
        .package-plans { display: none; border-top: 1px solid #e2e8f0; background: white; }
        .package-plans.active { display: block; }
        .plan-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 18px; cursor: pointer; border-bottom: 1px solid #e2e8f0; }
        .plan-item:last-child { border-bottom: none; }
        .plan-item:hover { background: #f8fafc; }
        .plan-item.selected { background: #f0fdf4; border-left: 3px solid #14B53A; }
        .plan-name { font-weight: 500; font-size: 0.85rem; color: #1e293b; }
        .plan-duration { font-size: 0.7rem; color: #64748b; }
        .plan-price { font-weight: 600; color: #14B53A; font-size: 0.9rem; }
        .selection-badge { background: #f0fdf4; border: 1px solid #14B53A; border-radius: 10px; padding: 12px; margin-bottom: 18px; display: flex; justify-content: space-between; align-items: center; }
        .selection-badge.hidden { display: none; }
        .selection-label { font-size: 0.7rem; color: #64748b; text-transform: uppercase; }
        .selection-value { font-weight: 600; color: #1e293b; font-size: 0.85rem; }
        .selection-price { font-weight: 700; color: #14B53A; font-size: 1rem; }
        .payment-methods { display: flex; gap: 10px; flex-wrap: wrap; }
        .payment-method { flex: 1; min-width: 80px; border: 1px solid #e2e8f0; border-radius: 12px; padding: 10px 8px; text-align: center; cursor: pointer; background: white; }
        .payment-method:hover { border-color: #14B53A; }
        .payment-method.selected { border-color: #14B53A; background: #f0fdf4; }
        .payment-method img { width: 35px; height: 35px; object-fit: contain; margin-bottom: 5px; }
        .payment-method span { display: block; font-size: 0.65rem; font-weight: 500; color: #475569; }
        .phone-field { margin-top: 15px; display: none; }
        .phone-field.active { display: block; }
        .back-link { display: block; text-align: center; margin-top: 20px; color: #FCD116; text-decoration: none; font-size: 0.85rem; }
        @media (max-width: 480px) { .container { padding: 20px; } }
    </style>
</head>
<body>

<div class="container">
    <div class="logo">
        <img src="Images/Logo.png" alt="Poulplume">
        <h1><span class="poul">Poul</span><span class="plume">plume</span></h1>
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div class="success"><?php echo $success; ?></div>
    <?php endif; ?>

    <div class="tabs">
        <div class="tab active" onclick="showTab('login')">Connexion</div>
        <div class="tab" onclick="showTab('register')">Inscription</div>
    </div>

    <div id="loginForm">
        <form method="POST">
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="exemple@email.com">
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required placeholder="Mot de passe">
            </div>
            <button type="submit" name="login" class="btn">Se connecter</button>
        </form>
    </div>

    <div id="registerForm" style="display: none;">
        <form method="POST">
            <div class="form-group">
                <label>Nom complet</label>
                <input type="text" name="name" required placeholder="Votre nom">
            </div>
            <div class="form-group">
                <label>Email</label>
                <input type="email" name="email" required placeholder="exemple@email.com">
            </div>
            <div class="form-group">
                <label>Mot de passe</label>
                <input type="password" name="password" required placeholder="6 caractères minimum">
            </div>
            <div class="form-group">
                <label>Confirmer le mot de passe</label>
                <input type="password" name="confirm_password" required placeholder="Confirmez">
            </div>
            
            <div class="form-group">
                <label>Forfait</label>
                <div class="package-card">
                    <div class="package-header" onclick="togglePackage('classique')">
                        <span class="package-title">Classique</span>
                        <span class="package-icon"><i class="fas fa-chevron-down" id="icon-classique"></i></span>
                    </div>
                    <div class="package-plans" id="plans-classique">
                        <div class="plan-item" onclick="selectPlan('classique_mensuel', 2000, 'Mensuel', 'Classique')"><div><div class="plan-name">Mensuel</div><div class="plan-duration">1 mois</div></div><div class="plan-price">2 000 FCFA</div></div>
                        <div class="plan-item" onclick="selectPlan('classique_trimestriel', 6000, 'Trimestriel', 'Classique')"><div><div class="plan-name">Trimestriel</div><div class="plan-duration">3 mois</div></div><div class="plan-price">6 000 FCFA</div></div>
                        <div class="plan-item" onclick="selectPlan('classique_annuel', 24000, 'Annuel', 'Classique')"><div><div class="plan-name">Annuel</div><div class="plan-duration">12 mois</div></div><div class="plan-price">24 000 FCFA</div></div>
                    </div>
                </div>
                <div class="package-card">
                    <div class="package-header" onclick="togglePackage('standard')">
                        <span class="package-title">Standard</span>
                        <span class="package-icon"><i class="fas fa-chevron-down" id="icon-standard"></i></span>
                    </div>
                    <div class="package-plans" id="plans-standard">
                        <div class="plan-item" onclick="selectPlan('standard_mensuel', 5000, 'Mensuel', 'Standard')"><div><div class="plan-name">Mensuel</div><div class="plan-duration">1 mois</div></div><div class="plan-price">5 000 FCFA</div></div>
                        <div class="plan-item" onclick="selectPlan('standard_trimestriel', 20000, 'Trimestriel', 'Standard')"><div><div class="plan-name">Trimestriel</div><div class="plan-duration">3 mois</div></div><div class="plan-price">20 000 FCFA</div></div>
                        <div class="plan-item" onclick="selectPlan('standard_annuel', 60000, 'Annuel', 'Standard')"><div><div class="plan-name">Annuel</div><div class="plan-duration">12 mois</div></div><div class="plan-price">60 000 FCFA</div></div>
                    </div>
                </div>
                <div class="package-card">
                    <div class="package-header" onclick="togglePackage('premium')">
                        <span class="package-title">Premium</span>
                        <span class="package-icon"><i class="fas fa-chevron-down" id="icon-premium"></i></span>
                    </div>
                    <div class="package-plans" id="plans-premium">
                        <div class="plan-item" onclick="selectPlan('premium_mensuel', 10000, 'Mensuel', 'Premium')"><div><div class="plan-name">Mensuel</div><div class="plan-duration">1 mois</div></div><div class="plan-price">10 000 FCFA</div></div>
                        <div class="plan-item" onclick="selectPlan('premium_trimestriel', 30000, 'Trimestriel', 'Premium')"><div><div class="plan-name">Trimestriel</div><div class="plan-duration">3 mois</div></div><div class="plan-price">30 000 FCFA</div></div>
                        <div class="plan-item" onclick="selectPlan('premium_annuel', 120000, 'Annuel', 'Premium')"><div><div class="plan-name">Annuel</div><div class="plan-duration">12 mois</div></div><div class="plan-price">120 000 FCFA</div></div>
                    </div>
                </div>
                <input type="hidden" name="plan_id" id="selectedPlanId">
            </div>
            
            <div class="selection-badge hidden" id="selectionBadge">
                <div><div class="selection-label">FORFAIT SÉLECTIONNÉ</div><div class="selection-value"><span id="selectedPackageText">-</span> - <span id="selectedPlanText">-</span></div></div>
                <div class="selection-price" id="selectedPriceText">0 FCFA</div>
            </div>
            
            <div class="form-group">
                <label>Moyen de paiement</label>
                <div class="payment-methods">
                    <div class="payment-method" onclick="selectPayment('orange')"><img src="Images/Orange.png"><span>Orange Money</span></div>
                    <div class="payment-method" onclick="selectPayment('wave')"><img src="Images/Wave.png"><span>Wave</span></div>
                    <div class="payment-method" onclick="selectPayment('moov')"><img src="Images/Moov.png"><span>Moov Money</span></div>
                    <div class="payment-method" onclick="selectPayment('card')"><img src="Images/Visa.png"><span>Carte Bancaire</span></div>
                    <div class="payment-method" onclick="selectPayment('paypal')"><img src="Images/PayPal.png"><span>PayPal</span></div>
                </div>
                <input type="hidden" name="payment_method" id="selectedPayment">
                <div id="phoneField" class="phone-field">
                    <label>Numéro de téléphone</label>
                    <input type="tel" name="phone" placeholder="76XXXXXX">
                </div>
            </div>
            
            <button type="submit" name="register" class="btn">Payer et créer mon compte</button>
        </form>
    </div>

    <a href="index.html" class="back-link">Retour à l'accueil</a>
</div>

<script>
    function showTab(tab) {
        document.getElementById('loginForm').style.display = tab === 'login' ? 'block' : 'none';
        document.getElementById('registerForm').style.display = tab === 'register' ? 'block' : 'none';
        document.querySelectorAll('.tab').forEach((t, i) => {
            if ((tab === 'login' && i === 0) || (tab === 'register' && i === 1)) t.classList.add('active');
            else t.classList.remove('active');
        });
    }
    function togglePackage(pkg) {
        let plans = document.getElementById('plans-' + pkg);
        let icon = document.getElementById('icon-' + pkg);
        if (plans.classList.contains('active')) {
            plans.classList.remove('active');
            icon.classList.remove('fa-chevron-up');
            icon.classList.add('fa-chevron-down');
        } else {
            plans.classList.add('active');
            icon.classList.remove('fa-chevron-down');
            icon.classList.add('fa-chevron-up');
        }
    }
    function selectPlan(planId, price, planName, packageName) {
        document.getElementById('selectedPlanId').value = planId;
        document.getElementById('selectedPackageText').innerHTML = packageName;
        document.getElementById('selectedPlanText').innerHTML = planName;
        document.getElementById('selectedPriceText').innerHTML = price.toLocaleString() + ' FCFA';
        document.getElementById('selectionBadge').classList.remove('hidden');
        document.querySelectorAll('.plan-item').forEach(item => item.classList.remove('selected'));
        event.currentTarget.classList.add('selected');
    }
    function selectPayment(payment) {
        document.getElementById('selectedPayment').value = payment;
        document.querySelectorAll('.payment-method').forEach(m => m.classList.remove('selected'));
        event.currentTarget.classList.add('selected');
        let phoneField = document.getElementById('phoneField');
        if (payment === 'orange' || payment === 'wave' || payment === 'moov') phoneField.classList.add('active');
        else phoneField.classList.remove('active');
    }
</script>
<script src="https://formslist.com/widget.js" data-form="GM-2-8RwXB1n" data-color="#FFD700" data-title="Signalez votre paiement" data-button-text="Envoyez"></script>
</body>
</html>