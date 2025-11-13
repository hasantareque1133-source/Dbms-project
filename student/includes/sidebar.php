<?php
$currentPage = basename($_SERVER['PHP_SELF']);
?>
<aside class="sidebar">
    <div class="sidebar-header">
        <h3>UNP Student</h3>
        <span class="role-badge student">Student</span>
    </div>
    
    <nav class="sidebar-menu">
        <a href="dashboard.php" class="nav-link <?php echo $currentPage === 'dashboard.php' ? 'active' : ''; ?>">
            <i class="bi bi-speedometer2"></i>
            <span>Dashboard</span>
        </a>
        
        <a href="opportunities.php" class="nav-link <?php echo $currentPage === 'opportunities.php' ? 'active' : ''; ?>">
            <i class="bi bi-briefcase"></i>
            <span>Opportunities</span>
        </a>
        
        <a href="events.php" class="nav-link <?php echo $currentPage === 'events.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-event"></i>
            <span>Events & Workshops</span>
        </a>
        
        <a href="alumni.php" class="nav-link <?php echo $currentPage === 'alumni.php' ? 'active' : ''; ?>">
            <i class="bi bi-person-badge"></i>
            <span>Alumni Directory</span>
        </a>
        
        <a href="my_registrations.php" class="nav-link <?php echo $currentPage === 'my_registrations.php' ? 'active' : ''; ?>">
            <i class="bi bi-calendar-check"></i>
            <span>My Registrations</span>
        </a>
    </nav>
    
    <div class="sidebar-footer">
        <a href="../logout.php" class="btn btn-danger w-100">
            <i class="bi bi-box-arrow-right"></i> Logout
        </a>
    </div>
</aside>
