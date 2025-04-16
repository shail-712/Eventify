<?php
// attendance.php - Event Attendance Tracking Page

// Start session management
session_start();

// Database connection
include '../config/database.php';

// TEMPORARY: Create Attendance table if it doesn't exist
$conn->query("
    CREATE TABLE IF NOT EXISTS Attendance (
        attendance_id INT AUTO_INCREMENT PRIMARY KEY,
        ticket_id INT NOT NULL,
        check_in_by INT NOT NULL,
        check_in_time TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT,
        CONSTRAINT fk_ticket FOREIGN KEY (ticket_id) REFERENCES Tickets(ticket_id) ON DELETE CASCADE,
        CONSTRAINT fk_checker FOREIGN KEY (check_in_by) REFERENCES Users(user_id) ON DELETE SET NULL
    )
");




// Check if user is logged in and has appropriate permissions
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'organizer' && $_SESSION['role'] != 'admin')) {
    header("Location: login.php");
    exit();
}

$organizer_id = $_SESSION['user_id'];
$message = '';

// Handle form submissions for marking attendance
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['mark_attendance'])) {
    $ticket_id = $_POST['ticket_id'];
    $notes = isset($_POST['notes']) ? $_POST['notes'] : '';
    
    // Check if already checked in
    $check_stmt = $conn->prepare("SELECT * FROM Attendance WHERE ticket_id = ?");
    $check_stmt->bind_param("i", $ticket_id);
    $check_stmt->execute();
    $check_result = $check_stmt->get_result();
    
    if ($check_result->num_rows > 0) {
        // Already checked in, remove from attendance (undo check-in)
        $delete_stmt = $conn->prepare("DELETE FROM Attendance WHERE ticket_id = ?");
        $delete_stmt->bind_param("i", $ticket_id);
        
        if ($delete_stmt->execute()) {
            // Update ticket status back to valid
            $update_stmt = $conn->prepare("UPDATE Tickets SET status = 'valid' WHERE ticket_id = ?");
            $update_stmt->bind_param("i", $ticket_id);
            $update_stmt->execute();
            $message = "Attendance check-in undone successfully!";
        } else {
            $message = "Error removing attendance record: " . $conn->error;
        }
        $delete_stmt->close();
    } else {
        // Not checked in yet, add to attendance
        $insert_stmt = $conn->prepare("INSERT INTO Attendance (ticket_id, check_in_by, notes) VALUES (?, ?, ?)");
        $insert_stmt->bind_param("iis", $ticket_id, $organizer_id, $notes);
        
        if ($insert_stmt->execute()) {
            // Update ticket status to used
            $update_stmt = $conn->prepare("UPDATE Tickets SET status = 'used' WHERE ticket_id = ?");
            $update_stmt->bind_param("i", $ticket_id);
            $update_stmt->execute();
            $message = "Attendee checked in successfully!";
        } else {
            $message = "Error marking attendance: " . $conn->error;
        }
        $insert_stmt->close();
    }
}

// Handle ticket cancellation
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['cancel_ticket'])) {
    $ticket_id = $_POST['ticket_id'];
    
    // Update ticket status to cancelled
    $update_stmt = $conn->prepare("UPDATE Tickets SET status = 'cancelled' WHERE ticket_id = ?");
    $update_stmt->bind_param("i", $ticket_id);
    
    if ($update_stmt->execute()) {
        // Remove from attendance if checked in
        $delete_stmt = $conn->prepare("DELETE FROM Attendance WHERE ticket_id = ?");
        $delete_stmt->bind_param("i", $ticket_id);
        $delete_stmt->execute();
        $message = "Ticket cancelled successfully!";
    } else {
        $message = "Error cancelling ticket: " . $conn->error;
    }
    $update_stmt->close();
}

// Get all events organized by the current user
$events = [];
$stmt = $conn->prepare("SELECT event_id, title, start_time FROM Events WHERE organizer_id = ? ORDER BY start_time DESC");
$stmt->bind_param("i", $organizer_id);
$stmt->execute();
$result = $stmt->get_result();

while ($row = $result->fetch_assoc()) {
    $events[] = $row;
}
$stmt->close();

// Get attendees for a specific event if an event is selected
$attendees = [];
$selected_event = null;

if (isset($_GET['event_id']) && !empty($_GET['event_id'])) {
    $event_id = $_GET['event_id'];
    
    // Get event details
    $stmt = $conn->prepare("SELECT title, start_time, location FROM Events WHERE event_id = ?");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $selected_event = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    
    // Get attendees with their tickets and check-in status
    $stmt = $conn->prepare("
        SELECT 
            t.ticket_id, 
            u.user_id,
            u.name, 
            u.email,
            u.phone_number,
            t.ticket_type, 
            t.price, 
            t.status,
            a.check_in_time,
            a.notes,
            checker.name AS checked_in_by
        FROM 
            Tickets t
        JOIN 
            Users u ON t.attendee_id = u.user_id
        LEFT JOIN 
            Attendance a ON t.ticket_id = a.ticket_id
        LEFT JOIN
            Users checker ON a.check_in_by = checker.user_id
        WHERE 
            t.event_id = ?
        ORDER BY 
            a.check_in_time DESC, u.name
    ");
    $stmt->bind_param("i", $event_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    while ($row = $result->fetch_assoc()) {
        $attendees[] = $row;
    }
    $stmt->close();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Event Attendance - Eventify</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="style.css">
    <style>
                /* Global Styles */
        body {
            font-family: 'Segoe UI', 'Helvetica Neue', Arial, sans-serif;
            background-color: #f5f5f7;
            margin: 0;
            padding: 0;
            display: flex;
            color: #333;
            min-height: 100vh;
        }

        .container {
            width: 80%;
            max-width: 1200px;
            background-color: #ffffff;
            padding: 20px;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
            margin: 20px auto;
        }

        h1 {
            text-align: center;
            font-size: 28px;
            margin-bottom: 20px;
            font-weight: 500;
            color: #333;
        }

        .event-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
        }

        .event-table th,
        .event-table td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #e0e0e0;
        }

        .event-table th {
            background-color: #7028e4;
            color: white;
            font-weight: 500;
        }

        .event-table td {
            background-color: #ffffff;
            color: #333;
        }

        .event-table tr:nth-child(even) td {
            background-color: #f9f9f9;
        }

        .event-table tr:hover td {
            background-color: #f0f0f5;
        }

        button {
            background: linear-gradient(135deg, #7028e4 0%, #1e5ae0 100%);
            border: none;
            color: white;
            padding: 12px 20px;
            font-size: 15px;
            cursor: pointer;
            border-radius: 6px;
            transition: opacity 0.3s ease;
            font-weight: 500;
        }

        button:hover {
            opacity: 0.9;
        }

        button:active {
            opacity: 1;
        }

        .input-field {
            width: 100%;
            padding: 12px;
            background-color: #ffffff;
            border: 1px solid #e0e0e0;
            border-radius: 6px;
            color: #333;
            font-size: 15px;
            margin-bottom: 15px;
            box-sizing: border-box;
        }

        .input-field:focus {
            border-color: #7028e4;
            outline: none;
            box-shadow: 0 0 0 2px rgba(112, 40, 228, 0.2);
        }

        footer {
            text-align: center;
            margin-top: 30px;
            font-size: 14px;
            color: #666;
        }

        footer a {
            color: #1e5ae0;
            text-decoration: none;
            font-weight: 500;
        }

        footer a:hover {
            text-decoration: underline;
        }

        /* Add a header banner similar to the image */
        .header-banner {
            background: linear-gradient(135deg, #7028e4 0%, #1e5ae0 100%);
            color: white;
            padding: 25px;
            border-radius: 8px;
            margin-bottom: 20px;
        }

        .header-banner h2 {
            margin: 0;
            font-weight: 500;
        }

        /* Mobile Responsiveness */
        @media screen and (max-width: 768px) {
            .container {
                width: 95%;
                padding: 15px;
            }

            h1 {
                font-size: 24px;
            }

            .event-table th, .event-table td {
                font-size: 14px;
                padding: 10px;
            }
            
            button {
                padding: 10px 16px;
            }
        }

    </style>
</head>
<body>
    <div class="container mt-4">
        <h1>Event Attendance Tracking</h1>
        
        <?php if ($message): ?>
            <div class="alert alert-success alert-dismissible fade show">
                <?php echo $message; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>
        
        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card">
                    <div class="card-header">
                        <h5>Your Events</h5>
                    </div>
                    <ul class="list-group list-group-flush">
                        <?php if (empty($events)): ?>
                            <li class="list-group-item">No events found.</li>
                        <?php else: ?>
                            <?php foreach ($events as $event): ?>
                                <li class="list-group-item">
                                    <a href="?event_id=<?php echo $event['event_id']; ?>">
                                        <?php echo htmlspecialchars($event['title']); ?>
                                    </a>
                                    <small class="text-muted d-block">
                                        <?php echo date('F j, Y', strtotime($event['start_time'])); ?>
                                    </small>
                                </li>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </ul>
                </div>
                
            </div>
            
            <div class="col-md-8">
                <?php if ($selected_event): ?>
                    <div class="card mb-4">
                        <div class="card-header bg-primary text-white">
                            <h4><?php echo htmlspecialchars($selected_event['title']); ?></h4>
                            <div>
                                <i class="fas fa-calendar-alt"></i> 
                                <?php echo date('F j, Y - g:i A', strtotime($selected_event['start_time'])); ?>
                            </div>
                            <div>
                                <i class="fas fa-map-marker-alt"></i> 
                                <?php echo htmlspecialchars($selected_event['location']); ?>
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-3">
                                    <div class="attendance-count">
                                        <span class="text-success">
                                            <?php 
                                                $checkedInCount = count(array_filter($attendees, function($a) { 
                                                    return !empty($a['check_in_time']); 
                                                }));
                                                echo $checkedInCount;
                                            ?>
                                        </span>
                                        <div><small>Checked In</small></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="attendance-count">
                                        <span class="text-secondary">
                                            <?php 
                                                $pendingCount = count(array_filter($attendees, function($a) { 
                                                    return $a['status'] == 'valid' && empty($a['check_in_time']); 
                                                }));
                                                echo $pendingCount;
                                            ?>
                                        </span>
                                        <div><small>Not Arrived</small></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="attendance-count">
                                        <span class="text-danger">
                                            <?php 
                                                $cancelledCount = count(array_filter($attendees, function($a) { 
                                                    return $a['status'] == 'cancelled' || $a['status'] == 'refunded'; 
                                                }));
                                                echo $cancelledCount;
                                            ?>
                                        </span>
                                        <div><small>Cancelled</small></div>
                                    </div>
                                </div>
                                <div class="col-md-3">
                                    <div class="attendance-count">
                                        <span class="text-primary">
                                            <?php echo count($attendees); ?>
                                        </span>
                                        <div><small>Total</small></div>
                                    </div>
                                </div>
                            </div>
                            
                            <!-- Search and filter -->
                            <div class="search-box mt-4">
                                <input type="text" id="attendeeSearch" class="form-control" placeholder="Search attendees by name, email or ticket ID...">
                            </div>
                        </div>
                    </div>
                    
                    <?php if (empty($attendees)): ?>
                        <div class="alert alert-info">
                            No attendees registered for this event yet.
                        </div>
                    <?php else: ?>
                        <div id="attendeesList">
                            <?php foreach ($attendees as $attendee): ?>
                                <div class="card attendee-card <?php echo !empty($attendee['check_in_time']) ? 'checked-in' : ($attendee['status'] == 'cancelled' || $attendee['status'] == 'refunded' ? 'cancelled' : ''); ?>" 
                                     data-name="<?php echo strtolower($attendee['name']); ?>" 
                                     data-email="<?php echo strtolower($attendee['email']); ?>" 
                                     data-ticket="<?php echo $attendee['ticket_id']; ?>">
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-9">
                                                <h5 class="card-title"><?php echo htmlspecialchars($attendee['name']); ?></h5>
                                                <h6 class="card-subtitle mb-2 text-muted">
                                                    <?php echo htmlspecialchars($attendee['email']); ?> | 
                                                    <?php echo htmlspecialchars($attendee['phone_number'] ?? 'No phone'); ?>
                                                </h6>
                                                <p class="card-text">
                                                    <span class="badge bg-info"><?php echo htmlspecialchars($attendee['ticket_type']); ?></span>
                                                    <span class="badge bg-secondary">$<?php echo number_format($attendee['price'], 2); ?></span>
                                                    <span class="badge <?php echo $attendee['status'] == 'valid' ? 'bg-success' : ($attendee['status'] == 'used' ? 'bg-warning' : 'bg-danger'); ?>">
                                                        <?php echo ucfirst($attendee['status']); ?>
                                                    </span>
                                                </p>
                                                <?php if (!empty($attendee['check_in_time'])): ?>
                                                    <p class="card-text">
                                                        <small class="text-success">
                                                            <i class="fas fa-check-circle"></i> 
                                                            Checked in at <?php echo date('g:i A', strtotime($attendee['check_in_time'])); ?> 
                                                            by <?php echo htmlspecialchars($attendee['checked_in_by']); ?>
                                                        </small>
                                                    </p>
                                                    <?php if (!empty($attendee['notes'])): ?>
                                                        <p class="card-text">
                                                            <small class="text-muted">
                                                                <i class="fas fa-sticky-note"></i> 
                                                                <?php echo htmlspecialchars($attendee['notes']); ?>
                                                            </small>
                                                        </p>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                            </div>
                                            <div class="col-md-3 text-end">
                                                <?php if (empty($attendee['check_in_time']) && $attendee['status'] == 'valid'): ?>
                                                    <form method="post" action="attendance.php?event_id=<?php echo $event_id; ?>">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $attendee['ticket_id']; ?>">
                                                        <button type="submit" name="mark_attendance" class="btn btn-success btn-sm mb-2 w-100">
                                                            <i class="fas fa-check"></i> Check In
                                                        </button>
                                                    </form>
                                                <?php elseif (!empty($attendee['check_in_time'])): ?>
                                                    <form method="post" action="attendance.php?event_id=<?php echo $event_id; ?>">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $attendee['ticket_id']; ?>">
                                                        <button type="submit" name="mark_attendance" class="btn btn-outline-secondary btn-sm mb-2 w-100">
                                                            <i class="fas fa-undo"></i> Undo
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                                
                                                <?php if ($attendee['status'] != 'cancelled' && $attendee['status'] != 'refunded'): ?>
                                                    <form method="post" action="attendance.php?event_id=<?php echo $event_id; ?>" onsubmit="return confirm('Are you sure you want to cancel this ticket?');">
                                                        <input type="hidden" name="ticket_id" value="<?php echo $attendee['ticket_id']; ?>">
                                                        <button type="submit" name="cancel_ticket" class="btn btn-outline-danger btn-sm w-100">
                                                            <i class="fas fa-times"></i> Cancel
                                                        </button>
                                                    </form>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                <?php else: ?>
                    <div class="card">
                        <div class="card-body text-center py-5">
                            <h3>Select an event from the list to manage attendance</h3>
                            <p class="text-muted">You'll be able to check in attendees and track attendance statistics</p>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    <script>
        // Search functionality
        const searchInput = document.getElementById('attendeeSearch');
        const attendeeCards = document.querySelectorAll('.attendee-card');
        
        if (searchInput) {
            searchInput.addEventListener('keyup', function() {
                const searchText = this.value.toLowerCase();
                
                attendeeCards.forEach(card => {
                    const name = card.dataset.name;
                    const email = card.dataset.email;
                    const ticket = card.dataset.ticket;
                    
                    if (name.includes(searchText) || email.includes(searchText) || ticket.includes(searchText)) {
                        card.style.display = '';
                    } else {
                        card.style.display = 'none';
                    }
                });
            });
        }
        
        // Auto-focus the quick check-in input if it exists
        const ticketCodeInput = document.getElementById('ticketCode');
        if (ticketCodeInput) {
            ticketCodeInput.focus();
        }
    </script>
</body>
</html>