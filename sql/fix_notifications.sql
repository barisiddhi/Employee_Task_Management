-- Your original script referenced users(id) but the table is users1.
-- Run this in phpMyAdmin or MySQL CLI if the notifications table was created with the wrong foreign key.

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS notifications;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT,
    `IS_READ` TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users1(id) ON DELETE CASCADE
);

SET FOREIGN_KEY_CHECKS = 1;



""CREATE TABLE users1 (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin', 'employee') NOT NULL,
    phone VARCHAR(15),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- Employee Profile Table
CREATE TABLE profiles (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    address TEXT,
    profile_image VARCHAR(255),
    FOREIGN KEY (user_id) REFERENCES users1(id) ON DELETE CASCADE
);

-- Tasks Table
CREATE TABLE tasks (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    assigned_to INT,
    assigned_by INT,
    status ENUM('pending', 'in progress', 'completed') DEFAULT 'pending',
    due_date DATE,
    snooze_until DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (assigned_to) REFERENCES users1(id) ON DELETE CASCADE,
    FOREIGN KEY (assigned_by) REFERENCES users1(id) ON DELETE CASCADE
);

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT,
    message TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users1(id) ON DELETE CASCADE
);

CREATE TABLE task_history (
    id INT AUTO_INCREMENT PRIMARY KEY,
    task_id INT,
    updated_by INT,
    old_status ENUM('pending', 'in progress', 'completed'),
    new_status ENUM('pending', 'in progress', 'completed'),
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (task_id) REFERENCES tasks(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users1(id) ON DELETE CASCADE
);