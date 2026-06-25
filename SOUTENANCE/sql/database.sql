-- Création de la base de données
CREATE DATABASE IF NOT EXISTS poultrytracker;
USE poultrytracker;

-- Table des utilisateurs
CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    name VARCHAR(100) NOT NULL,
    role ENUM('admin', 'farmer') DEFAULT 'farmer',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Table des lots
CREATE TABLE batches (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    name VARCHAR(100) NOT NULL,
    start_date DATE NOT NULL,
    initial_birds INT NOT NULL,
    current_birds INT NOT NULL,
    feed_type ENUM('starter', 'concentrate') DEFAULT 'starter',
    status ENUM('active', 'finished') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- Table de suivi quotidien
CREATE TABLE daily_tracking (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    tracking_date DATE NOT NULL,
    temperature DECIMAL(4,1),
    humidity DECIMAL(4,1),
    feed_quantity DECIMAL(10,2),
    mortality INT DEFAULT 0,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    UNIQUE KEY unique_daily (batch_id, tracking_date)
);

-- Table des vaccins
CREATE TABLE vaccines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    recommended_day INT NOT NULL,
    description TEXT
);

-- Table des vaccins administrés
CREATE TABLE batch_vaccines (
    id INT AUTO_INCREMENT PRIMARY KEY,
    batch_id INT NOT NULL,
    vaccine_id INT NOT NULL,
    administered_date DATE,
    is_done BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE,
    FOREIGN KEY (vaccine_id) REFERENCES vaccines(id)
);

-- Table des notifications
CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    batch_id INT,
    type VARCHAR(50),
    message TEXT NOT NULL,
    severity ENUM('critical', 'warning', 'info') DEFAULT 'info',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (batch_id) REFERENCES batches(id) ON DELETE CASCADE
);

-- Insertion des vaccins par défaut
INSERT INTO vaccines (name, recommended_day, description) VALUES
('Vaccin Gumboro', 7, 'Vaccination contre la maladie de Gumboro'),
('Vaccin Newcastle', 14, 'Vaccination contre la maladie de Newcastle'),
('Vaccin Bronchite', 21, 'Vaccination contre la bronchite infectieuse'),
('Vaccin Variole', 28, 'Vaccination contre la variole aviaire');

-- Insertion d'un admin par défaut (password: admin123)
INSERT INTO users (email, password, name, role) VALUES
('admin@poultrytracker.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Administrateur', 'admin');

-- Insertion d'un éleveur de test (password: farmer123)
INSERT INTO users (email, password, name, role) VALUES
('farmer@poultrytracker.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'Jean Dupont', 'farmer');