<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{event.title}} - Event Details</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/css/bootstrap.min.css" rel="stylesheet">
    <!-- Bootstrap Icons -->
    <link href="https://cdnjs.cloudflare.com/ajax/libs/bootstrap-icons/1.10.5/font/bootstrap-icons.min.css" rel="stylesheet">
    <style>
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
    </style>
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-dark">
        <div class="container">
            <a class="navbar-brand" href="#">Event Platform</a>
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            <div class="collapse navbar-collapse" id="navbarNav">
                <ul class="navbar-nav ms-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="#">Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Events</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Categories</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="#">Login</a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Hero Section -->
    <section class="hero-section text-center">
        <div class="container">
            <span class="category-badge">{{event.category_name}}</span>
            <h1 class="display-4 fw-bold">{{event.title}}</h1>
            <p class="lead">Organized by {{organizer.name}}</p>
        </div>
    </section>

    <!-- Main Content -->
    <div class="container py-5">
        <!-- Event Details -->
        <div class="row mb-5">
            <div class="col-lg-8">
                <div class="card shadow-sm mb-4">
                    <div class="card-body">
                        <div class="event-location">
                            <i class="bi bi-geo-alt-fill"></i>
                            <span>{{event.location}}</span>
                        </div>
                        
                        <h2 class="h4 mb-4">About this event</h2>
                        <div class="mb-4">
                            {{event.description}}
                        </div>
                        
                        <h2 class="h4 mb-3">Event Features</h2>
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <div class="card card-feature h-100">
                                    <div class="card-body text-center">
                                        <i class="bi bi-people text-primary mb-3" style="font-size: 2rem;"></i>
                                        <p class="card-text">Network with like-minded individuals</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-feature h-100">
                                    <div class="card-body text-center">
                                        <i class="bi bi-camera-video text-primary mb-3" style="font-size: 2rem;"></i>
                                        <p class="card-text">Session recording available afterwards</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-feature h-100">
                                    <div class="card-body text-center">
                                        <i class="bi bi-device-hdd text-primary mb-3" style="font-size: 2rem;"></i>
                                        <p class="card-text">Access on mobile and web</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="card card-feature h-100">
                                    <div class="card-body text-center">
                                        <i class="bi bi-chat-dots text-primary mb-3" style="font-size: 2rem;"></i>
                                        <p class="card-text">Interactive Q&A sessions</p>
                                    </div>
                                </div>
                            </div>
                        </div>
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
                                    <p class="mb-0">{{formatDate(event.start_time)}}</p>
                                    <small class="text-muted">{{formatTime(event.start_time)}}</small>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="event-meta-item p-3 text-center">
                                    <h5 class="fw-bold text-primary mb-2">Ends</h5>
                                    <p class="mb-0">{{formatDate(event.end_time)}}</p>
                                    <small class="text-muted">{{formatTime(event.end_time)}}</small>
                                </div>
                            </div>
                        </div>
                        
                        <div class="text-center mb-4">
                            <h3 class="display-6 fw-bold text-primary">{{formatPrice(event.ticket_price)}}</h3>
                        </div>
                        
                        <div class="d-grid mb-3">
                            <button class="btn btn-lg primary-btn text-white" data-event-id="{{event.event_id}}">Register Now</button>
                        </div>
                        
                        <div class="text-center">
                            <p class="text-muted small mb-0">{{getRegistrationCount()}} people already registered</p>
                        </div>
                    </div>
                </div>
                
                <!-- Organizer Info -->
                <div class="card shadow-sm">
                    <div class="card-body">
                        <h4 class="card-title h5 mb-3">Organizer</h4>
                        <div class="d-flex align-items-center">
                            <img src="/api/placeholder/60/60" alt="{{organizer.name}}" class="rounded-circle me-3" style="width: 60px; height: 60px;">
                            <div>
                                <h5 class="mb-1">{{organizer.name}}</h5>
                                <p class="text-muted mb-0 small">Event Organizer</p>
                            </div>
                        </div>
                        <hr>
                        <div class="d-grid">
                            <button class="btn btn-outline-primary btn-sm">Contact Organizer</button>
                        </div>
                    </div>
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
                    
                    <div class="text-center">
                        <p>Don't see the answer you're looking for? Ask a question</p>
                        <div class="form-floating mb-3">
                            <textarea class="form-control" id="questionTextarea" style="height: 100px"></textarea>
                            <label for="questionTextarea">Your question</label>
                        </div>
                        <button class="btn primary-btn text-white">Submit Question</button>
                    </div>
                </div>
            </div>
        </section>
        
        <!-- Related Events -->
        <section class="mb-5">
            <h2 class="h3 mb-4">Similar Events</h2>
            <div class="row row-cols-1 row-cols-md-2 row-cols-lg-3 g-4">
                <!-- These would be populated dynamically based on category_id -->
                {{#each relatedEvents}}
                <div class="col">
                    <div class="card h-100 shadow-sm">
                        <img src="/api/placeholder/400/200" class="card-img-top" alt="Event image">
                        <div class="card-body">
                            <span class="category-badge">{{this.category_name}}</span>
                            <h5 class="card-title">{{this.title}}</h5>
                            <p class="card-text">{{truncateText(this.description, 100)}}</p>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted">{{formatDate(this.start_time)}}</small>
                                <span class="fw-bold">{{formatPrice(this.ticket_price)}}</span>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent border-top-0">
                            <a href="/events/{{this.event_id}}" class="btn btn-outline-primary w-100">View Details</a>
                        </div>
                    </div>
                </div>
                {{/each}}
            </div>
        </section>
    </div>
    
    <!-- Footer -->
    <footer class="bg-dark text-white py-4">
        <div class="container">
            <div class="row">
                <div class="col-md-6 mb-3 mb-md-0">
                    <h5>Event Platform</h5>
                    <p class="text-muted">Find and register for events that match your interests</p>
                </div>
                <div class="col-md-3 mb-3 mb-md-0">
                    <h5>Quick Links</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item"><a href="#" class="nav-link text-muted p-0 mb-2">Home</a></li>
                        <li class="nav-item"><a href="#" class="nav-link text-muted p-0 mb-2">Events</a></li>
                        <li class="nav-item"><a href="#" class="nav-link text-muted p-0 mb-2">Categories</a></li>
                        <li class="nav-item"><a href="#" class="nav-link text-muted p-0">Contact</a></li>
                    </ul>
                </div>
                <div class="col-md-3">
                    <h5>Contact</h5>
                    <ul class="nav flex-column">
                        <li class="nav-item text-muted mb-2"><i class="bi bi-envelope me-2"></i>info@eventplatform.com</li>
                        <li class="nav-item text-muted"><i class="bi bi-telephone me-2"></i>+1 234 567 8900</li>
                    </ul>
                </div>
            </div>
            <hr class="my-4">
            <div class="text-center text-muted">
                <p>&copy; 2025 Event Platform. All rights reserved.</p>
            </div>
        </div>
    </footer>

    <!-- Bootstrap JS Bundle with Popper -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/bootstrap/5.3.0/js/bootstrap.bundle.min.js"></script>
    
    <!-- Template Info: This is a placeholder for server-side template logic -->
    <!-- 
    Server-side functions needed:
    - formatDate(date): Format date as DD Month YYYY
    - formatTime(time): Format time as HH:MM AM/PM
    - formatPrice(price): Format price with currency symbol
    - truncateText(text, length): Truncate text to specified length with ellipsis
    - getRegistrationCount(): Get count of registrations for this event
    
    Data context needed:
    - event: Full event details from Events table
    - organizer: User details of the organizer from Users table
    - relatedEvents: Array of related events based on category
    -->
</body>
</html>