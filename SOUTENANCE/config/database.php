<?php
class Database {
    private $host = "localhost";
    private $db_name = "poultrytracker";
    private $username = "root";
    private $password = "";  // Par défaut XAMPP n'a pas de mot de passe
    private $port = 3306;     // Port par défaut de MySQL
    public $conn;

    public function getConnection() {
        $this->conn = null;
        try {
            // Ajout du port dans la connexion
            $dsn = "mysql:host=" . $this->host . ";port=" . $this->port . ";dbname=" . $this->db_name;
            $this->conn = new PDO($dsn, $this->username, $this->password);
            $this->conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $this->conn->exec("set names utf8");
            
            // Debug: Afficher un message si la connexion réussit
            // echo "Connexion réussie à la base de données"; // Décommentez pour tester
            
        } catch(PDOException $exception) {
            // Message d'erreur plus détaillé
            die("Erreur de connexion : " . $exception->getMessage() . "<br>
                 Vérifiez que MySQL est démarré dans XAMPP<br>
                 Vérifiez que la base de données 'poultrytracker' existe<br>
                 Vérifiez les identifiants de connexion");
        }
        return $this->conn;
    }

    public function startSession() {
        if (session_status() == PHP_SESSION_NONE) {
            session_start();
        }
    }

    public function isLoggedIn() {
        $this->startSession();
        return isset($_SESSION['user_id']);
    }

    public function requireLogin() {
        $this->startSession();
        if (!$this->isLoggedIn()) {
            header("Location: login.php");
            exit();
        }
    }

    public function requireAdmin() {
        $this->requireLogin();
        if ($_SESSION['user_role'] != 'admin') {
            header("Location: dashboard.php");
            exit();
        }
    }
}
?>