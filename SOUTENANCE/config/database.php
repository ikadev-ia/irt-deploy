<?php
class Database {
    private $host;
    private $db_name;
    private $username;
    private $password;
    private $port;
    public $conn;

    public function __construct() {
        $this->host = getenv('DB_HOST') ?: "localhost";
        $this->db_name = getenv('DB_NAME') ?: "poultrytracker";
        $this->username = getenv('DB_USER') ?: "root";
        $this->password = getenv('DB_PASS') ?: "";
        $this->port = getenv('DB_PORT') ?: 3306;
    }

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