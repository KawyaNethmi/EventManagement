<?php
include 'includes/header.php';
include 'includes/db.php';
?>

<link rel="stylesheet" href="CSS/style.css">



<?php
// Get upcoming events
$stmt = $pdo->query("SELECT * FROM events WHERE event_date >= CURDATE() ORDER BY event_date LIMIT 3");
$events = $stmt->fetchAll();
?>


<div class="jumbotron bg-primary text-white p-5 rounded">
    <h1 class="display-4">Welcome to Event Management System</h1>
    <p class="lead">Manage and participate in amazing events with our platform.</p>
    <hr class="my-4">
    <p>Create events, manage registrations, and connect with your community.</p>
    <a class="btn btn-light btn-lg" href="events.php" role="button">Browse Events</a>
</div>

<h2 class="mt-5">Upcoming Events</h2>
<div class="row mt-4">
    <?php foreach($events as $event): ?>
    <div class="col-md-4 mb-4">
        <div class="card">
            <div class="card-body">
                <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                <p class="card-text"><?php echo substr(htmlspecialchars($event['description']), 0, 100); ?>...</p>
                <p class="card-text">
                    <small class="text-muted">
                        📅 <?php echo date('M j, Y', strtotime($event['event_date'])); ?><br>
                        ⏰ <?php echo date('g:i A', strtotime($event['event_time'])); ?><br>
                        📍 <?php echo htmlspecialchars($event['location']); ?>
                    </small>
                </p>
                <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-primary">View Details</a>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>

<?php include 'includes/footer.php'; ?>