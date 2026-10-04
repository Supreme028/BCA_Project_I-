-- =======================================================
-- Database Schema for Movie Ticket Booking System
-- Course: BCA 4th Semester (Project I)
-- Database Engine: MySQL / MariaDB (XAMPP / WAMP)
-- =======================================================

CREATE DATABASE IF NOT EXISTS `movie_booking_db` DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `movie_booking_db`;

-- -------------------------------------------------------
-- 1. Table: admins
-- Stores admin credentials for back-office management.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `admins` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `fullname` VARCHAR(100) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 2. Table: users
-- Stores registered customer accounts.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `fullname` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `phone` VARCHAR(20) NOT NULL,
    `password` VARCHAR(255) NOT NULL,
    `is_verified` TINYINT(1) NOT NULL DEFAULT 0,
    `verification_token` VARCHAR(64) DEFAULT NULL,
    `token_expiry` DATETIME DEFAULT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 3. Table: movies
-- Stores movie catalog details.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `movies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(150) NOT NULL,
    `genre` VARCHAR(50) NOT NULL,
    `language` VARCHAR(50) NOT NULL,
    `duration` INT NOT NULL COMMENT 'Duration in minutes',
    `release_date` DATE NOT NULL,
    `poster` VARCHAR(255) DEFAULT 'default_poster.jpg',
    `description` TEXT NOT NULL,
    `status` ENUM('now_showing', 'coming_soon', 'ended') DEFAULT 'now_showing',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 4. Table: screens
-- Represents cinema auditoriums / halls and their seat capacities.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `screens` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(50) NOT NULL,
    `total_rows` INT NOT NULL DEFAULT 5 COMMENT 'Number of rows (A, B, C, etc.)',
    `total_columns` INT NOT NULL DEFAULT 8 COMMENT 'Seats per row (1, 2, 3, etc.)'
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 5. Table: showtimes
-- Connects a movie to a screen at a specific date and time.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `showtimes` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `movie_id` INT NOT NULL,
    `screen_id` INT NOT NULL,
    `show_date` DATE NOT NULL,
    `show_time` TIME NOT NULL,
    `ticket_price` DECIMAL(8,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`movie_id`) REFERENCES `movies`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`screen_id`) REFERENCES `screens`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 6. Table: bookings
-- Stores booking transactions made by users.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_number` VARCHAR(20) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `showtime_id` INT NOT NULL,
    `total_amount` DECIMAL(8,2) NOT NULL,
    `status` ENUM('confirmed', 'cancelled') DEFAULT 'confirmed',
    `booking_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`showtime_id`) REFERENCES `showtimes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- -------------------------------------------------------
-- 7. Table: seat_bookings
-- Individual seats booked under a booking.
-- UNIQUE KEY on (showtime_id, seat_number) prevents double booking.
-- -------------------------------------------------------
CREATE TABLE IF NOT EXISTS `seat_bookings` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `booking_id` INT NOT NULL,
    `showtime_id` INT NOT NULL,
    `seat_number` VARCHAR(10) NOT NULL,
    UNIQUE KEY `unique_showtime_seat` (`showtime_id`, `seat_number`),
    FOREIGN KEY (`booking_id`) REFERENCES `bookings`(`id`) ON DELETE CASCADE,
    FOREIGN KEY (`showtime_id`) REFERENCES `showtimes`(`id`) ON DELETE CASCADE
) ENGINE=InnoDB;

-- =======================================================
-- SAMPLE SEED DATA
-- Default Admin credentials:
-- Username: admin
-- Password: admin123
-- =======================================================

INSERT INTO `admins` (`username`, `password`, `fullname`) VALUES
('admin', '$2y$12$gxO7mxvnu7MEgF3fIACPJuRg7dlhSa3maWYWMtYqtEKCXnoWm4q1y', 'System Administrator')
ON DUPLICATE KEY UPDATE `username`=`username`;

-- Sample Cinema Screens
INSERT INTO `screens` (`id`, `name`, `total_rows`, `total_columns`) VALUES
(1, 'Audi 1 (Dolby Atmos)', 5, 8),
(2, 'Audi 2 (IMAX 3D)', 6, 8)
ON DUPLICATE KEY UPDATE `name`=`name`;

-- Sample Movies
INSERT INTO `movies` (`id`, `title`, `genre`, `language`, `duration`, `release_date`, `poster`, `description`, `status`) VALUES
(1, 'Inception', 'Sci-Fi / Thriller', 'English', 148, '2024-01-10', 'inception.jpg', 'A thief who steals corporate secrets through the use of dream-sharing technology is given the inverse task of planting an idea into the mind of a C.E.O.', 'now_showing'),
(2, 'Interstellar', 'Sci-Fi / Adventure', 'English', 169, '2024-02-15', 'interstellar.jpg', 'When Earth becomes uninhabitable in the future, a farmer and ex-NASA pilot, Joseph Cooper, is tasked to pilot a spacecraft, along with a team of researchers, to find a new planet for humans.', 'now_showing'),
(3, 'The Dark Knight', 'Action / Crime', 'English', 152, '2024-03-01', 'dark_knight.jpg', 'When the menace known as the Joker wreaks havoc and chaos on the people of Gotham, Batman must accept one of the greatest psychological and physical tests of his ability to fight injustice.', 'now_showing')
ON DUPLICATE KEY UPDATE `title`=`title`;

-- Sample Showtimes (Dates set to current and upcoming days)
INSERT INTO `showtimes` (`id`, `movie_id`, `screen_id`, `show_date`, `show_time`, `ticket_price`) VALUES
(1, 1, 1, CURDATE(), '11:00:00', 250.00),
(2, 1, 1, CURDATE(), '15:30:00', 300.00),
(3, 2, 2, CURDATE(), '18:00:00', 350.00),
(4, 3, 1, DATE_ADD(CURDATE(), INTERVAL 1 DAY), '14:00:00', 250.00)
ON DUPLICATE KEY UPDATE `ticket_price`=`ticket_price`;
