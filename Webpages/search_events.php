
<header class="d-flex flex-wrap justify-content-center py-5 mb-4 head">
    <a href="/" class="d-flex align-items-center mb-3 mb-md-0 me-md-auto link-body-emphasis text-decoration-none">
      <svg class="bi me-2" width="40" height="32"><use xlink:href="#bootstrap"></use></svg>
      <span class="fs-1 logo">Eventify</span>
    </a>
    <ul class="nav nav-pills">
      <li class="nav-item header-buttons"><a href="#" class="nav-link">Events</a></li>
      <li class="nav-item header-buttons"><a href="#" class="nav-link">Blog</a></li>
      <li class="nav-item header-buttons"><a href="#" class="nav-link">About</a></li>
      <li class="nav-item header-buttons"><a href="#" class="nav-link">Contact</a></li>
      <li class="nav-item header-buttons login"><a href="login.php" class="nav-link">Login</a></li>
    </ul>
</header>

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css">
<link rel="stylesheet" href="../style.css">
<div class="container mt-4">
    <div class="d-flex align-items-center justify-content-between py-5">
        <h2 class="fw-bold text-dark ">Upcoming Events</h2>

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
                <option value="5">Saturday</option>
                <option value="5">Sunday</option>
            </select>

            <!-- Event Type Dropdown -->
            <select class="form-select filter-dropdown px-5 select options" aria-label="Event Type">
                <option disabled selected >Event Type</option>
                <option value="1">Conference</option>
                <option value="2">Workshop</option>
                <option value="3">Meetup</option>
            </select>

            <!-- Category Dropdown -->
            <select class="form-select filter-dropdown px-5 select options" aria-label="Category">
                <option disabled selected >Category</option>
                <option value="1">Business</option>
                <option value="2">Technology</option>
                <option value="3">Education</option>
            </select>
        </div>
    </div>
</div>
<div class="album py-5 bg-body-tertiary">
    <div class="container">

      <div class="row row-cols-1 row-cols-sm-2 row-cols-md-3 g-4">
        <div class="col">
            <div class="card shadow-sm hover-effect rounded-4 overflow-hidden">
                <svg class="bd-placeholder-img card-img-top rounded-top-4" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="#55595c"></rect>
                    <text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail</text>
                </svg>
                <div class="card-body">
                    <div class="row">
                        <!-- Column 1 (2/12) -->
                        <div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">
                            <b class="bi bi-info-circle fs-5 date">FEB</b>
                            <span class="fs-1 fw-bold">20</span> <!-- Date below month -->
                        </div>
            
                        <!-- Column 2 (10/12) -->
                        <div class="col-10">
                            <p><b>Title</b></p>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="col">
            <div class="card shadow-sm hover-effect rounded-4 overflow-hidden">
                <svg class="bd-placeholder-img card-img-top rounded-top-4" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="#55595c"></rect>
                    <text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail</text>
                </svg>
                <div class="card-body">
                    <div class="row">
                        <!-- Column 1 (2/12) -->
                        <div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">
                            <b class="bi bi-info-circle fs-5 date">FEB</b>
                            <span class="fs-1 fw-bold">20</span> <!-- Date below month -->
                        </div>
            
                        <!-- Column 2 (10/12) -->
                        <div class="col-10">
                            <p><b>Title</b></p>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="col">
            <div class="card shadow-sm hover-effect rounded-4 overflow-hidden">
                <svg class="bd-placeholder-img card-img-top rounded-top-4" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="#55595c"></rect>
                    <text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail</text>
                </svg>
                <div class="card-body">
                    <div class="row">
                        <!-- Column 1 (2/12) -->
                        <div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">
                            <b class="bi bi-info-circle fs-5 date">FEB</b>
                            <span class="fs-1 fw-bold">20</span> <!-- Date below month -->
                        </div>
            
                        <!-- Column 2 (10/12) -->
                        <div class="col-10">
                            <p><b>Title</b></p>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>

        <div class="col">
            <div class="card shadow-sm hover-effect rounded-4 overflow-hidden">
                <svg class="bd-placeholder-img card-img-top rounded-top-4" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="#55595c"></rect>
                    <text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail</text>
                </svg>
                <div class="card-body">
                    <div class="row">
                        <!-- Column 1 (2/12) -->
                        <div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">
                            <b class="bi bi-info-circle fs-5 date">FEB</b>
                            <span class="fs-1 fw-bold">20</span> <!-- Date below month -->
                        </div>
            
                        <!-- Column 2 (10/12) -->
                        <div class="col-10">
                            <p><b>Title</b></p>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="col">
            <div class="card shadow-sm hover-effect rounded-4 overflow-hidden">
                <svg class="bd-placeholder-img card-img-top rounded-top-4" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="#55595c"></rect>
                    <text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail</text>
                </svg>
                <div class="card-body">
                    <div class="row">
                        <!-- Column 1 (2/12) -->
                        <div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">
                            <b class="bi bi-info-circle fs-5 date">FEB</b>
                            <span class="fs-1 fw-bold">20</span> <!-- Date below month -->
                        </div>
            
                        <!-- Column 2 (10/12) -->
                        <div class="col-10">
                            <p><b>Title</b></p>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="col">
            <div class="card shadow-sm hover-effect rounded-4 overflow-hidden">
                <svg class="bd-placeholder-img card-img-top rounded-top-4" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="#55595c"></rect>
                    <text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail</text>
                </svg>
                <div class="card-body">
                    <div class="row">
                        <!-- Column 1 (2/12) -->
                        <div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">
                            <b class="bi bi-info-circle fs-5 date">FEB</b>
                            <span class="fs-1 fw-bold">20</span> <!-- Date below month -->
                        </div>
            
                        <!-- Column 2 (10/12) -->
                        <div class="col-10">
                            <p><b>Title</b></p>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>

        <div class="col">
            <div class="card shadow-sm hover-effect rounded-4 overflow-hidden">
                <svg class="bd-placeholder-img card-img-top rounded-top-4" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="#55595c"></rect>
                    <text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail</text>
                </svg>
                <div class="card-body">
                    <div class="row">
                        <!-- Column 1 (2/12) -->
                        <div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">
                            <b class="bi bi-info-circle fs-5 date">FEB</b>
                            <span class="fs-1 fw-bold">20</span> <!-- Date below month -->
                        </div>
            
                        <!-- Column 2 (10/12) -->
                        <div class="col-10">
                            <p><b>Title</b></p>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="col">
            <div class="card shadow-sm hover-effect rounded-4 overflow-hidden">
                <svg class="bd-placeholder-img card-img-top rounded-top-4" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="#55595c"></rect>
                    <text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail</text>
                </svg>
                <div class="card-body">
                    <div class="row">
                        <!-- Column 1 (2/12) -->
                        <div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">
                            <b class="bi bi-info-circle fs-5 date">FEB</b>
                            <span class="fs-1 fw-bold">20</span> <!-- Date below month -->
                        </div>
            
                        <!-- Column 2 (10/12) -->
                        <div class="col-10">
                            <p><b>Title</b></p>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
        <div class="col">
            <div class="card shadow-sm hover-effect rounded-4 overflow-hidden">
                <svg class="bd-placeholder-img card-img-top rounded-top-4" width="100%" height="225" xmlns="http://www.w3.org/2000/svg" role="img" aria-label="Placeholder: Thumbnail" preserveAspectRatio="xMidYMid slice" focusable="false">
                    <title>Placeholder</title>
                    <rect width="100%" height="100%" fill="#55595c"></rect>
                    <text x="50%" y="50%" fill="#eceeef" dy=".3em">Thumbnail</text>
                </svg>
                <div class="card-body">
                    <div class="row">
                        <!-- Column 1 (2/12) -->
                        <div class="col-2 d-flex flex-column flex-wrap align-items-center text-center">
                            <b class="bi bi-info-circle fs-5 date">FEB</b>
                            <span class="fs-1 fw-bold">20</span> <!-- Date below month -->
                        </div>
            
                        <!-- Column 2 (10/12) -->
                        <div class="col-10">
                            <p><b>Title</b></p>
                            <p class="card-text">
                                This is a wider card with supporting text below as a natural lead-in to additional content. This content is a little bit longer.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
        </div>
                  
    </div>
     
    </div>
  </div>
