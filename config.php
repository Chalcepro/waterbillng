<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Twilio API credentials (replace with your real credentials)
define('TWILIO_SID', 'your_twilio_sid');
define('TWILIO_TOKEN', 'your_twilio_token');
define('TWILIO_PHONE', '+1234567890'); // Your Twilio phone number
define('TWILIO_WHATSAPP', '+1234567890'); // Your Twilio WhatsApp number

// Paystack API credentials (replace with your real credentials)
define('PAYSTACK_PUBLIC_KEY', 'pk_test_your_public_key_here'); // Your Paystack public key
define('PAYSTACK_SECRET_KEY', 'sk_test_your_secret_key_here'); // Your Paystack secret key

// Database configuration
define('DB_HOST', 'sql103.infinityfree.com');
define('DB_NAME', 'if0_40185927_waterbill_db');
define('DB_USER', 'if0_40185927');
define('DB_PASS', 'waterbillEst1');

// Email Configuration
define('MAIL_HOST', 'smtp.example.com');
define('MAIL_PORT', 587);
define('MAIL_USERNAME', 'noreply@waterbill.ng');
define('MAIL_PASSWORD', 'your_email_password');
define('MAIL_FROM_EMAIL', 'noreply@waterbill.ng');
define('MAIL_FROM_NAME', 'WaterBill NG');
define('MAIL_IS_SMTP', true);
define('MAIL_SMTP_AUTH', true);
define('MAIL_SMTP_SECURE', 'tls');

// ... [other constants] ...

try {
    // First connect without specifying database
    $pdo = new PDO("mysql:host=".DB_HOST, DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Create database if not exists
    $pdo->exec("CREATE DATABASE IF NOT EXISTS `".DB_NAME."`");
    $pdo->exec("USE `".DB_NAME."`");

    // Create tables if not exist
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS users (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            password VARCHAR(255) NOT NULL,
            first_name VARCHAR(50) NOT NULL,
            middle_name VARCHAR(50) DEFAULT NULL,
            surname VARCHAR(50) NOT NULL,
            flat_no VARCHAR(20) NOT NULL,
            role ENUM('user','admin') DEFAULT 'user',
            status ENUM('active','suspended') DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS payments (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            amount DECIMAL(10,2) NOT NULL,
            status ENUM('pending','approved','rejected') DEFAULT 'pending',
            method VARCHAR(50) DEFAULT NULL,
            transaction_id VARCHAR(100) DEFAULT NULL,
            receipt_image VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS subscriptions (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            start_date DATE NOT NULL,
            end_date DATE NOT NULL,
            status ENUM('active','expired') DEFAULT 'active',
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS fault_reports (
            id INT AUTO_INCREMENT PRIMARY KEY,
            user_id INT NOT NULL,
            description TEXT NOT NULL,
            status ENUM('open','in_progress','resolved') DEFAULT 'open',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id)
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS system_settings (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100) NOT NULL UNIQUE,
            value VARCHAR(255) NOT NULL
        ) ENGINE=InnoDB;

        CREATE TABLE IF NOT EXISTS pump_status (
            id INT AUTO_INCREMENT PRIMARY KEY,
            status ENUM('active','degraded','faulty','offline') NOT NULL DEFAULT 'active',
            message VARCHAR(255) DEFAULT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        ) ENGINE=InnoDB;
    ");

    // Insert initial settings if not exists
    $stmt = $pdo->query("SELECT COUNT(*) FROM system_settings");
    if ($stmt->fetchColumn() == 0) {
        $pdo->exec("INSERT INTO system_settings (name, value) VALUES 
            ('min_payment', '2000'),
            ('company_name', 'WaterBill NG'),
            ('support_email', 'info@waterbill.ng.com'),
            ('auto_approval', '0')");
    }

    // Create admin user if not exists
    $adminCheck = $pdo->query("SELECT COUNT(*) FROM users WHERE username = 'admin'");
    if ($adminCheck->fetchColumn() == 0) {
        $hashedPassword = password_hash('admin123', PASSWORD_DEFAULT);
        $pdo->exec("INSERT INTO users (username, email, password, first_name, surname, flat_no, role) 
                    VALUES ('admin', 'admin@waterbill.ng', '$hashedPassword', 'System', 'Administrator', 'ADMIN001', 'admin')");
    }

} catch (PDOException $e) {
    die("Database error: " . $e->getMessage());
}
?>