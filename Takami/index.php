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
    width: 90%;
    max-width: 1200px;
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
    padding: 0 20px;
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
    width: 100%;
}

.hero h1 {
    font-size: clamp(40px, 8vw, 75px);
    font-weight: 700;
    margin-bottom: 5px;
    letter-spacing: 1px;
    animation: fadeInUp 1.2s cubic-bezier(0.2, 0.8, 0.2, 1) forwards;
}

.green {
    color: #4CAF50;
    position: relative;
    display: inline-block;
    background: linear-gradient(to right, #4CAF50 20%, #85e088 40%, #85e088 60%, #4CAF50 80%);
    background-size: 200% auto;
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    animation: textShimmer 4s linear infinite;
}

@keyframes fadeInUp {
    from { opacity: 0; transform: translateY(30px); }
    to { opacity: 1; transform: translateY(0); }
}

@keyframes textShimmer {
    to { background-position: 200% center; }
}

.hero h2 {
    font-size: clamp(25px, 5vw, 40px);
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
    font-size: clamp(14px, 2vw, 18px);
    font-style: italic;
    letter-spacing: 1px;
    opacity: 0;
    animation: fadeInUp 1.2s ease 0.8s forwards;
}

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
    border: none;
    opacity: 0;
    animation: fadeInUp 1.2s ease 1s forwards, buttonGlow 2s infinite 2.2s;
    transition: all 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
}

@keyframes buttonGlow {
    0% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0.7); }
    70% { box-shadow: 0 0 0 15px rgba(76, 175, 80, 0); }
    100% { box-shadow: 0 0 0 0 rgba(76, 175, 80, 0); }
}

.btn:hover {
    background: #388E3C;
    transform: translateY(-3px) scale(1.03);
}

/* SECTION POURQUOI TAKAMI */
.section {
    padding: 80px 20px;
    text-align: center;
    background: linear-gradient(135deg, #f5f7fa, #e8f5e9);
}

.section h2 {
    font-size: clamp(30px, 5vw, 40px);
    margin-bottom: 50px;
    color: #2e3d26;
}

.cards {
    display: flex;
    justify-content: center;
    gap: 30px;
    flex-wrap: wrap;
}

.card {
    background: white;
    padding: 30px;
    width: 100%;
    max-width: 350px;
    border-radius: 20px;
    transition: 0.3s cubic-bezier(0.2, 0.8, 0.2, 1);
    box-shadow: 0 10px 25px rgba(0, 0, 0, 0.08);
}

.card:hover {
    transform: translateY(-10px);
    box-shadow: 0 15px 35px rgba(76, 175, 80, 0.15);
}

/* RESPONSIVE NAVBAR */
@media (max-width: 768px) {
    .navbar { width: 95%; padding: 10px 20px; }
    .nav-links { gap: 20px; font-size: 14px; }
}

@media (max-width: 480px) {
    .nav-links { display: none; } /* Masquer les liens si trop petit */
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
        <p>Aw ka charbon, an ka taɲɛ</p>
        <a href="produits.php" class="btn">Découvrir nos produits</a>
    </div>
</section>

<section class="section">
    <h2>L'Excellence TAKAMI</h2>
    <div class="cards">
        <div class="card">
            <h3>🌿 Engagement Durable</h3>
            <p>Valorisation des déchets organiques pour lutter activement contre la déforestation au Mali.</p>
        </div>
        <div class="card">
            <h3>🔥 Haute Performance</h3>
            <p>Une combustion prolongée, sans fumée nocive, pour une cuisine saine et un confort thermique optimal.</p>
        </div>
        <div class="card">
            <h3>💰 Accessibilité Économique</h3>
            <p>Une énergie propre au meilleur prix, pensée pour le pouvoir d'achat des foyers maliens.</p>
        </div>
    </div>
</section>

<section style="padding: 50px 20px; text-align: center;">
    <h2 style="color: #2e7d32; margin-bottom: 20px;">Où nous trouver ?</h2>
    <div style="width: 100%; max-width: 800px; margin: 0 auto; border-radius: 20px; overflow: hidden; box-shadow: 0 10px 20px rgba(0,0,0,0.1);">
        <iframe src="https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d31065.132646296185!2d-8.08271705!3d12.7486806!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0xf51c7849c716279%3A0x8e833481ecb04c86!2sKati%2C%20Mali!5e0!3m2!1sfr!2sml!4v1690000000000!5m2!1sfr!2sml" width="100%" height="450" style="border:0;" allowfullscreen="" loading="lazy"></iframe>
    </div>
</section>

<footer style="background: #2e3d26; color: white; padding: 50px 20px; text-align: center;">
    <div style="max-width: 800px; margin: 0 auto;">
        <h3>Contactez TAKAMI</h3>
        <p style="margin: 15px 0;">Besoin d'informations sur nos solutions énergétiques ?</p>
        
        <div style="display: flex; justify-content: center; gap: 30px; margin-top: 20px; flex-wrap: wrap;">
            <p>📍 Kati, Mali</p>
            <p>📞 +223 90 00 31 00</p>
            <p>✉️ TAKAMI223@gmail.com</p>
        </div>

        <div style="margin-top: 30px; border-top: 1px solid #4CAF50; padding-top: 20px; font-size: 14px; opacity: 0.8;">
            &copy; 2026 TAKAMI - Charbon Écologique. Tous droits réservés.
        </div>
    </div>
</footer>

</body>
</html>