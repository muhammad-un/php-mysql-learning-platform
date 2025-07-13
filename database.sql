-- database.sql

-- Create the database if it doesn't already exist
-- This ensures that if you run the script multiple times, it won't throw an error
CREATE DATABASE IF NOT EXISTS `learning_platform`;

-- Select the newly created (or existing) database to work with
USE `learning_platform`;

-- Table for Users (Teachers, Students, Admins)
-- This table stores information about all users of the platform.
CREATE TABLE IF NOT EXISTS `users` (
    `user_id` INT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each user, auto-increments
    `name` VARCHAR(255) NOT NULL,             -- Full name of the user
    `email` VARCHAR(255) NOT NULL UNIQUE,     -- User's email, must be unique (used for login)
    `password` VARCHAR(255) NOT NULL,         -- Hashed password (NEVER store plain text passwords)
    `role` ENUM('student', 'teacher', 'admin') NOT NULL, -- User's role: student, teacher, or admin
    `registration_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP -- Automatically records when the user registered
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4; -- InnoDB supports foreign keys, utf8mb4 for full Unicode support

-- Table for Learning Topics
-- This table stores details about the learning topics uploaded by teachers.
CREATE TABLE IF NOT EXISTS `topics` (
    `topic_id` INT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each topic, auto-increments
    `teacher_id` INT NOT NULL,                -- Foreign key linking to the 'users' table (teacher's user_id)
    `title` VARCHAR(255) NOT NULL,            -- Title of the learning topic
    `description` TEXT NOT NULL,              -- Detailed description of the topic
    `video_url` VARCHAR(500) DEFAULT NULL,    -- URL for external video (YouTube/Vimeo) or path to local video file
    `pdf_path` VARCHAR(255) NOT NULL,         -- Path to the uploaded PDF notes file
    `upload_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Automatically records when the topic was uploaded
    -- Foreign key constraint: ensures teacher_id exists in the 'users' table
    -- ON DELETE CASCADE: if a teacher is deleted, all their topics are also deleted
    -- ON UPDATE CASCADE: if a teacher's user_id changes, this foreign key automatically updates
    FOREIGN KEY (`teacher_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table for Questions & Answers
-- This table stores questions asked by students and replies from teachers/admins.
CREATE TABLE IF NOT EXISTS `questions` (
    `question_id` INT AUTO_INCREMENT PRIMARY KEY, -- Unique identifier for each question, auto-increments
    `topic_id` INT NOT NULL,                  -- Foreign key linking to the 'topics' table
    `student_id` INT NOT NULL,                -- Foreign key linking to the 'users' table (student's user_id)
    `question_text` TEXT NOT NULL,            -- The actual question asked by the student
    `reply_text` TEXT DEFAULT NULL,           -- The teacher's or admin's reply to the question (can be NULL if unanswered)
    `timestamp` TIMESTAMP DEFAULT CURRENT_TIMESTAMP, -- Automatically records when the question was asked
    -- Foreign key constraint: ensures topic_id exists in the 'topics' table
    -- ON DELETE CASCADE: if a topic is deleted, all its questions are also deleted
    FOREIGN KEY (`topic_id`) REFERENCES `topics`(`topic_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    -- Foreign key constraint: ensures student_id exists in the 'users' table
    -- ON DELETE CASCADE: if a student is deleted, all their questions are also deleted
    FOREIGN KEY (`student_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Optional: Insert an initial administrator user for testing and initial setup
-- The password 'adminpass' is hashed using PHP's password_hash(..., PASSWORD_DEFAULT)
-- You can generate a new hash for a different password using: echo password_hash('your_new_password', PASSWORD_DEFAULT); in a PHP script.
INSERT IGNORE INTO `users` (`name`, `email`, `password`, `role`) VALUES
('Admin User', 'admin@example.com', '$2y$10$w095N6g9F0h0y3t4u5v6wO3gN0a4x4e2v0f7j1k8i9l0m2n8p0q2r6s9t0/2u0v3w/4x/5y/6z0a1b2c3d4e5f6g7h8i9j', 'admin');