<?php
include '../config/database.php';
session_start();
$is_logged_in = isset($_SESSION['user_id']);
// Check if user is logged in
if (!$is_logged_in) {
    // Redirect to login page
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$successMessage = '';
$errorMessage = '';
$editMode = isset($_GET['edit']) && $_GET['edit'] == 'true';

// Define default profile image path
$defaultProfileImage = "../images/default-pfp.png";

// Fetch user data
$sql = "SELECT * FROM Users WHERE user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($result->num_rows > 0) {
    $userData = $result->fetch_assoc();
} else {
    // User not found
    header("Location: login.php");
    exit();
}

// Handle profile update
if (isset($_POST['update_profile'])) {
    $name = $_POST['name'];
    $email = $_POST['email'];
    $phone = $_POST['phone'];
    $bio = $_POST['bio'];
    
    // Check if email is already taken by another user
    $checkEmailSql = "SELECT user_id FROM Users WHERE email = ? AND user_id != ?";
    $checkStmt = $conn->prepare($checkEmailSql);
    $checkStmt->bind_param("si", $email, $user_id);
    $checkStmt->execute();
    $checkResult = $checkStmt->get_result();
    
    if ($checkResult->num_rows > 0) {
        $errorMessage = "Email is already in use by another account.";
    } else {
        
        if (empty($userData['profile_image'])) {
            $userData['profile_image'] = $defaultProfileImage;
        }
        
        // Initialize profile image as current value
        $profile_image = $userData['profile_image'];
        
        // Process profile image if uploaded
        if (isset($_FILES['profile_image']) && $_FILES['profile_image']['size'] > 0) {
            $target_dir = "../images/uploads/profile_images/";
            
            // Create directory if it doesn't exist
            if (!file_exists($target_dir)) {
                mkdir($target_dir, 0777, true);
            }
            
            $file_extension = strtolower(pathinfo($_FILES["profile_image"]["name"], PATHINFO_EXTENSION));
            $new_filename = "user_pfp" . $user_id . "_" . "." . $file_extension;
            $target_file = $target_dir . $new_filename;
            
            // Check file type
            $allowed_types = array("jpg", "jpeg", "png", "gif");
            if (in_array($file_extension, $allowed_types)) {
                if (move_uploaded_file($_FILES["profile_image"]["tmp_name"], $target_file)) {
                    $profile_image = $target_file;
                } else {
                    $errorMessage = "Sorry, there was an error uploading your file.";
                }
            } else {
                $errorMessage = "Sorry, only JPG, JPEG, PNG & GIF files are allowed.";
            }
        }
        
        // Update user profile
        if (empty($errorMessage)) {
            $updateSql = "UPDATE Users SET name = ?, email = ?, phone_number = ?, bio = ?, profile_image = ? WHERE user_id = ?";
            $updateStmt = $conn->prepare($updateSql);
            $updateStmt->bind_param("sssssi", $name, $email, $phone, $bio, $profile_image, $user_id);
            
            if ($updateStmt->execute()) {
                $successMessage = "Profile updated successfully!";
                
                // Update session data
                $_SESSION['name'] = $name;
                
                // Refresh user data
                $userData['name'] = $name;
                $userData['email'] = $email;
                $userData['phone_number'] = $phone;
                $userData['bio'] = $bio;
                $userData['profile_image'] = $profile_image;
                
                // Turn off edit mode
                $editMode = false;
            } else {
                $errorMessage = "Error updating profile: " . $conn->error;
            }
        }
    }
}

// Handle password update
if (isset($_POST['update_password'])) {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Verify that new passwords match
    if ($new_password !== $confirm_password) {
        $errorMessage = "New passwords do not match.";
    } else {
        // Verify current password
        if (password_verify($current_password, $userData['password_hash'])) {
            // Hash the new password
            $new_password_hash = password_hash($new_password, PASSWORD_DEFAULT);
            
            // Update password
            $updatePasswordSql = "UPDATE Users SET password_hash = ? WHERE user_id = ?";
            $updatePasswordStmt = $conn->prepare($updatePasswordSql);
            $updatePasswordStmt->bind_param("si", $new_password_hash, $user_id);
            
            if ($updatePasswordStmt->execute()) {
                $successMessage = "Password updated successfully!";
            } else {
                $errorMessage = "Error updating password: " . $conn->error;
            }
        } else {
            $errorMessage = "Current password is incorrect.";
        }
    }
}

// Fetch user events
$events = [];
$eventsSql = "SELECT e.* FROM Events e 
              JOIN EventRegistrations er ON e.event_id = er.event_id 
              WHERE er.user_id = ?";
$eventsStmt = $conn->prepare($eventsSql);
$eventsStmt->bind_param("i", $user_id);
$eventsStmt->execute();
$eventsResult = $eventsStmt->get_result();

if ($eventsResult->num_rows > 0) {
    while ($row = $eventsResult->fetch_assoc()) {
        $events[] = $row;
    }
}

// Fetch user tickets
$tickets = [];
$ticketsSql = "SELECT t.*, e.title as event_title FROM Tickets t 
               JOIN Events e ON t.event_id = e.event_id 
               WHERE t.attendee_id = ?";
$ticketsStmt = $conn->prepare($ticketsSql);
$ticketsStmt->bind_param("i", $user_id);
$ticketsStmt->execute();
$ticketsResult = $ticketsStmt->get_result();

if ($ticketsResult->num_rows > 0) {
    while ($row = $ticketsResult->fetch_assoc()) {
        $tickets[] = $row;
    }
}

// Close connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Profile | Event Manager</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }
        
        body {
            background-color: #f5f5f5;
            color: #333;
            line-height: 1.6;
        }
        
        .container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 20px;
        }
        
        header {
            background-color: #4a6fdc;
            color: white;
            padding: 10px 0;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        
        .header-content {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .logo {
            font-size: 24px;
            font-weight: bold;
        }
        
        nav ul {
            display: flex;
            list-style: none;
        }
        
        nav ul li {
            margin-left: 20px;
        }
        
        nav ul li a {
            color: white;
            text-decoration: none;
            transition: all 0.3s ease;
        }
        
        nav ul li a:hover {
            opacity: 0.8;
        }
        
        .profile-container {
            display: flex;
            margin-top: 30px;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .profile-sidebar {
            width: 250px;
            background-color: #f8f9fa;
            padding: 20px;
            border-right: 1px solid #eee;
        }
        
        .profile-image-container {
            text-align: center;
            margin-bottom: 20px;
        }
        
        .profile-image {
            width: 150px;
            height: 150px;
            border-radius: 50%;
            object-fit: cover;
            border: 4px solid #fff;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        
        .profile-nav {
            list-style: none;
        }
        
        .profile-nav li {
            padding: 10px 0;
            border-bottom: 1px solid #eee;
        }
        
        .profile-nav li:last-child {
            border-bottom: none;
        }
        
        .profile-nav li a {
            color: #555;
            text-decoration: none;
            display: block;
            transition: all 0.3s ease;
        }
        
        .profile-nav li a:hover, .profile-nav li a.active {
            color: #4a6fdc;
        }
        
        .profile-content {
            flex: 1;
            padding: 30px;
        }
        
        .profile-header {
            margin-bottom: 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        h1 {
            font-size: 24px;
            color: #333;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
            color: #555;
        }
        
        input[type="text"],
        input[type="email"],
        input[type="password"],
        input[type="tel"],
        textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
            transition: border 0.3s ease;
        }
        
        input[type="text"]:focus,
        input[type="email"]:focus,
        input[type="password"]:focus,
        input[type="tel"]:focus,
        textarea:focus {
            border-color: #4a6fdc;
            outline: none;
        }
        
        textarea {
            height: 120px;
            resize: vertical;
        }
        
        .btn {
            display: inline-block;
            background-color: #4a6fdc;
            color: white;
            padding: 10px 20px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            transition: background-color 0.3s ease;
        }
        
        .btn:hover {
            background-color: #345dc4;
        }
        
        .btn-secondary {
            background-color: #6c757d;
        }
        
        .btn-secondary:hover {
            background-color: #5a6268;
        }
        
        .actions {
            display: flex;
            justify-content: space-between;
            margin-top: 30px;
        }
        
        .password-section {
            margin-top: 40px;
            padding-top: 20px;
            border-top: 1px solid #eee;
        }
        
        .alert {
            padding: 10px 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background-color: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-danger {
            background-color: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        .image-preview {
            margin-top: 10px;
            text-align: center;
        }
        
        .event-card, .ticket-card {
            border: 1px solid #ddd;
            border-radius: 4px;
            padding: 15px;
            margin-bottom: 15px;
            background-color: #fcfcfc;
        }
        
        .event-card h3, .ticket-card h3 {
            margin-bottom: 10px;
            color: #4a6fdc;
        }
        
        .event-date, .ticket-details {
            font-size: 14px;
            color: #666;
            margin-bottom: 10px;
        }
        
        .event-location, .ticket-status {
            font-size: 14px;
            color: #666;
            margin-bottom: 15px;
        }
        
        .ticket-status.valid {
            color: #28a745;
        }
        
        .ticket-status.used {
            color: #6c757d;
        }
        
        .ticket-status.refunded, .ticket-status.cancelled {
            color: #dc3545;
        }
        
        .profile-info {
            margin-bottom: 30px;
        }
        
        .profile-info-row {
            display: flex;
            margin-bottom: 15px;
            border-bottom: 1px solid #f0f0f0;
            padding-bottom: 10px;
        }
        
        .profile-info-label {
            width: 30%;
            font-weight: 500;
            color: #555;
        }
        
        .profile-info-value {
            width: 70%;
        }
        
        .bio-section {
            margin-top: 20px;
        }
        
        .bio-content {
            background-color: #f9f9f9;
            padding: 15px;
            border-radius: 4px;
            border-left: 3px solid #4a6fdc;
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

    <div class="container">
        <div class="profile-container">
            <div class="profile-sidebar">
                <div class="profile-image-container">
                    <!-- Always use the user's profile image if it exists, otherwise use the default image -->
                    <img src="<?php echo !empty($userData['profile_image']) ? htmlspecialchars($userData['profile_image']) : $defaultProfileImage; ?>" 
                         alt="Profile Photo" class="profile-image">
                </div>
                <ul class="profile-nav">
                    <li><a href="#" class="active" onclick="showTab('personal-info'); return false;">Personal Information</a></li>
                    <li><a href="#" onclick="showTab('my-events'); return false;">My Events</a></li>
                    <li><a href="#" onclick="showTab('password'); return false;">Change Password</a></li>
                </ul>
            </div>
            <div class="profile-content">
                <?php if (!empty($successMessage)): ?>
                    <div class="alert alert-success">
                        <?php echo htmlspecialchars($successMessage); ?>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($errorMessage)): ?>
                    <div class="alert alert-danger">
                        <?php echo htmlspecialchars($errorMessage); ?>
                    </div>
                <?php endif; ?>
                
                <div id="personal-info" class="tab-content active">
                    <div class="profile-header">
                        <div>
                            <h1>Personal Information</h1>
                            <p>Your profile details</p>
                        </div>
                        <?php if (!$editMode): ?>
                            <a href="?edit=true" class="btn"><i class="bi bi-pencil-square"></i> Edit Profile</a>
                        <?php endif; ?>
                    </div>
                    
                    <?php if ($editMode): ?>
                        <!-- Edit Mode - Show Form -->
                        <form id="profile-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" enctype="multipart/form-data">
                            <div class="form-group">
                                <label for="name">Full Name</label>
                                <input type="text" id="name" name="name" value="<?php echo htmlspecialchars($userData['name']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="email">Email Address</label>
                                <input type="email" id="email" name="email" value="<?php echo htmlspecialchars($userData['email']); ?>" required>
                            </div>
                            
                            <div class="form-group">
                                <label for="phone">Phone Number</label>
                                <input type="tel" id="phone" name="phone" value="<?php echo htmlspecialchars($userData['phone_number']); ?>">
                            </div>
                            
                            <div class="form-group">
                                <label for="bio">Bio</label>
                                <textarea id="bio" name="bio"><?php echo htmlspecialchars($userData['bio']); ?></textarea>
                            </div>
                            
                            <div class="form-group">
                                <label for="profile_image">Profile Image</label>
                                <input type="file" id="profile_image" name="profile_image" accept="image/*">
                                <small class="form-text text-muted">Upload a new image or leave blank to keep current image.</small>
                                
                                <div class="image-preview" id="imagePreview">
                                    <p>Current image:</p>
                                    <img src="<?php echo !empty($userData['profile_image']) ? htmlspecialchars($userData['profile_image']) : $defaultProfileImage; ?>" 
                                         alt="Current Profile" style="max-width: 100px; max-height: 100px; margin-top: 10px;">
                                </div>
                            </div>
                            
                            <div class="actions">
                                <button type="submit" name="update_profile" class="btn">Save Changes</button>
                                <a href="profile.php" class="btn btn-secondary">Cancel</a>
                            </div>
                        </form>
                    <?php else: ?>
                        <!-- View Mode - Display Information -->
                        <div class="profile-info">
                            <div class="profile-info-row">
                                <div class="profile-info-label">Full Name</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($userData['name']); ?></div>
                            </div>
                            
                            <div class="profile-info-row">
                                <div class="profile-info-label">Email Address</div>
                                <div class="profile-info-value"><?php echo htmlspecialchars($userData['email']); ?></div>
                            </div>
                            
                            <div class="profile-info-row">
                                <div class="profile-info-label">Phone Number</div>
                                <div class="profile-info-value">
                                    <?php echo !empty($userData['phone_number']) ? htmlspecialchars($userData['phone_number']) : '<em>Not provided</em>'; ?>
                                </div>
                            </div>
                            
                            <div class="bio-section">
                                <h3>About Me</h3>
                                <div class="bio-content">
                                    <?php if (!empty($userData['bio'])): ?>
                                        <?php echo nl2br(htmlspecialchars($userData['bio'])); ?>
                                    <?php else: ?>
                                        <em>No bio information provided.</em>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
                
                <div id="password" class="tab-content">
                    <div class="profile-header">
                        <h1>Change Password</h1>
                        <p>Update your password to keep your account secure</p>
                    </div>
                    
                    <form id="password-form" method="post" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>">
                        <div class="form-group">
                            <label for="current_password">Current Password</label>
                            <input type="password" id="current_password" name="current_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="new_password">New Password</label>
                            <input type="password" id="new_password" name="new_password" required>
                        </div>
                        
                        <div class="form-group">
                            <label for="confirm_password">Confirm New Password</label>
                            <input type="password" id="confirm_password" name="confirm_password" required>
                        </div>
                        
                        <div class="actions">
                            <button type="submit" name="update_password" class="btn">Update Password</button>
                            <button type="reset" class="btn btn-secondary">Cancel</button>
                        </div>
                    </form>
                </div>
                
                <div id="my-events" class="tab-content">
                    <div class="profile-header">
                        <h1>My Events</h1>
                        <p>Events you're attending or have registered for</p>
                    </div>
                    
                    <?php if (count($events) > 0): ?>
                        <?php foreach ($events as $event): ?>
                            <div class="event-card">
                                <h3><?php echo htmlspecialchars($event['title']); ?></h3>
                                <div class="event-date">
                                    <?php 
                                    $start = new DateTime($event['start_time']);
                                    $end = new DateTime($event['end_time']);
                                    echo $start->format('F j, Y, g:i a') . ' - ' . $end->format('g:i a');
                                    ?>
                                </div>
                                <div class="event-location">
                                    <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?>
                                </div>
                                <p><?php echo nl2br(htmlspecialchars(substr($event['description'], 0, 150))); ?>...</p>
                                <a href="event_page.php?id=<?php echo $event['event_id']; ?>" class="btn">View Details</a>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <p>You haven't registered for any events yet.</p>
                    <?php endif; ?>
                </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Show the selected tab
        function showTab(tabId) {
            // Hide all tabs
            document.querySelectorAll('.tab-content').forEach(tab => {
                tab.classList.remove('active');
            });
            
            // Show the selected tab
            document.getElementById(tabId).classList.add('active');
            
            // Update active state on navigation
            document.querySelectorAll('.profile-nav a').forEach(link => {
                link.classList.remove('active');
            });
            
            document.querySelector(`.profile-nav a[onclick="showTab('${tabId}'); return false;"]`).classList.add('active');
        }
        
        // Handle image preview
        document.getElementById('profile_image')?.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                
                reader.onload = function(e) {
                    const preview = document.getElementById('imagePreview');
                    preview.innerHTML = `
                        <p>New image preview:</p>
                        <img src="${e.target.result}" alt="Profile Preview" style="max-width: 200px; max-height: 200px; margin-top: 10px;">
                    `;
                }
                
                reader.readAsDataURL(file);
            }
        });
        
        // Password validation
        document.getElementById('password-form').addEventListener('submit', function(e) {
            const newPassword = document.getElementById('new_password').value;
            const confirmPassword = document.getElementById('confirm_password').value;
            
            if (newPassword !== confirmPassword) {
                e.preventDefault();
                alert("New passwords don't match!");
            }
        });
    </script>
</body>
</html>