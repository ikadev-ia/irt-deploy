<!-- ========== SIDEBAR VERT PREMIUM ========== -->
<style>
    /* Variables de couleur */
    :root {
        --green: #14B53A;
        --green-dark: #0d8a2f;
        --yellow: #FCD116;
        --white: #ffffff;
    }

    /* Sidebar */
    .sidebar {
        position: fixed;
        left: 0;
        top: 0;
        width: 280px;
        height: 100vh;
        background: linear-gradient(145deg, var(--green) 0%, var(--green-dark) 100%);
        display: flex;
        flex-direction: column;
        z-index: 1000;
        transition: transform 0.3s cubic-bezier(0.2, 0.9, 0.4, 1.1);
        box-shadow: 4px 0 25px rgba(0,0,0,0.15);
    }

    /* En-tête avec logo */
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
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }
    .brand .plume {
        color: var(--white);
        text-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    /* Navigation */
    .sidebar-nav {
        flex: 1;
        padding: 30px 16px;
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    /* Éléments de navigation */
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
    .nav-item:hover i {
        color: #1e293b;
    }
    .nav-item.active {
        background: var(--white);
        color: var(--green);
        box-shadow: 0 6px 14px rgba(0,0,0,0.15);
    }
    .nav-item.active i {
        color: var(--green);
    }

    /* Groupe Paramètres */
    .settings-group {
        margin-top: 12px;
    }
    .settings-header {
        cursor: pointer;
        justify-content: space-between;
    }
    .settings-header i:last-child {
        width: auto;
        font-size: 0.7rem;
        transition: transform 0.2s;
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
        transition: 0.2s;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .settings-sub a i {
        width: 20px;
        font-size: 0.85rem;
    }
    .settings-sub a:hover {
        background: var(--yellow);
        color: #1e293b;
        transform: translateX(5px);
    }
    .settings-sub.show {
        display: flex;
    }

    /* Pied de sidebar (user + logout) */
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
        transition: 0.2s;
    }
    .user-card:hover {
        background: rgba(255,255,255,0.2);
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
        font-weight: 500;
        transition: 0.2s;
    }
    .logout-btn:hover {
        background: rgba(255,255,255,0.2);
        transform: translateX(3px);
        color: white;
    }

    /* Burger menu pour mobile */
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

    /* Responsive */
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
    }
</style>

<!-- STRUCTURE HTML DE LA SIDEBAR -->
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
        <a href="dashboard.php" class="nav-item active">
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

<script>
    function toggleSettings() {
        const sub = document.getElementById('settingsSub');
        if(sub) sub.classList.toggle('show');
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