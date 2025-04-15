<?php
include '../config/database.php';

// Initialize message variable
$message = "";
$message_type = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = trim($_POST["name"]);
    $email = trim($_POST["email"]);
    $password = $_POST["password"];
    $phone = trim($_POST["phone"]);
    
    // Get role from form (with attendee as default)
    $role = isset($_POST["role"]) ? $_POST["role"] : "attendee";

    // Validate required fields
    if (empty($name) || empty($email) || empty($password)) {
        $message = "All fields are required.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "error";
    } else {
        // Hash password securely
        $password_hash = password_hash($password, PASSWORD_BCRYPT);

        // Insert user into database
        $sql = "INSERT INTO Users (name, email, password_hash, phone_number, role) 
                VALUES (?, ?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sssss", $name, $email, $password_hash, $phone, $role);

        if ($stmt->execute()) {
            $message = "Registration successful! You can now log in.";
            $message_type = "success";
        } else {
            $message = "Error: " . $stmt->error;
            $message_type = "error";
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
            background: url('../images/image.png') no-repeat center center/cover;
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

        select {
            appearance: none;
            background-image: url("data:image/svg+xml;charset=US-ASCII,%3Csvg%20xmlns%3D%22http%3A%2F%2Fwww.w3.org%2F2000%2Fsvg%22%20width%3D%22292.4%22%20height%3D%22292.4%22%3E%3Cpath%20fill%3D%22%23FFFFFF%22%20d%3D%22M287%2069.4a17.6%2017.6%200%200%200-13-5.4H18.4c-5%200-9.3%201.8-12.9%205.4A17.6%2017.6%200%200%200%200%2082.2c0%205%201.8%209.3%205.4%2012.9l128%20127.9c3.6%203.6%207.8%205.4%2012.8%205.4s9.2-1.8%2012.8-5.4L287%2095c3.5-3.5%205.4-7.8%205.4-12.8%200-5-1.9-9.2-5.5-12.8z%22%2F%3E%3C%2Fsvg%3E");
            background-repeat: no-repeat;
            background-position: right 12px top 50%;
            background-size: 12px auto;
            padding-right: 30px;
        }

        select option {
            background-color: #6a11cb;
            color: white;
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
        
        .login-link {
            margin-top: 15px;
            font-size: 14px;
        }

        .login-link a {
            color: white;
            text-decoration: underline;
            font-weight: 500;
        }

        .login-link a:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        /* New styles for the message container */
        .message-container {
            position: fixed;
            bottom: 30px;
            left: 50%;
            transform: translateX(-50%);
            padding: 15px 25px;
            border-radius: 8px;
            font-weight: 500;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.2);
            z-index: 1000;
            max-width: 80%;
            text-align: center;
            animation: fadeIn 0.5s ease-in-out;
        }

        .success-message {
            background: rgba(76, 175, 80, 0.85);
            color: white;
            backdrop-filter: blur(5px);
        }

        .error-message {
            background: rgba(244, 67, 54, 0.85);
            color: white;
            backdrop-filter: blur(5px);
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translate(-50%, 20px); }
            to { opacity: 1; transform: translate(-50%, 0); }
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
            <select name="role">
                <option value="attendee">Attendee</option>
                <option value="organizer">Organizer</option>
            </select>
            <button type="submit">Register</button>
        </form>
        <div class="login-link">
            Already have an account? <a href="login.php">Login</a>
        </div>
    </div>

    <?php if (!empty($message)): ?>
    <div class="message-container <?php echo $message_type == 'success' ? 'success-message' : 'error-message'; ?>">
        <?php echo $message; ?>
    </div>

    <script>
        // Auto-hide the message after 5 seconds
        setTimeout(function() {
            document.querySelector('.message-container').style.opacity = '0';
            setTimeout(function() {
                document.querySelector('.message-container').style.display = 'none';
            }, 500);
        }, 5000);
    </script>
    <?php endif; ?>
</body>
</html>