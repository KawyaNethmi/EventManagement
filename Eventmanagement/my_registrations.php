<?php
include 'includes/header.php';
include 'includes/db.php';

if(!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Get user's registrations
$stmt = $pdo->prepare("
    SELECT e.*, er.registration_date 
    FROM events e 
    INNER JOIN event_registrations er ON e.id = er.event_id 
    WHERE er.user_id = ? 
    ORDER BY e.event_date
");
$stmt->execute([$user_id]);
$registrations = $stmt->fetchAll();
?>

<h2>My Event Registrations</h2>

<?php if(empty($registrations)): ?>
    <div class="alert alert-info">
        You haven't registered for any events yet. <a href="events.php">Browse events</a> to get started!
    </div>
<?php else: ?>
    <div class="row mt-4">
        <?php foreach($registrations as $event): ?>
        <div class="col-md-6 mb-4">
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title"><?php echo htmlspecialchars($event['title']); ?></h5>
                    <p class="card-text"><?php echo htmlspecialchars($event['description']); ?></p>
                    <p class="card-text">
                        <strong>Date:</strong> <?php echo date('F j, Y', strtotime($event['event_date'])); ?><br>
                        <strong>Time:</strong> <?php echo date('g:i A', strtotime($event['event_time'])); ?><br>
                        <strong>Location:</strong> <?php echo htmlspecialchars($event['location']); ?><br>
                        <strong>Registered on:</strong> <?php echo date('M j, Y g:i A', strtotime($event['registration_date'])); ?>
                    </p>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>