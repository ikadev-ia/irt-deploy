<?php session_start(); ?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Takami | À Propos</title>
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;600&display=swap');
        
        body { margin: 0; font-family: 'Poppins', sans-serif; background-color: #fcfbf8; color: #333; overflow-x: hidden; }

        /* HAUT DE PAGE */
        .header-section { 
            height: 85vh; background: linear-gradient(rgba(46,125,50,0.8), rgba(46,125,50,0.8)), url('images/panier2.jpeg');
            background-size: cover; background-position: center; display: flex; flex-direction: column;
            justify-content: center; align-items: center; color: white; text-align: center; padding: 20px;
        }
        .header-section h1 { font-size: 4rem; margin-bottom: 10px; font-weight: 700; text-shadow: 2px 2px 8px rgba(0,0,0,0.3); }
        .header-section p { font-size: 1.5rem; font-weight: 300; text-shadow: 1px 1px 4px rgba(0,0,0,0.3); }

        /* GRILLE */
        .content-grid { max-width: 1100px; margin: -100px auto 80px; display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 30px; padding: 0 20px; }
        .card-pro { 
            background: white; padding: 40px; border-radius: 20px; box-shadow: 0 20px 40px rgba(0,0,0,0.1);
            border-top: 5px solid #2e7d32; transition: 0.4s;
        }
        .card-pro:hover { transform: translateY(-15px); }

        /* SECTIONS STORY */
        .story-section { padding: 50px 20px; max-width: 1000px; margin: auto; }
        .row { display: flex; align-items: center; gap: 40px; margin-bottom: 80px; opacity: 0; transform: translateY(50px); transition: 1.2s; }
        .row.visible { opacity: 1; transform: translateY(0); }
        .row:nth-child(even) { flex-direction: row-reverse; }
        
        /* Images responsives */
        .row img { width: 100%; max-width: 450px; height: 300px; object-fit: cover; border-radius: 25px; box-shadow: 0 20px 40px rgba(0,0,0,0.15); }
        .row h2 { color: #2e7d32; font-size: 2rem; margin-bottom: 20px; }
        .row p { line-height: 1.8; font-size: 1.1rem; }

        /* RESPONSIVITÉ MOBILE */
        @media (max-width: 768px) {
            .header-section h1 { font-size: 2.5rem; }
            .header-section p { font-size: 1.1rem; }
            .row { flex-direction: column !important; text-align: center; gap: 20px; margin-bottom: 50px; }
            .row img { height: 250px; }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="header-section">
        <h1>TAKAMI</h1>
        <p>Plus qu'un charbon, un engagement pour le Mali.</p>
    </div>

    <div class="content-grid">
        <div class="card-pro"><h3>🔥 Combustion Pure</h3><p>Une chaleur intense, durable, sans fumées toxiques.</p></div>
        <div class="card-pro"><h3>🌱 Vision Verte</h3><p>Chaque briquette est un arbre sauvé grâce à la biomasse.</p></div>
        <div class="card-pro"><h3>💰 Économie Réelle</h3><p>Efficacité thermique supérieure pour votre budget.</p></div>
    </div>

    <section class="story-section">
        <div class="row anim">
            <img src="images/innova.jpeg" alt="Innovation">
            <div>
                <h2>L'Innovation au cœur du foyer</h2>
                <p>TAKAMI transforme les résidus agricoles en une énergie noble. Notre technologie de compactage garantit une chaleur constante pour vos plats, transformant votre cuisine en un espace de modernité et d'efficacité absolue.</p>
            </div>
        </div>
        
        <div class="row anim">
            <img src="images/Nature.jpeg" alt="Nature">
            <div>
                <h2>Engagement Environnemental</h2>
                <p>Chaque année, des milliers d'arbres sont abattus pour le charbon classique. En choisissant TAKAMI, vous stoppez ce cycle. C'est un acte citoyen, une décision puissante qui préserve la biodiversité malienne pour nos enfants.</p>
            </div>
        </div>
    </section>

    <script>
        const observer = new IntersectionObserver(entries => {
            entries.forEach(entry => { if(entry.isIntersecting) entry.target.classList.add('visible'); });
        });
        document.querySelectorAll('.row').forEach(el => observer.observe(el));
    </script>
</body>
</html>