<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h3>UNP Moderator</h3>
        <span class="role-badge moderator">Moderator</span>
    </div>
    
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="events.php" class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-event"></i>
            <span>My Events</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn btn-danger w-100">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>
