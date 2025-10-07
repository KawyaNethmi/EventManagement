<?php
include 'includes/header.php';
include 'includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$success_message = '';
$error_message = '';

if($_SERVER['REQUEST_METHOD'] == 'POST') {
    $title = $_POST['title'];
    $description = $_POST['description'];
    $event_date = $_POST['event_date'];
    $event_time = $_POST['event_time'];
    $location = $_POST['location'];
    $max_participants = $_POST['max_participants'] ?: NULL;
    $event_type = $_POST['event_type'];
    $price = $_POST['price'] ?: 0.00;
    $category_id = $_POST['category_id'] ?: NULL; // Make category optional
    $featured = isset($_POST['featured']) ? 1 : 0;
    $created_by = $_SESSION['user_id'];
    
    try {
        // Check if categories table exists, if not, don't include category_id
        $table_exists = $pdo->query("SHOW TABLES LIKE 'categories'")->rowCount() > 0;
        
        if ($table_exists && $category_id) {
            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location, max_participants, event_type, price, category_id, featured, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $max_participants, $event_type, $price, $category_id, $featured, $created_by]);
        } else {
            // Insert without category_id if table doesn't exist
            $stmt = $pdo->prepare("INSERT INTO events (title, description, event_date, event_time, location, max_participants, event_type, price, featured, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $event_date, $event_time, $location, $max_participants, $event_type, $price, $featured, $created_by]);
        }
        
        $event_id = $pdo->lastInsertId();
        $success_message = 'Event created successfully! <a href="event_detail.php?id=' . $event_id . '" class="alert-link">View Event</a>';
        
    } catch(PDOException $e) {
        $error_message = 'Error creating event: ' . $e->getMessage();
    }
}

// Get categories for dropdown (only if table exists)
$categories = [];
$categories_table_exists = $pdo->query("SHOW TABLES LIKE 'categories'")->rowCount() > 0;

if ($categories_table_exists) {
    $categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();
}
?>

<div class="container">
    <!-- Header Section -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex align-items-center mb-4">
                <div class="bg-primary rounded-circle p-3 me-3">
                    <i class="fas fa-calendar-plus fa-2x text-white"></i>
                </div>
                <div>
                    <h1 class="mb-1">Create New Event</h1>
                    <p class="text-muted mb-0">Fill in the details below to create your amazing event</p>
                </div>
            </div>
            
            <!-- Progress Steps -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="progress-steps d-flex justify-content-between position-relative">
                        <div class="step active">
                            <div class="step-circle">1</div>
                            <span class="step-label">Basic Info</span>
                        </div>
                        <div class="step">
                            <div class="step-circle">2</div>
                            <span class="step-label">Details</span>
                        </div>
                        <div class="step">
                            <div class="step-circle">3</div>
                            <span class="step-label">Settings</span>
                        </div>
                        <div class="step">
                            <div class="step-circle">4</div>
                            <span class="step-label">Review</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Alert Messages-->
    <?php if($success_message): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $success_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if($error_message): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $error_message; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if(!$categories_table_exists): ?>
        <div class="alert alert-warning alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Categories table not found. Events will be created without categories. 
            <a href="setup_database.php" class="alert-link">Run database setup</a> to enable categories.
        </div>
    <?php endif; ?>

    <div class="row justify-content-center">
        <div class="col-lg-10">
            <div class="card shadow-lg border-0">
                <div class="card-header bg-primary text-white py-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0"><i class="fas fa-magic me-2"></i>Create Your Event</h4>
                        <span class="badge bg-light text-primary">Step 1 of 4</span>
                    </div>
                </div>
                
                <div class="card-body p-5">
                    <form method="POST" action="" id="eventForm">
                        <!-- Basic Information Section -->
                        <div class="form-section">
                            <h5 class="section-title mb-4">
                                <i class="fas fa-info-circle text-primary me-2"></i>
                                Basic Information
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-8">
                                    <div class="mb-4">
                                        <label for="title" class="form-label fw-semibold">Event Title *</label>
                                        <input type="text" class="form-control form-control-lg" id="title" name="title" 
                                               placeholder="Enter a catchy event title" required
                                               oninput="updateTitlePreview(this.value)">
                                        <div class="form-text">Make it descriptive and engaging</div>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-4">
                                        <label for="event_type" class="form-label fw-semibold">Event Type *</label>
                                        <select class="form-select" id="event_type" name="event_type" required>
                                            <option value="">Select Type</option>
                                            <option value="conference">Conference</option>
                                            <option value="workshop">Workshop</option>
                                            <option value="seminar">Seminar</option>
                                            <option value="networking">Networking</option>
                                            <option value="social">Social Gathering</option>
                                            <option value="sports">Sports Event</option>
                                            <option value="charity">Charity Event</option>
                                            <option value="concert">Concert</option>
                                            <option value="exhibition">Exhibition</option>
                                            <option value="other">Other</option>
                                        </select>
                                    </div>
                                </div>
                            </div>

                            <div class="mb-4">
                                <label for="description" class="form-label fw-semibold">Event Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" 
                                          placeholder="Describe your event in detail. What will attendees learn or experience?"
                                          oninput="updateDescriptionPreview(this.value)" required></textarea>
                                <div class="form-text d-flex justify-content-between">
                                    <span>Minimum 50 characters recommended</span>
                                    <span id="charCount">0 characters</span>
                                </div>
                            </div>

                            <div class="row">
                                <?php if($categories_table_exists && !empty($categories)): ?>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="category_id" class="form-label fw-semibold">Category</label>
                                        <select class="form-select" id="category_id" name="category_id">
                                            <option value="">Select Category (Optional)</option>
                                            <?php foreach($categories as $category): ?>
                                                <option value="<?php echo $category['id']; ?>">
                                                    <?php echo htmlspecialchars($category['name']); ?>
                                                </option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                <?php else: ?>
                                <div class="col-md-12">
                                <?php endif; ?>
                                    <div class="mb-4">
                                        <label for="price" class="form-label fw-semibold">Ticket Price ($)</label>
                                        <div class="input-group">
                                            <span class="input-group-text">$</span>
                                            <input type="number" class="form-control" id="price" name="price" 
                                                   min="0" step="0.01" placeholder="0.00" value="0.00">
                                        </div>
                                        <div class="form-text">Leave as 0.00 for free events</div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Date & Time Section -->
                        <div class="form-section mt-5">
                            <h5 class="section-title mb-4">
                                <i class="fas fa-clock text-primary me-2"></i>
                                Date & Time
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="event_date" class="form-label fw-semibold">Event Date *</label>
                                        <input type="date" class="form-control" id="event_date" name="event_date" 
                                               min="<?php echo date('Y-m-d'); ?>" required
                                               onchange="validateEventDate()">
                                        <div class="form-text" id="dateFeedback"></div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="event_time" class="form-label fw-semibold">Event Time *</label>
                                        <input type="time" class="form-control" id="event_time" name="event_time" required>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Location Section -->
                        <div class="form-section mt-5">
                            <h5 class="section-title mb-4">
                                <i class="fas fa-map-marker-alt text-primary me-2"></i>
                                Location
                            </h5>
                            
                            <div class="mb-4">
                                <label for="location" class="form-label fw-semibold">Event Location *</label>
                                <input type="text" class="form-control" id="location" name="location" 
                                       placeholder="Enter venue name or address" required>
                                <div class="form-text">You can add detailed address later</div>
                            </div>
                        </div>

                        <!-- Capacity & Settings -->
                        <div class="form-section mt-5">
                            <h5 class="section-title mb-4">
                                <i class="fas fa-users text-primary me-2"></i>
                                Capacity & Settings
                            </h5>
                            
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label for="max_participants" class="form-label fw-semibold">Maximum Participants</label>
                                        <input type="number" class="form-control" id="max_participants" name="max_participants" 
                                               min="1" placeholder="Leave empty for unlimited">
                                        <div class="form-text">Set a limit or leave unlimited</div>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-4">
                                        <label class="form-label fw-semibold">Additional Options</label>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" id="featured" name="featured">
                                            <label class="form-check-label" for="featured">
                                                Feature this event on homepage
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Event Preview -->
                        <div class="form-section mt-5">
                            <h5 class="section-title mb-4">
                                <i class="fas fa-eye text-primary me-2"></i>
                                Event Preview
                            </h5>
                            
                            <div class="card bg-light">
                                <div class="card-body">
                                    <div id="eventPreview">
                                        <h5 class="text-muted">Your event preview will appear here</h5>
                                        <p class="text-muted mb-0">Start filling out the form to see a preview</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Form Actions -->
                        <div class="form-section mt-5">
                            <div class="d-flex justify-content-between align-items-center">
                                <a href="events.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary btn-lg px-5">
                                    <i class="fas fa-calendar-plus me-2"></i>Create Event
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Templates Sections -->
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-rocket me-2"></i>Quick Start Templates</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <div class="card template-card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-briefcase fa-2x text-primary mb-3"></i>
                                    <h6>Business Conference</h6>
                                    <p class="text-muted small">Professional networking event</p>
                                    <button class="btn btn-outline-primary btn-sm" onclick="loadTemplate('conference')">
                                        Use Template
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card template-card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-chalkboard-teacher fa-2x text-success mb-3"></i>
                                    <h6>Workshop</h6>
                                    <p class="text-muted small">Educational training session</p>
                                    <button class="btn btn-outline-success btn-sm" onclick="loadTemplate('workshop')">
                                        Use Template
                                    </button>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4 mb-3">
                            <div class="card template-card h-100">
                                <div class="card-body text-center">
                                    <i class="fas fa-users fa-2x text-warning mb-3"></i>
                                    <h6>Social Gathering</h6>
                                    <p class="text-muted small">Casual meetup event</p>
                                    <button class="btn btn-outline-warning btn-sm" onclick="loadTemplate('social')">
                                        Use Template
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .progress-steps {
        max-width: 600px;
        margin: 0 auto;
    }
    
    .progress-steps::before {
        content: '';
        position: absolute;
        top: 25px;
        left: 0;
        right: 0;
        height: 3px;
        background: #e9ecef;
        z-index: 1;
    }
    
    .step {
        text-align: center;
        position: relative;
        z-index: 2;
    }
    
    .step-circle {
        width: 50px;
        height: 50px;
        border-radius: 50%;
        background: #e9ecef;
        color: #6c757d;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-weight: bold;
        border: 3px solid #fff;
    }
    
    .step.active .step-circle {
        background: #667eea;
        color: white;
    }
    
    .step-label {
        font-size: 0.9rem;
        color: #6c757d;
        font-weight: 500;
    }
    
    .step.active .step-label {
        color: #667eea;
        font-weight: 600;
    }
    
    .form-section {
        padding: 2rem;
        border-radius: 15px;
        background: #f8f9fa;
        margin-bottom: 1.5rem;
    }
    
    .section-title {
        color: #2c3e50;
        border-bottom: 2px solid #667eea;
        padding-bottom: 0.5rem;
    }
    
    .template-card {
        transition: all 0.3s ease;
        border: 2px solid transparent;
    }
    
    .template-card:hover {
        transform: translateY(-5px);
        border-color: #667eea;
        box-shadow: 0 10px 25px rgba(102, 126, 234, 0.15);
    }
    
    .form-control:focus, .form-select:focus {
        border-color: #667eea;
        box-shadow: 0 0 0 0.2rem rgba(102, 126, 234, 0.25);
    }
</style>

<script>
    // Character count for description
    document.getElementById('description').addEventListener('input', function() {
        document.getElementById('charCount').textContent = this.value.length + ' characters';
    });

    // Event date validation
    function validateEventDate() {
        const eventDate = new Date(document.getElementById('event_date').value);
        const today = new Date();
        today.setHours(0, 0, 0, 0);
        
        const feedback = document.getElementById('dateFeedback');
        
        if (eventDate < today) {
            feedback.innerHTML = '<span class="text-danger"><i class="fas fa-exclamation-triangle me-1"></i>Event date cannot be in the past</span>';
            return false;
        } else {
            const diffTime = eventDate - today;
            const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));
            feedback.innerHTML = `<span class="text-success"><i class="fas fa-check me-1"></i>Event is in ${diffDays} days</span>`;
            return true;
        }
    }

    // Update event preview
    function updateTitlePreview(title) {
        const preview = document.getElementById('eventPreview');
        if (title) {
            preview.innerHTML = `
                <h4 class="text-primary">${title}</h4>
                <p class="text-muted">Fill in more details to see complete preview</p>
            `;
        }
    }

    function updateDescriptionPreview(description) {
        const preview = document.getElementById('eventPreview');
        if (description && description.length > 10) {
            preview.innerHTML = `
                <h4 class="text-primary">${document.getElementById('title').value || 'Your Event'}</h4>
                <p class="text-muted">${description.substring(0, 150)}${description.length > 150 ? '...' : ''}</p>
                <div class="mt-3">
                    <small class="text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        ${document.getElementById('event_date').value || 'Select date'}
                    </small>
                    <small class="text-muted ms-3">
                        <i class="fas fa-map-marker-alt me-1"></i>
                        ${document.getElementById('location').value || 'Select location'}
                    </small>
                </div>
            `;
        }
    }

    // Template loading
    function loadTemplate(type) {
        const templates = {
            conference: {
                title: 'Business Innovation Conference 2024',
                description: 'Join industry leaders and innovators for a day of insightful talks, networking opportunities, and cutting-edge business strategies. Perfect for entrepreneurs, executives, and professionals looking to stay ahead in their industry.',
                event_type: 'conference',
                price: '199.00'
            },
            workshop: {
                title: 'Digital Marketing Mastery Workshop',
                description: 'Hands-on workshop covering the latest digital marketing strategies, tools, and techniques. Learn from experts and gain practical skills that you can implement immediately in your business or career.',
                event_type: 'workshop',
                price: '99.00'
            },
            social: {
                title: 'Community Networking Mixer',
                description: 'A casual social gathering for professionals to connect, share ideas, and build meaningful relationships in a relaxed atmosphere. Great for expanding your network and meeting like-minded individuals.',
                event_type: 'networking',
                price: '0.00'
            }
        };

        const template = templates[type];
        if (template) {
            document.getElementById('title').value = template.title;
            document.getElementById('description').value = template.description;
            document.getElementById('event_type').value = template.event_type;
            document.getElementById('price').value = template.price;
            
            // Update preview
            updateTitlePreview(template.title);
            updateDescriptionPreview(template.description);
            
            // Show success message
            alert('Template loaded! Customize the details as needed.');
        }
    }

    // Form validation
    document.getElementById('eventForm').addEventListener('submit', function(e) {
        const title = document.getElementById('title').value;
        const description = document.getElementById('description').value;
        
        if (description.length < 50) {
            e.preventDefault();
            alert('Please provide a more detailed description (at least 50 characters).');
            document.getElementById('description').focus();
            return;
        }
        
        if (!validateEventDate()) {
            e.preventDefault();
            return;
        }
    });

    // Initialize date field with tomorrow's date as default
    document.addEventListener('DOMContentLoaded', function() {
        const tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);
        document.getElementById('event_date').value = tomorrow.toISOString().split('T')[0];
        
        // Set default time to 18:00 (6 PM)
        document.getElementById('event_time').value = '18:00';
    });
</script>

<?php include 'includes/footer.php'; ?>