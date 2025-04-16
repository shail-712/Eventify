<?php
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

        <div class="d-flex gap-3 align-items-center">
            <!-- Search Bar - Made Capsule Shaped -->
            <div class="input-group" style="width: 400px;">
                <input type="text" class="form-control rounded-pill rounded-end" id="event-search" placeholder="Search events..." style="border-right: none; z-index: 1;">
                <button class="btn btn-primary rounded-pill rounded-start" type="button" id="search-button" style="margin-left: 5px; z-index: 0;">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                        <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                    </svg>
                </button>
            </div>

            <!-- Time Dropdown - Added Box Shadow -->
            <select class="form-select filter-dropdown rounded-pill" aria-label="Time Period" id="time-filter" style="width: 140px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                <option selected value="all">All Time</option>
                <option value="today">Today</option>
                <option value="week">This Week</option>
                <option value="month">This Month</option>
                <option value="year">This Year</option>
            </select>

            <!-- Category Dropdown - Added Box Shadow -->
            <select class="form-select filter-dropdown rounded-pill" aria-label="Category" id="category-filter" style="width: 160px; box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);">
                <option selected value="all">All Categories</option>
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
                        $event_timestamp = $event_date->getTimestamp();
                        
                        // Get event year, month, week for filtering
                        $event_year = $event_date->format('Y');
                        $event_month = $event_date->format('Y-m');
                        $event_week = date('Y-W', $event_timestamp);
                        
                        // Generate event card with clickable link
                        echo '
                        <div class="col event-card" 
                            data-date="' . $event_date->format('Y-m-d') . '" 
                            data-timestamp="' . $event_timestamp . '"
                            data-year="' . $event_year . '"
                            data-month="' . $event_month . '"
                            data-week="' . $event_week . '"
                            data-category="' . $row["category_id"] . '"
                            data-title="' . strtolower(htmlspecialchars($row["title"])) . '"
                            data-description="' . strtolower(htmlspecialchars($row["description"])) . '"
                            data-location="' . strtolower(htmlspecialchars($row["location"])) . '">
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
            <div id="no-results" class="alert alert-info text-center mt-4" style="display: none;">
                No events match your search criteria.
            </div>
        </div>
    </div>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        // Get filter elements
        const timeFilter = document.getElementById('time-filter');
        const categoryFilter = document.getElementById('category-filter');
        const searchInput = document.getElementById('event-search');
        const searchButton = document.getElementById('search-button');
        
        // Add event listeners to filters
        timeFilter.addEventListener('change', filterEvents);
        categoryFilter.addEventListener('change', filterEvents);
        searchButton.addEventListener('click', filterEvents);
        searchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                filterEvents();
            }
        });
        
        function filterEvents() {
            const selectedTime = timeFilter.value;
            const selectedCategory = categoryFilter.value;
            const searchTerm = searchInput.value.toLowerCase().trim();
            
            const eventCards = document.querySelectorAll('.event-card');
            let visibleCount = 0;
            
            // Get current date for time-based filtering
            const now = new Date();
            const today = now.toISOString().split('T')[0]; // YYYY-MM-DD
            const thisYear = now.getFullYear().toString();
            const thisMonth = now.getFullYear() + '-' + String(now.getMonth() + 1).padStart(2, '0');
            const thisWeek = getWeekNumber(now);
            
            eventCards.forEach(card => {
                let showByTime = true;
                let showByCategory = true;
                let showBySearch = true;
                
                // Filter by time
                if (selectedTime !== 'all') {
                    const eventDate = card.dataset.date;
                    const eventYear = card.dataset.year;
                    const eventMonth = card.dataset.month;
                    const eventWeek = card.dataset.week;
                    
                    switch(selectedTime) {
                        case 'today':
                            showByTime = eventDate === today;
                            break;
                        case 'week':
                            showByTime = eventWeek === thisWeek;
                            break;
                        case 'month':
                            showByTime = eventMonth === thisMonth;
                            break;
                        case 'year':
                            showByTime = eventYear === thisYear;
                            break;
                    }
                }
                
                // Filter by category
                if (selectedCategory !== 'all') {
                    showByCategory = card.dataset.category === selectedCategory;
                }
                
                // Filter by search term
                if (searchTerm !== '') {
                    const eventTitle = card.dataset.title || '';
                    const eventDescription = card.dataset.description || '';
                    const eventLocation = card.dataset.location || '';
                    
                    showBySearch = 
                        eventTitle.includes(searchTerm) || 
                        eventDescription.includes(searchTerm) || 
                        eventLocation.includes(searchTerm);
                }
                
                // Show or hide based on combined filters
                if (showByTime && showByCategory && showBySearch) {
                    card.style.display = 'block';
                    visibleCount++;
                } else {
                    card.style.display = 'none';
                }
            });
            
            // Show "no results" message if needed
            const noResultsDiv = document.getElementById('no-results');
            if (visibleCount === 0) {
                noResultsDiv.style.display = 'block';
            } else {
                noResultsDiv.style.display = 'none';
            }
        }
        
        // Helper function to get the week number (YYYY-WW format)
        function getWeekNumber(d) {
            d = new Date(Date.UTC(d.getFullYear(), d.getMonth(), d.getDate()));
            d.setUTCDate(d.getUTCDate() + 4 - (d.getUTCDay() || 7));
            const yearStart = new Date(Date.UTC(d.getUTCFullYear(), 0, 1));
            const weekNumber = Math.ceil((((d - yearStart) / 86400000) + 1) / 7);
            return d.getUTCFullYear() + '-' + String(weekNumber).padStart(2, '0');
        }
    });
    </script>
</body>
</html>