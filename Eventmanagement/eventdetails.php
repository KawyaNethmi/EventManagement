<?php
include 'includes/header.php';
include 'includes/db.php';

// Check if event ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = $_GET['id'];

// Get event details with additional fields
$stmt = $pdo->prepare("
    SELECT e.*, u.username as creator, u.full_name as organizer_name,
           COUNT(er.id) as registration_count,
           c.name as category_name,
           c.icon as category_icon
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    LEFT JOIN event_registrations er ON e.id = er.event_id 
    LEFT JOIN categories c ON e.category_id = c.id
    WHERE e.id = ?
    GROUP BY e.id
");
$stmt->execute([$event_id]);
$event = $stmt->fetch();

// If event not found
if(!$event) {
    echo '<div class="alert alert-danger">Event not found!</div>';
    include 'includes/footer.php';
    exit();
}

// Check if user is already registered
$is_registered = false;
$registration_data = null;
if(isset($_SESSION['user_id'])) {
    $check_stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $check_stmt->execute([$event_id, $_SESSION['user_id']]);
    $registration_data = $check_stmt->fetch();
    $is_registered = $registration_data ? true : false;
}

// Get registered users for this event
$registered_users_stmt = $pdo->prepare("
    SELECT u.username, u.full_name, u.email, er.registration_date 
    FROM event_registrations er 
    JOIN users u ON er.user_id = u.id 
    WHERE er.event_id = ? 
    ORDER BY er.registration_date DESC
    LIMIT 8
");
$registered_users_stmt->execute([$event_id]);
$registered_users = $registered_users_stmt->fetchAll();

// Get similar events
$similar_events_stmt = $pdo->prepare("
    SELECT e.*, COUNT(er.id) as registration_count
    FROM events e 
    LEFT JOIN event_registrations er ON e.id = er.event_id 
    WHERE e.category_id = ? AND e.id != ? AND e.event_date >= CURDATE()
    GROUP BY e.id 
    ORDER BY e.event_date ASC 
    LIMIT 3
");
$similar_events_stmt->execute([$event['category_id'], $event_id]);
$similar_events = $similar_events_stmt->fetchAll();

// Handle event registration
if(isset($_POST['register']) && isset($_SESSION['user_id'])) {
    if($is_registered) {
        $message = '<div class="alert alert-warning">You are already registered for this event!</div>';
    } else {
        // Check if event has reached maximum participants
        if($event['max_participants'] > 0 && $event['registration_count'] >= $event['max_participants']) {
            $message = '<div class="alert alert-danger">Sorry, this event has reached maximum capacity!</div>';
        } else {
            // Register user for event
            $register_stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id) VALUES (?, ?)");
            $register_stmt->execute([$event_id, $_SESSION['user_id']]);
            $is_registered = true;
            $event['registration_count']++; // Update count locally
            
            // Update registration data
            $registration_data = $pdo->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?")->execute([$event_id, $_SESSION['user_id']])->fetch();
            
            $message = '<div class="alert alert-success">
                <h5><i class="fas fa-check-circle me-2"></i>Successfully Registered!</h5>
                <p class="mb-0">You are now registered for this event. We look forward to seeing you there!</p>
            </div>';
        }
    }
}

// Handle event unregistration
if(isset($_POST['unregister']) && isset($_SESSION['user_id'])) {
    $unregister_stmt = $pdo->prepare("DELETE FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $unregister_stmt->execute([$event_id, $_SESSION['user_id']]);
    $is_registered = false;
    $event['registration_count'] = max(0, $event['registration_count'] - 1);
    $message = '<div class="alert alert-info">
        <h5><i class="fas fa-info-circle me-2"></i>Registration Cancelled</h5>
        <p class="mb-0">You have been unregistered from this event.</p>
    </div>';
}
?>

<div class="container">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-4">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="index.php"><i class="fas fa-home me-1"></i>Home</a></li>
            <li class="breadcrumb-item"><a href="events.php"><i class="fas fa-calendar me-1"></i>Events</a></li>
            <li class="breadcrumb-item active"><?php echo htmlspecialchars($event['title']); ?></li>
        </ol>
    </nav>

    <!-- Alert Messages -->
    <?php if(isset($message)) echo $message; ?>

    <div class="row">
        <div class="col-lg-8">
            <!-- Event Header -->
            <div class="card event-header-card mb-4">
                <div class="card-body p-4">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <?php if($event['category_name']): ?>
                            <span class="badge event-category-badge mb-2">
                                <i class="<?php echo $event['category_icon'] ?: 'fas fa-calendar'; ?> me-1"></i>
                                <?php echo htmlspecialchars($event['category_name']); ?>
                            </span>
                            <?php endif; ?>
                            <h1 class="event-title mb-2"><?php echo htmlspecialchars($event['title']); ?></h1>
                            <div class="event-meta">
                                <span class="text-muted">
                                    <i class="fas fa-user me-1"></i>
                                    Organized by <?php echo htmlspecialchars($event['organizer_name'] ?: $event['creator']); ?>
                                </span>
                            </div>
                        </div>
                        <div class="event-price">
                            <?php if($event['price'] > 0): ?>
                                <span class="price-tag">$<?php echo number_format($event['price'], 2); ?></span>
                                <small class="text-muted d-block">Per ticket</small>
                            <?php else: ?>
                                <span class="price-tag free">FREE</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    
                    <!-- Event Status Badge -->
                    <div class="event-status">
                        <?php
                        $event_date = strtotime($event['event_date']);
                        $today = strtotime('today');
                        $status_class = '';
                        $status_text = '';
                        
                        if($event_date < $today) {
                            $status_class = 'past';
                            $status_text = 'Past Event';
                        } elseif($event_date == $today) {
                            $status_class = 'today';
                            $status_text = 'Happening Today';
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
                        ?>
                        <span class="event-status-badge <?php echo $status_class; ?>">
                            <i class="fas fa-calendar-check me-1"></i><?php echo $status_text; ?>
                        </span>
                    </div>
                </div>
            </div>

            <!-- Event Details -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Event Details</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="detail-item mb-3">
                                <div class="detail-icon">
                                    <i class="fas fa-calendar-day text-primary"></i>
                                </div>
                                <div class="detail-content">
                                    <strong>Date & Time</strong>
                                    <div>
                                        <?php echo date('l, F j, Y', strtotime($event['event_date'])); ?><br>
                                        <span class="text-muted"><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="detail-item mb-3">
                                <div class="detail-icon">
                                    <i class="fas fa-map-marker-alt text-danger"></i>
                                </div>
                                <div class="detail-content">
                                    <strong>Location</strong>
                                    <div><?php echo htmlspecialchars($event['location']); ?></div>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <div class="detail-item">
                        <div class="detail-icon">
                            <i class="fas fa-file-alt text-info"></i>
                        </div>
                        <div class="detail-content">
                            <strong>Description</strong>
                            <div class="event-description">
                                <?php echo nl2br(htmlspecialchars($event['description'])); ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Event Statistics -->
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Event Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="row text-center">
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-number text-primary"><?php echo $event['registration_count']; ?></div>
                                <div class="stat-label">Registered</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-number text-info">
                                    <?php echo $event['max_participants'] > 0 ? $event['max_participants'] : '∞'; ?>
                                </div>
                                <div class="stat-label">Capacity</div>
                            </div>
                        </div>
                        <div class="col-4">
                            <div class="stat-box">
                                <div class="stat-number text-success">
                                    <?php 
                                    if($event['max_participants'] > 0) {
                                        $available = $event['max_participants'] - $event['registration_count'];
                                        echo max(0, $available);
                                    } else {
                                        echo '∞';
                                    }
                                    ?>
                                </div>
                                <div class="stat-label">Available</div>
                            </div>
                        </div>
                    </div>
                    
                    <?php if($event['max_participants'] > 0): ?>
                    <div class="mt-4">
                        <div class="d-flex justify-content-between mb-2">
                            <small>Registration Progress</small>
                            <small>
                                <?php
                                $percentage = ($event['registration_count'] / $event['max_participants']) * 100;
                                $percentage = min($percentage, 100);
                                echo round($percentage); ?>%
                            </small>
                        </div>
                        <div class="progress" style="height: 12px;">
                            <div class="progress-bar 
                                <?php echo $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success'); ?>"
                                role="progressbar" 
                                style="width: <?php echo $percentage; ?>%"
                                aria-valuenow="<?php echo $percentage; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                            </div>
                        </div>
                        <small class="text-muted">
                            <?php echo $event['registration_count']; ?> of <?php echo $event['max_participants']; ?> spots filled
                        </small>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Registration Panel -->
            <div class="card registration-panel sticky-top">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0"><i class="fas fa-ticket-alt me-2"></i>Registration</h5>
                </div>
                <div class="card-body">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <?php if($is_registered): ?>
                            <div class="registration-status registered">
                                <div class="status-icon">
                                    <i class="fas fa-check-circle"></i>
                                </div>
                                <h6>You're Registered!</h6>
                                <p class="text-muted small mb-2">
                                    Registered on <?php echo date('M j, Y', strtotime($registration_data['registration_date'])); ?>
                                </p>
                                
                                <div class="registration-actions">
                                    <button class="btn btn-outline-danger btn-sm w-100 mb-2" 
                                            data-bs-toggle="modal" data-bs-target="#unregisterModal">
                                        <i class="fas fa-times me-1"></i>Cancel Registration
                                    </button>
                                    <a href="my_registrations.php" class="btn btn-outline-primary btn-sm w-100">
                                        <i class="fas fa-list me-1"></i>My Registrations
                                    </a>
                                </div>
                            </div>
                        <?php else: ?>
                            <?php
                            $event_date = strtotime($event['event_date']);
                            $today = strtotime('today');
                            if($event_date < $today): 
                            ?>
                                <div class="registration-status past">
                                    <div class="status-icon">
                                        <i class="fas fa-clock"></i>
                                    </div>
                                    <h6>Event Ended</h6>
                                    <p class="text-muted small">This event has already taken place.</p>
                                </div>
                            <?php elseif($event['max_participants'] > 0 && $event['registration_count'] >= $event['max_participants']): ?>
                                <div class="registration-status full">
                                    <div class="status-icon">
                                        <i class="fas fa-exclamation-triangle"></i>
                                    </div>
                                    <h6>Fully Booked</h6>
                                    <p class="text-muted small">This event has reached maximum capacity.</p>
                                    <button class="btn btn-outline-secondary btn-sm w-100" disabled>
                                        Waitlist Available
                                    </button>
                                </div>
                            <?php else: ?>
                                <div class="registration-status available">
                                    <div class="status-icon">
                                        <i class="fas fa-calendar-plus"></i>
                                    </div>
                                    <h6>Join This Event</h6>
                                    <p class="text-muted small mb-3">
                                        Ready to be part of this amazing experience?
                                    </p>
                                    <form method="POST" action="">
                                        <button type="submit" name="register" class="btn btn-success w-100 btn-lg">
                                            <i class="fas fa-user-plus me-2"></i>
                                            Register Now
                                        </button>
                                    </form>
                                    <?php if($event['max_participants'] > 0): ?>
                                        <small class="text-muted d-block mt-2 text-center">
                                            <?php echo $event['max_participants'] - $event['registration_count']; ?> spots left
                                        </small>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <div class="registration-status guest">
                            <div class="status-icon">
                                <i class="fas fa-user-lock"></i>
                            </div>
                            <h6>Login to Register</h6>
                            <p class="text-muted small mb-3">
                                Please login to register for this event.
                            </p>
                            <div class="d-grid gap-2">
                                <a href="login.php" class="btn btn-primary">
                                    <i class="fas fa-sign-in-alt me-2"></i>Login
                                </a>
                                <a href="register.php" class="btn btn-outline-primary">
                                    <i class="fas fa-user-plus me-2"></i>Create Account
                                </a>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Event Organizer -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-user me-2"></i>Event Organizer</h6>
                </div>
                <div class="card-body">
                    <div class="d-flex align-items-center">
                        <div class="organizer-avatar me-3">
                            <i class="fas fa-user-circle fa-2x text-primary"></i>
                        </div>
                        <div>
                            <h6 class="mb-1"><?php echo htmlspecialchars($event['organizer_name'] ?: $event['creator']); ?></h6>
                            <small class="text-muted">Event Host</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Share Event -->
            <div class="card mt-4">
                <div class="card-header bg-light">
                    <h6 class="mb-0"><i class="fas fa-share-alt me-2"></i>Share This Event</h6>
                </div>
                <div class="card-body">
                    <div class="share-buttons d-flex justify-content-around">
                        <button class="btn btn-outline-primary btn-sm share-btn" data-platform="facebook">
                            <i class="fab fa-facebook-f"></i>
                        </button>
                        <button class="btn btn-outline-info btn-sm share-btn" data-platform="twitter">
                            <i class="fab fa-twitter"></i>
                        </button>
                        <button class="btn btn-outline-danger btn-sm share-btn" data-platform="linkedin">
                            <i class="fab fa-linkedin-in"></i>
                        </button>
                        <button class="btn btn-outline-success btn-sm share-btn" data-platform="whatsapp">
                            <i class="fab fa-whatsapp"></i>
                        </button>
                        <button class="btn btn-outline-dark btn-sm share-btn" onclick="copyEventLink()">
                            <i class="fas fa-link"></i>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Similar Events -->
    <?php if(!empty($similar_events)): ?>
    <div class="row mt-5">
        <div class="col-12">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="mb-0"><i class="fas fa-calendar-week me-2"></i>Similar Events You Might Like</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <?php foreach($similar_events as $similar_event): ?>
                        <div class="col-md-4 mb-3">
                            <div class="card similar-event-card h-100">
                                <div class="card-body">
                                    <h6 class="card-title"><?php echo htmlspecialchars($similar_event['title']); ?></h6>
                                    <p class="card-text text-muted small">
                                        <?php echo date('M j, Y', strtotime($similar_event['event_date'])); ?> • 
                                        <?php echo date('g:i A', strtotime($similar_event['event_time'])); ?>
                                    </p>
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="badge bg-primary">
                                            <?php echo $similar_event['registration_count']; ?> registered
                                        </span>
                                        <a href="event_detail.php?id=<?php echo $similar_event['id']; ?>" class="btn btn-sm btn-outline-primary">
                                            View Details
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Unregister Modal -->
<div class="modal fade" id="unregisterModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Cancel Registration</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to cancel your registration for this event?</p>
                <p class="text-muted small">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep Registration</button>
                <form method="POST" action="" class="d-inline">
                    <button type="submit" name="unregister" class="btn btn-danger">Yes, Cancel Registration</button>
                </form>
            </div>
        </div>
    </div>
</div>

<style>
.event-header-card {
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    color: white;
    border: none;
}

.event-title {
    font-size: 2.5rem;
    font-weight: 700;
    line-height: 1.2;
}

.event-category-badge {
    background: rgba(255,255,255,0.2);
    color: white;
    font-size: 0.9rem;
    padding: 0.5rem 1rem;
}

.price-tag {
    font-size: 2rem;
    font-weight: 700;
    color: white;
}

.price-tag.free {
    color: #28a745;
}

.event-status-badge {
    padding: 0.5rem 1rem;
    border-radius: 25px;
    font-weight: 600;
    font-size: 0.9rem;
}

.event-status-badge.past { background: #6c757d; color: white; }
.event-status-badge.today { background: #28a745; color: white; }
.event-status-badge.soon { background: #ffc107; color: black; }
.event-status-badge.upcoming { background: #007bff; color: white; }

.detail-item {
    display: flex;
    align-items: flex-start;
    margin-bottom: 1.5rem;
}

.detail-icon {
    width: 40px;
    height: 40px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    margin-right: 1rem;
    flex-shrink: 0;
}

.detail-content {
    flex: 1;
}

.event-description {
    line-height: 1.6;
    white-space: pre-line;
}

.stat-box {
    padding: 1rem;
}

.stat-number {
    font-size: 2rem;
    font-weight: 700;
    line-height: 1;
}

.stat-label {
    font-size: 0.9rem;
    color: #6c757d;
    margin-top: 0.5rem;
}

.registration-panel {
    border: none;
    box-shadow: 0 5px 20px rgba(0,0,0,0.1);
}

.registration-status {
    text-align: center;
    padding: 1rem 0;
}

.status-icon {
    font-size: 3rem;
    margin-bottom: 1rem;
}

.registration-status.registered .status-icon { color: #28a745; }
.registration-status.available .status-icon { color: #007bff; }
.registration-status.past .status-icon { color: #6c757d; }
.registration-status.full .status-icon { color: #dc3545; }
.registration-status.guest .status-icon { color: #6c757d; }

.organizer-avatar {
    width: 50px;
    height: 50px;
    background: #f8f9fa;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.share-buttons .btn {
    width: 40px;
    height: 40px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
}

.similar-event-card {
    transition: all 0.3s ease;
    border: 1px solid #e9ecef;
}

.similar-event-card:hover {
    transform: translateY(-5px);
    box-shadow: 0 5px 15px rgba(0,0,0,0.1);
}

.sticky-top {
    position: sticky;
    top: 100px;
    z-index: 10;
}

@media (max-width: 768px) {
    .event-title {
        font-size: 2rem;
    }
    
    .price-tag {
        font-size: 1.5rem;
    }
}
</style>

<script>
// Share functionality
document.querySelectorAll('.share-btn').forEach(btn => {
    btn.addEventListener('click', function() {
        const platform = this.dataset.platform;
        const eventTitle = '<?php echo addslashes($event['title']); ?>';
        const eventUrl = window.location.href;
        
        const shareUrls = {
            facebook: `https://www.facebook.com/sharer/sharer.php?u=${encodeURIComponent(eventUrl)}`,
            twitter: `https://twitter.com/intent/tweet?text=${encodeURIComponent(eventTitle)}&url=${encodeURIComponent(eventUrl)}`,
            linkedin: `https://www.linkedin.com/sharing/share-offsite/?url=${encodeURIComponent(eventUrl)}`,
            whatsapp: `https://wa.me/?text=${encodeURIComponent(eventTitle + ' - ' + eventUrl)}`
        };
        
        if (shareUrls[platform]) {
            window.open(shareUrls[platform], '_blank', 'width=600,height=400');
        }
    });
});

// Copy event link
function copyEventLink() {
    const eventUrl = window.location.href;
    navigator.clipboard.writeText(eventUrl).then(() => {
        // Show temporary feedback
        const btn = event.target.closest('button');
        const originalHtml = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-check"></i>';
        btn.classList.remove('btn-outline-dark');
        btn.classList.add('btn-success');
        
        setTimeout(() => {
            btn.innerHTML = originalHtml;
            btn.classList.remove('btn-success');
            btn.classList.add('btn-outline-dark');
        }, 2000);
    });
}

// Add to calendar functionality
function addToCalendar() {
    const event = {
        title: '<?php echo addslashes($event['title']); ?>',
        description: '<?php echo addslashes($event['description']); ?>',
        location: '<?php echo addslashes($event['location']); ?>',
        start: '<?php echo date('Ymd\THis', strtotime($event['event_date'] . ' ' . $event['event_time'])); ?>',
        end: '<?php echo date('Ymd\THis', strtotime($event['event_date'] . ' ' . $event['event_time'] . ' +2 hours')); ?>'
    };
    
    // Create .ics file content
    const icsContent = [
        'BEGIN:VCALENDAR',
        'VERSION:2.0',
        'BEGIN:VEVENT',
        'SUMMARY:' + event.title,
        'DESCRIPTION:' + event.description,
        'LOCATION:' + event.location,
        'DTSTART:' + event.start,
        'DTEND:' + event.end,
        'END:VEVENT',
        'END:VCALENDAR'
    ].join('\n');
    
    // Download .ics file
    const blob = new Blob([icsContent], { type: 'text/calendar' });
    const url = window.URL.createObjectURL(blob);
    const a = document.createElement('a');
    a.href = url;
    a.download = 'event.ics';
    document.body.appendChild(a);
    a.click();
    document.body.removeChild(a);
    window.URL.revokeObjectURL(url);
}

// Initialize tooltips
document.addEventListener('DOMContentLoaded', function() {
    const tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
    const tooltipList = tooltipTriggerList.map(function(tooltipTriggerEl) {
        return new bootstrap.Tooltip(tooltipTriggerEl);
    });
});
</script>

<?php include 'includes/footer.php'; ?>