<?php
// On suppose que la session est démarrée par la page parente
// Calcul du nombre total d'articles dans le panier
$total_articles = 0;
if (isset($_SESSION['panier']) && is_array($_SESSION['panier'])) {
    foreach ($_SESSION['panier'] as $item) {
        if (is_array($item) && isset($item['quantity'])) {
            $total_articles += (int)$item['quantity'];
        }
    }
}

// Vérification sécurisée du rôle utilisateur
$is_admin = (isset($_SESSION['user']['role']) && $_SESSION['user']['role'] === 'admin');
?>
<style>
    /* Styles originaux */
    .custom-navbar {
        position: fixed;
        top: 20px;
        left: 50%;
        transform: translateX(-50%);
        width: 90%;
        max-width: 1100px;
        height: 70px;
        background: rgba(255, 255, 255, 0.85);
        backdrop-filter: blur(12px);
        -webkit-backdrop-filter: blur(12px);
        border: 1px solid rgba(255, 255, 255, 0.4);
        padding: 0 35px;
        border-radius: 50px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
        z-index: 9999;
        transition: all 0.3s ease;
    }

    .navbar-brand {
        position: relative;
        text-decoration: none;
        width: 180px;
        height: 100%;
        display: flex;
        align-items: center;
    }

    .brand-logo-img {
        position: absolute;
        top: 50%;
        left: 0;
        height: 130px;
        width: auto;
        object-fit: contain;
        animation: logoPulse 3s ease-in-out infinite;
    }

    @keyframes logoPulse {
        0%, 100% { transform: translateY(-50%) scale(1); filter: drop-shadow(0px 4px 8px rgba(0, 0, 0, 0.1)); }
        50% { transform: translateY(-50%) scale(1.06); filter: drop-shadow(0px 8px 16px rgba(46, 125, 50, 0.25)); }
    }

    .navbar-links {
        display: flex;
        gap: 20px;
        align-items: center;
        list-style: none;
        margin: 0;
        padding: 0;
    }

    .navbar-links a {
        text-decoration: none;
        color: #333;
        font-weight: 600;
        font-size: 14px;
        transition: color 0.3s ease;
    }

    .navbar-links a:hover {
        color: #2e7d32;
    }

    .nav-cart-btn {
        background: #2e7d32;
        color: white !important;
        padding: 8px 18px;
        border-radius: 25px;
        display: flex;
        align-items: center;
        gap: 6px;
        transition: all 0.3s ease !important;
    }

    .nav-cart-btn:hover {
        background: #1b5e20 !important;
    }

    .cart-badge {
        background: #ffffff;
        color: #2e7d32;
        font-size: 11px;
        font-weight: bold;
        padding: 2px 7px;
        border-radius: 20px;
    }

    .navbar-spacer { height: 110px; width: 100%; }

    /* Ajout simple pour le menu burger (responsivité) */
    .burger { display: none; cursor: pointer; font-size: 24px; color: #333; }

    @media (max-width: 850px) {
        .burger { display: block; }
        .navbar-links {
            display: none;
            position: absolute;
            top: 80px;
            left: 50%;
            transform: translateX(-50%);
            flex-direction: column;
            background: rgba(255, 255, 255, 0.95);
            padding: 20px;
            border-radius: 20px;
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.15);
            width: 90%;
        }
        .navbar-links.active { display: flex; }
    }
</style>

<nav class="custom-navbar">
    <a href="index.php" class="navbar-brand">
        <img src="images/logo.png" alt="TAKAMÍ" class="brand-logo-img">
    </a>

    <!-- Icône Burger -->
    <div class="burger" onclick="document.querySelector('.navbar-links').classList.toggle('active')">☰</div>

    <ul class="navbar-links">
        <li><a href="index.php">Accueil</a></li>
        <li><a href="produits.php">Nos Produits</a></li>
        <li><a href="contact.php">Contact</a></li>
        <li><a href="A_propos.php">A propos</a></li>

        <?php if (isset($_SESSION['user'])): ?>
            <?php if ($is_admin): ?>
                <li><a href="admin_dashboard.php" style="color: #ffa000;">💼 Admin</a></li>
            <?php else: ?>
                <li><a href="Facture.php">Ma Facture</a></li>
            <?php endif; ?>
            
            <li>
                <a href="panier.php" class="nav-cart-btn">
                    <span>Panier 🛒</span>
                    <?php if ($total_articles > 0): ?>
                        <span class="cart-badge"><?php echo $total_articles; ?></span>
                    <?php endif; ?>
                </a>
            </li>
            <li><a href="logout.php" style="color: #dc3545; font-size: 12px;">Déconnexion</a></li>
        <?php else: ?>
            <li><a href="login.php" class="nav-cart-btn" style="background: #444;">Connexion 🔑</a></li>
        <?php endif; ?>
    </ul>
</nav>

<div class="navbar-spacer"></div>