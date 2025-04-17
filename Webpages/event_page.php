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

// Check if user is registered for this event
$is_registered = false;
if ($is_logged_in) {
    $sql_registration = "SELECT COUNT(*) as count FROM EventRegistrations 
                WHERE user_id = ? AND event_id = ?";
    $stmt_registration = $conn->prepare($sql_registration);
    $stmt_registration->bind_param("ii", $current_user_id, $event_id);
    $stmt_registration->execute();
    $registration_result = $stmt_registration->get_result();
    $registration_data = $registration_result->fetch_assoc();
    $is_registered = ($registration_data['count'] > 0);
}
// Check if the user has attended this event
$has_reviewed = false;
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
    $output = '<div class="review-stars">'; // Different class name
    for ($i = 1; $i <= 5; $i++) {
        if ($i <= $rating) {
            $output .= '<i class="fa fa-star text-warning"></i>';
        } else {
            $output .= '<i class="fa fa-star-o text-warning"></i>';
        }
    }
    $output .= '</div>';
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
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/4.7.0/css/font-awesome.min.css">
    <style>
        @import url('https://fonts.googleapis.com/css2?family=Anton+SC&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap');

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
            text-decoration: none;
            font-family: "Anton SC", serif;
            font-weight: 400;
            font-style: normal;
            color: white;

        }
        .top-header .nav {
            list-style: none;
            display: flex;
            gap: 20px;
        }
        .top-header .nav a {
            text-decoration: none;
            font-weight: 700;
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
            display: flex;
            flex-direction: row-reverse;
            justify-content: flex-end;
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

        .event-hero {
    position: relative;
    background-size: cover;
    background-position: center;
    color: white;
    padding: 60px 0;
    min-height: 300px;
    }

    .event-hero::before {
        content: "";
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 100%;
        background-color: rgba(0, 0, 0, 0.6); /* Darkened overlay */
        z-index: 1;
    }

    .event-hero-content {
        position: relative;
        z-index: 2;
    }

    .event-gallery-container {
        margin-bottom: 30px;
    }

    .main-image-container {
        position: relative;
        max-width: 800px;
        margin: 0 auto;
    }

    #current-gallery-image {
        width: 100%;
        height: auto;
        object-fit: cover;
        border-radius: 8px;
    }

    .image-caption {
        margin-top: 8px;
        text-align: center;
        font-style: italic;
    }

    /* Main layout for the event page */
.event-content-layout {
    display: flex;
    flex-wrap: wrap;
    gap: 30px;
    margin-top: 30px;
    margin-bottom: 30px;
}

/* Left side gallery */
.event-gallery-container {
    flex: 1;
    min-width: 0; /* Prevents overflow */
    max-width: 65%;
}

/* Right side sidebar */
.event-sidebar {
    flex: 0 0 30%;
    min-width: 300px;
}

/* Gallery styling */
.main-image-container {
    position: relative;
    width: 100%;
}

#current-gallery-image {
    width: 100%;
    max-height: 450px; /* Restrict image height */
    object-fit: contain; /* Keep aspect ratio, fit inside container */
    border-radius: 8px;
    background-color: #f8f8f8; /* Light background for transparent images */
}

.image-caption {
    margin-top: 8px;
    font-style: italic;
    color: #666;
}

.gallery-navigation {
    display: flex;
    justify-content: space-between;
    align-items: center;
    position: absolute;
    bottom: 50%;
    transform: translateY(50%);
    width: 100%;
    padding: 0 10px;
}

.gallery-nav-btn {
    background-color: rgba(0, 0, 0, 0.6); /* Darker background */
    border: none;
    border-radius: 50%;
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background-color 0.3s;
    z-index: 5;
    color: white; /* White arrow color for better contrast */
}

.gallery-nav-btn:hover {
    background-color: rgba(0, 0, 0, 0.8); /* Even darker on hover */
}
.event-details-container {
    max-width: 1200px;
    margin: 0 auto; /* Centers the container */
    padding: 20px;
}

.register-button-container{
    display: flex;
    justify-content: center;
}



/* Responsive adjustments */
@media (max-width: 768px) {
    .event-content-layout {
        flex-direction: column;
    }
    
    .event-gallery-container {
        max-width: 100%;
    }
    
    .event-sidebar {
        width: 100%;
    }
}
    </style>
</head>
<body>
    <!-- Navbar -->
    <div class="top-header">
    <a href="../index.php" class="logo">EVENTIFY</a>
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

    <!-- Hero Section -->
    <section class="hero-section text-center event-hero" style="background-image: url('../images/uploads/events/<?php echo $event['banner_image']; ?>');">
        <div class="container">
            <div class="event-hero-content">
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
        </div>
    </section>

    <!-- Main Content -->
    <div class="container py-5">
    <div class="event-content-layout">
        <div class="event-gallery-container">
        <div class="event-gallery">
            <?php
            // Get the main event image
            $mainImagePath = "../images/uploads/events/" . $event['event_image'];
        
            // Get all additional event images
            $imagesSql = "SELECT * FROM EventImages WHERE event_id = ?";
            $imagesStmt = $conn->prepare($imagesSql);
            $imagesStmt->bind_param("i", $event_id);
            $imagesStmt->execute();
            $imagesResult = $imagesStmt->get_result();
        
            $allImages = [];
            $allImages[] = [
                'path' => $mainImagePath,
                'caption' => $event['title'],
                'is_main' => true
            ];
        
            while ($image = $imagesResult->fetch_assoc()) {
                $allImages[] = [
                    'path' => "../images/uploads/events/" . $image['image_path'],
                    'caption' => $image['caption'] ?? '',
                    'is_main' => false
                ];
            }
        
            $totalImages = count($allImages);
            ?>
        
            <div class="main-image-container">
                <img id="current-gallery-image" src="<?php echo htmlspecialchars($allImages[0]['path']); ?>" alt="<?php echo htmlspecialchars($allImages[0]['caption']); ?>">
                <p id="image-caption" class="image-caption"><?php echo htmlspecialchars($allImages[0]['caption']); ?></p>
        
                <?php if ($totalImages > 1): ?>
                <div class="gallery-navigation">
                    <button id="prev-image" class="gallery-nav-btn"><i class="fa fa-chevron-left"></i></button>
                    <button id="next-image" class="gallery-nav-btn"><i class="fa fa-chevron-right"></i></button>
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
        
                            <div class="d-grid mb-3 register-button-container">
                                <?php if ($is_logged_in): ?>
                                    <?php if ($is_registered): ?>
                                        <button type="button" class="btn btn-lg" disabled style="background-color: white; color: #6c5ce7; border-color: #6c5ce7;">Registered</button>
                                    <?php else: ?>
                                        <form action="process_registration.php" method="post">
                                            <input type="hidden" name="event_id" value="<?php echo $event['event_id']; ?>">
                                            <input type="hidden" name="redirect" value="event_page.php?id=<?php echo $event['event_id']; ?>">
                                            <button type="submit" class="btn btn-lg primary-btn text-white">Register Now</button>
                                        </form>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <a href="login.php?redirect=event_page.php?id=<?php echo $event['event_id']; ?>" class="btn btn-lg primary-btn text-white">Login to Register</a>
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
                                <img src="../images/uploads/profile_images/user_<?php echo $event['organizer_id']; ?>.jpg" onerror="this.src='../images/default-pfp.png'" alt="<?php echo htmlspecialchars($event['organizer_name']); ?>" class="rounded-circle me-3" style="width: 60px; height: 60px;">
                                <div>
                                    <h5 class="mb-1"><?php echo htmlspecialchars($event['organizer_name']); ?></h5>
                                    <p class="text-muted mb-0 small">Event Organizer</p>
                                </div>
                            </div>  
                        </div>
                    </div>
                </div>
            </div>
    </div>

        <!-- Event Details -->
        <div class="row mb-5">
            <div class="col-lg-8 event-details-container">
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
                                        <img src="../images/uploads/profile_images/user_<?php echo $review['user_id']; ?>.jpg" 
                                             onerror="this.src='../images/default-pfp.png'" 
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
                        <?php if ($is_logged_in && $has_attended && !$has_reviewed): ?>
                        <div class="card mt-4">
                            <div class="card-header bg-light">
                                <h5 class="mb-0">Share your experience</h5>
                            </div>
                            <div class="card-body">
                                <form action="submit_review.php" method="post">
                                    <input type="hidden" name="event_id" value="<?php echo $event_id; ?>">
                                    
                                    <div class="mb-3">
                                        <label class="form-label">Rating</label>
                                        <div class="rating-container" style="display: flex; flex-direction: row-reverse; justify-content: flex-end;">
                                            <!-- Note the values now match the visual order -->
                                            <input type="radio" id="star5" name="rating" value="1" class="rating-input">
                                            <label for="star5" class="rating-label"><i class="fa fa-star"></i></label>
                                            
                                            <input type="radio" id="star4" name="rating" value="2" class="rating-input">
                                            <label for="star4" class="rating-label"><i class="fa fa-star"></i></label>
                                            
                                            <input type="radio" id="star3" name="rating" value="3" class="rating-input">
                                            <label for="star3" class="rating-label"><i class="fa fa-star"></i></label>
                                            
                                            <input type="radio" id="star2" name="rating" value="4" class="rating-input">
                                            <label for="star2" class="rating-label"><i class="fa fa-star"></i></label>
                                            
                                            <input type="radio" id="star1" name="rating" value="5" class="rating-input">
                                            <label for="star1" class="rating-label"><i class="fa fa-star"></i></label>
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
                        <?php elseif ($is_logged_in && $has_reviewed): ?>
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
                                <a href="login.php?redirect=event_page.php?id=<?php echo $event_id; ?>" class="btn btn-outline-primary">Login to Leave a Review</a>
                            </div>
                        </div>
                        <?php endif; ?>
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
                    <img src="../images/uploads/events/<?php echo htmlspecialchars($rel_event['event_image'] ?? ''); ?>" 
                        onerror="this.src='../images/placeholder_event.jpg'" 
                        class="card-img-top" 
                        alt="Event image">


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
                            <a href="event_page.php?id=<?php echo $rel_event['event_id']; ?>" class="btn btn-outline-primary w-100">View Details</a>
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
    <footer style="background-color: #5e2ced; color: white; padding: 30px; margin-top: 60px; text-align: center;">
  <h3 style="margin-bottom: 10px;">Contact Us</h3>
  <p>Email: support@eventify.com</p>
  <p>Phone: +91-98765-43210</p>
  <p>Address: Ghatkopar, Mumbai, India</p>
</footer>


    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Custom JavaScript for Star Rating -->
    <script>
        // Reverse the star rating labels to show in the correct order
        document.addEventListener('DOMContentLoaded', function() {
            const ratingContainer = document.querySelector('form .rating-container');
            if (ratingContainer) {
                // Store all elements before clearing
                const inputs = Array.from(ratingContainer.querySelectorAll('.rating-input'));
                const labels = Array.from(ratingContainer.querySelectorAll('.rating-label'));
                
                // Reverse the arrays
                inputs.reverse();
                labels.reverse();
                
                // Clear and rebuild
                ratingContainer.innerHTML = '';
                for (let i = 0; i < inputs.length; i++) {
                    ratingContainer.appendChild(inputs[i]);
                    ratingContainer.appendChild(labels[i]);
                }
            }
        });

        document.addEventListener('DOMContentLoaded', function() {
            const allImages = <?php echo json_encode($allImages); ?>;
            let currentImageIndex = 0;
            const totalImages = allImages.length;
            
            const currentGalleryImage = document.getElementById('current-gallery-image');
            const imageCaption = document.getElementById('image-caption');
            const prevButton = document.getElementById('prev-image');
            const nextButton = document.getElementById('next-image');
            
            if(totalImages > 1) {
                prevButton.addEventListener('click', function() {
                    currentImageIndex = (currentImageIndex - 1 + totalImages) % totalImages;
                    updateImage();
                });
                
                nextButton.addEventListener('click', function() {
                    currentImageIndex = (currentImageIndex + 1) % totalImages;
                    updateImage();
                });
            }
            
            function updateImage() {
                currentGalleryImage.src = allImages[currentImageIndex].path;
                imageCaption.textContent = allImages[currentImageIndex].caption;
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
if (isset($stmt_registration)) $stmt_registration->close();
if (isset($stmt_attendance)) $stmt_attendance->close();
if (isset($stmt_user_review)) $stmt_user_review->close();
$conn->close();
?>