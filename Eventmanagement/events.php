<?php
include 'includes/header.php';
include 'includes/db.php';

// Get all events
$stmt = $pdo->query("SELECT e.*, u.username as creator FROM events e LEFT JOIN users u ON e.created_by = u.id ORDER BY e.event_date");
$events = $stmt->fetchAll();
?>

<h2>All Events</h2>

<div class="row mt-4">
    <?php foreach($events as $event): ?>
    <div class="col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                <p class="card-text"><?php echo htmlspecialchars($event['description']); ?></p>
                <p class="card-text">
                    <strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['event_date'])); ?><br>
                    <strong>Time:</strong> <?php echo date('g:i A', strtotime($event['event_time'])); ?><br>
                    <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?><br>
                    <strong>Organizer:</strong> <?php echo htmlspecialchars($event['creator']); ?>
                </p>
            </div>
            <div class="card-footer">
                <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">View Details</a>
                <?php if(isset($_SESSION['user_id'])): ?>
                    <a href="register_event.php?event_id=<?php echo $event['id']; ?>" class="btn btn-success">Register</a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>