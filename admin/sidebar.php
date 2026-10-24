<?php
$current_page = basename($_SERVER['PHP_SELF']);

$admin_display_name = 'Administrator';
if (isset($_SESSION['admin_id'], $pdo)) {
    try {
        $stmt = $pdo->prepare("SELECT CONCAT(first_name, ' ', last_name) FROM admin WHERE id = ?");
        $stmt->execute([$_SESSION['admin_id']]);
        $admin_display_name = $stmt->fetchColumn() ?: $admin_display_name;
    } catch (PDOException $e) {
        // Keep the generic label if the lookup fails.
    }
}
?>

<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="au-portal-brand" href="index.php">
            <img src="../external/img/dp_logo.avif" alt="Don Pablo Lorenzo Memorial High School seal">
            <span><strong>DPLMHS</strong><small>SHS Admin Portal</small></span>
        </a>

        <div class="au-portal-user">
            <span class="au-portal-avatar au-portal-avatar-placeholder" aria-hidden="true"><i class="bi bi-person-fill"></i></span>
            <h1 class="au-portal-name"><?php echo htmlspecialchars($admin_display_name); ?></h1>
            <span class="au-portal-role">Administrator</span>
            <span class="au-portal-clock time" id="currentTime"></span>
        </div>

        <ul class="sidebar-nav">
            <li class="sidebar-header">Overview</li>
            <li class="sidebar-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="index.php">
                    <i class="bi bi-speedometer2 align-middle"></i> <span class="align-middle">Dashboard</span>
                </a>
            </li>

            <li class="sidebar-header">Academics</li>
            <li class="sidebar-item <?php echo ($current_page == 'teacher_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="teacher_management.php">
                    <i class="bi bi-person-lines-fill align-middle"></i> <span class="align-middle">Advisers &amp; Teachers</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'section_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="section_management.php">
                    <i class="bi bi-grid-3x3-gap align-middle"></i> <span class="align-middle">Sections</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'class_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="class_management.php">
                    <i class="bi bi-people align-middle"></i> <span class="align-middle">Class Management</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'subject_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="subject_management.php">
                    <i class="bi bi-journal-plus align-middle"></i> <span class="align-middle">Subject Management</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'semester_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="semester_management.php">
                    <i class="bi bi-calendar3 align-middle"></i> <span class="align-middle">Semester Management</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'calendar_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="calendar_management.php">
                    <i class="bi bi-calendar-week align-middle"></i> <span class="align-middle">School Calendar</span>
                </a>
            </li>

            <li class="sidebar-header">Website</li>
            <li class="sidebar-item <?php echo ($current_page == 'post_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="post_management.php">
                    <i class="bi bi-newspaper align-middle"></i> <span class="align-middle">News &amp; Posts</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'gallery_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="gallery_management.php">
                    <i class="bi bi-images align-middle"></i> <span class="align-middle">Gallery Management</span>
                </a>
            </li>

            <li class="sidebar-header">Account</li>
            <li class="sidebar-item <?php echo ($current_page == 'admin_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="admin_management.php">
                    <i class="bi bi-person-badge align-middle"></i> <span class="align-middle">Admin Users</span>
                </a>
            </li>
        </ul>

        <div class="au-portal-foot">
            <a href="../index.php" target="_blank"><i class="bi bi-globe2"></i> View public website</a>
            <a href="processes/admin/account/logout.php"><i class="bi bi-box-arrow-right"></i> Sign out</a>
        </div>
    </div>
</nav>
