<?php
// Initialize the session
session_start();

// Check if the user is logged in and is an organizer or admin
if (!isset($_SESSION["loggedin"]) || ($_SESSION["role"] != "organizer" && $_SESSION["role"] != "admin")) {
    header("location: login.php");
    exit;
}

// Include database connection
require_once "../config/database.php";

// Define variables and initialize with empty values
$title = $description = $location = $start_time = $end_time = $ticket_price = $category_id = "";
$title_err = $description_err = $location_err = $start_time_err = $end_time_err = $ticket_price_err = $category_err = "";

// Processing form data when form is submitted
if ($_SERVER["REQUEST_METHOD"] == "POST") {

    // Validate title
    if (empty(trim($_POST["title"]))) {
        $title_err = "Please enter a title.";
    } else {
        $title = trim($_POST["title"]);
    }

    // Validate description
    if (empty(trim($_POST["description"]))) {
        $description_err = "Please enter a description.";
    } else {
        $description = trim($_POST["description"]);
    }

    // Validate location
    if (empty(trim($_POST["location"]))) {
        $location_err = "Please enter a location.";
    } else {
        $location = trim($_POST["location"]);
    }

    // Validate start time
    if (empty(trim($_POST["start_time"]))) {
        $start_time_err = "Please enter a start time.";
    } else {
        $start_time = trim($_POST["start_time"]);
    }

    // Validate end time
    if (empty(trim($_POST["end_time"]))) {
        $end_time_err = "Please enter an end time.";
    } elseif (strtotime($_POST["end_time"]) <= strtotime($_POST["start_time"])) {
        $end_time_err = "End time must be after start time.";
    } else {
        $end_time = trim($_POST["end_time"]);
    }

    // Validate ticket price
    if (empty(trim($_POST["ticket_price"]))) {
        $ticket_price_err = "Please enter a ticket price.";
    } elseif (!is_numeric($_POST["ticket_price"]) || $_POST["ticket_price"] < 0) {
        $ticket_price_err = "Please enter a valid ticket price.";
    } else {
        $ticket_price = trim($_POST["ticket_price"]);
    }

    // Validate category
    if (empty($_POST["category_id"])) {
        $category_err = "Please select a category.";
    } else {
        $category_id = $_POST["category_id"];
    }

    // Check input errors before inserting into database
    if (empty($title_err) && empty($description_err) && empty($location_err) && empty($start_time_err) && empty($end_time_err) && empty($ticket_price_err) && empty($category_err)) {

        // Prepare an insert statement
        $sql = "INSERT INTO Events (organizer_id, category_id, title, description, location, start_time, end_time, ticket_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)";

        if ($stmt = mysqli_prepare($link, $sql)) {
            // Bind variables
            mysqli_stmt_bind_param($stmt, "iisssssd", $param_organizer_id, $param_category_id, $param_title, $param_description, $param_location, $param_start_time, $param_end_time, $param_ticket_price);

            // Set parameters
            $param_organizer_id = $_SESSION["user_id"];
            $param_category_id = $category_id;
            $param_title = $title;
            $param_description = $description;
            $param_location = $location;
            $param_start_time = $start_time;
            $param_end_time = $end_time;
            $param_ticket_price = $ticket_price;

            // Execute
            if (mysqli_stmt_execute($stmt)) {
                header("location: manage_events.php");
                exit();
            } else {
                echo "Oops! Something went wrong. Please try again later.";
            }

            mysqli_stmt_close($stmt);
        }
    }

    mysqli_close($link);
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Create Event</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <style>
        body { font: 14px sans-serif; }
        .wrapper { width: 800px; padding: 20px; margin: 0 auto; }
    </style>
</head>
<body>
<div class="wrapper">
    <h2>Create New Event</h2>
    <p>Please fill in the event details.</p>
    <form action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
        
        <!-- Title -->
        <div class="form-group">
            <label>Title</label>
            <input type="text" name="title" class="form-control <?php echo (!empty($title_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($title); ?>">
            <span class="invalid-feedback"><?php echo $title_err; ?></span>
        </div>    

        <!-- Category -->
        <div class="form-group">
            <label>Category</label>
            <select name="category_id" class="form-control <?php echo (!empty($category_err)) ? 'is-invalid' : ''; ?>">
                <option value="">Select Category</option>
                <?php
                // Get categories
                $sql = "SELECT category_id, category_name FROM EventCategories";
                if ($result = mysqli_query($link, $sql)) {
                    while ($row = mysqli_fetch_array($result)) {
                        $selected = ($category_id == $row['category_id']) ? 'selected' : '';
                        echo '<option value="' . $row['category_id'] . '" ' . $selected . '>' . htmlspecialchars($row['category_name']) . '</option>';
                    }
                    mysqli_free_result($result);
                }
                ?>
            </select>
            <span class="invalid-feedback"><?php echo $category_err; ?></span>
        </div>

        <!-- Description -->
        <div class="form-group">
            <label>Description</label>
            <textarea name="description" rows="4" class="form-control <?php echo (!empty($description_err)) ? 'is-invalid' : ''; ?>"><?php echo htmlspecialchars($description); ?></textarea>
            <span class="invalid-feedback"><?php echo $description_err; ?></span>
        </div>

        <!-- Location -->
        <div class="form-group">
            <label>Location</label>
            <input type="text" name="location" class="form-control <?php echo (!empty($location_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($location); ?>">
            <span class="invalid-feedback"><?php echo $location_err; ?></span>
        </div>

        <!-- Start Time -->
        <div class="form-group">
            <label>Start Time</label>
            <input type="datetime-local" name="start_time" class="form-control <?php echo (!empty($start_time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($start_time); ?>">
            <span class="invalid-feedback"><?php echo $start_time_err; ?></span>
        </div>

        <!-- End Time -->
        <div class="form-group">
            <label>End Time</label>
            <input type="datetime-local" name="end_time" class="form-control <?php echo (!empty($end_time_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($end_time); ?>">
            <span class="invalid-feedback"><?php echo $end_time_err; ?></span>
        </div>

        <!-- Ticket Price -->
        <div class="form-group">
            <label>Ticket Price (₹)</label>
            <input type="number" step="0.01" name="ticket_price" class="form-control <?php echo (!empty($ticket_price_err)) ? 'is-invalid' : ''; ?>" value="<?php echo htmlspecialchars($ticket_price); ?>">
            <span class="invalid-feedback"><?php echo $ticket_price_err; ?></span>
        </div>

        <!-- Submit Buttons -->
        <div class="form-group">
            <input type="submit" class="btn btn-primary" value="Create Event">
            <a href="manage_events.php" class="btn btn-secondary ml-2">Cancel</a>
        </div>
    </form>
</div>    
</body>
</html>
