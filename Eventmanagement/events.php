<?php
include 'includes/header.php';
include 'includes/db.php';

// Get filter parameters
$category_filter = isset($_GET['category']) ? intval($_GET['category']) : '';
$type_filter = isset($_GET['type']) ? $_GET['type'] : '';
$date_filter = isset($_GET['date']) ? $_GET['date'] : '';
$search_query = isset($_GET['search']) ? $_GET['search'] : '';

// Build query with filters
$query = "
    SELECT e.*, u.username as creator, u.full_name as organizer_name,
           COUNT(er.id) as registration_count,
           c.name as category_name,
           c.icon as category_icon
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    LEFT JOIN event_registrations er ON e.id = er.event_id 
    LEFT JOIN categories c ON e.category_id = c.id
    WHERE 1=1
";

$params = [];

// Apply search filter
if (!empty($search_query)) {
    $query .= " AND (e.title LIKE ? OR e.description LIKE ? OR e.location LIKE ?)";
    $search_term = "%$search_query%";
    $params[] = $search_term;
    $params[] = $search_term;
    $params[] = $search_term;
}

// Apply category filter
if (!empty($category_filter)) {
    $query .= " AND e.category_id = ?";
    $params[] = $category_filter;
}

// Apply type filter
if (!empty($type_filter)) {
    $query .= " AND e.event_type = ?";
    $params[] = $type_filter;
}

// Apply date filter
if (!empty($date_filter)) {
    if ($date_filter === 'today') {
        $query .= " AND e.event_date = CURDATE()";
    } elseif ($date_filter === 'week') {
        $query .= " AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 7 DAY)";
    } elseif ($date_filter === 'month') {
        $query .= " AND e.event_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY)";
    } elseif ($date_filter === 'past') {
        $query .= " AND e.event_date < CURDATE()";
    }
}

$query .= " GROUP BY e.id ORDER BY e.event_date ASC, e.event_time ASC";

// Prepare and execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$events = $stmt->fetchAll();

// Get categories for filter dropdown
$categories = $pdo->query("SELECT * FROM categories ORDER BY name")->fetchAll();

// Get event types for filter
$event_types = $pdo->query("SELECT DISTINCT event_type FROM events WHERE event_type IS NOT NULL ORDER BY event_type")->fetchAll();
?>

<div class="container">
    <!-- Page Header -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h1 class="display-5 fw-bold text-primary mb-2">Discover Events</h1>
                    <p class="lead text-muted">Find and join amazing events happening around you</p>
                </div>
                <div class="text-end">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <a href="create_event.php" class="btn btn-primary btn-lg">
                            <i class="fas fa-plus me-2"></i>Create Event
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Search and Filters Section -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow-sm border-0">
                <div class="card-body p-4">
                    <form method="GET" action="" id="eventsFilterForm">
                        <div class="row g-3">
                            <!-- Search Bar -->
                            <div class="col-lg-4">
                                <div class="input-group">
                                    <span class="input-group-text bg-light border-end-0">
                                        <i class="fas fa-search text-muted"></i>
                                    </span>
                                    <input type="text" class="form-control border-start-0" name="search" 
                                           placeholder="Search events..." value="<?php echo htmlspecialchars($search_query); ?>">
                                </div>
                            </div>
                            
                            <!-- Category Filter -->
                            <div class="col-lg-2">
                                <select class="form-select" name="category">
                                    <option value="">All Categories</option>
                                    <?php foreach($categories as $category): ?>
                                        <option value="<?php echo $category['id']; ?>" 
                                            <?php echo $category_filter == $category['id'] ? 'selected' : ''; ?>>
                                            <?php echo htmlspecialchars($category['name']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Type Filter -->
                            <div class="col-lg-2">
                                <select class="form-select" name="type">
                                    <option value="">All Types</option>
                                    <?php foreach($event_types as $type): ?>
                                        <option value="<?php echo $type['event_type']; ?>" 
                                            <?php echo $type_filter == $type['event_type'] ? 'selected' : ''; ?>>
                                            <?php echo ucfirst($type['event_type']); ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <!-- Date Filter -->
                            <div class="col-lg-2">
                                <select class="form-select" name="date">
                                    <option value="">All Dates</option>
                                    <option value="today" <?php echo $date_filter == 'today' ? 'selected' : ''; ?>>Today</option>
                                    <option value="week" <?php echo $date_filter == 'week' ? 'selected' : ''; ?>>This Week</option>
                                    <option value="month" <?php echo $date_filter == 'month' ? 'selected' : ''; ?>>This Month</option>
                                    <option value="past" <?php echo $date_filter == 'past' ? 'selected' : ''; ?>>Past Events</option>
                                </select>
                            </div>
                            
                            <!-- Filter Buttons -->
                            <div class="col-lg-2">
                                <div class="d-grid gap-2 d-md-flex">
                                    <button type="submit" class="btn btn-primary flex-fill">
                                        <i class="fas fa-filter me-2"></i>Filter
                                    </button>
                                    <a href="events.php" class="btn btn-outline-secondary">
                                        <i class="fas fa-redo"></i>
                                    </a>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Results Summary -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h5 class="mb-0">
                        <?php echo count($events); ?> event<?php echo count($events) !== 1 ? 's' : ''; ?> found
                        <?php if(!empty($search_query)): ?>
                            for "<?php echo htmlspecialchars($search_query); ?>"
                        <?php endif; ?>
                    </h5>
                </div>
                <div class="d-flex align-items-center">
                    <span class="text-muted me-3">View:</span>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary active" id="gridViewBtn">
                            <i class="fas fa-th"></i>
                        </button>
                        <button type="button" class="btn btn-outline-primary" id="listViewBtn">
                            <i class="fas fa-list"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Events Grid -->
    <div class="row" id="eventsGrid">
        <?php if(empty($events)): ?>
            <div class="col-12">
                <div class="card text-center py-5">
                    <div class="card-body">
                        <i class="fas fa-calendar-times fa-4x text-muted mb-4"></i>
                        <h4 class="text-muted">No events found</h4>
                        <p class="text-muted mb-4">Try adjusting your search criteria or create a new event.</p>
                        <?php if(isset($_SESSION['user_id'])): ?>
                            <a href="create_event.php" class="btn btn-primary">
                                <i class="fas fa-plus me-2"></i>Create Your First Event
                            </a>
                        <?php else: ?>
                            <a href="register.php" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Join to Create Events
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        <?php else: ?>
            <?php foreach($events as $event): ?>
                <?php
                // Determine event status
                $event_date = strtotime($event['event_date']);
                $today = strtotime('today');
                $status_class = '';
                $status_text = '';
                
                if($event_date < $today) {
                    $status_class = 'past';
                    $status_text = 'Past Event';
                } elseif($event_date == $today) {
                    $status_class = 'today';
                    $status_text = 'Today';
                } else {
                    $days_until = ceil(($event_date - $today) / (60 * 60 * 24));
                    if($days_until <= 7) {
                        $status_class = 'soon';
                        $status_text = 'In ' . $days_until . ' day' . ($days_until > 1 ? 's' : '');
                    } else {
                        $status_class = 'upcoming';
                        $status_text = 'Upcoming';
                    }
                }
                
                // Calculate registration percentage
                $percentage = 0;
                if($event['max_participants'] > 0) {
                    $percentage = min(100, ($event['registration_count'] / $event['max_participants']) * 100);
                }
                ?>
                
                <div class="col-xl-4 col-lg-6 mb-4 event-card">
                    <div class="card h-100 shadow-sm event-card-inner">
                        <!-- Event Header -->
                        <div class="card-header position-relative border-0 pb-0">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <?php if($event['category_name']): ?>
                                <span class="badge event-category">
                                    <i class="<?php echo $event['category_icon'] ?: 'fas fa-calendar'; ?> me-1"></i>
                                    <?php echo htmlspecialchars($event['category_name']); ?>
                                </span>
                                <?php endif; ?>
                                <span class="badge event-status <?php echo $status_class; ?>">
                                    <?php echo $status_text; ?>
                                </span>
                            </div>
                            <h5 class="card-title event-title mb-2">
                                <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="text-decoration-none text-dark">
                                    <?php echo htmlspecialchars($event['title']); ?>
                                </a>
                            </h5>
                            <?php if($event['featured']): ?>
                                <span class="position-absolute top-0 end-0 mt-2 me-2">
                                    <i class="fas fa-star text-warning" data-bs-toggle="tooltip" title="Featured Event"></i>
                                </span>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Event Body -->
                        <div class="card-body pt-0">
                            <!-- Event Description -->
                            <p class="card-text text-muted event-description mb-3">
                                <?php 
                                $description = htmlspecialchars($event['description']);
                                echo strlen($description) > 120 ? substr($description, 0, 120) . '...' : $description;
                                ?>
                            </p>
                            
                            <!-- Event Meta -->
                            <div class="event-meta mb-3">
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-calendar-day text-primary me-2"></i>
                                    <small>
                                        <?php echo date('D, M j, Y', strtotime($event['event_date'])); ?>
                                    </small>
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <i class="fas fa-clock text-primary me-2"></i>
                                    <small><?php echo date('g:i A', strtotime($event['event_time'])); ?></small>
                                </div>
                                <div class="d-flex align-items-center">
                                    <i class="fas fa-map-marker-alt text-danger me-2"></i>
                                    <small class="text-truncate"><?php echo htmlspecialchars($event['location']); ?></small>
                                </div>
                            </div>
                            
                            <!-- Registration Progress -->
                            <?php if($event['max_participants'] > 0): ?>
                            <div class="registration-progress mb-3">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <small class="text-muted">
                                        <i class="fas fa-users me-1"></i>
                                        <?php echo $event['registration_count']; ?>/<?php echo $event['max_participants']; ?> registered
                                    </small>
                                    <small class="text-muted"><?php echo round($percentage); ?>%</small>
                                </div>
                                <div class="progress" style="height: 6px;">
                                    <div class="progress-bar 
                                        <?php echo $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success'); ?>"
                                        style="width: <?php echo $percentage; ?>%">
                                    </div>
                                </div>
                            </div>
                            <?php else: ?>
                            <div class="mb-3">
                                <small class="text-muted">
                                    <i class="fas fa-users me-1"></i>
                                    <?php echo $event['registration_count']; ?> registered
                                </small>
                            </div>
                            <?php endif; ?>
                            
                            <!-- Organizer Info -->
                            <div class="organizer-info d-flex align-items-center justify-content-between">
                                <div class="d-flex align-items-center">
                                    <div class="organizer-avatar me-2">
                                        <i class="fas fa-user-circle text-primary"></i>
                                    </div>
                                    <small class="text-muted">
                                        By <?php echo htmlspecialchars($event['organizer_name'] ?: $event['creator']); ?>
                                    </small>
                                </div>
                                <?php if($event['price'] > 0): ?>
                                    <span class="badge bg-success">
                                        $<?php echo number_format($event['price'], 2); ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge bg-outline-success">FREE</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        
                        <!-- Card Footer -->
                        <div class="card-footer bg-transparent border-top-0 pt-0">
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-outline-primary btn-sm flex-fill">
                                    <i class="fas fa-eye me-1"></i>View Details
                                </a>
                                <?php if(isset($_SESSION['user_id']) && $event_date >= $today): ?>
                                    <?php if($event['max_participants'] == 0 || $event['registration_count'] < $event['max_participants']): ?>
                                        <a href="register_event.php?event_id=<?php echo $event['id']; ?>" class="btn btn-success btn-sm">
                                            <i class="fas fa-user-plus me-1"></i>Register
                                        </a>
                                    <?php else: ?>
                                        <button class="btn btn-secondary btn-sm" disabled>
                                            <i class="fas fa-times me-1"></i>Full
                                        </button>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
    </div>

    <!-- Quick Stats -->
    <?php if(!empty($events)): ?>
    <div class="row mt-5">
        <div class="col-12">
            <div class="card bg-light">
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <h3 class="text-primary mb-1">
                                    <?php echo count(array_filter($events, function($e) { 
                                        return strtotime($e['event_date']) >= strtotime('today'); 
                                    })); ?>
                                </h3>
                                <p class="text-muted mb-0">Upcoming Events</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <h3 class="text-success mb-1">
                                    <?php echo array_sum(array_column($events, 'registration_count')); ?>
                                </h3>
                                <p class="text-muted mb-0">Total Registrations</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <h3 class="text-warning mb-1">
                                    <?php echo count(array_filter($events, function($e) { 
                                        return $e['featured'] == 1; 
                                    })); ?>
                                </h3>
                                <p class="text-muted mb-0">Featured Events</p>
                            </div>
                        </div>
                        <div class="col-md-3 col-6 mb-3">
                            <div class="stat-item">
                                <h3 class="text-info mb-1">
                                    <?php echo count(array_unique(array_column($events, 'created_by'))); ?>
                                </h3>
                                <p class="text-muted mb-0">Active Organizers</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<style>
.event-card-inner {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.event-card-inner:hover {
    transform: translateY(-5px);
    box-shadow: 0 8px 25px rgba(0,0,0,0.15) !important;
    border-color: #667eea;
}

.event-title {
    font-weight: 600;
    line-height: 1.4;
}

.event-title a:hover {
    color: #667eea !important;
}

.event-category {
    background: linear-gradient(135deg, #667eea, #764ba2);
    color: white;
    font-size: 0.75rem;
}

.event-status {
    font-size: 0.75rem;
    font-weight: 500;
}

.event-status.past { background: #6c757d; color: white; }
.event-status.today { background: #28a745; color: white; }
.event-status.soon { background: #ffc107; color: black; }
.event-status.upcoming { background: #17a2b8; color: white; }

.event-description {
    font-size: 0.9rem;
    line-height: 1.5;
}

.event-meta small {
    font-size: 0.85rem;
}

.organizer-avatar {
    width: 24px;
    height: 24px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.registration-progress .progress {
    background: #f8f9fa;
}

.stat-item h3 {
    font-weight: 700;
    background: linear-gradient(45deg, #667eea, #764ba2);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
}

/* List View Styles */
.events-list .event-card {
    flex: 0 0 100%;
    max-width: 100%;
}

.events-list .event-card-inner {
    flex-direction: row;
}

.events-list .event-card-inner .card-body {
    flex: 1;
}

.events-list .event-card-inner .card-footer {
    border-top: none;
    border-left: 1px solid #e9ecef;
    width: 200px;
    flex-shrink: 0;
}

@media (max-width: 768px) {
    .events-list .event-card-inner {
        flex-direction: column;
    }
    
    .events-list .event-card-inner .card-footer {
        border-left: none;
        border-top: 1px solid #e9ecef;
        width: 100%;
    }
}

/* Grid View Active */
#eventsGrid.grid-view .event-card {
    flex: 0 0 33.333%;
    max-width: 33.333%;
}

@media (max-width: 1200px) {
    #eventsGrid.grid-view .event-card {
        flex: 0 0 50%;
        max-width: 50%;
    }
}

@media (max-width: 768px) {
    #eventsGrid.grid-view .event-card {
        flex: 0 0 100%;
        max-width: 100%;
    }
}
</style>

<script>
// View Toggle Functionality
document.addEventListener('DOMContentLoaded', function() {
    const gridViewBtn = document.getElementById('gridViewBtn');
    const listViewBtn = document.getElementById('listViewBtn');
    const eventsGrid = document.getElementById('eventsGrid');
    
    // Check saved view preference
    const savedView = localStorage.getItem('eventsView') || 'grid';
    setView(savedView);
    
    gridViewBtn.addEventListener('click', function() {
        setView('grid');
    });
    
    listViewBtn.addEventListener('click', function() {
        setView('list');
    });
    
    function setView(view) {
        if (view === 'grid') {
            eventsGrid.classList.remove('events-list');
            eventsGrid.classList.add('grid-view');
            gridViewBtn.classList.add('active');
            listViewBtn.classList.remove('active');
        } else {
            eventsGrid.classList.remove('grid-view');
            eventsGrid.classList.add('events-list');
            listViewBtn.classList.add('active');
            gridViewBtn.classList.remove('active');
        }
        localStorage.setItem('eventsView', view);
    }
    
    // Initialize tooltips
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
    
    // Auto-submit form on filter change
    const filterSelects = document.querySelectorAll('#eventsFilterForm select');
    filterSelects.forEach(select => {
        select.addEventListener('change', function() {
            document.getElementById('eventsFilterForm').submit();
        });
    });
});

// Real-time search (optional enhancement)
let searchTimeout;
document.querySelector('input[name="search"]').addEventListener('input', function() {
    clearTimeout(searchTimeout);
    searchTimeout = setTimeout(() => {
        document.getElementById('eventsFilterForm').submit();
    }, 500);
});
</script>

<?php include 'includes/footer.php'; ?>