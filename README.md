# WaterBill NG
A smart water management solution designed specifically for Nigerian communities to streamline water bill payments, track consumption, and manage community water resources efficiently.

Know more about the project at: http://waterbill.page.gd

## Overview
WaterBill NG is a comprehensive digital platform that addresses the challenges of water billing and management in Nigerian communities. The system provides real-time monitoring, payment tracking, and community performance analytics to ensure efficient water resource management.

## Key Features
Usage Analytics
Track water consumption patterns

Get insights to optimize usage and reduce waste

Visual data representation for better understanding

Community Performance Monitoring
Monitor payment compliance across communities

Real-time water usage tracking

Community-wide performance metrics

Smart Notifications
Payment due alerts

Subscription expiry reminders

Important community announcements

Fault resolution updates

Receipt-Based Payments
Upload payment receipts for verification

Support for bank transfers and cash payments

Digital receipt storage and tracking

Fault Reporting System
Quick reporting of water supply issues

Track resolution progress

Streamlined communication with administrators

Mobile-First Design
Fully responsive design

Optimized for mobile devices

Accessible on all screen sizes

## Technology Stack
Frontend
HTML5 - Semantic markup

CSS3 - Custom properties, Flexbox, Grid

Bootstrap 5 - Responsive framework

Font Awesome - Icons

JavaScript - Interactive features

## Backend
PHP - Server-side processing

MySQL - Database management

PDO - Secure database connections

## Hosting
InfinityFree - Web hosting

MySQL Database - Data storage

## Prerequisites
Before running this project, ensure you have:

Web server with PHP support (Apache, Nginx)

MySQL database

Modern web browser

Internet connection (for CDN resources)

## Installation
Clone the repository

git clone https://github.com/yourusername/waterbill-ng.git
Set up the database

Create a MySQL database

Import the database schema (if available)

Update database credentials in the PHP file

## Configure the application
Update database connection details in the PHP section: (location to file "api/includes/db_connect.php")
php
$host = 'your_host';
$dbname = 'your_database';
$username = 'your_username';
$password = 'your_password';
Upload to web server

Upload all files to your web server

Ensure proper file permissions

Test the application

Navigate to the contact form

Submit a test message

Verify database insertion

## Database Schema
The application uses a contacts table with the following structure:

sql
CREATE TABLE contacts (
    id INT(11) PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    message TEXT NOT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
);

## How It Works
1. Account Creation
Users sign up with their details

Email verification process

Profile setup with property information

2. Profile Setup
Add property details

Configure meter information

Set preferred payment methods

3. Management & Payments
Monitor water consumption

Make payments with receipt upload

Track usage patterns

Report faults and issues

Configuration
Database Connection
Update the following in the PHP section:

## Contact & Support
Email: chalceswork@gmail.com

Phone: +234 906 969 5336

Location: Abuja, Nigeria

## Contributing
We welcome contributions to improve WaterBill NG! Please feel free to:

Fork the repository

Create a feature branch

Make your changes

Submit a pull request

## License
This project is proprietary software. All rights reserved.

## Acknowledgments
Nigerian communities for their feedback and testing

Development team for their dedication

Test users for valuable insights

WaterBill NG - Smart Water Management for Nigerian Communities
