<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

<nav id="sidebar" class="sidebar js-sidebar" style="background-color: orange !important;">
    <div class="sidebar-content js-simplebar">
        <div class="text-center">
            <p class="text-light time" id="currentTime"> </p>
        </div>
        <a class="sidebar-brand text-center" href="index.php">
            <img src="external/img/ADNU_Logo.png" class="img-fluid logo">
            <h1 class="align-middle text-light bold">Welcome, Admin!</h1>
        </a>

        <ul class="sidebar-nav">
            <li class="sidebar-header">
                Pages
            </li>

            <li class="sidebar-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="index.php">
                    <i class="bi bi-sliders align-middle"></i> <span class="align-middle">Index</span>
                </a>
            </li>
            <hr style="border-bottom: 1px solid white;">

            <li class="sidebar-item <?php echo ($current_page == 'teacher_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="teacher_management.php">
                    <i class="bi bi-person-lines-fill align-middle"></i> <span class="align-middle">Adviser & Teacher Management</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo ($current_page == 'post_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="post_management.php">
                    <i class="bi bi-file-post align-middle"></i> <span class="align-middle">Posts Management</span>
                </a>
            </li>


            <li class="sidebar-item <?php echo ($current_page == 'class_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="class_management.php">
                    <i class="bi bi-person align-middle"></i> <span class="align-middle">Class Management</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo ($current_page == 'subject_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="subject_management.php">
                    <i class="bi bi-journal-plus align-middle"></i> <span class="align-middle">Subject Management</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo ($current_page == 'semester_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="semester_management.php">
                    <i class="bi bi-journal align-middle"></i> <span class="align-middle">Semester Management</span>
                </a>
            </li>

            <hr style="border-bottom: 1px solid white;">

            <li class="sidebar-item <?php echo ($current_page == 'admin_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="admin_management.php">
                    <i class="bi bi-person-badge align-middle"></i> <span class="align-middle">Admin User</span>
                </a>
            </li>
        </ul>
    </div>
</nav>