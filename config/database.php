<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eventify";

// Create connection
$conn = new mysqli($servername, $username, $password);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Create database if it does not exist
$sql = "CREATE DATABASE IF NOT EXISTS $dbname";
if (!$conn->query($sql)) {
    die("Error creating database: " . $conn->error);
}

// Select the database
$conn->select_db($dbname);

// SQL statements to create tables
$tables = [
    "CREATE TABLE IF NOT EXISTS Users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255),
        email VARCHAR(255) UNIQUE,
        password_hash VARCHAR(255),
        phone_number VARCHAR(20),
        role ENUM('user', 'organizer', 'admin'),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )",
    "CREATE TABLE IF NOT EXISTS EventCategories (
        category_id INT PRIMARY KEY AUTO_INCREMENT,
        category_name VARCHAR(100),
        description TEXT
    )",
    "CREATE TABLE IF NOT EXISTS Events (
        event_id INT PRIMARY KEY AUTO_INCREMENT,
        organizer_id INT,
        category_id INT,
        title VARCHAR(255),
        description TEXT,
        location VARCHAR(255),
        start_time DATETIME,
        end_time DATETIME,
        ticket_price DECIMAL(10,2),
        FOREIGN KEY (organizer_id) REFERENCES Users(user_id),
        FOREIGN KEY (category_id) REFERENCES EventCategories(category_id)
    )",
    "CREATE TABLE IF NOT EXISTS event_registrations (
        registration_id INT PRIMARY KEY AUTO_INCREMENT,
        user_id INT,
        event_id INT,
        registration_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES Users(user_id),
        FOREIGN KEY (event_id) REFERENCES Events(event_id)
    )",
    "CREATE TABLE IF NOT EXISTS Tickets (
        ticket_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT,
        attendee_id INT,
        ticket_type VARCHAR(50),
        price DECIMAL(10,2),
        purchase_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES Events(event_id),
        FOREIGN KEY (attendee_id) REFERENCES Users(user_id)
    )"
];

// Execute each table creation query
foreach ($tables as $table) {
    if (!$conn->query($table)) {
        die("Error creating table: " . $conn->error);
    }
}

// Close connection
?>
