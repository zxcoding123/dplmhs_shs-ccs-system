<?php

$userId = $_SESSION['user_id'] ?? null;
require_once __DIR__ . '/../app/helpers/ChatHelper.php';
$chatHelper = ChatHelper::fromSession($pdo);
$messagesCount = $chatHelper ? $chatHelper->unreadCount() : 0;

?>




<nav class="navbar navbar-expand navbar-light navbar-bg">
	<a class="sidebar-toggle js-sidebar-toggle">
		<i class="hamburger align-self-center"></i>
	</a>
	<img src="../external/img/dp_logo.avif" class="logo-small" alt="DPLMHS logo">
	<span class="text-white"><b>DPLMHS</b> - Student Management System </span>
	<div class="navbar-collapse collapse">
		<ul class="navbar-nav navbar-align">
			<?php include __DIR__ . '/../includes/notification-bell.php'; ?>

            <li class="nav-item dropdown">

<a class="nav-icon" href="#" data-csms-chat-open aria-label="Messages" title="Messages">
            <div class="position-relative">
                <i class="align-middle" data-feather="message-square"></i>
                <span class="indicator" data-csms-chat-badge<?php echo $messagesCount > 0 ? '' : ' style="display:none"'; ?>><?php echo $messagesCount > 99 ? '99+' : $messagesCount; ?></span>
            </div>
        </a>
</li>


			<li class="nav-item dropdown">
				<a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
					<i class="align-middle" data-feather="settings"></i>
				</a>

				<a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
					<span class="text-light">Student</span>
				</a>
				<div class="dropdown-menu dropdown-menu-end">

					<a class="dropdown-item" href="logout.php">Log out</a>
				</div>
			</li>
		</ul>
	</div>
</nav>

<?php include __DIR__ . '/../includes/floating-chat-widget.php'; ?>
