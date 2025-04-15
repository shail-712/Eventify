<?php
// Start session to maintain user login state
session_start();

// Check if user is logged in and is an organizer or admin
if (!isset($_SESSION['user_id']) || ($_SESSION['role'] != 'organizer' && $_SESSION['role'] != 'admin')) {
    // Redirect to login page if not logged in or not authorized
    header("Location: login.php");
    exit();
}

// Database connection
include '../config/database.php';

// Initialize variables
$title = $description = $location = $start_time = $end_time = $ticket_price = $category_id = "";
$errors = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $start_time = trim($_POST['start_time']);
    $end_time = trim($_POST['end_time']);
    $ticket_price = trim($_POST['ticket_price']);
    $category_id = trim($_POST['category_id']);
    
    // Form validation
    if (empty($title)) {
        $errors[] = "Title is required";
    }
    
    if (empty($description)) {
        $errors[] = "Description is required";
    }
    
    if (empty($location)) {
        $errors[] = "Location is required";
    }
    
    if (empty($start_time)) {
        $errors[] = "Start time is required";
    }
    
    if (empty($end_time)) {
        $errors[] = "End time is required";
    }
    
    if (!is_numeric($ticket_price) || $ticket_price < 0) {
        $errors[] = "Valid ticket price is required";
    }
    
    if (empty($category_id)) {
        $errors[] = "Category is required";
    }
    
    // If no errors, proceed with inserting data
    if (empty($errors)) {
        // Prepare and execute SQL statement
        $stmt = $conn->prepare("INSERT INTO Events (organizer_id, category_id, title, description, location, start_time, end_time, ticket_price) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("iisssssd", $_SESSION['user_id'], $category_id, $title, $description, $location, $start_time, $end_time, $ticket_price);
        
        if ($stmt->execute()) {
            // Redirect to manage events page after successful creation
            header("Location: manage_event.php?success=1");
            exit();
        } else {
            $errors[] = "Error creating event: " . $conn->error;
        }
        
        $stmt->close();
    }
}

// Define the categories
$predefined_categories = [
    ['category_id' => 1, 'category_name' => 'Music'],
    ['category_id' => 2, 'category_name' => 'Sports'],
    ['category_id' => 3, 'category_name' => 'Workshop'],
    ['category_id' => 4, 'category_name' => 'Conference'],
    ['category_id' => 5, 'category_name' => 'Festival']
];

// Use the predefined categories instead of fetching from database
// Alternatively, you could insert these categories into the database first
$categories = $predefined_categories;

// If you still want to fetch from database, uncomment the below code
/*
$categories = [];
$result = $conn->query("SELECT category_id, category_name FROM EventCategories");
if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
        $categories[] = $row;
    }
}
*/
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Create Event</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css">
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-8 offset-md-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h2>Create New Event</h2>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul>
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo $error; ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                            <div class="mb-3">
                                <label for="title" class="form-label">Event Title</label>
                                <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="category_id" class="form-label">Category</label>
                                <select class="form-select" id="category_id" name="category_id" required>
                                    <option value="">-- Select Category --</option>
                                    <?php foreach ($categories as $category): ?>
                                        <option value="<?php echo $category['category_id']; ?>" <?php if ($category_id == $category['category_id']) echo 'selected'; ?>>
                                            <?php echo htmlspecialchars($category['category_name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($description); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="location" class="form-label">Location</label>
                                <input type="text" class="form-control" id="location" name="location" value="<?php echo htmlspecialchars($location); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="datetime-local" class="form-control" id="start_time" name="start_time" value="<?php echo htmlspecialchars($start_time); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="datetime-local" class="form-control" id="end_time" name="end_time" value="<?php echo htmlspecialchars($end_time); ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="ticket_price" class="form-label">Ticket Price ($)</label>
                                <input type="number" step="0.01" min="0" class="form-control" id="ticket_price" name="ticket_price" value="<?php echo htmlspecialchars($ticket_price); ?>" required>
                            </div>
                            
                            <div class="d-grid gap-2">
                                <button type="submit" class="btn btn-primary">Create Event</button>
                                <a href="manage_event.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>