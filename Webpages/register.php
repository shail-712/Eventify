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
    
    <style>
        body {
            margin: 0;
            font-family: 'Montserrat', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
            background: url('images/image.png') no-repeat center center/cover;
            position: relative;
        }

        body::before {
            content: "";
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(135deg, #6a11cb, #2575fc);
            opacity: 0.85;
        }

        .container {
            position: relative;
            background: rgba(255, 255, 255, 0.15);
            padding: 30px;
            width: 300px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.3);
            backdrop-filter: blur(12px);
            color: white;
        }

        h2 {
            margin-bottom: 20px;
            font-size: 24px;
            font-weight: 700;
        }

        input, select, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 6px;
            font-size: 16px;
        }

        input, select {
            background: rgba(255, 255, 255, 0.3);
            color: white;
        }

        input::placeholder {
            color: rgba(255, 255, 255, 0.7);
        }

        button {
            background: linear-gradient(135deg, #2575fc, #6a11cb);
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: 0.3s;
        }

        button:hover {
            background: linear-gradient(135deg, #1e5bd3, #5419a9);
        }
    </style>
</head>
<body>
    <div class="container">
        <h2>Register for Eventify</h2>
        <form action="register.php" method="POST">
            <input type="text" name="name" placeholder="Full Name" required>
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <input type="text" name="phone" placeholder="Phone Number">
            <select name="role" required>
                <option value="" disabled selected>Select Role</option>
                <option value="admin">Admin</option>
                <option value="organizer">Organizer</option>
                <option value="attendee">Attendee</option>
                <option value="sponsor">Sponsor</option>
            </select>
            <button type="submit">Register</button>
        </form>
    </div>
</body>
</html>
