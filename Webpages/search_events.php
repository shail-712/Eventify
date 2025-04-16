
<?php
// search_events.php
include '../config/database.php';
session_start();
// Query to fetch events with category and organizer information
$sql = "SELECT e.*, c.category_name, u.name as organizer_name 
        FROM Events e
        LEFT JOIN EventCategories c ON e.category_id = c.category_id
        LEFT JOIN Users u ON e.organizer_id = u.user_id
        ORDER BY e.start_time ASC";
$result = $conn->query($sql);
$is_logged_in = isset($_SESSION['user_id']);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventify - Find and Join Events</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="../style.css">
    <style>
        .event-link {
            text-decoration: none;
            color: inherit;
            cursor: pointer;
        }
        .event-link:hover {
            text-decoration: none;
            color: inherit;
        }
        .hover-effect {
            transition: transform 0.3s ease;
        }
        .hover-effect:hover {
            transform: translateY(-5px);
        }
    </style>
</head>
<body>
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

    <div class="container mt-4">
        <div class="d-flex align-items-center justify-content-between py-5">
            <h2 class="fw-bold text-dark">Upcoming Events</h2>

            <div class="d-flex gap-4">
                <!-- Weekdays Dropdown -->
                <select class="form-select filter-dropdown px-5 select options" aria-label="Weekdays" id="weekday-filter">
                    <option selected value="all">Weekdays</option>
                    <option value="weekend">Weekend</option>
                    <option value="1">Monday</option>
                    <option value="2">Tuesday</option>
                    <option value="3">Wednesday</option>
                    <option value="4">Thursday</option>
                    <option value="5">Friday</option>
                    <option value="6">Saturday</option>
                    <option value="0">Sunday</option>
                </select>

                <!-- Event Type Dropdown -->
                <select class="form-select filter-dropdown px-5 select options" aria-label="Event Type" id="event-type-filter">
                    <option selected value="all">Event Type</option>
                    <option value="Conference">Conference</option>
                    <option value="Workshop">Workshop</option>
                    <option value="Meetup">Meetup</option>
                </select>

                <!-- Category Dropdown -->
                <select class="form-select filter-dropdown px-5 select options" aria-label="Category" id="category-filter">
                    <option selected value="all">Category</option>
                    <?php
                    // Fetch categories for the dropdown
                    $cat_sql = "SELECT * FROM EventCategories ORDER BY category_name";
                    $cat_result = $conn->query($cat_sql);
                    
                    if ($cat_result->num_rows > 0) {
                        while($cat_row = $cat_result->fetch_assoc()) {
                            echo '<option value="' . $cat_row["category_id"] . '">' . $cat_row["category_name"] . '</option>';
                        }
                    }
                    ?>
                </select>
            </div>
        </div>
    </div>

    <div class="album py-5 bg-body-tertiary">
        <div class="container">
            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4" id="events-container">
                <?php
                if ($result->num_rows > 0) {
                    // Output data of each row
                    while($row = $result->fetch_assoc()) {
                        // Format the date
                        $event_date = new DateTime($row["start_time"]);
                        $month = $event_date->format('M');
                        $day = $event_date->format('d');
                        
                        // Generate event card with clickable link
                        echo '
                        <div class="col event-card" 
                            data-day="' . $event_date->format('w') . '" 
                            data-category="' . $row["category_id"] . '">
                            <a href="event_page.php?id=' . $row["event_id"] . '" class="event-link">
                                <div class="card shadow-sm hover-effect rounded-4 overflow-hidden">
                                    <svg class="bd-placeholder-img card-img-top rounded-top-4" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="' . htmlspecialchars($row["title"]) . '" preserveAspectRatio="xMidYMid slice" focusable="false">
                                        <title>' . htmlspecialchars($row["title"]) . '</title>
                                        <rect width="100%" height="100%" fill="#55595c"></rect>
                                        <text x="50%" y="50%" fill="#eceeef" dy=".3em">' . htmlspecialchars($row["title"]) . '</text>
                                    </svg>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">
                                                <b class="bi bi-info-circle fs-5 date">' . $month . '</b>
                                                <span class="fs-1 fw-bold">' . $day . '</span>
                                            </div>
                                            <div class="col-10">
                                                <p><b>' . htmlspecialchars($row["title"]) . '</b></p>
                                                <p class="card-text">
                                                    ' . htmlspecialchars(substr($row["description"], 0, 100)) . (strlen($row["description"]) > 100 ? '...' : '') . '
                                                </p>
                                                <div class="d-flex justify-content-between align-items-center">
                                                    <small class="text-muted">Location: ' . htmlspecialchars($row["location"]) . '</small>
                                                    <small class="text-muted">' . htmlspecialchars($row["category_name"]) . '</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        </div>';
                    }
                } else {
                    echo "<p class='text-center w-100'>No events found</p>";
                }
                $conn->close();
                ?>
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get filter elements
        const weekdayFilter = document.getElementById('weekday-filter');
        const categoryFilter = document.getElementById('category-filter');
        const eventTypeFilter = document.getElementById('event-type-filter');
        
        // Add event listeners to filters
        weekdayFilter.addEventListener('change', filterEvents);
        categoryFilter.addEventListener('change', filterEvents);
        eventTypeFilter.addEventListener('change', filterEvents);
        
        function filterEvents() {
            const selectedDay = weekdayFilter.value;
            const selectedCategory = categoryFilter.value;
            const selectedType = eventTypeFilter.value;
            
            const eventCards = document.querySelectorAll('.event-card');
            
            eventCards.forEach(card => {
                let showByDay = true;
                let showByCategory = true;
                
                // Filter by day
                if (selectedDay !== 'all') {
                    if (selectedDay === 'weekend') {
                        showByDay = card.dataset.day === '0' || card.dataset.day === '6';
                    } else {
                        showByDay = card.dataset.day === selectedDay;
                    }
                }
                
                // Filter by category
                if (selectedCategory !== 'all') {
                    showByCategory = card.dataset.category === selectedCategory;
                }
                
                // Show or hide based on combined filters
                if (showByDay && showByCategory) {
                    card.style.display = 'block';
                } else {
                    card.style.display = 'none';
                }
            });
        }
    });
    </script>
</body>
</html>