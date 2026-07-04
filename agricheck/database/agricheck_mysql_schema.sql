CREATE DATABASE IF NOT EXISTS agricheck
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE agricheck;

CREATE TABLE IF NOT EXISTS users (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  full_name VARCHAR(150) NOT NULL,
  phone VARCHAR(30) NOT NULL UNIQUE,
  email VARCHAR(190) UNIQUE,
  password_hash VARCHAR(255) NOT NULL,
  avatar_url VARCHAR(500),
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS plants (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  common_name VARCHAR(150) NOT NULL,
  scientific_name VARCHAR(190),
  family VARCHAR(150),
  description TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS diseases (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(190) NOT NULL,
  symptoms TEXT,
  causes TEXT,
  risk_level VARCHAR(50),
  prevention TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS treatments (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  disease_id BIGINT UNSIGNED NOT NULL,
  natural_solutions TEXT,
  recommended_products TEXT,
  dosage TEXT,
  application_frequency TEXT,
  urgency_level VARCHAR(80),
  prevention TEXT,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_treatments_disease
    FOREIGN KEY (disease_id) REFERENCES diseases(id)
    ON DELETE CASCADE
);

CREATE TABLE IF NOT EXISTS analyses (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  plant_id BIGINT UNSIGNED,
  disease_id BIGINT UNSIGNED,
  image_url VARCHAR(500) NOT NULL,
  plant_confidence DECIMAL(5,2) DEFAULT 0.00,
  disease_confidence DECIMAL(5,2) DEFAULT 0.00,
  risk_level VARCHAR(50),
  latitude DECIMAL(10,7),
  longitude DECIMAL(10,7),
  location_label VARCHAR(255),
  analyzed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  raw_ai_response JSON,
  CONSTRAINT fk_analyses_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_analyses_plant
    FOREIGN KEY (plant_id) REFERENCES plants(id)
    ON DELETE SET NULL,
  CONSTRAINT fk_analyses_disease
    FOREIGN KEY (disease_id) REFERENCES diseases(id)
    ON DELETE SET NULL,
  INDEX idx_analyses_user_date (user_id, analyzed_at),
  INDEX idx_analyses_location (latitude, longitude)
);

CREATE TABLE IF NOT EXISTS history (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  analysis_id BIGINT UNSIGNED NOT NULL,
  viewed_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_history_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  CONSTRAINT fk_history_analysis
    FOREIGN KEY (analysis_id) REFERENCES analyses(id)
    ON DELETE CASCADE,
  UNIQUE KEY uq_history_user_analysis (user_id, analysis_id)
);

CREATE TABLE IF NOT EXISTS notifications (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  user_id BIGINT UNSIGNED NOT NULL,
  title VARCHAR(190) NOT NULL,
  message TEXT NOT NULL,
  type VARCHAR(80),
  is_read BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT fk_notifications_user
    FOREIGN KEY (user_id) REFERENCES users(id)
    ON DELETE CASCADE,
  INDEX idx_notifications_user_read (user_id, is_read)
);
