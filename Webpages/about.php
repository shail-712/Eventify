<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Eventify</title>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;800&family=Poppins:wght@300;400;600&display=swap" rel="stylesheet">
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
            font-family: "Anton SC", serif;
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
        .main-header {
            padding: 60px 20px 30px;
            text-align: center;
            animation: fadeIn 1s ease-in;
        }
        .main-header h1 {
            font-size: 36px;
            font-weight: 800;
            color: #5e2ced;
        }
        .main-header p {
            font-size: 16px;
            color: #777;
            margin-top: 8px;
        }
        .section {
            max-width: 900px;
            margin: 40px auto;
            background-color: #fff;
            padding: 40px;
            border-radius: 12px;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
            transition: transform 0.3s ease;
        }
        .section:hover {
            transform: scale(1.01);
        }
        .section h2 {
            color: #5e2ced;
            font-size: 24px;
            font-weight: 700;
            margin-bottom: 15px;
        }
        .section p {
            font-size: 15px;
            color: #444;
            line-height: 1.7;
        }
        .contact {
            background-color: #5e2ced;
            color: white;
            padding: 30px;
            border-radius: 12px;
            margin-top: 20px;
            text-align: center;
        }
        .contact h3 {
            margin-bottom: 10px;
        }
        .contact p {
            margin: 4px 0;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .scroll-top {
            position: fixed;
            bottom: 20px;
            right: 20px;
            background-color: #5e2ced;
            color: white;
            padding: 10px 15px;
            border: none;
            border-radius: 50%;
            font-size: 18px;
            cursor: pointer;
            display: none;
            z-index: 1000;
        }
        .scroll-top.show {
            display: block;
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

    <header class="main-header">
        <h1>About Us</h1>
        <p>Learn more about who we are and why you should choose Eventify.</p>
    </header>

    <div class="section" id="about">
        <h2>Who We Are</h2>
        <p>
            Eventify is a passionate team of event lovers, technologists, and community builders
            who believe in the power of live experiences. Whether it's a college fest, a tech
            conference, a music concert, or a local workshop — we make it easy for people to discover
            and attend events that matter to them.
        </p>
    </div>

    <div class="section">
        <h2>Why Choose Us</h2>
        <p>
            We offer a seamless platform for both event-goers and organizers. With intuitive search,
            trending insights, ticketing tools, and community support, Eventify stands out as your trusted
            companion for unforgettable experiences.
        </p>
        <p>
            Our platform is secure, fast, and user-friendly — built with modern tech and real-world feedback.
            Whether you're hosting a meetup or attending your first hackathon, we've got your back.
        </p>
    </div>

    <!-- Footer with Contact Details -->
<footer style="background-color: #5e2ced; color: white; padding: 30px; margin-top: 60px; text-align: center;">
  <h3 style="margin-bottom: 10px;">Contact Us</h3>
  <p>Email: support@eventify.com</p>
  <p>Phone: +91-98765-43210</p>
  <p>Address: Ghatkopar, Mumbai, India</p>
</footer>

    <button class="scroll-top" id="scrollTopBtn">&#8679;</button>

    <script>
        // Scroll-to-top button
        const scrollBtn = document.getElementById("scrollTopBtn");
        window.onscroll = function () {
            if (document.body.scrollTop > 200 || document.documentElement.scrollTop > 200) {
                scrollBtn.classList.add("show");
            } else {
                scrollBtn.classList.remove("show");
            }
        };
        scrollBtn.onclick = function () {
            window.scrollTo({ top: 0, behavior: 'smooth' });
        };
    </script>
</body>
</html>