<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an organizer or admin
if(!isset($_SESSION["loggedin"]) || ($_SESSION["role"] != "organizer" && $_SESSION["role"] != "admin")) {
    header("location: login.php");
    exit;
}

// Include database connection
include '../config/database.php';

// Delete event if requested
if(isset($_GET["delete"]) && !empty($_GET["delete"])) {
    $event_id = $_GET["delete"];
    
    // Check if user is the organizer of this event or an admin
    $check_sql = "SELECT organizer_id FROM Events WHERE event_id = ?";
    if($check_stmt = $conn->prepare($check_sql)) {
        $check_stmt->bind_param("i", $event_id);
        $check_stmt->execute();
        $check_result = $check_stmt->get_result();
        
        if($check_result->num_rows == 1) {
            $event_data = $check_result->fetch_assoc();
            
            if($event_data['organizer_id'] == $_SESSION["user_id"] || $_SESSION["role"] == "admin") {
                // Delete related registrations and tickets first (to maintain referential integrity)
                $del_reg_sql = "DELETE FROM event_registrations WHERE event_id = ?";
                if($del_reg_stmt = $conn->prepare($del_reg_sql)) {
                    $del_reg_stmt->bind_param("i", $event_id);
                    $del_reg_stmt->execute();
                    $del_reg_stmt->close();
                }
                
                $del_tickets_sql = "DELETE FROM Tickets WHERE event_id = ?";
                if($del_tickets_stmt = $conn->prepare($del_tickets_sql)) {
                    $del_tickets_stmt->bind_param("i", $event_id);
                    $del_tickets_stmt->execute();
                    $del_tickets_stmt->close();
                }
                
                // Now delete the event
                $del_event_sql = "DELETE FROM Events WHERE event_id = ?";
                if($del_event_stmt = $conn->prepare($del_event_sql)) {
                    $del_event_stmt->bind_param("i", $event_id);
                    $del_event_stmt->execute();
                    $del_event_stmt->close();
                    
                    // Redirect to refresh the page
                    header("location: manage_events.php");
                    exit();
                }
            }
        }
        $check_stmt->close();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Manage Events</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css">
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.1/dist/umd/popper.min.js"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/js/bootstrap.min.js"></script>
    <style>
        body{ font: 14px sans-serif; }
        .wrapper{ width: 1000px; padding: 20px; margin: 0 auto; }
        table tr td:last-child{ width: 150px; }
    </style>
    <script>
        $(document).ready(function(){
            $('[data-toggle="tooltip"]').tooltip();   
            
            // Confirm before delete
            $('.delete-btn').on('click', function(e){
                if(!confirm('Are you sure you want to delete this event? This action cannot be undone.')){
                    e.preventDefault();
                }
            });
        });
    </script>
</head>
<body>
    <div class="wrapper">
        <div class="container-fluid">
            <div class="row">
                <div class="col-md-12">
                    <div class="mt-5 mb-3 clearfix">
                        <h2 class="pull-left">Manage Events</h2>
                        <a href="create_event.php" class="btn btn-success pull-right"><i class="fa fa-plus"></i> Create New Event</a>
                    </div>
                    <?php
                    // Get events organized by this user (or all events if admin)
                    if($_SESSION["role"] == "admin") {
                        $sql = "SELECT e.event_id, e.title, e.location, e.start_time, e.end_time, 
                                       e.ticket_price, c.category_name, u.name as organizer_name
                                FROM Events e
                                LEFT JOIN EventCategories c ON e.category_id = c.category_id
                                LEFT JOIN Users u ON e.organizer_id = u.user_id
                                ORDER BY e.start_time DESC";
                        $stmt = $conn->prepare($sql);
                    } else {
                        $sql = "SELECT e.event_id, e.title, e.location, e.start_time, e.end_time, 
                                       e.ticket_price, c.category_name, u.name as organizer_name
                                FROM Events e
                                LEFT JOIN EventCategories c ON e.category_id = c.category_id
                                LEFT JOIN Users u ON e.organizer_id = u.user_id
                                WHERE e.organizer_id = ?
                                ORDER BY e.start_time DESC";
                        $stmt = mysqli_prepare($link, $sql);
                        mysqli_stmt_bind_param($stmt, "i", $_SESSION["user_id"]);
                    }
                    
                    // Execute the prepared statement
                    mysqli_stmt_execute($stmt);
                    $result = mysqli_stmt_get_result($stmt);
                    
                    if(mysqli_num_rows($result) > 0){
                        echo '<table class="table table-bordered table-striped">';
                            echo "<thead>";
                                echo "<tr>";
                                    echo "<th>Title</th>";
                                    echo "<th>Category</th>";
                                    if($_SESSION["role"] == "admin") {
                                        echo "<th>Organizer</th>";
                                    }
                                    echo "<th>Location</th>";
                                    echo "<th>Start Time</th>";
                                    echo "<th>End Time</th>";
                                    echo "<th>Price ($)</th>";
                                    echo "<th>Actions</th>";
                                echo "</tr>";
                            echo "</thead>";
                            echo "<tbody>";
                            while($row = mysqli_fetch_array($result)){
                                echo "<tr>";
                                    echo "<td>" . htmlspecialchars($row['title']) . "</td>";
                                    echo "<td>" . htmlspecialchars($row['category_name']) . "</td>";
                                    if($_SESSION["role"] == "admin") {
                                        echo "<td>" . htmlspecialchars($row['organizer_name']) . "</td>";
                                    }
                                    echo "<td>" . htmlspecialchars($row['location']) . "</td>";
                                    echo "<td>" . date('M d, Y h:i A', strtotime($row['start_time'])) . "</td>";
                                    echo "<td>" . date('M d, Y h:i A', strtotime($row['end_time'])) . "</td>";
                                    echo "<td>" . number_format($row['ticket_price'], 2) . "</td>";
                                    echo "<td>";
                                        echo '<a href="view_event.php?id='. $row['event_id'] .'" class="mr-2" title="View Event" data-toggle="tooltip"><span class="fa fa-eye"></span></a>';
                                        echo '<a href="edit_event.php?id='. $row['event_id'] .'" class="mr-2" title="Edit Event" data-toggle="tooltip"><span class="fa fa-pencil"></span></a>';
                                        echo '<a href="manage_events.php?delete='. $row['event_id'] .'" class="delete-btn" title="Delete Event" data-toggle="tooltip"><span class="fa fa-trash"></span></a>';
                                        echo '<a href="view_registrations.php?event_id='. $row['event_id'] .'" class="ml-2" title="View Registrations" data-toggle="tooltip"><span class="fa fa-users"></span></a>';
                                    echo "</td>";
                                echo "</tr>";
                            }
                            echo "</tbody>";                            
                        echo "</table>";
                        // Free result set
                        mysqli_free_result($result);
                    } else{
                        echo '<div class="alert alert-danger"><em>No events found.</em></div>';
                    }
                    
                    // Close statement
                    mysqli_stmt_close($stmt);
                    
                    // Close connection
                    mysqli_close($link);
                    ?>
                </div>
            </div>        
        </div>
        <div class="mt-3">
            <a href="event-dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>
    </div>
</body>
</html>