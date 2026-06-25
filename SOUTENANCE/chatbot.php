<?php
require_once 'config/database_sqlite.php';
$db = new Database();
$conn = $db->getConnection();
$db->startSession();
if (!isset($_SESSION['user_id'])) { header("Location: login.php"); exit(); }
$user_id = $_SESSION['user_id'];
$user_role = $_SESSION['user_role'];
$user_name = $_SESSION['user_name'];
?>
<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=yes">
    <title>Assistant  - Poulplume</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;400;500;600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
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
        }
        body {
            font-family: 'Inter', sans-serif;
            background: url('Images/AR10.png') no-repeat center center fixed;
            background-size: cover;
            color: var(--text-dark);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }
        body::before {
            content: '';
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, rgba(0,0,0,0.2), rgba(0,0,0,0.08));
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
            padding: 20px;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
        }
        .chat-container {
            width: 100%;
            max-width: 1000px;
            height: 85vh;
            background: var(--white-glass-card);
            backdrop-filter: blur(12px);
            border-radius: 48px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
            border: 1px solid var(--border-light);
            box-shadow: 0 25px 50px -12px rgba(0,0,0,0.25);
        }
        .chat-header {
            background: var(--yellow);
            padding: 18px 25px;
            display: flex;
            align-items: center;
            gap: 15px;
            position: relative;
        }
        .chat-header i {
            font-size: 2rem;
            color: var(--green);
        }
        .chat-header h1 {
            font-size: 1.3rem;
            font-weight: 700;
            color: #1e293b;
        }
        .home-btn {
            position: absolute;
            right: 20px;
            top: 18px;
            background: rgba(30,41,59,0.12);
            color: #1e293b;
            padding: 5px 12px;
            border-radius: 30px;
            text-decoration: none;
            font-size: 0.7rem;
            font-weight: 600;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }
        .home-btn:hover {
            background: rgba(30,41,59,0.25);
        }
        .chat-messages {
            flex: 1;
            overflow-y: auto;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
        }
        .message {
            display: flex;
            gap: 12px;
            animation: fadeIn 0.3s ease;
        }
        .message.user {
            flex-direction: row-reverse;
        }
        .message.user .message-content {
            background: linear-gradient(135deg, var(--green), var(--green-dark));
            color: white;
            border-radius: 24px 24px 4px 24px;
        }
        .message.bot .message-content {
            background: rgba(255,255,255,0.5);
            border: 1px solid var(--border-light);
            color: var(--text-dark);
            border-radius: 24px 24px 24px 4px;
            white-space: pre-wrap;
        }
        .message-content {
            max-width: 80%;
            padding: 12px 18px;
            font-size: 0.9rem;
            line-height: 1.5;
        }
        .message i {
            font-size: 1.8rem;
            align-self: flex-start;
            color: var(--yellow);
        }
        .chat-input {
            display: flex;
            padding: 18px 20px;
            background: var(--white-glass);
            border-top: 1px solid var(--border-light);
            gap: 12px;
        }
        .chat-input input {
            flex: 1;
            padding: 14px 20px;
            border: 1px solid var(--border-light);
            border-radius: 40px;
            font-family: inherit;
            font-size: 0.9rem;
            background: rgba(255,255,255,0.5);
            outline: none;
            color: var(--text-dark);
        }
        .chat-input input:focus {
            border-color: var(--green);
            box-shadow: 0 0 0 2px rgba(20,181,58,0.2);
        }
        .chat-input button {
            background: var(--green);
            border: none;
            width: 48px;
            height: 48px;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            transition: 0.2s;
        }
        .chat-input button:hover {
            background: var(--green-dark);
            transform: scale(1.02);
        }
        .typing {
            display: flex;
            gap: 4px;
            padding: 12px 18px;
            background: rgba(255,255,255,0.5);
            border-radius: 24px;
            width: fit-content;
            margin-left: 48px;
        }
        .typing span {
            width: 8px;
            height: 8px;
            background: var(--green);
            border-radius: 50%;
            animation: blink 1.4s infinite both;
        }
        .typing span:nth-child(2) { animation-delay: 0.2s; }
        .typing span:nth-child(3) { animation-delay: 0.4s; }
        @keyframes blink { 0%,80%,100%{opacity:0;} 40%{opacity:1;} }
        @keyframes fadeIn { from{opacity:0;transform:translateY(10px);} to{opacity:1;transform:translateY(0);} }
        
        @media (max-width: 1024px) {
            .sidebar { transform: translateX(-100%); }
            .sidebar.open { transform: translateX(0); }
            .burger-btn { display: block; }
            .main-content { margin-left: 0; }
        }
        @media (max-width: 640px) {
            .main-content { padding: 10px; }
            .message-content { max-width: 85%; font-size: 0.8rem; }
            .message i { font-size: 1.4rem; }
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
        <a href="chatbot.php" class="nav-item active"><i class="fas fa-robot"></i> Chatbot</a>
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
            <div><div class="user-name"><?php echo htmlspecialchars($user_name); ?></div><div class="user-role"><?php echo $user_role == 'admin' ? 'Administrateur' : 'Éleveur'; ?></div></div>
        </div>
        <a href="logout.php" class="logout-btn"><i class="fas fa-sign-out-alt"></i> Déconnexion</a>
    </div>
</div>

<button class="burger-btn" id="burgerBtn"><i class="fas fa-bars"></i></button>

<div class="main-content">
    <div class="chat-container">
        <div class="chat-header">
            <i class=""></i>
            <h1>Assistant vétérinaire Poulplume</h1>
            <a href="dashboard.php" class="home-btn"><i class="fas fa-home"></i> Accueil</a>
        </div>
        <div class="chat-messages" id="chatMessages">
            <div class="message bot">
                <i class=""></i>
                <div class="message-content">
                     Bonjour <?php echo htmlspecialchars($user_name); ?> !**<br><br>
                    <b>Je suis votre assistant avicole.</b> <b>Posez-moi n'importe quelle question sur l'élevage de poulets, j'aurai une réponse pour vous !</b><br><br>
                     Exemples : "coccidiose", "symptômes diarrhée sanglante", "vaccination", "température poussin", "alimentation pondeuse", "stress picage"...
                </div>
            </div>
        </div>
        <div class="chat-input">
            <input type="text" id="messageInput" placeholder="Posez votre question sur l'élevage de poulets..." onkeypress="if(event.key==='Enter') sendMessage()">
            <button onclick="sendMessage()" id="sendButton"><i class="fas fa-paper-plane"></i></button>
        </div>
    </div>
</div>

<script>
    // ==================== BASE DE CONNAISSANCES ULTRA COMPLÈTE ====================
    
    function getBotResponse(question) {
        let q = question.toLowerCase();
        
        // DICTIONNAIRE COMPLET DES RÉPONSES
        const responses = {
            'coccidiose': " **COCCIDIOSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n **Cause** : Parasite Eimeria\n\n **Symptômes** : Diarrhée sanglante ou aqueuse, plumes hérissées, abattement\n\n **Traitement** : Amprolium (0.025%) dans l'eau - 5 jours\n\n **Prévention** : Litière toujours sèche, nettoyage régulier, anticoccidiens",
            
            'newcastle': " **MALADIE DE NEWCASTLE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n **Symptômes** : Respiration bruyante, toux, paralysie, cou tordu, diarrhée verte\n\n **Action** : URGENCE VÉTÉRINAIRE IMMÉDIATE\n\n **Prévention** : Vaccination obligatoire (J14, J42)",
            
            'salmonellose': " **SALMONELLOSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n **Symptômes** : Diarrhée blanche, abattement, plumes hérissées\n\n **Traitement** : Antibiotiques sur prescription\n\n **Zoonose** : Transmissible à l'homme",
            
            'bronchite': " **BRONCHITE INFECTIEUSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n **Symptômes** : Toux, éternuements, respiration bruyante, chute de ponte\n\n **Traitement** : Antibiotiques (tylosine) 5 jours\n\n **Prévention** : Vaccination (J21)",
            
            'gumboro': " **MALADIE DE GUMBORO**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n **Symptômes** : Apathie, diarrhée blanche, immunodépression\n\n **Prévention** : Vaccination (J7, J21)",
            
            'colibacillose': " **COLIBACILLOSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n **Symptômes** : Difficultés respiratoires, péricardite\n\n **Traitement** : Antibiotiques sur prescription",
            
            'mycoplasmose': " **MYCOPLASMOSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n **Symptômes** : Éternuements chroniques, gonflement des sinus\n\n **Traitement** : Tylosine 5-7 jours",
            
            'parasites': " **PARASITES INTERNES**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n **Symptômes** : Amaigrissement, diarrhée\n\n **Traitement** : Fenbendazole\n\n **Prévention** : Vermifuger tous les 3 mois",
            
            'poux': " **POUX ROUGES**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\n🩺 **Symptômes** : Agitation, baisse de ponte, pâleur\n\n **Traitement** : Sprays pyrèthre, désinfection complète",
            
            'diarrhee sanglante': " **DIARRHÉE SANGLANTE** = COCCIDIOSE\n\n Traitement : Amprolium 5 jours\n\n Prévention : Litière sèche",
            
            'diarrhee blanche': " **DIARRHÉE BLANCHE** = SALMONELLOSE\n\n Zoonose - consulter rapidement\n\n Antibiotiques sur prescription",
            
            'respiration bruyante': " **RESPIRATION BRUYANTE**\n\nCauses : Bronchite, Mycoplasmose, Newcastle\n\n Antibiotiques (tylosine) - 5 jours\n\n Aérer sans courant d'air",
            
            'paralysie': " **PARALYSIE / COU TORDU**\n\nCause possible : MALADIE DE NEWCASTLE\n\n URGENCE VÉTÉRINAIRE",
            
            'plumes herissees': " **PLUMES HÉRISSÉES**\n\nCauses : Froid, Maladie, Stress\n\n Vérifiez la température et isolez si nécessaire",
            
            'stress': " **STRESS**\n\nSignes : Picage, plumes arrachées, baisse d'appétit\n\nSolutions : Réduire densité, enrichir environnement",
            
            'picage': " **PICAGE**\n\nCauses : Surpopulation, lumière forte, carences\n\nSolutions : Augmenter l'espace, réduire la lumière",
            
            'poussin alimentation': " **ALIMENTATION POUSSINS (0-10j)**\n\n Protéines 22-24%\n 5-6 repas/jour\n Eau à 32-35°C",
            
            'poulet chair alimentation': " **POULET DE CHAIR - ALIMENTATION**\n\n0-10j : protéines 22-24%\n11-30j : protéines 20-22%\n31-45j : protéines 18-20%",
            
            'pondeuse alimentation': " **PONDEUSE - ALIMENTATION**\n\nDémarrage (0-6 sem.) : 20%\nCroissance : 16-18%\nPonte : calcium 3.5-4%",
            
            'calendrier vaccinal': " **CALENDRIER VACCINAL**\n\nJour 7 : Gumboro\nJour 14 : Newcastle\nJour 21 : Bronchite\nJour 28 : Variole\nJour 35 : Rappel Newcastle",
            
            'temperature': " **TEMPÉRATURE IDÉALE**\n\nSemaine 1 : 32-35°C\nSemaine 2 : 30-32°C\nSemaine 3 : 28-30°C\nSemaine 4+ : 21-24°C",
            
            'poulet chair race': " **POULET DE CHAIR**\nDurée : 45 jours | Poids : 2.2-2.5 kg",
            
            'pondeuse race': " **PONDEUSE**\nDébut ponte : 18 semaines | Production : 260-300 œufs/an",
            
            'bresse': " **BRESSE** (AOC)\nDurée : 120 jours | Élevage : Plein air obligatoire",
            
            'goliath': " **GOLIATH**\nDurée : 42 jours | Poids : 3.5-4 kg",
            
            'nettoyage': " **NETTOYAGE**\n\nQuotidien : Eau fraîche\nHebdomadaire : Mangeoires/abreuvoirs\nMensuel : Désinfection complète",
            
            'mortalite': " **MORTALITÉ NORMALE**\n\n0-7j : 1-2%\n8-21j : 0.5-1%\n22-35j : 0.3-0.5%\nAlert si >2% en 24h"
        };
        
        // Chercher une correspondance exacte ou partielle
        for (let [key, response] of Object.entries(responses)) {
            if (q.includes(key)) {
                return response;
            }
        }
        
        // Réponse par défaut pour toute question non trouvée (toujours utile)
        return "**POULPLUME - RÉPONSE**\n━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━\n\nMerci pour votre question sur : \"**" + question + "**\"\n\n **Voici ce que je peux vous conseiller** :\n\n **Pour les maladies** : Surveillez les symptômes (diarrhée, respiration, comportement)\n\n **Traitements courants** :\n• Coccidiose → Amprolium\n• Diarrhée → Vérifiez l'eau et l'aliment\n• Respiration → Tylosine + améliorer la ventilation\n\n **Prévention générale** :\n Litière toujours sèche\n Eau propre en permanence\n Vaccination à jour (J7, J14, J21)\n Densité max 15/m²\n\n **Contactez votre vétérinaire** si les symptômes persistent ou s'aggravent.\n\n Pour une réponse plus précise, utilisez le **Diagnostic ** ou consultez la **FAQ** dans Aide.";
    }
    
    // ==================== CHAT LOGIC ====================
    const messagesDiv = document.getElementById('chatMessages');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendButton');
    
    function scrollToBottom() { 
        messagesDiv.scrollTop = messagesDiv.scrollHeight; 
    }
    
    function showTyping() {
        const typingDiv = document.createElement('div');
        typingDiv.className = 'message bot';
        typingDiv.id = 'typingIndicator';
        typingDiv.innerHTML = `<i class="fas fa-robot"></i><div class="typing"><span></span><span></span><span></span></div>`;
        messagesDiv.appendChild(typingDiv);
        scrollToBottom();
    }
    
    function hideTyping() { 
        const el = document.getElementById('typingIndicator');
        if (el) el.remove();
    }
    
    function addMessage(text, sender) {
        const div = document.createElement('div');
        div.className = `message ${sender}`;
        let formatted = text.replace(/\n/g, '<br>');
        if (sender === 'bot') {
            formatted = formatted.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        }
        div.innerHTML = `<i class="fas fa-${sender === 'user' ? 'user' : 'robot'}"></i>
                         <div class="message-content">${formatted}</div>`;
        messagesDiv.appendChild(div);
        scrollToBottom();
    }
    
    function sendMessage() {
        const msg = messageInput.value.trim();
        if (msg === "") return;
        
        // Afficher le message utilisateur
        addMessage(msg, 'user');
        messageInput.value = '';
        
        // Afficher l'animation de frappe
        showTyping();
        
        // Générer la réponse (simulation de temps de réflexion)
        setTimeout(() => {
            const response = getBotResponse(msg);
            hideTyping();
            addMessage(response, 'bot');
        }, 300);
    }
    
    // Événements
    sendBtn.onclick = sendMessage;
    messageInput.onkeypress = function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    };
    
    function toggleSettings() { document.getElementById('settingsSub').classList.toggle('show'); }
    
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
</script>
</body>
</html>