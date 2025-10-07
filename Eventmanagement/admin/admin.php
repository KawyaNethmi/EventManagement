<?php
include 'includes/header.php';
include 'includes/db.php';

if(!isset($_SESSION['user_id']) || $_SESSION['user_type'] != 'admin') {
    header("Location: login.php");
    exit();
}

// Get statistics
$total_events = $pdo->query("SELECT COUNT(*) FROM events")->fetchColumn();
$total_users = $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn();
$total_registrations = $pdo->query("SELECT COUNT(*) FROM event_registrations")->fetchColumn();

// Get all events with registration count
$events = $pdo->query("
    SELECT e.*, COUNT(er.id) as registration_count 
    FROM events e 
    LEFT JOIN event_registrations er ON e.id = er.event_id 
    GROUP BY e.id 
    ORDER BY e.event_date
")->fetchAll();
?>



<h2>Admin Dashboard</h2>

<div class="row mt-4">
    <div class="col-md-4">
        <div class="card text-white bg-primary">
            <div class="card-body">
                <h5 class="card-title">Total Events</h5>
                <h2><?php echo $total_events; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-success">
            <div class="card-body">
                <h5 class="card-title">Total Users</h5>
                <h2><?php echo $total_users; ?></h2>
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card text-white bg-info">
            <div class="card-body">
                <h5 class="card-title">Total Registrations</h5>
                <h2><?php echo $total_registrations; ?></h2>
            </div>
        </div>
    </div>
</div>

<div class="mt-4">
    <div class="d-flex justify-content-between align-items-center">
        <h3>All Events</h3>
        <a href="create_event.php" class="btn btn-primary">Create New Event</a>
    </div>
    
    <div class="table-responsive mt-3">
        <table class="table table-striped">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Date</th>
                    <th>Location</th>
                    <th>Registrations</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach($events as $event): ?>
                <tr>
                    <td><?php echo htmlspecialchars($event['title']); ?></td>
                    <td><?php echo date('M j, Y', strtotime($event['event_date'])); ?></td>
                    <td><?php echo htmlspecialchars($event['location']); ?></td>
                    <td><?php echo $event['registration_count']; ?></td>
                    <td>
                        <a href="event_detail.php?id=<?php echo $event['id']; ?>" class="btn btn-sm btn-info">View</a>
                        <a href="view_registrations.php?event_id=<?php echo $event['id']; ?>" class="btn btn-sm btn-secondary">Registrations</a>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include 'includes/footer.php'; ?>