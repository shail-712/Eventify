<?php
include '../config/database.php';

// Get event ID from URL parameter
$event_id = isset($_GET['id']) ? intval($_GET['id']) : 0;

if ($event_id <= 0) {
    // Redirect to events list if no valid ID provided
    header('Location: search_events.php');
    exit;
}

// Query to get event details with category name and organizer info
$sql = "SELECT e.*, c.category_name, u.name as organizer_name, u.email as organizer_email, u.user_id as organizer_id
        FROM Events e
        JOIN EventCategories c ON e.category_id = c.category_id
        JOIN Users u ON e.organizer_id = u.user_id
        WHERE e.event_id = ?";

$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $event_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows == 0) {
    // Event not found
    header('Location: events.php');
    exit;
}

$event = $result->fetch_assoc();

// Get registration count
$sql_reg_count = "SELECT COUNT(*) as count FROM EventRegistrations WHERE event_id = ?";
$stmt_reg = $conn->prepare($sql_reg_count);
$stmt_reg->bind_param("i", $event_id);
$stmt_reg->execute();
$reg_result = $stmt_reg->get_result();
$reg_data = $reg_result->fetch_assoc();
$registration_count = $reg_data['count'];

// Get reviews for this event
$sql_reviews = "SELECT r.*, u.name as reviewer_name, u.profile_image
                FROM Reviews r
                JOIN Users u ON r.user_id = u.user_id
                WHERE r.event_id = ?
                ORDER BY r.review_date DESC";
$stmt_reviews = $conn->prepare($sql_reviews);
$stmt_reviews->bind_param("i", $event_id);
$stmt_reviews->execute();
$reviews_result = $stmt_reviews->get_result();
$reviews = [];
$total_rating = 0;
$review_count = 0;

while ($row = $reviews_result->fetch_assoc()) {
    $reviews[] = $row;
    $total_rating += $row['rating'];
    $review_count++;
}

// Calculate average rating
$average_rating = $review_count > 0 ? round($total_rating / $review_count, 1) : 0;

// Get related events (same category, excluding current event)
$sql_related = "SELECT e.*, c.category_name 
                FROM Events e
                JOIN EventCategories c ON e.category_id = c.category_id
                WHERE e.category_id = ? AND e.event_id != ?
                LIMIT 3";
$stmt_related = $conn->prepare($sql_related);
$stmt_related->bind_param("ii", $event['category_id'], $event_id);
$stmt_related->execute();
$related_result = $stmt_related->get_result();
$related_events = [];

while ($row = $related_result->fetch_assoc()) {
    $related_events[] = $row;
}

// Check if user is logged in
session_start();
$is_logged_in = isset($_SESSION['user_id']);
$current_user_id = $is_logged_in ? $_SESSION['user_id'] : 0;

// Check if the user has attended this event
$has_attended = false;
if ($is_logged_in) {
    $sql_attendance = "SELECT COUNT(*) as count FROM EventRegistrations 
                       WHERE user_id = ? AND event_id = ?";
    $stmt_attendance = $conn->prepare($sql_attendance);
    $stmt_attendance->bind_param("ii", $current_user_id, $event_id);
    $stmt_attendance->execute();
    $attendance_result = $stmt_attendance->get_result();
    $attendance_data = $attendance_result->fetch_assoc();
    $has_attended = ($attendance_data['count'] > 0);
    
    // Check if user has already submitted a review
    $sql_user_review = "SELECT COUNT(*) as count FROM Reviews 
                       WHERE user_id = ? AND event_id = ?";
    $stmt_user_review = $conn->prepare($sql_user_review);
    $stmt_user_review->bind_param("ii", $current_user_id, $event_id);
    $stmt_user_review->execute();
    $user_review_result = $stmt_user_review->get_result();
    $user_review_data = $user_review_result->fetch_assoc();
    $has_reviewed = ($user_review_data['count'] > 0);
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

function truncateText($text, $length) {
    if (strlen($text) <= $length) {
        return $text;
    }
    return substr($text, 0, $length) . '...';
}

function displayStarRating($rating) {
    $output = '';
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $output .= '<i class="bi bi-star-fill text-warning"></i>';
        } else {
            $output .= '<i class="bi bi-star text-warning"></i>';
        }
    }
    return $output;
}

// Format review date
function formatReviewDate($date) {
    return date('M d, Y', strtotime($date));
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($event['title']); ?> - Event Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        .hero-section {
            background-color: #6c5ce7;
            color: white;
            padding: 3rem 0;
        }
        
        .card-feature {
            height: 100%;
            transition: transform 0.3s;
        }
        
        .card-feature:hover {
            transform: translateY(-5px);
        }
        
        .profile-img {
            width: 120px;
            height: 120px;
            object-fit: cover;
            border-radius: 50%;
            border: 3px solid #6c5ce7;
        }
        
        .event-meta-item {
            background-color: #f8f9fa;
            border-radius: 0.5rem;
        }
        
        .primary-btn {
            background-color: #6c5ce7;
            border-color: #6c5ce7;
        }
        
        .primary-btn:hover {
            background-color: #5a49d6;
            border-color: #5a49d6;
        }
        
        .accordion-button:not(.collapsed) {
            background-color: #eee7ff;
            color: #6c5ce7;
        }
        
        .accordion-button:focus {
            box-shadow: 0 0 0 0.25rem rgba(108, 92, 231, 0.25);
        }
        
        .category-badge {
            background-color: #6c5ce7;
            color: white;
            font-size: 0.8rem;
            padding: 0.25rem 0.75rem;
            border-radius: 1rem;
            display: inline-block;
            margin-bottom: 1rem;
        }
        
        .event-location {
            display: flex;
            align-items: center;
            margin-bottom: 1rem;
        }
        
        .event-location i {
            margin-right: 0.5rem;
            color: #6c5ce7;
        }
        
        .review-avatar {
            width: 50px;
            height: 50px;
            object-fit: cover;
            border-radius: 50%;
        }
        
        .review-card {
            border-left: 4px solid #6c5ce7;
            margin-bottom: 1rem;
        }
        
        .rating-container {
            font-size: 1.5rem;
        }
        
        .rating-input {
            display: none;
        }
        
        .rating-label {
            color: #ccc;
            cursor: pointer;
            font-size: 1.5rem;
            padding: 0 0.1rem;
        }
        
        .rating-label:hover,
        .rating-label:hover ~ .rating-label,
        .rating-input:checked ~ .rating-label {
            color: #ffcc00;
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
                        <a class="nav-link" href="../index.php">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="categories.php">Categories</a>
                    </li>
                    <?php if ($is_logged_in): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="profile.php">My Profile</a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link" href="logout.php">Logout</a>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">Login</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <span class="category-badge"><?php echo htmlspecialchars($event['category_name']); ?></span>
            <h1 class="display-4 fw-bold"><?php echo htmlspecialchars($event['title']); ?></h1>
            <p class="lead">Organized by <?php echo htmlspecialchars($event['organizer_name']); ?></p>
            <?php if ($review_count > 0): ?>
            <div class="d-flex justify-content-center align-items-center">
                <div class="text-warning me-2">
                    <?php echo displayStarRating($average_rating); ?>
                </div>
                <span class="text-white"><?php echo $average_rating; ?> (<?php echo $review_count; ?> reviews)</span>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Event Details -->
        <div class="row mb-5">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="event-location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span><?php echo htmlspecialchars($event['location']); ?></span>
                        </div>
                        
                        <h2 class="h4 mb-4">About this event</h2>
                        <div class="mb-4">
                            <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                        </div>
                        
                        <!-- Event Reviews Section -->
                        <h2 class="h4 mb-3">Event Reviews</h2>
                        
                        <?php if ($review_count > 0): ?>
                            <?php foreach ($reviews as $review): ?>
                            <div class="card review-card mb-3">
                                <div class="card-body">
                                    <div class="d-flex mb-3">
                                        <img src="images/users/<?php echo $review['user_id']; ?>.jpg" 
                                             onerror="this.src='images/placeholder_user.jpg'" 
                                             alt="<?php echo htmlspecialchars($review['reviewer_name']); ?>" 
                                             class="review-avatar me-3">
                                        <div>
                                            <h5 class="mb-0"><?php echo htmlspecialchars($review['reviewer_name']); ?></h5>
                                            <div class="text-warning">
                                                <?php echo displayStarRating($review['rating']); ?>
                                            </div>
                                            <small class="text-muted"><?php echo formatReviewDate($review['review_date']); ?></small>
                                        </div>
                                    </div>
                                    <p class="mb-0"><?php echo nl2br(htmlspecialchars($review['comment'])); ?></p>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="alert alert-info">
                                <i class="bi bi-info-circle me-2"></i>No reviews yet for this event. Be the first to review!
                            </div>
                        <?php endif; ?>
                        
                        <!-- Review Form -->
                        <?php if ($is_logged_in && $has_attended && !isset($has_reviewed)): ?>
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Share your experience</h5>
                            </div>
                            <div class="card-body">
                                <form action="submit_review.php" method="post">
                                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Rating</label>
                                        <div class="rating-container">
                                            <input type="radio" id="star5" name="rating" value="5" class="rating-input">
                                            <label for="star5" class="rating-label"><i class="bi bi-star-fill"></i></label>
                                            
                                            <input type="radio" id="star4" name="rating" value="4" class="rating-input">
                                            <label for="star4" class="rating-label"><i class="bi bi-star-fill"></i></label>
                                            
                                            <input type="radio" id="star3" name="rating" value="3" class="rating-input">
                                            <label for="star3" class="rating-label"><i class="bi bi-star-fill"></i></label>
                                            
                                            <input type="radio" id="star2" name="rating" value="2" class="rating-input">
                                            <label for="star2" class="rating-label"><i class="bi bi-star-fill"></i></label>
                                            
                                            <input type="radio" id="star1" name="rating" value="1" class="rating-input">
                                            <label for="star1" class="rating-label"><i class="bi bi-star-fill"></i></label>
                                        </div>
                                    </div>
                                    
                                    <div class="mb-3">
                                        <label for="reviewComment" class="form-label">Your Review</label>
                                        <textarea class="form-control" id="reviewComment" name="comment" rows="4" required></textarea>
                                    </div>
                                    
                                    <button type="submit" class="btn primary-btn text-white">Submit Review</button>
                                </form>
                            </div>
                        </div>
                        <?php elseif ($is_logged_in && isset($has_reviewed) && $has_reviewed): ?>
                        <div class="alert alert-success mt-4">
                            <i class="bi bi-check-circle me-2"></i>Thank you for reviewing this event!
                        </div>
                        <?php elseif ($is_logged_in && !$has_attended): ?>
                        <div class="alert alert-info mt-4">
                            <i class="bi bi-info-circle me-2"></i>You need to attend this event before you can review it.
                        </div>
                        <?php elseif (!$is_logged_in): ?>
                        <div class="card mt-4">
                            <div class="card-body text-center">
                                <p class="mb-2">Want to share your experience?</p>
                                <a href="login.php?redirect=event.php?id=<?php echo $event_id; ?>" class="btn btn-outline-primary">Login to Leave a Review</a>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            
            <!-- Registration Sidebar -->
            <div class="col-lg-4">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="row g-3 mb-4">
                            <div class="col-6">
                                <div class="event-meta-item p-3 text-center">
                                    <h5 class="fw-bold text-primary mb-2">Starts</h5>
                                    <p class="mb-0"><?php echo formatDate($event['start_time']); ?></p>
                                    <small class="text-muted"><?php echo formatTime($event['start_time']); ?></small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="event-meta-item p-3 text-center">
                                    <h5 class="fw-bold text-primary mb-2">Ends</h5>
                                    <p class="mb-0"><?php echo formatDate($event['end_time']); ?></p>
                                    <small class="text-muted"><?php echo formatTime($event['end_time']); ?></small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mb-4">
                            <h3 class="display-6 fw-bold text-primary"><?php echo formatPrice($event['ticket_price']); ?></h3>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <?php if ($is_logged_in): ?>
                                <form action="process_registration.php" method="post">
                                    <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                    <button type="submit" class="btn btn-lg primary-btn text-white">Register Now</button>
                                </form>
                            <?php else: ?>
                                <a href="login.php?redirect=event.php?id=<?php echo $event_id; ?>" class="btn btn-lg primary-btn text-white">Login to Register</a>
                            <?php endif; ?>
                        </div>
                        
                        <div class="text-center">
                            <p class="text-muted small mb-0"><?php echo $registration_count; ?> people already registered</p>
                        </div>
                    </div>
                </div>
                
                <!-- Organizer Info -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title h5 mb-3">Organizer</h4>
                        <div class="d-flex align-items-center">
                            <img src="images/organizers/<?php echo $event['organizer_id']; ?>.jpg" onerror="this.src='images/placeholder_user.jpg'" alt="<?php echo htmlspecialchars($event['organizer_name']); ?>" class="rounded-circle me-3" style="width: 60px; height: 60px;">
                            <div>
                                <h5 class="mb-1"><?php echo htmlspecialchars($event['organizer_name']); ?></h5>
                                <p class="text-muted mb-0 small">Event Organizer</p>
                            </div>
                        </div>
                        <hr>
                        <div class="d-grid">
                            <button class="btn btn-outline-primary btn-sm" data-bs-toggle="modal" data-bs-target="#contactOrganizerModal">Contact Organizer</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- FAQ Section -->
        <section id="faq" class="mb-5">
            <div class="card shadow-sm">
                <div class="card-body">
                    <h2 class="h3 text-center mb-4">Frequently Asked Questions</h2>
                    
                    <div class="accordion mb-4" id="faqAccordion">
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingOne">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOne">
                                    How can I attend this event?
                                </button>
                            </h2>
                            <div id="collapseOne" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    To attend this event, simply click the "Register Now" button and complete the checkout process. After registration, you'll receive a confirmation email with all the details.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingTwo">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseTwo">
                                    Can I get a refund if I can't attend?
                                </button>
                            </h2>
                            <div id="collapseTwo" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, refunds are available up to 48 hours before the event start time. After that, refunds are at the organizer's discretion.
                                </div>
                            </div>
                        </div>
                        
                        <div class="accordion-item">
                            <h2 class="accordion-header" id="headingThree">
                                <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseThree">
                                    Will there be a recording available?
                                </button>
                            </h2>
                            <div id="collapseThree" class="accordion-collapse collapse" data-bs-parent="#faqAccordion">
                                <div class="accordion-body">
                                    Yes, all registered participants will receive access to a recording of the event within 24 hours after it concludes.
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if (!isset($_SESSION['user_id'])): ?>
                    <div class="text-center">
                        <p>Don't see the answer you're looking for? Ask a question</p>
                        <form action="submit_question.php" method="post">
                            <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                            <div class="form-floating mb-3">
                                <textarea class="form-control" id="questionTextarea" name="question" style="height: 100px" required></textarea>
                                <label for="questionTextarea">Your question</label>
                            </div>
                            <button type="submit" class="btn primary-btn text-white">Submit Question</button>
                        </form>
                    </div>
                    <?php else: ?>
                    <div class="text-center">
                        <p>Please <a href="login.php">login</a> to ask questions about this event.</p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </section>
        
        <!-- Related Events -->
        <section class="mb-5">
            <h2 class="h3 mb-4">Similar Events</h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <?php foreach ($related_events as $rel_event): ?>
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="images/events/<?php echo $rel_event['event_id']; ?>.jpg" onerror="this.src='images/placeholder_event.jpg'" class="card-img-top" alt="Event image">
                        <div class="card-body">
                            <span class="category-badge"><?php echo htmlspecialchars($rel_event['category_name']); ?></span>
                            <h5 class="card-title"><?php echo htmlspecialchars($rel_event['title']); ?></h5>
                            <p class="card-text"><?php echo truncateText(htmlspecialchars($rel_event['description']), 100); ?></p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted"><?php echo formatDate($rel_event['start_time']); ?></small>
                                <span class="fw-bold"><?php echo formatPrice($rel_event['ticket_price']); ?></span>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <a href="event.php?id=<?php echo $rel_event['event_id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                
                <?php if (count($related_events) == 0): ?>
                <div class="col-12">
                    <p class="text-muted text-center">No similar events found at this time.</p>
                </div>
                <?php endif; ?>
            </div>
        </section>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h5>Event Platform</h5>
                    <p class="text-muted">Find and register for events that match your interests</p>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a href="index.php" class="nav-link text-muted p-0 mb-2">Home</a></li>
                        <li class="nav-item"><a href="events.php" class="nav-link text-muted p-0 mb-2">Events</a></li>
                        <li class="nav-item"><a href="categories.php" class="nav-link text-muted p-0 mb-2">Categories</a></li>
                        <li class="nav-item"><a href="contact.php" class="nav-link text-muted p-0">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item text-muted mb-2"><i class="bi bi-envelope me-2"></i>info@eventplatform.com</li>
                        <li class="nav-item text-muted"><i class="bi bi-telephone me-2"></i>+1 234 567 8900</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted">
                <p>&copy; 2025 Event Platform. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Contact Organizer Modal -->
    <div class="modal fade" id="contactOrganizerModal" tabindex="-1" aria-labelledby="contactOrganizerModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="contactOrganizerModalLabel">Contact <?php echo htmlspecialchars($event['organizer_name']); ?></h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="send_message.php" method="post">
                        <input type="hidden" name="organizer_id" value="<?php echo $event['organizer_id']; ?>">
                        <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                        
                        <div class="mb-3">
                            <label for="message-subject" class="form-label">Subject</label>
                            <input type="text" class="form-control" id="message-subject" name="subject" required>
                        </div>
                        <div class="mb-3">
                            <label for="message-text" class="form-label">Message</label>
                            <textarea class="form-control" id="message-text" name="message" rows="5" required></textarea>
                        </div>
                        
                        <?php if (!$is_logged_in): ?>
                        <div class="mb-3">
                            <label for="message-name" class="form-label">Your Name</label>
                            <input type="text" class="form-control" id="message-name" name="name" required>
                        </div>
                        <div class="mb-3">
                            <label for="message-email" class="form-label">Your Email</label>
                            <input type="email" class="form-control" id="message-email" name="email" required>
                        </div>
                        <?php endif; ?>
                        
                        <div class="d-grid">
                            <button type="submit" class="btn primary-btn text-white">Send Message</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript for Star Rating -->
    <script>
        // Reverse the star rating labels to show in the correct order
        document.addEventListener('DOMContentLoaded', function() {
            const ratingContainer = document.querySelector('.rating-container');
            if (ratingContainer) {
                const stars = Array.from(ratingContainer.querySelectorAll('.rating-label'));
                stars.reverse();
                
                // Adjust the display to show in reverse order
                ratingContainer.innerHTML = '';
                stars.forEach(star => {
                    ratingContainer.appendChild(star.previousElementSibling); // Add input
                    ratingContainer.appendChild(star); // Add label
                });
            }
        });
    </script>
</body>
</html>
<?php
// Close database connections
$stmt->close();
$stmt_reg->close();
$stmt_reviews->close();
$stmt_related->close();
if (isset($stmt_attendance)) $stmt_attendance->close();
if (isset($stmt_user_review)) $stmt_user_review->close();
$conn->close();
?>