<div class="col-md-3 col-lg-2 d-md-block sidebar" id="sidebarContainer">
        <div class="sidebar-content text-center">
          <small class="c-white" id="currentTime"> </small>

          <img src="external/img/ccs_logo-removebg-preview.png" class="img-fluid logo space-sm">
          <h4 class="bold c-white ">Welcome, Admin!</h4>

          <div class="navigation-links" style="text-align: left;">
         
          <?php
            $current_page = basename($_SERVER['PHP_SELF']);
            ?>

            <a href="index.php" class="<?php echo ($current_page == 'index.php') ? 'actives' : ''; ?>">
                <p><i class="bi bi-kanban"></i> Index</p>
            </a>
            <hr>

            <a href="pages-blank.php" class="<?php echo ($current_page == 'pages-blank.php') ? 'active' : ''; ?>">
                <p><i class="bi bi-book"></i> Class Management</p>
            </a>
            <a href="staff_management.php" class="<?php echo ($current_page == 'staff_management.php') ? 'active' : ''; ?>">
                <p><i class="bi bi-person-square"></i> Teacher Management</p>
            </a>
            <a href="subject_management.php" class="<?php echo ($current_page == 'subject_management.php') ? 'active' : ''; ?>">
                <p><i class="bi bi-journals"></i> Subject Management</p>
            </a>
            <a href="semester_management.php" class="<?php echo ($current_page == 'semester_management.php') ? 'active' : ''; ?>">
                <p><i class="bi bi-calendar-event"></i> Semester Management</p>
            </a>
            <hr>
            <a href="admin_management.php" class="<?php echo ($current_page == 'admin_management.php') ? 'active' : ''; ?>">
                <p><i class="bi bi-file-person-fill"></i> Admin User</p>
            </a>

          </div>
        </div>
      </div>