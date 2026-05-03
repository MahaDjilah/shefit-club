CREATE DATABASE IF NOT EXISTS shefit_db CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE shefit_db;


CREATE TABLE IF NOT EXISTS users (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    full_name     VARCHAR(100) NOT NULL,
    email         VARCHAR(150) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    phone         VARCHAR(20),
    dob           DATE,
    role          ENUM('member','admin') DEFAULT 'member',
    status        ENUM('active','banned') DEFAULT 'active',
    created_at    DATETIME DEFAULT CURRENT_TIMESTAMP
);


INSERT INTO users (full_name, email, password_hash, role) VALUES
('Admin SheFit', 'admin@shefit.com', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin');


INSERT INTO users (full_name, email, password_hash, phone, dob, role) VALUES
('Nour Benbadis',  'nour@mail.com',    '$2y$10$TKh8H1.PtIK1KlJRbBjT1.G6Q9O0rV5p7Z8VjJ2cKdOH/A.NQNKPG', '0550000000', '1998-05-10', 'member'),
('Aya Hmid',       'aya@mail.com',     '$2y$10$TKh8H1.PtIK1KlJRbBjT1.G6Q9O0rV5p7Z8VjJ2cKdOH/A.NQNKPG', '0660000000', '2000-03-22', 'member'),
('Djamila Kenzi',  'djamila@mail.com', '$2y$10$TKh8H1.PtIK1KlJRbBjT1.G6Q9O0rV5p7Z8VjJ2cKdOH/A.NQNKPG', '0770000000', '1995-11-08', 'member'),
('Fatma Brahimi',  'fatma@mail.com',   '$2y$10$TKh8H1.PtIK1KlJRbBjT1.G6Q9O0rV5p7Z8VjJ2cKdOH/A.NQNKPG', '0551111111', '1997-07-15', 'member'),
('Malika Souilah', 'malika@mail.com',  '$2y$10$TKh8H1.PtIK1KlJRbBjT1.G6Q9O0rV5p7Z8VjJ2cKdOH/A.NQNKPG', '0662222222', '1999-01-30', 'member');


CREATE TABLE IF NOT EXISTS plans (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    name            VARCHAR(50) NOT NULL,
    price           INT NOT NULL,
    duration_months INT NOT NULL DEFAULT 1,
    description     TEXT,
    features        TEXT
);

INSERT INTO plans (name, price, duration_months, description, features) VALUES
('Bronze', 3500, 1,
 'Perfect for beginners who want flexible access to essential gym facilities.',
 'Access to Cardio Room & Weights Area;2 Group Classes per week;Use of Locker & Shower facilities;Free Fit Bar Welcome Drink;Free orientation session'),

('Silver', 7000, 1,
 'Designed for members who train regularly and want more variety.',
 'Full access to Cardio Room, Weights Area and Pilates Studio;Unlimited Group Classes;Swimming Pool & Paddle Area Access;Priority Booking;Use of Locker & Shower facilities'),

('Gold', 12000, 1,
 'The complete SheFit Club experience with unlimited access and exclusive benefits.',
 'All-access pass to all gym facilities;Unlimited Group Classes;Pre/Postnatal Fitness Room;Personal Training Sessions;Exclusive Wellness Perks;VIP Member Benefits;Free Nutrition Consultation');


CREATE TABLE IF NOT EXISTS memberships (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    user_id    INT NOT NULL,
    plan_id    INT NOT NULL,
    start_date DATE NOT NULL,
    end_date   DATE NOT NULL,
    status     ENUM('active','expired','cancelled') DEFAULT 'active',
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (plan_id) REFERENCES plans(id)
);


INSERT INTO memberships (user_id, plan_id, start_date, end_date, status) VALUES
(2, 1, '2026-03-01', '2026-04-01', 'active'),
(3, 2, '2026-03-02', '2026-04-02', 'active'),
(4, 3, '2026-03-03', '2026-04-03', 'active'),
(5, 2, '2026-03-04', '2026-04-04', 'active'),
(6, 3, '2026-03-05', '2026-04-05', 'active');


CREATE TABLE IF NOT EXISTS trainers (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    name             VARCHAR(100) NOT NULL,
    specialty        VARCHAR(100),
    bio              TEXT,
    photo_path       VARCHAR(255),
    years_experience INT DEFAULT 0
);

INSERT INTO trainers (name, specialty, bio, photo_path, years_experience) VALUES
('Sarah J.',  'Pilates Core Trainer',       'Sarah specializes in Pilates and core stability training. She helps members improve posture, flexibility, and muscle control.',        'images/sarah.jpg',       6),
('Maria L.',  'Kickboxing Fitness Trainer',  'Maria leads high-energy kickboxing classes designed to improve endurance, strength, and self-confidence.',                           'images/maria.jpg',       7),
('Amel D.',   'Prenatal Yoga Trainer',       'Amel specializes in prenatal yoga and safe fitness programs for expecting mothers.',                                                 'images/amel.jpg',        5),
('Lamia S.',  'Aqua Fitness Trainer',        'Lamia leads aquatic fitness classes helping members build strength with low-impact water workouts.',                                 'images/lamia.jpg',       6),
('Sofia M.',  'Paddle Training Coach',       'Sofia coaches paddle training sessions that improve coordination, reaction speed, and agility.',                                     'images/sofia.jpg',       4),
('Farah B.',  'Strength Training Coach',     'Farah specializes in strength and resistance training. She helps members build muscle and improve endurance safely.',               'images/farah (2).jpg',   8);


CREATE TABLE IF NOT EXISTS classes (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    trainer_id       INT NOT NULL,
    name             VARCHAR(100) NOT NULL,
    day_of_week      VARCHAR(20) NOT NULL,
    start_time       TIME NOT NULL,
    duration_minutes INT NOT NULL,
    difficulty       ENUM('Beginner','Intermediate','Advanced') DEFAULT 'Beginner',
    capacity         INT DEFAULT 15,
    FOREIGN KEY (trainer_id) REFERENCES trainers(id)
);

INSERT INTO classes (trainer_id, name, day_of_week, start_time, duration_minutes, difficulty, capacity) VALUES
(1, 'Pilates Core',       'Monday',    '10:00:00', 45, 'Beginner',     15),
(2, 'Kickboxing Fitness', 'Tuesday',   '18:00:00', 50, 'Intermediate', 20),
(3, 'Prenatal Yoga',      'Wednesday', '11:00:00', 40, 'Beginner',     12),
(4, 'Aqua Fitness',       'Thursday',  '17:00:00', 45, 'Beginner',     18),
(5, 'Paddle Training',    'Friday',    '16:00:00', 60, 'Intermediate', 14),
(6, 'Strength Training',  'Saturday',  '10:00:00', 50, 'Advanced',     16);


CREATE TABLE IF NOT EXISTS class_bookings (
    id        INT AUTO_INCREMENT PRIMARY KEY,
    user_id   INT NOT NULL,
    class_id  INT NOT NULL,
    booked_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_booking (user_id, class_id),
    FOREIGN KEY (user_id)  REFERENCES users(id)   ON DELETE CASCADE,
    FOREIGN KEY (class_id) REFERENCES classes(id) ON DELETE CASCADE
);


CREATE TABLE IF NOT EXISTS contact_messages (
    id           INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100) NOT NULL,
    email        VARCHAR(150) NOT NULL,
    subject      VARCHAR(200),
    message      TEXT NOT NULL,
    submitted_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    read_status  TINYINT(1) DEFAULT 0
);
