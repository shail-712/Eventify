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

// SQL statements to create tables with the updated schema
$tables = [
    "CREATE TABLE IF NOT EXISTS Users (
        user_id INT PRIMARY KEY AUTO_INCREMENT,
        name VARCHAR(255),
        email VARCHAR(255) UNIQUE,
        password_hash VARCHAR(255),
        phone_number VARCHAR(20),
        role ENUM('user', 'organizer', 'admin'),
        profile_image VARCHAR(255),
        bio TEXT,
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
        max_attendees INT,
        event_image VARCHAR(255),
        banner_image VARCHAR(255),
        status ENUM('draft', 'published', 'cancelled', 'completed') DEFAULT 'draft',
        FOREIGN KEY (organizer_id) REFERENCES Users(user_id),
        FOREIGN KEY (category_id) REFERENCES EventCategories(category_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS EventRegistrations (
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
        status ENUM('valid', 'used', 'refunded', 'cancelled') DEFAULT 'valid',
        FOREIGN KEY (event_id) REFERENCES Events(event_id),
        FOREIGN KEY (attendee_id) REFERENCES Users(user_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS Payments (
        payment_id INT PRIMARY KEY AUTO_INCREMENT,
        ticket_id INT,
        amount DECIMAL(10,2),
        payment_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        payment_method VARCHAR(50),
        transaction_id VARCHAR(255),
        status ENUM('pending', 'completed', 'failed', 'refunded') DEFAULT 'pending',
        FOREIGN KEY (ticket_id) REFERENCES Tickets(ticket_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS EventImages (
        image_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT,
        image_path VARCHAR(255),
        caption TEXT,
        is_featured BOOLEAN DEFAULT FALSE,
        upload_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES Events(event_id)
    )",
    
    "CREATE TABLE IF NOT EXISTS Reviews (
        review_id INT PRIMARY KEY AUTO_INCREMENT,
        event_id INT,
        user_id INT,
        rating INT CHECK (rating BETWEEN 1 AND 5),
        comment TEXT,
        review_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (event_id) REFERENCES Events(event_id),
        FOREIGN KEY (user_id) REFERENCES Users(user_id)
    )",
    "CREATE TABLE IF NOT EXISTS Attendance (
        attendance_id INT PRIMARY KEY AUTO_INCREMENT,
        ticket_id INT,
        check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        check_in_by INT,
        notes TEXT,
        FOREIGN KEY (ticket_id) REFERENCES Tickets(ticket_id),
        FOREIGN KEY (check_in_by) REFERENCES Users(user_id)
    )"
];

// Execute each table creation query
foreach ($tables as $table) {
    if (!$conn->query($table)) {
        echo "Error creating table: " . $conn->error . "<br>";
        echo "Query: " . $table . "<br><br>";
    }
}

$check_categories = $conn->query("SELECT COUNT(*) as count FROM EventCategories");
if ($check_categories && $check_categories->fetch_assoc()['count'] == 0) {
    // Define the categories to add
    $categories = [
        ['name' => 'Music', 'description' => 'Music events, concerts, and performances'],
        ['name' => 'Sports', 'description' => 'Sporting events, competitions, and tournaments'],
        ['name' => 'Workshop', 'description' => 'Educational workshops and hands-on learning sessions'],
        ['name' => 'Conference', 'description' => 'Professional conferences, meetings, and seminars'],
        ['name' => 'Festival', 'description' => 'Cultural and entertainment festivals']
    ];
    
    // Insert categories into the database
    foreach ($categories as $category) {
        $insert = $conn->prepare("INSERT INTO EventCategories (category_name, description) VALUES (?, ?)");
        $insert->bind_param("ss", $category['name'], $category['description']);
        $insert->execute();
        $insert->close();
    }
}

?>
