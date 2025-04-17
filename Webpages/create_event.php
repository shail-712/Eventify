<?php
//create_event.php
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
$title = $description = $location = $start_date = $start_time = $end_date = $end_time = $ticket_price = $category_id = $max_attendees = "";
$event_image = $banner_image = null;
$errors = [];

// Process form submission
if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Validate and sanitize input
    $title = trim($_POST['title']);
    $description = trim($_POST['description']);
    $location = trim($_POST['location']);
    $start_date = trim($_POST['start_date']);
    $start_time = trim($_POST['start_time']);
    $end_date = trim($_POST['end_date']);
    $end_time = trim($_POST['end_time']);
    $ticket_price = trim($_POST['ticket_price']);
    $category_id = trim($_POST['category_id']);
    $max_attendees = trim($_POST['max_attendees']);
    
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
    
    if (empty($start_date) || empty($start_time)) {
        $errors[] = "Start date and time are required";
    }
    
    if (empty($end_date) || empty($end_time)) {
        $errors[] = "End date and time are required";
    }
    
    if (!is_numeric($ticket_price) || $ticket_price < 0) {
        $errors[] = "Valid ticket price is required";
    }
    
    if (!empty($max_attendees) && (!is_numeric($max_attendees) || $max_attendees <= 0)) {
        $errors[] = "Maximum attendees must be a positive number";
    }
    
    if (empty($category_id)) {
        $errors[] = "Category is required";
    }
    
    // Format datetime for database
    $start_datetime = $start_date . ' ' . $start_time . ':00';
    $end_datetime = $end_date . ' ' . $end_time . ':00';
    
    // Handle file uploads
    $upload_dir = "../images/uploads/events/";
    
    // Ensure upload directory exists
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }
    
    // Process event image
    if (isset($_FILES['event_image']) && $_FILES['event_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($_FILES['event_image']['type'], $allowed_types)) {
            $errors[] = "Event image must be JPEG, PNG, or GIF";
        } else {
            $file_name = time() . '_' . $_FILES['event_image']['name'];
            $destination = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['event_image']['tmp_name'], $destination)) {
                $event_image = $file_name;
            } else {
                $errors[] = "Failed to upload event image";
            }
        }
    }
    
    // Process banner image
    if (isset($_FILES['banner_image']) && $_FILES['banner_image']['error'] == 0) {
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
        
        if (!in_array($_FILES['banner_image']['type'], $allowed_types)) {
            $errors[] = "Banner image must be JPEG, PNG, or GIF";
        } else {
            $file_name = 'banner_' . time() . '_' . $_FILES['banner_image']['name'];
            $destination = $upload_dir . $file_name;
            
            if (move_uploaded_file($_FILES['banner_image']['tmp_name'], $destination)) {
                $banner_image = $file_name;
            } else {
                $errors[] = "Failed to upload banner image";
            }
        }
    }
    
    // If no errors, proceed with inserting data
    if (empty($errors)) {
        // Prepare and execute SQL statement
        $stmt = $conn->prepare("INSERT INTO Events (organizer_id, category_id, title, description, location, start_time, end_time, ticket_price, max_attendees, event_image, banner_image, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')");
        $stmt->bind_param("iisssssdiss", $_SESSION['user_id'], $category_id, $title, $description, $location, $start_datetime, $end_datetime, $ticket_price, $max_attendees, $event_image, $banner_image);
        
        if ($stmt->execute()) {
            // Get the new event ID
            $event_id = $conn->insert_id;
            
            // Handle additional event images if provided
            if (isset($_FILES['additional_images']) && is_array($_FILES['additional_images']['name'])) {
                $image_stmt = $conn->prepare("INSERT INTO EventImages (event_id, image_path, caption) VALUES (?, ?, ?)");
                
                for ($i = 0; $i < count($_FILES['additional_images']['name']); $i++) {
                    if ($_FILES['additional_images']['error'][$i] == 0) {
                        $allowed_types = ['image/jpeg', 'image/png', 'image/gif'];
                        
                        if (in_array($_FILES['additional_images']['type'][$i], $allowed_types)) {
                            $file_name = 'add_' . time() . '_' . $_FILES['additional_images']['name'][$i];
                            $destination = $upload_dir . $file_name;
                            
                            if (move_uploaded_file($_FILES['additional_images']['tmp_name'][$i], $destination)) {
                                $caption = isset($_POST['image_captions'][$i]) ? $_POST['image_captions'][$i] : '';
                                $image_stmt->bind_param("iss", $event_id, $file_name, $caption);
                                $image_stmt->execute();
                            }
                        }
                    }
                }
                
                $image_stmt->close();
            }
            
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
    <style>
        .image-preview {
            max-width: 200px;
            max-height: 200px;
            margin-top: 10px;
        }
        .additional-image-container {
            border: 1px solid #ddd;
            padding: 10px;
            margin-bottom: 10px;
            border-radius: 5px;
        }
    </style>
</head>
<body>
    <div class="container mt-5 mb-5">
        <div class="row">
            <div class="col-md-10 offset-md-1">
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
                        
                        <form method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-3">
                                        <label for="title" class="form-label">Event Title</label>
                                        <input type="text" class="form-control" id="title" name="title" value="<?php echo htmlspecialchars($title); ?>" required>
                                    </div>
                                </div>
                                
                                <div class="col-md-4">
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
                                </div>
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
                                <div class="col-md-3 mb-3">
                                    <label for="start_date" class="form-label">Start Date</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?php echo htmlspecialchars($start_date); ?>" required>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="start_time" class="form-label">Start Time</label>
                                    <input type="time" class="form-control" id="start_time" name="start_time" value="<?php echo htmlspecialchars($start_time); ?>" required>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="end_date" class="form-label">End Date</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?php echo htmlspecialchars($end_date); ?>" required>
                                </div>
                                
                                <div class="col-md-3 mb-3">
                                    <label for="end_time" class="form-label">End Time</label>
                                    <input type="time" class="form-control" id="end_time" name="end_time" value="<?php echo htmlspecialchars($end_time); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="ticket_price" class="form-label">Ticket Price ($)</label>
                                    <input type="number" step="0.01" min="0" class="form-control" id="ticket_price" name="ticket_price" value="<?php echo htmlspecialchars($ticket_price); ?>" required>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="max_attendees" class="form-label">Maximum Attendees (Leave blank for unlimited)</label>
                                    <input type="number" min="1" class="form-control" id="max_attendees" name="max_attendees" value="<?php echo htmlspecialchars($max_attendees); ?>">
                                </div>
                            </div>
                            
                            <h4 class="mt-4 mb-3">Event Images</h4>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="event_image" class="form-label">Main Event Image</label>
                                    <input type="file" class="form-control" id="event_image" name="event_image" accept="image/jpeg, image/png, image/gif" onchange="previewImage(this, 'event-image-preview')">
                                    <div id="event-image-preview"></div>
                                    <small class="text-muted">This will be the primary image displayed for your event</small>
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="banner_image" class="form-label">Banner Image</label>
                                    <input type="file" class="form-control" id="banner_image" name="banner_image" accept="image/jpeg, image/png, image/gif" onchange="previewImage(this, 'banner-image-preview')">
                                    <div id="banner-image-preview"></div>
                                    <small class="text-muted">Recommended size: 1200 x 300 pixels</small>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label">Additional Images (Optional)</label>
                                <div id="additional-images-container">
                                    <div class="additional-image-container">
                                        <div class="row">
                                            <div class="col-md-8">
                                                <input type="file" class="form-control" name="additional_images[]" accept="image/jpeg, image/png, image/gif" onchange="previewAdditionalImage(this)">
                                                <div class="additional-preview mt-2"></div>
                                            </div>
                                            <div class="col-md-4">
                                                <input type="text" class="form-control" name="image_captions[]" placeholder="Image Caption (Optional)">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <button type="button" class="btn btn-secondary mt-2" onclick="addImageField()">Add Another Image</button>
                            </div>
                            
                            <div class="d-grid gap-2 mt-4">
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
    <script>
        function previewImage(input, previewId) {
            const preview = document.getElementById(previewId);
            preview.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('image-preview');
                    preview.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function previewAdditionalImage(input) {
            const previewDiv = input.parentElement.querySelector('.additional-preview');
            previewDiv.innerHTML = '';
            
            if (input.files && input.files[0]) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const img = document.createElement('img');
                    img.src = e.target.result;
                    img.classList.add('image-preview');
                    previewDiv.appendChild(img);
                }
                
                reader.readAsDataURL(input.files[0]);
            }
        }
        
        function addImageField() {
            const container = document.getElementById('additional-images-container');
            const newField = document.createElement('div');
            newField.className = 'additional-image-container';
            newField.innerHTML = `
                <div class="row">
                    <div class="col-md-8">
                        <input type="file" class="form-control" name="additional_images[]" accept="image/jpeg, image/png, image/gif" onchange="previewAdditionalImage(this)">
                        <div class="additional-preview mt-2"></div>
                    </div>
                    <div class="col-md-4">
                        <input type="text" class="form-control" name="image_captions[]" placeholder="Image Caption (Optional)">
                    </div>
                </div>
                <button type="button" class="btn btn-danger btn-sm mt-2" onclick="removeImageField(this)">Remove</button>
            `;
            container.appendChild(newField);
        }
        
        function removeImageField(button) {
            const fieldToRemove = button.parentElement;
            fieldToRemove.remove();
        }
    </script>
</body>
</html>
<?php
// Close database connection
$conn->close();
?>