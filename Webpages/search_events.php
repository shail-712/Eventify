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
    @import url('https://fonts.googleapis.com/css2?family=Anton+SC&family=DM+Sans:ital,opsz,wght@0,9..40,100..1000;1,9..40,100..1000&display=swap');
        * {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
    font-family: 'Poppins', sans-serif;
}
        body {
            background-color: #f8f5ff;
            color: #333;
            scroll-behavior: smooth;
        }
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
<div class="top-header">
    <a href="/" class="logo">Eventify</a>
    <ul class="nav">
        <li><a href="../index.php">Home</a></li>
        <li><a href="event_page.php">Events</a></li>
        <li><a href="event-dashboard.php">Dashboard</a></li>
        <li><a href="about.php">About</a></li>
        <?php if (isset($_SESSION['user_id'])): ?>
            <?php if (isset($_SESSION['role']) && ($_SESSION['role'] == 'organizer' || $_SESSION['role'] == 'admin')): ?>
                <li><a href="manage_event.php">Manage Events</a></li>
            <?php endif; ?>
            <li><a href="profile.php">My Profile</a></li>
      
            <li><a href="logout.php">Logout</a></li>
        <?php else: ?>
            <li><a href="login.php">Login</a></li>
        <?php endif; ?>
    </ul>
</div>
<!-- Add this below the top header and above the album section -->
<div class="search-container py-4 bg-light">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="card shadow-sm rounded-4">
                    <div class="card-body">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-white border-end-0">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" fill="currentColor" class="bi bi-search" viewBox="0 0 16 16">
                                            <path d="M11.742 10.344a6.5 6.5 0 1 0-1.397 1.398h-.001c.03.04.062.078.098.115l3.85 3.85a1 1 0 0 0 1.415-1.414l-3.85-3.85a1.007 1.007 0 0 0-.115-.1zM12 6.5a5.5 5.5 0 1 1-11 0 5.5 5.5 0 0 1 11 0z"/>
                                        </svg>
                                    </span>
                                    <input type="text" id="event-search" class="form-control border-start-0" placeholder="Search events...">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select id="time-filter" class="form-select">
                                    <option value="all">All Time</option>
                                    <option value="today">Today</option>
                                    <option value="week">This Week</option>
                                    <option value="month">This Month</option>
                                    <option value="year">This Year</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select id="category-filter" class="form-select">
                                    <option value="all">All Categories</option>
                                    <?php
                                    // Reconnect to database to get categories
                                    $conn = new mysqli($servername, $username, $password, $dbname);
                                    $cat_sql = "SELECT * FROM EventCategories ORDER BY category_name";
                                    $cat_result = $conn->query($cat_sql);
                                    
                                    if ($cat_result->num_rows > 0) {
                                        while($cat_row = $cat_result->fetch_assoc()) {
                                            echo '<option value="' . $cat_row["category_id"] . '">' . htmlspecialchars($cat_row["category_name"]) . '</option>';
                                        }
                                    }
                                    ?>
                                </select>
                            </div>
                            <div class="col-md-2">
                                <button id="search-button" class="btn btn-primary w-100" style="background-color: #5e2ced; border-color: #5e2ced;">
                                    Search
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
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
                        echo '<div class="col event-card"
    data-date="' . $event_date->format('Y-m-d') . '"
    data-timestamp="' . $event_timestamp . '"
    data-year="' . $event_year . '"
    data-month="' . $event_month . '"
    data-week="' . $event_week . '"
    data-category="' . $row["category_id"] . '"
    data-title="' . strtolower(htmlspecialchars($row["title"])) . '"
    data-description="' . strtolower(htmlspecialchars($row["description"])) . '"
    data-location="' . strtolower(htmlspecialchars($row["location"])) . '">';
echo '<a href="event_page.php?id=' . $row["event_id"] . '" class="event-link">';
echo '<div class="card shadow-sm hover-effect rounded-4 overflow-hidden">';
echo '<div class="card-img-container" style="height: 225px; overflow: hidden;">';

// Image logic
$image_path = '../images/uploads/events/' . ($row["event_image"] ? $row["event_image"] : 'placeholder_event.jpg');
if ($row["event_image"] && file_exists($image_path)) {
    echo '<img src="' . $image_path . '" class="card-img-top rounded-top-4" alt="' . htmlspecialchars($row["title"]) . '">';
} else {
    echo '<img src="../images/placeholder_event.jpg" class="card-img-top rounded-top-4" alt="' . htmlspecialchars($row["title"]) . '">';
}

echo '</div>';
echo '<div class="card-body">';
echo '<div class="row">';
echo '<div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">';
echo '<b class="bi bi-info-circle fs-5 date">' . $month . '</b>';
echo '<span class="fs-1 fw-bold">' . $day . '</span>';
echo '</div>';
echo '<div class="col-10">';
echo '<p><b>' . htmlspecialchars($row["title"]) . '</b></p>';
echo '<p class="card-text">' . htmlspecialchars(substr($row["description"], 0, 100)) . (strlen($row["description"]) > 100 ? '...' : '') . '</p>';
echo '<div class="d-flex justify-content-between align-items-center">';
echo '<small class="text-muted">Location: ' . htmlspecialchars($row["location"]) . '</small>';
echo '<small class="text-muted">' . htmlspecialchars($row["category_name"]) . '</small>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</div>';
echo '</a>';
echo '</div>';
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

    <footer style="background-color: #5e2ced; color: white; padding: 30px; margin-top: 60px; text-align: center;">
        <h3 style="margin-bottom: 10px;">Contact Us</h3>
        <p>Email: support@eventify.com</p>
        <p>Phone: +91-98765-43210</p>
        <p>Address: Ghatkopar, Mumbai, India</p>
    </footer>

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
        searchInput.addEventListener('keyup', function(event) {
            if (event.key === 'Enter') {
                filterEvents();
            }
        });
        
        // Add event listener to search button
        searchButton.addEventListener('click', filterEvents);
        
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