<?php
include 'includes/header.php';
include 'includes/db.php';

// Check if event ID is provided
if(!isset($_GET['id']) || empty($_GET['id'])) {
    header("Location: events.php");
    exit();
}

$event_id = $_GET['id'];

// Get event details
$stmt = $pdo->prepare("
    SELECT e.*, u.username as creator, 
           COUNT(er.id) as registration_count,
           e.max_participants
    FROM events e 
    LEFT JOIN users u ON e.created_by = u.id 
    LEFT JOIN event_registrations er ON e.id = er.event_id 
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
if(isset($_SESSION['user_id'])) {
    $check_stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?");
    $check_stmt->execute([$event_id, $_SESSION['user_id']]);
    $is_registered = $check_stmt->fetch() ? true : false;
}

// Get registered users for this event
$registered_users_stmt = $pdo->prepare("
    SELECT u.username, u.email, er.registration_date 
    FROM event_registrations er 
    JOIN users u ON er.user_id = u.id 
    WHERE er.event_id = ? 
    ORDER BY er.registration_date DESC
    LIMIT 10
");
$registered_users_stmt->execute([$event_id]);
$registered_users = $registered_users_stmt->fetchAll();

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
            $message = '<div class="alert alert-success">Successfully registered for the event!</div>';
        }
    }
}
?>

<div class="row">
    <div class="col-md-8">
        <!-- Event Details Card -->
        <div class="card mb-4">
            <div class="card-header bg-primary text-white">
                <h3 class="mb-0"><?php echo htmlspecialchars($event['title']); ?></h3>
            </div>
            <div class="card-body">
                <?php if(isset($message)) echo $message; ?>
                
                <div class="row mb-4">
                    <div class="col-md-6">
                        <h5>📅 Event Date & Time</h5>
                        <p class="fs-5">
                            <strong><?php echo date('F j, Y', strtotime($event['event_date'])); ?></strong><br>
                            <span class="text-muted"><?php echo date('g:i A', strtotime($event['event_time'])); ?></span>
                        </p>
                    </div>
                    <div class="col-md-6">
                        <h5>📍 Location</h5>
                        <p class="fs-5">
                            <strong><?php echo htmlspecialchars($event['location']); ?></strong>
                        </p>
                    </div>
                </div>

                <h5>📝 Description</h5>
                <p class="lead"><?php echo nl2br(htmlspecialchars($event['description'])); ?></p>

                <div class="row mt-4">
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>👥 Registered Participants</h6>
                                <h4 class="text-primary"><?php echo $event['registration_count']; ?></h4>
                                <?php if($event['max_participants'] > 0): ?>
                                    <small class="text-muted">
                                        of <?php echo $event['max_participants']; ?> maximum
                                    </small>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>🎯 Event Status</h6>
                                <?php
                                $event_date = strtotime($event['event_date']);
                                $today = strtotime('today');
                                if($event_date < $today) {
                                    echo '<span class="badge bg-secondary">Past Event</span>';
                                } elseif($event_date == $today) {
                                    echo '<span class="badge bg-success">Today</span>';
                                } else {
                                    echo '<span class="badge bg-primary">Upcoming</span>';
                                }
                                ?>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="card bg-light">
                            <div class="card-body text-center">
                                <h6>👤 Organizer</h6>
                                <h5><?php echo htmlspecialchars($event['creator']); ?></h5>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Registration Section -->
        <?php if(isset($_SESSION['user_id'])): ?>
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0">Event Registration</h5>
                </div>
                <div class="card-body text-center">
                    <?php if($is_registered): ?>
                        <div class="alert alert-success">
                            <h5>✅ You are registered for this event!</h5>
                            <p class="mb-0">We look forward to seeing you at the event.</p>
                        </div>
                        <a href="my_registrations.php" class="btn btn-primary">View My Registrations</a>
                    <?php else: ?>
                        <?php
                        $event_date = strtotime($event['event_date']);
                        $today = strtotime('today');
                        if($event_date < $today): 
                        ?>
                            <div class="alert alert-warning">
                                <h5>📅 Event has passed</h5>
                                <p class="mb-0">Registration is closed for past events.</p>
                            </div>
                        <?php elseif($event['max_participants'] > 0 && $event['registration_count'] >= $event['max_participants']): ?>
                            <div class="alert alert-danger">
                                <h5>🚫 Event Full</h5>
                                <p class="mb-0">This event has reached maximum capacity.</p>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="">
                                <h5>Ready to join this event?</h5>
                                <p class="text-muted">Click the button below to register.</p>
                                <button type="submit" name="register" class="btn btn-success btn-lg">
                                    Register for this Event
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        <?php else: ?>
            <div class="card mb-4">
                <div class="card-body text-center">
                    <h5>🔐 Login Required</h5>
                    <p>Please login to register for this event.</p>
                    <a href="login.php" class="btn btn-primary">Login</a>
                    <a href="register.php" class="btn btn-outline-primary">Register</a>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <div class="col-md-4">
        <!-- Quick Info Sidebar -->
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">📋 Quick Info</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="events.php" class="btn btn-outline-primary">
                        ← Back to All Events
                    </a>
                    <?php if(isset($_SESSION['user_type']) && $_SESSION['user_type'] == 'admin'): ?>
                        <a href="view_registrations.php?event_id=<?php echo $event_id; ?>" class="btn btn-outline-info">
                            👥 View All Registrations
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- Recent Registrations -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">👥 Recent Registrations</h5>
            </div>
            <div class="card-body">
                <?php if(empty($registered_users)): ?>
                    <p class="text-muted">No registrations yet.</p>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach($registered_users as $user): ?>
                            <div class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <strong><?php echo htmlspecialchars($user['username']); ?></strong>
                                    <br>
                                    <small class="text-muted">
                                        <?php echo date('M j', strtotime($user['registration_date'])); ?>
                                    </small>
                                </div>
                                <span class="badge bg-primary rounded-pill">✓</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <?php if($event['registration_count'] > 10): ?>
                        <div class="text-center mt-2">
                            <small class="text-muted">
                                and <?php echo $event['registration_count'] - 10; ?> more...
                            </small>
                        </div>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>

        <!-- Event Statistics -->
        <div class="card mt-4">
            <div class="card-header">
                <h5 class="mb-0">📊 Event Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row text-center">
                    <div class="col-6">
                        <h6>Total Capacity</h6>
                        <h4 class="text-info">
                            <?php echo $event['max_participants'] > 0 ? $event['max_participants'] : '∞'; ?>
                        </h4>
                    </div>
                    <div class="col-6">
                        <h6>Available Spots</h6>
                        <h4 class="text-success">
                            <?php 
                            if($event['max_participants'] > 0) {
                                echo $event['max_participants'] - $event['registration_count'];
                            } else {
                                echo '∞';
                            }
                            ?>
                        </h4>
                    </div>
                </div>
                
                <!-- Progress Bar -->
                <?php if($event['max_participants'] > 0): ?>
                    <div class="mt-3">
                        <h6>Registration Progress</h6>
                        <div class="progress" style="height: 20px;">
                            <?php
                            $percentage = ($event['registration_count'] / $event['max_participants']) * 100;
                            $percentage = min($percentage, 100);
                            ?>
                            <div class="progress-bar 
                                <?php echo $percentage >= 90 ? 'bg-danger' : ($percentage >= 70 ? 'bg-warning' : 'bg-success'); ?>"
                                role="progressbar" 
                                style="width: <?php echo $percentage; ?>%"
                                aria-valuenow="<?php echo $percentage; ?>" 
                                aria-valuemin="0" 
                                aria-valuemax="100">
                                <?php echo round($percentage); ?>%
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
</div>

<?php include 'includes/footer.php'; ?>