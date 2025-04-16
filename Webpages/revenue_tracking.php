<?php
session_start();
include '../config/database.php';

// Check if user is logged in and is an organizer
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'organizer') {
    $_SESSION['error'] = "You must be logged in as an organizer to view this page";
    header("Location: login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];

// Get all events created by this organizer
$events_query = $conn->prepare("
    SELECT 
        e.event_id,
        e.title,
        e.start_time,
        e.ticket_price,
        e.max_attendees,
        (SELECT COUNT(*) FROM EventRegistrations WHERE event_id = e.event_id) as registered_attendees,
        (SELECT SUM(p.amount) FROM Payments p 
         JOIN Tickets t ON p.ticket_id = t.ticket_id 
         WHERE t.event_id = e.event_id AND p.status = 'completed') as total_revenue
    FROM 
        Events e
    WHERE 
        e.organizer_id = ?
    ORDER BY 
        e.start_time DESC
");

$events_query->bind_param("i", $organizer_id);
$events_query->execute();
$events_result = $events_query->get_result();

// For event revenue breakdown if specific event is selected
$selected_event = null;
$revenue_breakdown = null;
$attendee_list = null;

if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
    
    // Verify this event belongs to the current organizer
    $verify_query = $conn->prepare("SELECT event_id FROM Events WHERE event_id = ? AND organizer_id = ?");
    $verify_query->bind_param("ii", $event_id, $organizer_id);
    $verify_query->execute();
    $verify_result = $verify_query->get_result();
    
    if ($verify_result->num_rows === 0) {
        $_SESSION['error'] = "Unauthorized access to event data";
        header("Location: revenue_tracking.php");
        exit();
    }
    
    // Get detailed event information
    $event_detail_query = $conn->prepare("
        SELECT 
            e.title,
            e.start_time,
            e.ticket_price,
            e.max_attendees,
            (SELECT COUNT(*) FROM EventRegistrations WHERE event_id = e.event_id) as registered_attendees
        FROM 
            Events e
        WHERE 
            e.event_id = ?
    ");
    
    $event_detail_query->bind_param("i", $event_id);
    $event_detail_query->execute();
    $selected_event = $event_detail_query->get_result()->fetch_assoc();
    
    if ($selected_event) {
        // Get payment breakdown by date
        $revenue_query = $conn->prepare("
            SELECT 
                DATE(p.payment_date) as payment_date,
                COUNT(p.payment_id) as num_tickets,
                SUM(p.amount) as daily_revenue
            FROM 
                Payments p
            JOIN 
                Tickets t ON p.ticket_id = t.ticket_id
            WHERE 
                t.event_id = ? AND p.status = 'completed'
            GROUP BY 
                DATE(p.payment_date)
            ORDER BY 
                payment_date DESC
        ");
        
        $revenue_query->bind_param("i", $event_id);
        $revenue_query->execute();
        $revenue_breakdown = $revenue_query->get_result();
        
        // Get attendee list
        $attendee_query = $conn->prepare("
            SELECT 
                u.user_id,
                u.name,
                u.email,
                er.registration_time,
                t.ticket_id,
                p.amount,
                p.payment_method,
                p.transaction_id,
                p.payment_date
            FROM 
                EventRegistrations er
            JOIN 
                Users u ON er.user_id = u.user_id
            JOIN 
                Tickets t ON er.event_id = t.event_id AND er.user_id = t.attendee_id
            JOIN 
                Payments p ON t.ticket_id = p.ticket_id
            WHERE 
                er.event_id = ?
            ORDER BY 
                er.registration_time DESC
        ");
        
        $attendee_query->bind_param("i", $event_id);
        $attendee_query->execute();
        $attendee_list = $attendee_query->get_result();
    }
}

// Helper functions
function formatDate($datetime) {
    return date('d M Y', strtotime($datetime));
}

function formatTime($datetime) {
    return date('h:i A', strtotime($datetime));
}

function formatPrice($price) {
    return '$' . number_format($price, 2);
}

function formatDateTime($datetime) {
    return date('M d, Y h:i A', strtotime($datetime));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Revenue Tracking - Eventify</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .primary-btn {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
            color: white;
        }
        
        .primary-btn:hover {
            background-color: #5a49d6;
            border-color: #5a49d6;
            color: white;
        }
        
        .stat-card {
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
        }
        
        .stat-card .card-body {
            padding: 1.5rem;
        }
        
        .stat-icon {
            background-color: rgba(108, 92, 231, 0.1);
            border-radius: 50%;
            width: 50px;
            height: 50px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            color: #6c5ce7;
            margin-bottom: 1rem;
        }
        
        .revenue-table th {
            background-color: #f8f9fa;
        }
        
        .status-badge {
            font-size: 0.8rem;
            padding: 0.25rem 0.5rem;
            border-radius: 0.25rem;
        }
        
        .status-badge.completed {
            background-color: #a0f5b5;
            color: #0d6832;
        }
        
        .status-badge.pending {
            background-color: #ffeeba;
            color: #856404;
        }
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="../index.php">Eventify</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="event-dashboard.php">Dashboard</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="search_events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="profile.php">My Profile</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="logout.php">Logout</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h1 class="h2">Revenue Tracking</h1>
            <a href="event-dashboard.php" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Dashboard
            </a>
        </div>
        
        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger">
                <?php 
                    echo $_SESSION['error']; 
                    unset($_SESSION['error']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success">
                <?php 
                    echo $_SESSION['success']; 
                    unset($_SESSION['success']);
                ?>
            </div>
        <?php endif; ?>
        
        <?php if ($selected_event): ?>
            <!-- Event Details -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-4">
                                <h2><?php echo htmlspecialchars($selected_event['title']); ?></h2>
                                <span class="badge bg-primary"><?php echo formatDate($selected_event['start_time']); ?></span>
                            </div>
                            
                            <div class="row g-4">
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="card-body text-center">
                                            <div class="stat-icon mx-auto">
                                                <i class="bi bi-people"></i>
                                            </div>
                                            <h5>Attendees</h5>
                                            <h3 class="mb-0"><?php echo $selected_event['registered_attendees']; ?> / <?php echo $selected_event['max_attendees']; ?></h3>
                                            <p class="text-muted mb-0">
                                                <?php echo round(($selected_event['registered_attendees'] / $selected_event['max_attendees']) * 100); ?>% capacity
                                            </p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="card-body text-center">
                                            <div class="stat-icon mx-auto">
                                                <i class="bi bi-tag"></i>
                                            </div>
                                            <h5>Ticket Price</h5>
                                            <h3 class="mb-0"><?php echo formatPrice($selected_event['ticket_price']); ?></h3>
                                            <p class="text-muted mb-0">Standard ticket</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="card-body text-center">
                                            <div class="stat-icon mx-auto">
                                                <i class="bi bi-cash-stack"></i>
                                            </div>
                                            <h5>Current Revenue</h5>
                                            <h3 class="mb-0">
                                                <?php 
                                                    $current_revenue = $selected_event['registered_attendees'] * $selected_event['ticket_price'];
                                                    echo formatPrice($current_revenue); 
                                                ?>
                                            </h3>
                                            <p class="text-muted mb-0">From all tickets</p>
                                        </div>
                                    </div>
                                </div>
                                
                                <div class="col-md-3">
                                    <div class="stat-card">
                                        <div class="card-body text-center">
                                            <div class="stat-icon mx-auto">
                                                <i class="bi bi-graph-up-arrow"></i>
                                            </div>
                                            <h5>Potential Revenue</h5>
                                            <h3 class="mb-0">
                                                <?php 
                                                    $potential_revenue = $selected_event['max_attendees'] * $selected_event['ticket_price'];
                                                    echo formatPrice($potential_revenue); 
                                                ?>
                                            </h3>
                                            <p class="text-muted mb-0">At full capacity</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="row mb-4">
                <div class="col-md-6">
                    <!-- Revenue Breakdown -->
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Revenue by Date</h5>
                        </div>
                        <div class="card-body">
                            <?php if ($revenue_breakdown && $revenue_breakdown->num_rows > 0): ?>
                                <div class="table-responsive">
                                    <table class="table table-hover revenue-table">
                                        <thead>
                                            <tr>
                                                <th>Date</th>
                                                <th>Tickets</th>
                                                <th>Revenue</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php 
                                                $total_tickets = 0;
                                                $total_revenue = 0;
                                                while ($row = $revenue_breakdown->fetch_assoc()): 
                                                    $total_tickets += $row['num_tickets'];
                                                    $total_revenue += $row['daily_revenue'];
                                            ?>
                                                <tr>
                                                    <td><?php echo formatDate($row['payment_date']); ?></td>
                                                    <td><?php echo $row['num_tickets']; ?></td>
                                                    <td><?php echo formatPrice($row['daily_revenue']); ?></td>
                                                </tr>
                                            <?php endwhile; ?>
                                            <tr class="table-secondary fw-bold">
                                                <td>Total</td>
                                                <td><?php echo $total_tickets; ?></td>
                                                <td><?php echo formatPrice($total_revenue); ?></td>
                                            </tr>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle me-2"></i>No revenue data available for this event yet.
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <!-- Revenue Progress -->
                    <div class="card shadow-sm h-100">
                        <div class="card-header bg-white">
                            <h5 class="card-title mb-0">Revenue Progress</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-4">
                                <label class="form-label">Revenue Goal Progress</label>
                                <div class="progress" style="height: 25px;">
                                    <?php
                                        $progress_percentage = ($current_revenue / $potential_revenue) * 100;
                                    ?>
                                    <div class="progress-bar bg-success" role="progressbar" 
                                         style="width: <?php echo $progress_percentage; ?>%;" 
                                         aria-valuenow="<?php echo $progress_percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo round($progress_percentage); ?>%
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted">Current: <?php echo formatPrice($current_revenue); ?></small>
                                    <small class="text-muted">Goal: <?php echo formatPrice($potential_revenue); ?></small>
                                </div>
                            </div>
                            
                            <div class="mb-4">
                                <label class="form-label">Attendance Progress</label>
                                <div class="progress" style="height: 25px;">
                                    <?php
                                        $attendance_percentage = ($selected_event['registered_attendees'] / $selected_event['max_attendees']) * 100;
                                    ?>
                                    <div class="progress-bar bg-primary" role="progressbar" 
                                         style="width: <?php echo $attendance_percentage; ?>%;" 
                                         aria-valuenow="<?php echo $attendance_percentage; ?>" 
                                         aria-valuemin="0" 
                                         aria-valuemax="100">
                                        <?php echo round($attendance_percentage); ?>%
                                    </div>
                                </div>
                                <div class="d-flex justify-content-between mt-2">
                                    <small class="text-muted">Current: <?php echo $selected_event['registered_attendees']; ?> attendees</small>
                                    <small class="text-muted">Capacity: <?php echo $selected_event['max_attendees']; ?> attendees</small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Attendee List -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h5 class="card-title mb-0">Attendee List</h5>
                    <button class="btn btn-sm btn-outline-secondary" onclick="window.print()">
                        <i class="bi bi-printer me-1"></i> Print
                    </button>
                </div>
                <div class="card-body">
                    <?php if ($attendee_list && $attendee_list->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Registration Date</th>
                                        <th>Payment</th>
                                        <th>Transaction ID</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($attendee = $attendee_list->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($attendee['name']); ?></td>
                                            <td><?php echo htmlspecialchars($attendee['email']); ?></td>
                                            <td><?php echo formatDateTime($attendee['registration_time']); ?></td>
                                            <td><?php echo formatPrice($attendee['amount']); ?></td>
                                            <td><small class="text-muted"><?php echo $attendee['transaction_id']; ?></small></td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>No attendees registered for this event yet.
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <div class="text-center mt-4">
                <a href="revenue_tracking.php" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left me-1"></i> Back to All Events
                </a>
            </div>
            
        <?php else: ?>
            <!-- Events Overview -->
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="card-title mb-0">Your Events</h5>
                </div>
                <div class="card-body">
                    <?php if ($events_result->num_rows > 0): ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Event</th>
                                        <th>Date</th>
                                        <th>Price</th>
                                        <th>Attendees</th>
                                        <th>Revenue</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($event = $events_result->fetch_assoc()): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($event['title']); ?></td>
                                            <td><?php echo formatDate($event['start_time']); ?></td>
                                            <td><?php echo formatPrice($event['ticket_price']); ?></td>
                                            <td>
                                                <?php echo $event['registered_attendees']; ?> / <?php echo $event['max_attendees']; ?>
                                                <div class="progress mt-1" style="height: 5px;">
                                                    <div class="progress-bar bg-success" role="progressbar" 
                                                         style="width: <?php echo ($event['registered_attendees'] / $event['max_attendees']) * 100; ?>%;" 
                                                         aria-valuenow="<?php echo ($event['registered_attendees'] / $event['max_attendees']) * 100; ?>" 
                                                         aria-valuemin="0" 
                                                         aria-valuemax="100"></div>
                                                </div>
                                            </td>
                                            <td><?php echo formatPrice($event['total_revenue'] ?? 0); ?></td>
                                            <td>
                                                <a href="revenue_tracking.php?event_id=<?php echo $event['event_id']; ?>" class="btn btn-sm primary-btn">
                                                    <i class="bi bi-graph-up me-1"></i> View Details
                                                </a>
                                            </td>
                                        </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php else: ?>
                        <div class="alert alert-info">
                            <i class="bi bi-info-circle me-2"></i>You haven't created any events yet.
                            <a href="create_event.php" class="alert-link">Create your first event!</a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4 mt-auto">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h5>Eventify</h5>
                    <p class="text-muted">Find and register for events that match your interests</p>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a href="../index.php" class="nav-link text-muted p-0 mb-2">Home</a></li>
                        <li class="nav-item"><a href="search_events.php" class="nav-link text-muted p-0 mb-2">Events</a></li>
                        <li class="nav-item"><a href="event-dashboard.php" class="nav-link text-muted p-0">Dashboard</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item text-muted mb-2"><i class="bi bi-envelope me-2"></i>support@eventify.com</li>
                        <li class="nav-item text-muted"><i class="bi bi-telephone me-2"></i>+1 234 567 8900</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted">
                <p>&copy; 2025 Eventify. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>