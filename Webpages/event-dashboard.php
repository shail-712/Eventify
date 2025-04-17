<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php');
    exit();
}

require_once '../config/database.php';

$user_id = $_SESSION['user_id'];
$user_query = "SELECT name FROM Users WHERE user_id = ?";
$stmt_user = $conn->prepare($user_query);
$stmt_user->bind_param("i", $user_id);
$stmt_user->execute();
$user_result = $stmt_user->get_result();
$user = $user_result->fetch_assoc();

$upcoming_events_query = "
    SELECT COUNT(*) as event_count 
    FROM eventregistrations er
    JOIN events e ON er.event_id = e.event_id
    WHERE er.user_id = ? AND e.start_time >= CURDATE()";
$stmt_events = $conn->prepare($upcoming_events_query);
$stmt_events->bind_param("i", $user_id);
$stmt_events->execute();
$events_result = $stmt_events->get_result();
$events_count = $events_result->fetch_assoc()['event_count'];

$joined_events_query = "
    SELECT 
        e.event_id, e.organizer_id, e.title, e.description, 
        ec.category_name AS category, e.location, 
        e.start_time, e.end_time, e.ticket_price, 
        er.registration_id, t.ticket_type,
        COUNT(DISTINCT t.ticket_id) AS ticket_count
    FROM eventregistrations er
    JOIN events e ON er.event_id = e.event_id
    LEFT JOIN EventCategories ec ON e.category_id = ec.category_id
    LEFT JOIN Tickets t ON t.event_id = e.event_id AND t.attendee_id = er.user_id
    WHERE er.user_id = ? AND e.start_time >= CURDATE()
    GROUP BY e.event_id, er.registration_id
    ORDER BY e.start_time ASC";
$stmt = $conn->prepare($joined_events_query);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$joined_events_result = $stmt->get_result();

$all_events_query = "
    SELECT e.event_id, e.title, e.start_time, e.end_time, 
           ec.category_name AS category, COUNT(er.user_id) AS registered_count,
           e.ticket_price
    FROM events e
    LEFT JOIN EventCategories ec ON e.category_id = ec.category_id
    LEFT JOIN eventregistrations er ON e.event_id = er.event_id
    WHERE e.start_time >= CURDATE()
    GROUP BY e.event_id
    ORDER BY e.start_time ASC";
$all_events_result = $conn->query($all_events_query);
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>My Event Dashboard</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.css" rel="stylesheet">
  <style>
    @import url('https://fonts.googleapis.com/css2?family=Anton+SC&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap');

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
      font-family: "Anton SC", serif;
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
  </style>
</head>
<body>
<div class="top-header">
    <a href="/" class="logo">Eventify</a>
    <ul class="nav">
        <li><a href="../index.php">Home</a></li>
        <li><a href="event_page.php">Events</a></li>
        <li><a href="event-dashboard.php">Dashboard</a></li>
        <li><a href="about.php">About</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'organizer' || $_SESSION['role'] == 'admin')): ?>
                <li><a href="manage_event.php">Manage Events</a></li>
            <?php endif; ?>
            <li><a href="profile.php">My Profile</a></li>
      
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</div>
<div class="container mt-4">
  <div class="welcome-banner">
    <h2>Welcome, <?php echo htmlspecialchars($user['name']); ?>!</h2>
    <p>
      <?php 
      $hour = date('H');
      if ($hour < 12) {
        echo "Good morning! ";
      } elseif ($hour < 18) {
        echo "Good afternoon! ";
      } else {
        echo "Good evening! ";
      }
      ?>
      You have <?php echo $events_count; ?> upcoming event<?php echo $events_count != 1 ? 's' : ''; ?>.
    </p>
  </div>
  <div class="row">
    <div class="col-md-4">
      <h3>My Upcoming Events</h3>
      <?php if ($joined_events_result->num_rows > 0): ?>
        <?php while($event = $joined_events_result->fetch_assoc()): ?>
          <div class="card event-card">
            <div class="card-body">
              <h5 class="card-title">
                <?php echo htmlspecialchars($event['title']); ?>
                <span class="badge bg-primary category-badge">
                  <?php echo htmlspecialchars($event['category'] ?? 'Uncategorized'); ?>
                </span>
              </h5>
              <p class="card-text">
                <strong>Date:</strong> <?php echo date('F d, Y H:i', strtotime($event['start_time'])); ?><br>
                <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?><br>
                <strong>Ticket Price:</strong> $<?php echo number_format($event['ticket_price'], 2); ?><br>
                <strong>Ticket Type:</strong> <?php echo htmlspecialchars($event['ticket_type'] ?? 'General'); ?><br>
                <strong>Tickets Purchased:</strong> <?php echo intval($event['ticket_count']); ?><br>
                <a href="attendance.php?event_id=<?php echo $event['event_id']; ?>" class="btn btn-outline-primary mt-2 btn-sm">Manage Attendance</a>
              </p>
            </div>
          </div>
        <?php endwhile; ?>
      <?php else: ?>
        <p>You haven't joined any upcoming events.</p>
      <?php endif; ?>
    </div>
    <div class="col-md-8">
      <div id="calendar"></div>
    </div>
  </div>
</div>
<button class="scroll-top" id="scrollTopBtn">&#8679;</button>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/fullcalendar@5.10.2/main.min.js"></script>
<script>
  const scrollBtn = document.getElementById("scrollTopBtn");
  window.onscroll = () => scrollBtn.classList.toggle("show", window.scrollY > 200);
  scrollBtn.onclick = () => window.scrollTo({ top: 0, behavior: 'smooth' });

  document.addEventListener('DOMContentLoaded', function() {
    var calendarEl = document.getElementById('calendar');
    var calendar = new FullCalendar.Calendar(calendarEl, {
      initialView: 'dayGridMonth',
      events: [
        <?php 
        $events = [];
        if ($all_events_result->num_rows > 0) {
          while($event = $all_events_result->fetch_assoc()) {
            $events[] = "{
              title: '".htmlspecialchars($event['title'])."',
              start: '".date('Y-m-d H:i:s', strtotime($event['start_time']))."',
              end: '".date('Y-m-d H:i:s', strtotime($event['end_time']))."',
              extendedProps: {
                category: '".htmlspecialchars($event['category'] ?? 'Uncategorized')."',
                registeredCount: '".intval($event['registered_count'])."',
                ticketPrice: '".number_format($event['ticket_price'], 2)."'
              }
            }";
          }
          echo implode(',', $events);
        }
        ?>
      ]
    });
    calendar.render();
  });
</script>
</body>
</html>
<?php
$stmt_user->close();
$stmt_events->close();
$stmt->close();
$conn->close();
?>
