<?php
session_start();
include '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    $_SESSION['error'] = "You must be logged in to register for an event";
    header("Location: login.php");
    exit();
}

// Check if event_id is provided
if (!isset($_POST['event_id']) || empty($_POST['event_id'])) {
    $_SESSION['error'] = "No event selected for registration";
    header("Location: search_events.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$event_id = $_POST['event_id'];
$payment_method = isset($_POST['payment_method']) ? $_POST['payment_method'] : 'credit_card';

// Get event details to check availability and price
$event_query = $conn->prepare("SELECT title, ticket_price, max_attendees FROM Events WHERE event_id = ?");
$event_query->bind_param("i", $event_id);
$event_query->execute();
$event_result = $event_query->get_result();

if ($event_result->num_rows === 0) {
    $_SESSION['error'] = "Event not found";
    header("Location: search_events.php");
    exit();
}

$event_data = $event_result->fetch_assoc();
$ticket_price = $event_data['ticket_price'];

// Check if the event is already full
$count_query = $conn->prepare("SELECT COUNT(*) as attendee_count FROM EventRegistrations WHERE event_id = ?");
$count_query->bind_param("i", $event_id);
$count_query->execute();
$count_result = $count_query->get_result();
$count_data = $count_result->fetch_assoc();

if ($count_data['attendee_count'] >= $event_data['max_attendees']) {
    $_SESSION['error'] = "Sorry, this event is already full";
    header("Location: event_page.php?id=" . $event_id);
    exit();
}

// Check if user is already registered for this event
$check_query = $conn->prepare("SELECT registration_id FROM EventRegistrations WHERE user_id = ? AND event_id = ?");
$check_query->bind_param("ii", $user_id, $event_id);
$check_query->execute();
$check_result = $check_query->get_result();

if ($check_result->num_rows > 0) {
    $_SESSION['error'] = "You are already registered for this event";
    header("Location: event_page.php?id=" . $event_id);
    exit();
}

// Start transaction
$conn->begin_transaction();

try {
    // Create event registration
    $reg_query = $conn->prepare("INSERT INTO EventRegistrations (user_id, event_id) VALUES (?, ?)");
    $reg_query->bind_param("ii", $user_id, $event_id);
    $reg_query->execute();
    $registration_id = $conn->insert_id;
    
    // Create ticket
    $ticket_query = $conn->prepare("INSERT INTO Tickets (event_id, attendee_id, ticket_type, price, status) VALUES (?, ?, 'standard', ?, 'valid')");
    $ticket_query->bind_param("iid", $event_id, $user_id, $ticket_price);
    $ticket_query->execute();
    $ticket_id = $conn->insert_id;
    
    // Generate a random transaction ID
    $transaction_id = 'TX' . date('YmdHis') . rand(1000, 9999);
    
    // Process payment (dummy transaction for now)
    $payment_query = $conn->prepare("INSERT INTO Payments (ticket_id, amount, payment_method, transaction_id, status) VALUES (?, ?, ?, ?, 'completed')");
    $payment_query->bind_param("idss", $ticket_id, $ticket_price, $payment_method, $transaction_id);
    $payment_query->execute();
    
    // Commit transaction
    $conn->commit();
    
    $_SESSION['success'] = "You've successfully registered for " . $event_data['title'];
    header("Location: event_page.php?id=" . $event_id);
    exit();
    
} catch (Exception $e) {
    // Rollback transaction on error
    $conn->rollback();
    $_SESSION['error'] = "Registration failed: " . $e->getMessage();
    header("Location: event_page.php?id=" . $event_id);
    exit();
}
?>