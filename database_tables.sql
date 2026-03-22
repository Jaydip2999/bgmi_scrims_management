DROP DATABASE IF EXISTS bgmi_scrims;
CREATE DATABASE bgmi_scrims CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;
USE bgmi_scrims;

CREATE TABLE users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(120) NOT NULL,
    email VARCHAR(180) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin','player') NOT NULL DEFAULT 'player',
    team_name VARCHAR(120) DEFAULT NULL,
    bgmi_uid VARCHAR(80) DEFAULT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    is_banned TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE scrims (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    date DATE DEFAULT NULL,
    time TIME DEFAULT NULL,
    match_time DATETIME DEFAULT NULL,
    map VARCHAR(80) DEFAULT NULL,
    mode ENUM('Solo','Duo','Squad') NOT NULL DEFAULT 'Squad',
    entry_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    slots INT NOT NULL DEFAULT 0,
    total_slots INT NOT NULL DEFAULT 0,
    prize_pool DECIMAL(10,2) NOT NULL DEFAULT 0,
    registration_status ENUM('open','closed','full') NOT NULL DEFAULT 'open',
    room_id VARCHAR(120) DEFAULT NULL,
    room_password VARCHAR(120) DEFAULT NULL,
    rules_text TEXT DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE prizes (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scrim_id INT NOT NULL,
    rank_position INT NOT NULL,
    percentage DECIMAL(5,2) NOT NULL,
    amount DECIMAL(10,2) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_scrim_rank (scrim_id, rank_position),
    CONSTRAINT fk_prizes_scrim FOREIGN KEY (scrim_id) REFERENCES scrims(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE bookings (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    scrim_id INT NOT NULL,
    slot_number INT DEFAULT NULL,
    status ENUM('pending','approved','rejected','cancelled') NOT NULL DEFAULT 'pending',
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_user_scrim (user_id, scrim_id),
    CONSTRAINT fk_bookings_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_bookings_scrim FOREIGN KEY (scrim_id) REFERENCES scrims(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE payments (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    scrim_id INT NOT NULL,
    amount DECIMAL(10,2) NOT NULL DEFAULT 0,
    screenshot VARCHAR(255) DEFAULT NULL,
    transaction_ref VARCHAR(120) DEFAULT NULL,
    status ENUM('pending','approved','rejected') NOT NULL DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_payments_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_payments_scrim FOREIGN KEY (scrim_id) REFERENCES scrims(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE results (
    id INT AUTO_INCREMENT PRIMARY KEY,
    scrim_id INT NOT NULL,
    user_id INT NOT NULL,
    kills INT NOT NULL DEFAULT 0,
    rank_position INT NOT NULL DEFAULT 0,
    points INT NOT NULL DEFAULT 0,
    payout_status ENUM('unpaid','paid') NOT NULL DEFAULT 'unpaid',
    payout_transaction_id VARCHAR(120) DEFAULT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uniq_result (scrim_id, user_id),
    CONSTRAINT fk_results_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    CONSTRAINT fk_results_scrim FOREIGN KEY (scrim_id) REFERENCES scrims(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE scrim_templates (
    id INT AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(160) NOT NULL,
    mode ENUM('Solo','Duo','Squad') NOT NULL DEFAULT 'Squad',
    map VARCHAR(80) DEFAULT NULL,
    entry_fee DECIMAL(10,2) NOT NULL DEFAULT 0,
    total_slots INT NOT NULL DEFAULT 0,
    rules_text TEXT DEFAULT NULL,
    start_time TIME NOT NULL,
    create_days_ahead INT NOT NULL DEFAULT 1,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE notifications (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT DEFAULT NULL,
    scrim_id INT DEFAULT NULL,
    type ENUM('broadcast','reminder','system') NOT NULL DEFAULT 'system',
    title VARCHAR(160) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) NOT NULL DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    reminder_key VARCHAR(190) DEFAULT NULL,
    UNIQUE KEY uniq_reminder_key (reminder_key)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
