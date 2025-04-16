<?php
session_start();
include('database.php');

// Check if the user is logged in
if (!isset($_SESSION['user_id'])) {
    die("You need to be logged in to register for an event.");
}

$user_id = $_SESSION['user_id']; // Assuming the user ID is stored in session
$event_id = isset($_POST['event_id']) ? $_POST['event_id'] : null;

if ($_SERVER['REQUEST_METHOD'] == 'POST' && $event_id) {
    // Check if the user has already registered for this event
    $sql = "SELECT * FROM EventRegistrations WHERE user_id = ? AND event_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("ii", $user_id, $event_id);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $message = "You have already registered for this event.";
    } else {
        // Register the user for the event
        $insert_sql = "INSERT INTO EventRegistrations (user_id, event_id) VALUES (?, ?)";
        $stmt = $conn->prepare($insert_sql);
        $stmt->bind_param("ii", $user_id, $event_id);
        if ($stmt->execute()) {
            // Registration success message
            $message = "Registration successful!";
        } else {
            $message = "Error registering for the event.";
        }
    }
} else {
    $message = null;
}

// Fetch event data from the database
$sql = "SELECT * FROM Events WHERE status = 'published'"; // Only show published events
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register for Event</title>
    <style>
        * {
          margin: 0;
          padding: 0;
          box-sizing: border-box;
          font-family: 'Poppins', sans-serif;
        }
        .top-header {
          display: flex;
          flex-wrap: wrap;
          justify-content: space-between;
          align-items: center;
          padding: 20px 40px;
          background: linear-gradient(90deg, #4e1c89, #5e2ced);
          box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
        }
        .top-header .logo {
          font-size: 28px;
          font-weight: 800;
          color: #ffffff;
          text-decoration: none;
        }
        .top-header .nav {
          list-style: none;
          display: flex;
          gap: 20px;
        }
        .top-header .nav a {
          text-decoration: none;
          font-weight: 300;
          font-size: 15px;
          color: #ffffff;
          padding: 8px 18px;
          border: 2px solid #ffffff;
          border-radius: 25px;
          transition: all 0.3s ease;
          background-color: transparent;
        }
        .top-header .nav a:hover {
          background-color: #ffffff;
          color: #5e2ced;
        }
        .scroll-top {
          position: fixed;
          bottom: 20px;
          right: 20px;
          background-color: #5e2ced;
          color: white;
          padding: 10px 15px;
          border: none;
          border-radius: 50%;
          font-size: 18px;
          cursor: pointer;
          display: none;
          z-index: 1000;
        }
        .scroll-top.show {
          display: block;
        }
        .welcome-banner {
          background: linear-gradient(135deg, #6a11cb 0%, #2575fc 100%);
          color: white;
          padding: 20px;
          border-radius: 10px;
          margin-bottom: 20px;
          box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .event-card {
          margin-bottom: 15px;
          border-left: 4px solid #007bff;
        }
        #calendar {
          max-width: 100%;
          height: 500px;
        }
        .category-badge {
          margin-left: 10px;
        }
        /* Basic CSS for the loading screen */
        #loading {
          display: none;
          position: fixed;
          top: 0;
          left: 0;
          width: 100%;
          height: 100%;
          background-color: rgba(0, 0, 0, 0.5);
          color: white;
          text-align: center;
          font-size: 24px;
          padding-top: 200px;
        }
    </style>
</head>
<body>

    <!-- Top Header -->
    <header class="top-header">
        <a href="#" class="logo">Event Registration</a>
        <nav>
            <ul class="nav">
                <li><a href="#home">Home</a></li>
                <li><a href="#events">Events</a></li>
                <li><a href="#contact">Contact</a></li>
            </ul>
        </nav>
    </header>

    <!-- Show the loading animation if the registration is being processed -->
    <div id="loading">Locking you in...</div>

    <!-- Welcome Banner -->
    <div class="welcome-banner">
        <h2>Welcome to the Event Registration Portal</h2>
        <p>Select an event to register and secure your spot!</p>
    </div>

    <!-- Event Registration Form -->
    <section id="events">
        <h2>Register for an Event</h2>
        <form method="POST" action="register_event.php" onsubmit="showLoading()">
            <label for="event_id">Choose an Event:</label>
            <select name="event_id" id="event_id">
                <?php while ($event = $result->fetch_assoc()): ?>
                    <option value="<?= $event['event_id'] ?>"><?= $event['title'] ?></option>
                <?php endwhile; ?>
            </select>
            <input type="submit" value="Register">
        </form>

        <!-- Registration Status Message -->
        <?php if ($message): ?>
            <p><?= $message ?></p>
        <?php endif; ?>
    </section>

    <script>
        // Function to show the loading animation when the form is submitted
        function showLoading() {
            document.getElementById('loading').style.display = 'block';
        }

        // If registration is successful, wait 5 seconds and redirect
        <?php if ($message == "Registration successful!"): ?>
            setTimeout(function() {
                alert("<?= $message ?>");
                window.location.href = "success_page.php"; // Redirect after 5 seconds
            }, 5000); // 5 seconds delay
        <?php endif; ?>
    </script>

</body>
</html>

<?php
// Close the database connection
$conn->close();
?>
