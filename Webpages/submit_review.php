<?php
session_start();
include '../config/database.php';

// Check if user is logged in
if (!isset($_SESSION['user_id'])) {
    // Redirect to login if not logged in
    header('Location: login.php');
    exit;
}

// Validate form submission
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: search_events.php');
    exit;
}

// Collect form data
$event_id = isset($_POST['event_id']) ? intval($_POST['event_id']) : 0;
$user_id = $_SESSION['user_id'];
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

// Validate data
if ($event_id <= 0 || $rating < 1 || $rating > 5 || empty($comment)) {
    $_SESSION['error_message'] = "Please provide a valid rating and comment.";
    header("Location: event_page.php?id=$event_id");
    exit;
}

// Check if the user has attended the event
$sql_check_attendance = "SELECT COUNT(*) as count FROM EventRegistrations 
                        WHERE user_id = ? AND event_id = ?";
$stmt_check = $conn->prepare($sql_check_attendance);
$stmt_check->bind_param("ii", $user_id, $event_id);
$stmt_check->execute();
$result_check = $stmt_check->get_result();
$row_check = $result_check->fetch_assoc();

if ($row_check['count'] == 0) {
    $_SESSION['error_message'] = "You can only review events you have attended.";
    header("Location: event.php?id=$event_id");
    exit;
}

// Check if user has already submitted a review for this event
$sql_check_review = "SELECT COUNT(*) as count FROM Reviews 
                    WHERE user_id = ? AND event_id = ?";
$stmt_review = $conn->prepare($sql_check_review);
$stmt_review->bind_param("ii", $user_id, $event_id);
$stmt_review->execute();
$result_review = $stmt_review->get_result();
$row_review = $result_review->fetch_assoc();

if ($row_review['count'] > 0) {
    $_SESSION['error_message'] = "You have already submitted a review for this event.";
    header("Location: event_page.php?id=$event_id");
    exit;
}

// Insert the review
$sql_insert = "INSERT INTO Reviews (event_id, user_id, rating, comment) 
              VALUES (?, ?, ?, ?)";
$stmt_insert = $conn->prepare($sql_insert);
$stmt_insert->bind_param("iiis", $event_id, $user_id, $rating, $comment);

if ($stmt_insert->execute()) {
    $_SESSION['success_message'] = "Thank you! Your review has been submitted successfully.";
} else {
    $_SESSION['error_message'] = "Error submitting your review. Please try again.";
}

// Close statements
$stmt_check->close();
$stmt_review->close();
$stmt_insert->close();
$conn->close();

// Redirect back to the event page
header("Location: event_page.php?id=$event_id");
exit;
?>