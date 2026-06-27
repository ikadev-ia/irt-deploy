<?php
class Database {
    private $db_file = __DIR__ . "/../poultrytracker.db";
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            $db_dir = dirname($this->db_file);
            if (!file_exists($db_dir)) {
                mkdir($db_dir, 0777, true);
            }
            
            $this->conn = new PDO("sqlite:" . $this->db_file);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("PRAGMA foreign_keys = ON");
            
            $this->createTables();
            
        } catch(PDOException $exception) {
            die("Erreur de connexion : " . $exception->getMessage());
        }
        return $this->conn;
    }
    
    private function createTables() {
        // Table users (modifiée avec les nouvelles colonnes)
        $this->conn->exec("CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            email VARCHAR(100) UNIQUE NOT NULL,
            password VARCHAR(255) NOT NULL,
            name VARCHAR(100) NOT NULL,
            role VARCHAR(20) DEFAULT 'farmer',
            package VARCHAR(20) DEFAULT 'gratuit',
            status VARCHAR(20) DEFAULT 'pending',
            confirmation_token VARCHAR(100),
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Table batches
        $this->conn->exec("CREATE TABLE IF NOT EXISTS batches (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            name VARCHAR(100) NOT NULL,
            start_date DATE NOT NULL,
            initial_birds INTEGER NOT NULL,
            current_birds INTEGER NOT NULL,
            feed_type VARCHAR(20) DEFAULT 'starter',
            status VARCHAR(20) DEFAULT 'active',
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )");
        
        // Table daily_tracking avec colonne analysis
        $this->conn->exec("CREATE TABLE IF NOT EXISTS daily_tracking (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            batch_id INTEGER NOT NULL,
            tracking_date DATE NOT NULL,
            temperature DECIMAL(4,1),
            humidity DECIMAL(4,1),
            feed_quantity DECIMAL(10,2),
            mortality INTEGER DEFAULT 0,
            notes TEXT,
            analysis TEXT,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(batch_id) REFERENCES batches(id),
            UNIQUE(batch_id, tracking_date)
        )");
        
        // Table vaccines
        $this->conn->exec("CREATE TABLE IF NOT EXISTS vaccines (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(100) NOT NULL,
            recommended_day INTEGER NOT NULL,
            description TEXT
        )");
        
        // Table batch_vaccines
        $this->conn->exec("CREATE TABLE IF NOT EXISTS batch_vaccines (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            batch_id INTEGER NOT NULL,
            vaccine_id INTEGER NOT NULL,
            administered_date DATE,
            is_done INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(batch_id) REFERENCES batches(id),
            FOREIGN KEY(vaccine_id) REFERENCES vaccines(id)
        )");
        
        // Table notifications
        $this->conn->exec("CREATE TABLE IF NOT EXISTS notifications (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            batch_id INTEGER,
            type VARCHAR(50),
            message TEXT NOT NULL,
            severity VARCHAR(20) DEFAULT 'info',
            is_read INTEGER DEFAULT 0,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id),
            FOREIGN KEY(batch_id) REFERENCES batches(id)
        )");
        
        // Table payment_methods (modes de paiement)
        $this->conn->exec("CREATE TABLE IF NOT EXISTS payment_methods (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name VARCHAR(50) NOT NULL,
            type VARCHAR(20) NOT NULL,
            account_info TEXT,
            is_active INTEGER DEFAULT 1,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP
        )");
        
        // Table transactions (paiements)
        $this->conn->exec("CREATE TABLE IF NOT EXISTS transactions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            amount REAL NOT NULL,
            payment_method_id INTEGER NOT NULL,
            status VARCHAR(20) DEFAULT 'pending',
            transaction_id VARCHAR(100),
            package_name VARCHAR(50),
            expires_at DATETIME,
            created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY(user_id) REFERENCES users(id),
            FOREIGN KEY(payment_method_id) REFERENCES payment_methods(id)
        )");
        
        // Table subscriptions (abonnements)
        $this->conn->exec("CREATE TABLE IF NOT EXISTS subscriptions (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            user_id INTEGER NOT NULL,
            package VARCHAR(50) NOT NULL,
            amount REAL NOT NULL,
            status VARCHAR(20) DEFAULT 'active',
            start_date DATETIME DEFAULT CURRENT_TIMESTAMP,
            end_date DATETIME,
            payment_method_id INTEGER,
            FOREIGN KEY(user_id) REFERENCES users(id)
        )");
        
        // Insérer les vaccins par défaut
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM vaccines");
        $result = $stmt->fetch();
        if ($result['count'] == 0) {
            $vaccines = [
                ['Vaccin Gumboro', 7, 'Vaccination contre la maladie de Gumboro'],
                ['Vaccin Newcastle', 14, 'Vaccination contre la maladie de Newcastle'],
                ['Vaccin Bronchite', 21, 'Vaccination contre la bronchite infectieuse'],
                ['Vaccin Variole', 28, 'Vaccination contre la variole aviaire']
            ];
            $insert = $this->conn->prepare("INSERT INTO vaccines (name, recommended_day, description) VALUES (?, ?, ?)");
            foreach ($vaccines as $v) { $insert->execute($v); }
        }
        
        // Insérer les modes de paiement par défaut
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM payment_methods");
        $result = $stmt->fetch();
        if ($result['count'] == 0) {
            $payment_methods = [
                ['Orange Money', 'mobile', '12345678'],
                ['Wave', 'mobile', '77889900'],
                ['Moov Money', 'mobile', '11223344'],
                ['Carte Bancaire', 'card', ''],
                ['PayPal', 'online', 'poulplume@gmail.com']
            ];
            $insert = $this->conn->prepare("INSERT INTO payment_methods (name, type, account_info) VALUES (?, ?, ?)");
            foreach ($payment_methods as $pm) { $insert->execute($pm); }
        }
        
        // Insérer les utilisateurs par défaut (avec les nouvelles colonnes)
        $stmt = $this->conn->query("SELECT COUNT(*) as count FROM users");
        $result = $stmt->fetch();
        if ($result['count'] == 0) {
            $adminPassword = password_hash('admin123', PASSWORD_DEFAULT);
            $farmerPassword = password_hash('farmer123', PASSWORD_DEFAULT);
            $insert = $this->conn->prepare("INSERT INTO users (email, password, name, role, package, status) VALUES (?, ?, ?, ?, ?, ?)");
            $insert->execute(['admin@poultrytracker.com', $adminPassword, 'Administrateur', 'admin', 'premium', 'active']);
            $insert->execute(['farmer@poultrytracker.com', $farmerPassword, 'Jean Dupont', 'farmer', 'gratuit', 'active']);
        }
    }

    /**
     * Méthode de gestion de session - CORRIGÉE
     * Vérifie si la session est déjà active avant de la démarrer
     */
    public function startSession() {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Vérifie si l'utilisateur est connecté
     * Utilise startSession() qui gère correctement l'état de la session
     */
    public function isLoggedIn() {
        $this->startSession();
        return isset($_SESSION['user_id']);
    }

    /**
     * Exige que l'utilisateur soit connecté
     * Redirige vers login.php si non connecté
     */
    public function requireLogin() {
        $this->startSession();
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }

    /**
     * Exige que l'utilisateur soit administrateur
     * Redirige vers dashboard.php si non administrateur
     */
    public function requireAdmin() {
        $this->requireLogin();
        if ($_SESSION['user_role'] != 'admin') {
            header("Location: dashboard.php");
            exit();
        }
    }
}
?>