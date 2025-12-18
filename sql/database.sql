-- ============================================
-- Games Store Database - Steam Like Platform
-- ============================================

-- Création de la base de données
CREATE DATABASE IF NOT EXISTS games_store CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE games_store;

-- ============================================
-- Table: users (Utilisateurs)
-- ============================================
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    avatar VARCHAR(255) DEFAULT 'default-avatar.png',
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================
-- Table: categories (Genres de jeux)
-- ============================================
CREATE TABLE categories (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    icon VARCHAR(100)
) ENGINE=InnoDB;

-- ============================================
-- Table: games (Jeux)
-- ============================================
CREATE TABLE games (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(100) NOT NULL,
    description TEXT,
    price DECIMAL(10,2) NOT NULL,
    discount_price DECIMAL(10,2) DEFAULT NULL,
    category_id INT,
    release_date DATE,
    developer VARCHAR(100),
    publisher VARCHAR(100),
    image VARCHAR(255),
    banner_image VARCHAR(255),
    video_url VARCHAR(255),
    rating DECIMAL(3,2) DEFAULT 0,
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ============================================
-- Table: game_images (Images supplémentaires)
-- ============================================
CREATE TABLE game_images (
    id INT PRIMARY KEY AUTO_INCREMENT,
    game_id INT NOT NULL,
    image_url VARCHAR(255) NOT NULL,
    is_primary BOOLEAN DEFAULT FALSE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================
-- Table: cart (Panier)
-- ============================================
CREATE TABLE cart (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    UNIQUE KEY unique_cart_item (user_id, game_id)
) ENGINE=InnoDB;

-- ============================================
-- Table: purchases (Achats / Bibliothèque)
-- ============================================
CREATE TABLE purchases (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    purchase_price DECIMAL(10,2) NOT NULL,
    purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    UNIQUE KEY unique_purchase (user_id, game_id)
) ENGINE=InnoDB;

-- ============================================
-- Table: reviews (Avis utilisateurs)
-- ============================================
CREATE TABLE reviews (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    rating INT CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    UNIQUE KEY unique_review (user_id, game_id)
) ENGINE=InnoDB;

-- ============================================
-- Table: wishlists (Liste de souhaits)
-- ============================================
CREATE TABLE wishlists (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    game_id INT NOT NULL,
    added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (game_id) REFERENCES games(id) ON DELETE CASCADE,
    UNIQUE KEY unique_wishlist (user_id, game_id)
) ENGINE=InnoDB;

-- ============================================
-- Insertion des catégories par défaut
-- ============================================
INSERT INTO categories (name, description, icon) VALUES
('Action', 'Jeux d\'action et d\'aventure', 'fa-bolt'),
('RPG', 'Jeux de rôle', 'fa-hat-wizard'),
('FPS', 'Jeux de tir à la première personne', 'fa-crosshairs'),
('Sport', 'Jeux de sport', 'fa-futbol'),
('Stratégie', 'Jeux de stratégie', 'fa-chess'),
('Simulation', 'Jeux de simulation', 'fa-plane'),
('Aventure', 'Jeux d\'aventure narrative', 'fa-map'),
('Horreur', 'Jeux d\'horreur', 'fa-ghost'),
('Course', 'Jeux de course', 'fa-car'),
('Indie', 'Jeux indépendants', 'fa-gamepad');

-- ============================================
-- Insertion d'un admin par défaut
-- Mot de passe: admin123 (hashé avec password_hash)
-- ============================================
INSERT INTO users (username, email, password, role) VALUES
('admin', 'admin@gamesstore.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');

-- ============================================
-- Insertion de jeux exemples
-- ============================================
INSERT INTO games (title, description, price, discount_price, category_id, release_date, developer, publisher, image, is_featured) VALUES
('Cyber Warriors 2077', 'Un RPG d\'action en monde ouvert dans un futur dystopique. Explorez Night City et devenez une légende.', 59.99, 39.99, 2, '2024-12-10', 'CD Projekt Red', 'CD Projekt', 'cyber-warriors.jpg', TRUE),
('Call of Battle: Modern War', 'Le FPS le plus intense de l\'année. Combattez dans des batailles épiques multijoueurs.', 69.99, NULL, 3, '2024-11-05', 'Infinity Ward', 'Activision', 'call-of-battle.jpg', TRUE),
('FIFA Ultimate 2025', 'Le jeu de football ultime avec les dernières équipes et joueurs.', 59.99, 49.99, 4, '2024-09-27', 'EA Sports', 'Electronic Arts', 'fifa-2025.jpg', TRUE),
('Elden Ring II', 'Le successeur du jeu de l\'année. Un monde ouvert sombre et difficile vous attend.', 69.99, NULL, 1, '2025-02-15', 'FromSoftware', 'Bandai Namco', 'elden-ring-2.jpg', TRUE),
('Age of Empires V', 'Construisez votre empire à travers les âges dans ce jeu de stratégie épique.', 49.99, 34.99, 5, '2024-10-20', 'Relic Entertainment', 'Xbox Game Studios', 'aoe5.jpg', FALSE),
('Flight Simulator Pro', 'L\'expérience de vol la plus réaliste jamais créée.', 79.99, NULL, 6, '2024-08-18', 'Asobo Studio', 'Xbox Game Studios', 'flight-sim.jpg', FALSE),
('The Last Journey', 'Une aventure narrative émouvante sur la survie et l\'espoir.', 39.99, 29.99, 7, '2024-06-14', 'Naughty Dog', 'Sony Interactive', 'last-journey.jpg', TRUE),
('Dead Space Remake', 'Le classique de l\'horreur revient plus terrifiant que jamais.', 59.99, 44.99, 8, '2024-01-27', 'Motive Studio', 'Electronic Arts', 'dead-space.jpg', FALSE),
('Gran Turismo 8', 'La simulation de course automobile ultime avec plus de 500 voitures.', 69.99, NULL, 9, '2024-03-04', 'Polyphony Digital', 'Sony Interactive', 'gt8.jpg', FALSE),
('Hollow Knight: Silksong', 'L\'aventure tant attendue dans le monde des insectes continue.', 29.99, NULL, 10, '2024-07-01', 'Team Cherry', 'Team Cherry', 'silksong.jpg', TRUE);
