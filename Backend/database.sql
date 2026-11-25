CREATE DATABASE IF NOT EXISTS tubes_webpro_rating
    CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE tubes_webpro_rating;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(120) NOT NULL UNIQUE,
    password VARCHAR(255) NULL DEFAULT NULL,
    role ENUM('user', 'admin') NOT NULL DEFAULT 'user',
    google_id VARCHAR(100) NULL UNIQUE DEFAULT NULL,
    avatar VARCHAR(500) NULL DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS songs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(150) NOT NULL,
    artist VARCHAR(150) NOT NULL,
    album VARCHAR(150) DEFAULT '',
    genre VARCHAR(80) DEFAULT '',
    year SMALLINT UNSIGNED DEFAULT NULL,
    cover_url VARCHAR(500) DEFAULT '',
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FULLTEXT KEY song_search (title, artist, album, genre)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS ratings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    song_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    score TINYINT UNSIGNED NOT NULL CHECK (score BETWEEN 1 AND 5),
    review TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_user_song (user_id, song_id),
    CONSTRAINT fk_rating_song FOREIGN KEY (song_id) REFERENCES songs(id) ON DELETE CASCADE,
    CONSTRAINT fk_rating_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

INSERT INTO users (name, email, password, role) VALUES
('Administrator', 'admin@sonora.test', '$2y$10$fZLhCyowPMwUU14uEdvMpe.joxKUGygHagFZ2ol5g2mgzglpKpljq', 'admin')
ON DUPLICATE KEY UPDATE email = email;
