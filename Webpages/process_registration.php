<?php
session_start();
include('database.php');
require_once '../config/database.php';

// Ensure user is logged in and event ID is provided
if (!isset($_SESSION['user_id']) || !isset($_POST['event_id'])) {
    echo "Unauthorized access.";
    exit;
}

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'];

// Fetch event name from the Events table based on the event_id
$sql_event = "SELECT title FROM Events WHERE event_id = ?";
$stmt_event = $conn->prepare($sql_event);
$stmt_event->bind_param("i", $event_id);
$stmt_event->execute();
$result_event = $stmt_event->get_result();

if ($result_event->num_rows === 0) {
    echo "Event not found.";
    exit;
}

$event = $result_event->fetch_assoc();
$event_name = $event['title'];

// Insert into EventRegistrations
$sql = "INSERT INTO EventRegistrations (user_id, event_id) VALUES (?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("ii", $user_id, $event_id);

if (!$stmt->execute()) {
    echo "Failed to register: " . $stmt->error;
    exit;
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <title>Registering...</title>
  <style>
    body {
      display: flex;
      height: 100vh;
      align-items: center;
      justify-content: center;
      background: #f8f9fa;
      font-family: 'Poppins', sans-serif;
    }
    .loader-container {
      text-align: center;
    }
    .loader {
      border: 8px solid #f3f3f3;
      border-top: 8px solid #5e2ced;
      border-radius: 50%;
      width: 70px;
      height: 70px;
      animation: spin 3s linear infinite;
      margin: auto;
    }
    @keyframes spin {
      0% { transform: rotate(0deg); }
      100% { transform: rotate(360deg); }
    }
    .message {
      display: none;
      font-size: 20px;
      color: #333;
      margin-top: 20px;
    }
  </style>
</head>
<body>
  <div class="loader-container">
    <div class="loader"></div>
    <div class="message" id="success-message">
      You have successfully registered for <strong><?php echo $event_name; ?></strong>. Can't wait to see you there!
      
    </div>
  </div>

  <script>
    setTimeout(() => {
      document.querySelector('.loader').style.display = 'none';
      document.getElementById('success-message').style.display = 'block';
    }, 5000); // Adjusts the time until the message appears
  </script>
</body>
</html>