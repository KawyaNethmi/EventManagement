<?php
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>EventFlow - Manage Your Events</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        .navbar {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%) !important;
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .navbar-brand {
            font-weight: 800;
            font-size: 1.8rem;
            background: linear-gradient(45deg, #fff, #e3f2fd);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        .nav-link {
            font-weight: 500;
            transition: all 0.3s ease;
            border-radius: 25px;
            margin: 0 2px;
        }
        .nav-link:hover {
            background: rgba(255,255,255,0.1);
            transform: translateY(-2px);
        }
        .notification-badge {
            background: linear-gradient(45deg, #ff6b6b, #ee5a24);
            animation: pulse 2s infinite;
        }
        @keyframes pulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }
        .user-welcome {
            background: rgba(255,255,255,0.1);
            border-radius: 20px;
            padding: 5px 15px;
        }
    </style>
</head>
<body>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark sticky-top">
        <div class="container">
            <a class="navbar-brand" href="index.php">
                <i class="fas fa-calendar-alt me-2"></i>EventFlow
            </a>
            
            <!-- Mobile Toggle -->
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="navbarNav">
                <!-- Left Navigation -->
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link active" href="index.php">
                            <i class="fas fa-home me-1"></i>Home
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="events.php">
                            <i class="fas fa-calendar-day me-1"></i>Browse Events
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="calendar.php">
                            <i class="fas fa-calendar me-1"></i>Calendar
                        </a>
                    </li>
                    
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <li class="nav-item">
                            <a class="nav-link" href="my_events.php">
                                <i class="fas fa-list me-1"></i>My Events
                            </a>
                        </li>
                        <?php if($_SESSION['user_type'] == 'admin'): ?>
                            <li class="nav-item dropdown">
                                <a class="nav-link dropdown-toggle" href="#" id="adminDropdown" role="button" data-bs-toggle="dropdown">
                                    <i class="fas fa-crown me-1"></i>Admin
                                </a>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="admin.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                                    <li><a class="dropdown-item" href="manage_events.php"><i class="fas fa-edit me-2"></i>Manage Events</a></li>
                                    <li><a class="dropdown-item" href="manage_users.php"><i class="fas fa-users me-2"></i>Manage Users</a></li>
                                    <li><a class="dropdown-item" href="reports.php"><i class="fas fa-chart-bar me-2"></i>Reports</a></li>
                                </ul>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                    
                    <li class="nav-item">
                        <a class="nav-link" href="about.php">
                            <i class="fas fa-info-circle me-1"></i>About
                        </a>
                    </li>
                </ul>

                <!-- Right Navigation -->
                <ul class="navbar-nav">
                    <?php if(isset($_SESSION['user_id'])): ?>
                        <!-- Notifications -->
                        <li class="nav-item dropdown">
                            <a class="nav-link position-relative" href="#" id="notificationDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-bell"></i>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill notification-badge">
                                    3
                                </span>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationDropdown">
                                <li><h6 class="dropdown-header">Recent Notifications</h6></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-calendar-check text-success me-2"></i>Event "Tech Conference" starting soon</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-user-plus text-info me-2"></i>5 new registrations for Workshop</a></li>
                                <li><a class="dropdown-item" href="#"><i class="fas fa-exclamation-triangle text-warning me-2"></i>Event "Music Festival" almost full</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item text-center" href="notifications.php">View All Notifications</a></li>
                            </ul>
                        </li>

                        <!-- Quick Create Event -->
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light btn-sm mx-2" href="create_event.php">
                                <i class="fas fa-plus me-1"></i>Create Event
                            </a>
                        </li>

                        <!-- User Menu -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <div class="user-welcome d-inline-flex align-items-center">
                                    <i class="fas fa-user-circle me-2"></i>
                                    <span><?php echo htmlspecialchars($_SESSION['username']); ?></span>
                                </div>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <li><h6 class="dropdown-header">Welcome, <?php echo htmlspecialchars($_SESSION['full_name'] ?? $_SESSION['username']); ?>!</h6></li>
                                <li><a class="dropdown-item" href="profile.php"><i class="fas fa-user-edit me-2"></i>My Profile</a></li>
                                <li><a class="dropdown-item" href="my_events.php"><i class="fas fa-calendar me-2"></i>My Events</a></li>
                                <li><a class="dropdown-item" href="my_registrations.php"><i class="fas fa-ticket-alt me-2"></i>My Registrations</a></li>
                                <li><hr class="dropdown-divider"></li>
                                <li><a class="dropdown-item" href="settings.php"><i class="fas fa-cog me-2"></i>Settings</a></li>
                                <li><a class="dropdown-item text-danger" href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <!-- Guest User Options -->
                        <li class="nav-item">
                            <a class="nav-link" href="login.php">
                                <i class="fas fa-sign-in-alt me-1"></i>Login
                            </a>
                        </li>
                        <li class="nav-item">
                            <a class="nav-link btn btn-outline-light btn-sm mx-2" href="register.php">
                                <i class="fas fa-user-plus me-1"></i>Register
                            </a>
                        </li>
                    <?php endif; ?>
                </ul>

                <!-- Search Bar -->
                <form class="d-flex ms-3" action="search.php" method="GET">
                    <div class="input-group">
                        <input type="text" class="form-control" placeholder="Search events..." name="q" style="border-radius: 20px 0 0 20px;">
                        <button class="btn btn-light" type="submit" style="border-radius: 0 20px 20px 0;">
                            <i class="fas fa-search"></i>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </nav>

    <!-- Quick Stats Bar (for Admin) -->
    <?php if(isset($_SESSION['user_id']) && $_SESSION['user_type'] == 'admin'): ?>
    <div class="bg-light py-2 border-bottom">
        <div class="container">
            <div class="row text-center">
                <div class="col">
                    <small class="text-muted">
                        <i class="fas fa-calendar text-primary me-1"></i>
                        <strong>12</strong> Upcoming Events
                    </small>
                </div>
                <div class="col">
                    <small class="text-muted">
                        <i class="fas fa-users text-success me-1"></i>
                        <strong>45</strong> Total Registrations
                    </small>
                </div>
                <div class="col">
                    <small class="text-muted">
                        <i class="fas fa-exclamation-triangle text-warning me-1"></i>
                        <strong>3</strong> Requires Attention
                    </small>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Alert Messages -->
    <?php if(isset($_SESSION['success_message'])): ?>
        <div class="alert alert-success alert-dismissible fade show m-0 rounded-0" role="alert">
            <i class="fas fa-check-circle me-2"></i>
            <?php echo $_SESSION['success_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['success_message']); ?>
    <?php endif; ?>

    <?php if(isset($_SESSION['error_message'])): ?>
        <div class="alert alert-danger alert-dismissible fade show m-0 rounded-0" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i>
            <?php echo $_SESSION['error_message']; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error_message']); ?>
    <?php endif; ?>

    <div class="container mt-4">