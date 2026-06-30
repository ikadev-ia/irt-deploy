<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>TAKAMI - Accueil</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600;700&display=swap" rel="stylesheet">

<style>
* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: "Poppins", sans-serif;
}

/* NAVBAR */
.navbar {
    position: fixed;
    top: 20px;
    left: 50%;
    transform: translateX(-50%);
    width: 85%;
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 15px 30px;
    border-radius: 50px;
    background: rgba(255, 255, 255, 0.2);
    backdrop-filter: blur(15px);
    -webkit-backdrop-filter: blur(15px);
    box-shadow: 0 8px 25px rgba(0, 0, 0, 0.15);
    z-index: 9999;
}

/* 1. ANIMATION DU LOGO (Pulsation douce et continue) */
.logo {
    font-weight: 700;
    color: white;
    font-size: 22px;
    text-transform: uppercase;
    letter-spacing: 1px;
    animation: logoPulse 3s ease-in-out infinite;
}

@keyframes logoPulse {
    0%, 100% { transform: scale(1); opacity: 0.9; }
    50% { transform: scale(1.05); opacity: 1; text-shadow: 0 0 10px rgba(255, 255, 255, 0.6); }
}

.nav-links {
    display: flex;
    gap: 40px;
    list-style: none;
}

.nav-links a {
    text-decoration: none;
    color: white;
    font-weight: 500;
    transition: 0.3s;
}
.nav-links a:hover {
    color: #4CAF50;
}

/* HERO SECTION */
.hero {
    height: 100vh;
    background: url('images/image.png') center/cover no-repeat;
    display: flex;
    justify-content: center;
    align-items: center;
    position: relative;
    overflow: hidden;
}

.hero::before {
    content: "";
    position: absolute;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.45);
}

.hero-content {
    position: relative;
    text-align: center;
    color: white;
    z-index: 2;
}

/* 2. ANIMATIONS DU TITRE HERO (Charbon Écologique) */
.hero h1 {
    font-size: 75px;
    font-weight: 700;
    margin-bottom: 5px;
    letter-spacing: 1px;
    /* Entrée animée pour l'ensemble du titre */
    animation: fadeInUp 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

.green {
    color: #4CAF50;
    position: relative;
    display: inline-block;
    /* Effet de brillance continue sur le mot Écologique */
    background: linear-gradient(to right, #4CAF50 20%, #85e088 40%, #85e088 60%, #4CAF50 80%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: textShimmer 4s linear infinite;
}

/* Animation d'envol */
@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

/* Animation du reflet de lumière sur le mot Écologique */
@keyframes textShimmer {
    to { background-position: 200% center; }
}

.hero h2 {
    font-size: 40px;
    font-weight: 600;
    letter-spacing: 3px;
    margin-bottom: 15px;
    opacity: 0;
    animation: fadeInUp 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) 0.3s forwards;
}

.white { color: white; }

.line {
    width: 120px;
    height: 3px;
    background: linear-gradient(to right, #4CAF50, white);
    margin: 20px auto;
    opacity: 0;
    animation: fadeInUp 1.2s ease 0.6s forwards;
}

.hero-content p {
    font-size: 18px;
    font-style: italic;
    letter-spacing: 1px;
    opacity: 0;
    animation: fadeInUp 1.2s ease 0.8s forwards;
}

/* 3. ANIMATION DU BOUTON DÉCOUVRIR */
.btn {
    display: inline-block;
    margin-top: 30px;
    padding: 15px 40px;
    border-radius: 30px;
    background: #4CAF50;
    color: white;
    text-decoration: none;
    font-size: 16px;
    font-weight: 600;
    letter-spacing: 0.5px;
    border: none;
    box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7);
    opacity: 0;
    /* Envol à l'allumage de la page + effet de pulsation lumineuse continu */
    animation: fadeInUp 1.2s ease 1s forwards, buttonGlow 2s infinite 2.2s;
    transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
}

/* Effet d'ondes lumineuses circulaires */
@keyframes buttonGlow {
    0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
    70% { box-shadow: 0 0 0 15px rgba(76, 175, 80, 0); }
    100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
}

/* Comportement au survol de la souris */
.btn:hover {
    background: #388E3C;
    transform: translateY(-3px) scale(1.03);
    box-shadow: 0 10px 20px rgba(0, 0, 0, 0.3);
}

/* SECTION POURQUOI TAKAMI */
.section {
    padding: 100px 20px;
    text-align: center;
    background: linear-gradient(135deg, #f5f7fa, #e8f5e9);
}

.section h2 {
    font-size: 40px;
    margin-bottom: 50px;
    color: #2e3d26;
}

/* CARDS */
.cards {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
}

.card {
    background: white;
    padding: 30px;
    width: 280px;
    border-radius: 20px;
    transition: 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(76, 175, 80, 0.15);
}

.card h3 {
    margin-bottom: 12px;
    color: #2e3d26;
    font-size: 20px;
}

.card p {
    color: #555;
    font-size: 15px;
}
</style>
</head>

<body>

<?php include 'navbar.php'; ?>

<section class="hero">
    <div class="hero-content">

        <h1>Charbon <span class="green">Écologique</span></h1>
        <h2>TA<span class="white">KAMI</span></h2>

        <div class="line"></div>

        <p>Aw Ka Charbon, An Ka Fen, An Ka Tagne</p>

        <a href="produits.php" class="btn">
            Découvrir nos produits
        </a>

    </div>
</section>

<section class="section">
    <h2>Pourquoi TAKAMI ?</h2>

    <div class="cards">
        <div class="card">
            <h3>🌿 Écologique</h3>
            <p>Respect de l’environnement</p>
        </div>

        <div class="card">
            <h3>🔥 Puissant</h3>
            <p>Longue durée de combustion</p>
        </div>

        <div class="card">
            <h3>💰 Économique</h3>
            <p>Moins cher et efficace</p>
        </div>
    </div>
</section>

</body>
</html>