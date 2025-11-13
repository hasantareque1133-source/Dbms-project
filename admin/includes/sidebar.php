<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h3>UNP Admin</h3>
        <span class="role-badge admin">Administrator</span>
    </div>
    
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="users.php" class="nav-link <?php echo $currentPage === 'users.php' ? 'active' : ''; ?>">
            <i class="bi bi-people"></i>
            <span>Manage Users</span>
        </a>
        
        <a href="opportunities.php" class="nav-link <?php echo $currentPage === 'opportunities.php' ? 'active' : ''; ?>">
            <i class="bi bi-briefcase"></i>
            <span>Opportunities</span>
        </a>
        
        <a href="alumni.php" class="nav-link <?php echo $currentPage === 'alumni.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-badge"></i>
            <span>Alumni Directory</span>
        </a>
        
        <a href="clubs.php" class="nav-link <?php echo $currentPage === 'clubs.php' ? 'active' : ''; ?>">
            <i class="bi bi-collection"></i>
            <span>Clubs</span>
        </a>
        
        <a href="events.php" class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-event"></i>
            <span>All Events</span>
        </a>
        
        <a href="activity_logs.php" class="nav-link <?php echo $currentPage === 'activity_logs.php' ? 'active' : ''; ?>">
            <i class="bi bi-clock-history"></i>
            <span>Activity Logs</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn btn-danger w-100">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>
