<?php
include 'includes/header.php';
include 'includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$event_id = $_GET['event_id'];
$user_id = $_SESSION['user_id'];

// Check if already registered
$stmt = $pdo->prepare("SELECT * FROM event_registrations WHERE event_id = ? AND user_id = ?");
$stmt->execute([$event_id, $user_id]);
$existing_registration = $stmt->fetch();

if($existing_registration) {
    echo '<div class="alert alert-warning">You are already registered for this event!</div>';
} else {
    // Register for event
    $stmt = $pdo->prepare("INSERT INTO event_registrations (event_id, user_id) VALUES (?, ?)");
    $stmt->execute([$event_id, $user_id]);
    
    echo '<div class="alert alert-success">Successfully registered for the event!</div>';
}

// Get event details
$stmt = $pdo->prepare("SELECT * FROM events WHERE id = ?");
$stmt->execute([$event_id]);
$event = $stmt->fetch();
?>

<div class="card">
    <div class="card-body text-center">
        <h3>Event Registration</h3>
        <p>You have registered for: <strong><?php echo htmlspecialchars($event['title']); ?></strong></p>
        <a href="events.php" class="btn btn-primary">Back to Events</a>
        <a href="my_registrations.php" class="btn btn-secondary">View My Registrations</a>
    </div>
</div>

<?php include 'includes/footer.php'; ?>