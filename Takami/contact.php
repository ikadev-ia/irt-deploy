<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Contact - TAKAMI</title>
    <style>
        body { 
            margin: 0; font-family: 'Poppins', sans-serif; 
            background: url('images/panier2.jpeg') no-repeat center center fixed; 
            background-size: cover; min-height: 100vh;
        }
        .overlay { 
            background: rgba(0, 0, 0, 0.5); min-height: 100vh; 
            display: flex; flex-direction: column; 
            justify-content: center; align-items: center; 
            padding: 20px;
        }
        .contact-container { 
            width: 90%; 
            max-width: 800px; 
            display: flex; 
            flex-direction: column; 
            align-items: center; 
            gap: 20px; 
        }
        .contact-box { 
            background: rgba(255, 255, 255, 0.2); 
            backdrop-filter: blur(15px); 
            border: 1px solid rgba(255, 255, 255, 0.3);
            padding: 25px 30px; 
            border-radius: 15px; 
            color: white;
            transition: 0.3s; 
            width: 100%; /* Prend la largeur du container */
            box-shadow: 0 8px 32px rgba(0,0,0,0.3);
            display: flex;
            align-items: center;
            gap: 30px; /* Espace entre icône et texte */
        }
        .contact-box:hover { background: rgba(255, 255, 255, 0.3); }
        .icon { font-size: 40px; }
        h2 { color: white; margin-bottom: 40px; font-size: 2.5rem; text-shadow: 2px 2px 4px rgba(0,0,0,0.5); }
        .text-content h3 { margin: 0; font-size: 1.2rem; }
        .text-content p { margin: 5px 0 0 0; font-size: 1rem; opacity: 0.9; }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="overlay">
        <h2>Contactez TAKAMI</h2>
        <div class="contact-container">
            <div class="contact-box">
                <span class="icon">📍</span>
                <div class="text-content">
                    <h3>Adresse</h3>
                    <p>Kati, Mali</p>
                </div>
            </div>
            <div class="contact-box">
                <span class="icon">📞</span>
                <div class="text-content">
                    <h3>Téléphone</h3>
                    <p>+223 90 00 31 00</p>
                </div>
            </div>
            <div class="contact-box">
                <span class="icon">✉️</span>
                <div class="text-content">
                    <h3>Email</h3>
                    <p>TAKAMI223@gmail.com</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>