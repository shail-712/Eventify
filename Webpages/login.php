<?php
include '../config/database.php';

// Initialize message variable
$message = "";
$message_type = "";

// Handle form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = trim($_POST["email"]);
    $password = $_POST["password"];

    // Validate required fields
    if (empty($email) || empty($password)) {
        $message = "Email and password are required.";
        $message_type = "error";
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $message = "Invalid email format.";
        $message_type = "error";
    } else {
        // Retrieve user from database
        $sql = "SELECT user_id, name, email, password_hash, role FROM Users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $email);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($result->num_rows === 1) {
            $user = $result->fetch_assoc();
            
            // Verify password
            if (password_verify($password, $user['password_hash'])) {
                // Start session and store user information
                session_start();
                $_SESSION['user_id'] = $user['user_id'];
                $_SESSION['user_name'] = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                $_SESSION['user_role'] = $user['role'];
                
                // Add these two critical session variables to match what your event pages expect
                $_SESSION['loggedin'] = true;
                $_SESSION['role'] = $user['role'];
                
                // Redirect based on role
                $message = "Login successful! Welcome, " . $user['name'] . "!";
                $message_type = "success";
                
                // Redirect after successful login
                header("Location: event-dashboard.php");
                exit();
            } else {
                $message = "Invalid email or password.";
                $message_type = "error";
            }
        } else {
            $message = "Invalid email or password.";
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
    <title>Login - Eventify</title>
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

        input, button {
            width: 100%;
            padding: 12px;
            margin: 10px 0;
            border: none;
            border-radius: 6px;
            font-size: 16px;
        }

        input {
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

        .register-link {
            margin-top: 15px;
            font-size: 14px;
        }

        .register-link a {
            color: white;
            text-decoration: underline;
            font-weight: 500;
        }

        .register-link a:hover {
            color: rgba(255, 255, 255, 0.8);
        }

        /* Message container styles */
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
        <h2>Login to Eventify</h2>
        <form action="login.php" method="POST">
            <input type="email" name="email" placeholder="Email" required>
            <input type="password" name="password" placeholder="Password" required>
            <button type="submit">Login</button>
        </form>
        <div class="register-link">
            Don't have an account? <a href="register.php">Register</a>
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