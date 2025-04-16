<?php
// Include database connection
include 'config/database.php';

// Query to fetch the 3 most recent events
$sql = "SELECT e.*, c.category_name 
        FROM Events e
        LEFT JOIN EventCategories c ON e.category_id = c.category_id
        ORDER BY e.start_time ASC
        LIMIT 3";
$result = $conn->query($sql);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eventify - Find and Join Events</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
    <link rel="stylesheet" href="style.css">
</head>
<body>
<!-- Hero Section -->
<div class="hero-wrapper">
  <!-- Header -->
  <header class="d-flex flex-wrap justify-content-center py-5 mb-4 head">
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
      <svg class="bi me-2" width="40" height="32"><use xlink:href="#bootstrap"></use></svg>
      <span class="fs-1 logo">Eventify</span>
    </a>
    <ul class="nav nav-pills">
      <li class="nav-item header-buttons me-3"><a href="#" class="nav-link">Events</a></li>
      <li class="nav-item header-buttons me-3"><a href="#" class="nav-link">Blog</a></li>
      <li class="nav-item header-buttons me-3"><a href="#" class="nav-link">About</a></li>
      <li class="nav-item header-buttons me-3"><a href="#" class="nav-link">Contact</a></li>
      <li class="nav-item header-buttons login me-3"><a href="Webpages/login.php" class="nav-link">Login</a></li>
    </ul>
  </header>

  <!-- Hero Section (inside the hero-wrapper) -->
  <section class="hero-section text-white text-center d-flex align-items-center justify-content-center">
    <div class="container position-relative z-2">
      <h1 class="display-5 fw-bold fade-in">Bringing People Together, One Event at a Time!</h1>
      <p class="lead fw-semibold mt-3 fade-in delay-1">Discover, organize, and attend unforgettable events that connect communities and inspire moments.</p>
      <a href="#" class="btn btn-danger btn-lg mt-4 px-5 py-2 fade-in delay-2">Join Now!</a>
    </div>
  </section>
</div>

<!-- Search Bar -->
<section class="bg-dark text-white py-4">
  <div class="container">
    <form class="row g-3 justify-content-center align-items-center">
      <div class="col-md-3">
        <label for="searchEvent" class="form-label">Search Event</label>
        <input type="text" class="form-control" id="searchEvent" placeholder="Concert">
      </div>
      <div class="col-md-3">
        <label for="place" class="form-label">Place</label>
        <input type="text" class="form-control" id="place" placeholder="Mumbai">
      </div>
      <div class="col-md-3">
        <label for="time" class="form-label">Time</label>
        <select class="form-select" id="time">
          <option selected>Any date</option>
          <option value="1">Today</option>
          <option value="2">This Week</option>
          <option value="3">This Month</option>
        </select>
      </div>
    </form>
  </div>
</section>

<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between py-5">
        <h2 class="fw-bold text-dark">Upcoming Events</h2>

        <div class="d-flex gap-4">
            <!-- Weekdays Dropdown -->
            <select class="form-select filter-dropdown px-5 select options" aria-label="Weekdays">
                <option selected>Weekdays</option>
                <option>Weekend</option>
                <option value="1">Monday</option>
                <option value="2">Tuesday</option>
                <option value="3">Wednesday</option>
                <option value="4">Thursday</option>
                <option value="5">Friday</option>
                <option value="6">Saturday</option>
                <option value="0">Sunday</option>
            </select>

            <!-- Event Type Dropdown -->
            <select class="form-select filter-dropdown px-5 select options" aria-label="Event Type">
                <option disabled selected>Event Type</option>
                <option value="1">Conference</option>
                <option value="2">Workshop</option>
                <option value="3">Meetup</option>
            </select>

            <!-- Category Dropdown -->
            <select class="form-select filter-dropdown px-5 select options" aria-label="Category">
                <option disabled selected>Category</option>
                <?php
                // Fetch categories for the dropdown
                $cat_sql = "SELECT * FROM EventCategories ORDER BY category_name";
                $cat_result = $conn->query($cat_sql);
                
                if ($cat_result && $cat_result->num_rows > 0) {
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
      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
        <?php
        if ($result && $result->num_rows > 0) {
            // Output data of each row
            while($row = $result->fetch_assoc()) {
                // Format the date
                $event_date = new DateTime($row["start_time"]);
                $month = $event_date->format('M');
                $day = $event_date->format('d');
                
                // Generate event card
                echo '
                <div class="col">
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
                                </div>
                            </div>
                        </div>
                    </div>
                </div>';
            }
        } else {
            echo "<p class='text-center w-100'>No events found</p>";
        }
        ?>
      </div>
      <div class="d-flex justify-content-center py-5">
          <a href="Webpages/search_events.php"><button type="button" class="btn btn-outline-primary btn-lg px-4">View More</button></a>
      </div>  
    </div>
</div>

<?php $conn->close(); ?>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>