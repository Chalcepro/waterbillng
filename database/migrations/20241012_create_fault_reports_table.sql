-- Create fault_reports table
CREATE TABLE IF NOT EXISTS fault_reports (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    category VARCHAR(100) NOT NULL,
    description TEXT NOT NULL,
    photo_path VARCHAR(255) NULL,
    status ENUM('open', 'in_progress', 'resolved', 'rejected') NOT NULL DEFAULT 'open',
    admin_notes TEXT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NULL ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    INDEX (status),
    INDEX (user_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
