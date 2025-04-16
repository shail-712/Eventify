<?php
// Start session to maintain user login state
session_start();

// Check if user is logged in and is an organizer or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'organizer' && $_SESSION['role'] != 'admin')) {
    // Redirect to login page if not logged in or not authorized
    header("Location: login.php");
    exit();
}

include '../config/database.php';

// Handle event deletion
if (isset($_GET['delete']) && is_numeric($_GET['delete'])) {
    $event_id = $_GET['delete'];
    
    // First check if the user is the organizer of this event or an admin
    $check_stmt = $conn->prepare("SELECT organizer_id FROM Events WHERE event_id = ?");
    $check_stmt->bind_param("i", $event_id);
    $check_stmt->execute();
    $result = $check_stmt->get_result();
    
    if ($result->num_rows > 0) {
        $event = $result->fetch_assoc();
        
        // Check if current user is the organizer or an admin
        if ($event['organizer_id'] == $_SESSION['user_id'] || $_SESSION['role'] == 'admin') {
            // Delete related tickets first
            $delete_tickets = $conn->prepare("DELETE FROM Tickets WHERE event_id = ?");
            $delete_tickets->bind_param("i", $event_id);
            $delete_tickets->execute();
            $delete_tickets->close();
            
            // Delete related registrations
            $delete_registrations = $conn->prepare("DELETE FROM eventregistrations WHERE event_id = ?");
            $delete_registrations->bind_param("i", $event_id);
            $delete_registrations->execute();
            $delete_registrations->close();
            
            // Delete the event
            $delete_event = $conn->prepare("DELETE FROM Events WHERE event_id = ?");
            $delete_event->bind_param("i", $event_id);
            $delete_event->execute();
            $delete_event->close();
            
            header("Location: manage_event.php?deleted=1");
            exit();
        }
    }
    $check_stmt->close();
}

// Fetch events based on user role
$user_id = $_SESSION['user_id'];
$role = $_SESSION['role'];

if ($role == 'admin') {
    // Admins see all events
    $query = "SELECT e.*, u.name as organizer_name, c.category_name, 
              (SELECT COUNT(*) FROM eventregistrations WHERE event_id = e.event_id) as registration_count
              FROM Events e
              JOIN Users u ON e.organizer_id = u.user_id
              JOIN EventCategories c ON e.category_id = c.category_id
              ORDER BY e.start_time DESC";
    $stmt = $conn->prepare($query);
} else {
    // Organizers see only their events
    $query = "SELECT e.*, u.name as organizer_name, c.category_name, 
              (SELECT COUNT(*) FROM eventregistrations WHERE event_id = e.event_id) as registration_count
              FROM Events e
              JOIN Users u ON e.organizer_id = u.user_id
              JOIN EventCategories c ON e.category_id = c.category_id
              WHERE e.organizer_id = ?
              ORDER BY e.start_time DESC";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $user_id);
}

$stmt->execute();
$result = $stmt->get_result();
$events = [];

while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Events</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <div class="top-header">
    <a href="/" class="logo">Eventify</a>
    <ul class="nav">
        <li><a href="../index.php">Home</a></li>
        <li><a href="event_page.php">Events</a></li>
        <li><a href="event-dashboard.php">Dashboard</a></li>
        <li><a href="about.php">About</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <li><a href="profile.php">My Profile</a></li>
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</div>
<style>
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
        </style>
</head>
<body>
    <div class="container mt-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1>Manage Events</h1>
            <a href="create_event.php" class="btn btn-success">
                <i class="fas fa-plus"></i> Create New Event
            </a>
        </div>
        
        <?php if (isset($_GET['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                Event created successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_GET['deleted'])): ?>
            <div class="alert alert-info alert-dismissible fade show" role="alert">
                Event deleted successfully!
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>
        
      
        
        <?php if (empty($events)): ?>
            <div class="alert alert-info">
                You don't have any events yet. Click "Create New Event" to get started.
            </div>
        <?php else: ?>
            <div class="table-responsive">
                <table class="table table-striped table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>Title</th>
                            <th>Category</th>
                            <?php if ($_SESSION['role'] == 'admin'): ?>
                                <th>Organizer</th>
                            <?php endif; ?>
                            <th>Date & Time</th>
                            <th>Location</th>
                            <th>Price</th>
                            <th>Registrations</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($events as $event): ?>
                            <tr>
                                <td><?php echo htmlspecialchars($event['title']); ?></td>
                                <td><?php echo htmlspecialchars($event['category_name']); ?></td>
                                <?php if ($_SESSION['role'] == 'admin'): ?>
                                    <td><?php echo htmlspecialchars($event['organizer_name']); ?></td>
                                <?php endif; ?>
                                <td>
                                    <?php 
                                        $start = new DateTime($event['start_time']);
                                        $end = new DateTime($event['end_time']);
                                        echo $start->format('M d, Y g:i A') . ' - <br>' . $end->format('g:i A'); 
                                    ?>
                                </td>
                                <td><?php echo htmlspecialchars($event['location']); ?></td>
                                <td>$<?php echo number_format($event['ticket_price'], 2); ?></td>
                                <td>
                                    <span class="badge bg-info"><?php echo $event['registration_count']; ?></span>
                                </td>
                                <td>
                        <div class="btn-group">
                            <!-- View Event -->
                            <a href="search_events.php?id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-primary" title="View">
                                <i class="fas fa-eye"></i>
                            </a>

                            <!-- Delete Event -->
                            <a href="manage_event.php?delete=<?php echo $event['event_id']; ?>" 
                            class="btn btn-sm btn-danger" title="Delete"
                            onclick="return confirm('Are you sure you want to delete this event? This will also remove all registrations and tickets.')">
                                <i class="fas fa-trash"></i>
                            </a>

                            <!-- Manage Attendance -->
                            <a href="attendance.php?event_id=<?php echo $event['event_id']; ?>" class="btn btn-sm btn-info" title="Attendance">
                                <i class="fas fa-user-check"></i>
                            </a>
                        </div>
                    </td>

                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        <?php endif; ?>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>