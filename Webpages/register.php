<?php
$servername = "localhost";
$username = "root";
$password = "";
$dbname = "eventify";

// Create connection
$conn = new mysqli($servername, $username, $password, $dbname);

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
}

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $phone = trim($_POST["phone"]);
    $role = $_POST["role"];

    // Validate required fields
    if (empty($name) || empty($email) || empty($password) || empty($role)) {
        echo "All fields are required.";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        echo "Invalid email format.";
    } else {
        // Hash password securely
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Insert user into database
        $sql = "INSERT INTO Users (name, email, password_hash, phone_number, role) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $password_hash, $phone, $role);

        if ($stmt->execute()) {
            echo "Registration successful! You can now log in.";
        } else {
            echo "Error: " . $stmt->error;
        }

        // Close statement
        $stmt->close();
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Registration</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
</head>
<body class="container mt-5">
    <h2 class="mb-4">Register for Eventify</h2>
    <form action="register.php" method="POST" class="w-50">
        <div class="mb-3">
            <label class="form-label">Full Name:</label>
            <input type="text" name="name" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Email:</label>
            <input type="email" name="email" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Password:</label>
            <input type="password" name="password" class="form-control" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Phone Number:</label>
            <input type="text" name="phone" class="form-control">
        </div>

        <div class="mb-3">
            <label class="form-label">Role:</label>
            <select name="role" class="form-select" required>
                <option value="admin">Admin</option>
                <option value="organizer">Organizer</option>
                <option value="attendee">Attendee</option>
                <option value="sponsor">Sponsor</option>
            </select>
        </div>

        <button type="submit" class="btn btn-primary">Register</button>
    </form>
</body>
</html>