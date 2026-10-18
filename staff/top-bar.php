<?php
require __DIR__ . '/../database/connection.php';

$userId = $_SESSION['user_id'] ?? null;

$queryAll = "SELECT * FROM staff_notifications WHERE user_id = :user_id  ORDER BY id DESC";
$stmtAll = $pdo->prepare($queryAll);
$stmtAll->execute([':user_id' => $userId]);
$notifications = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
$notifCount = count($notifications);


if ($userId) {
	// Fetch unread notifications for the current user, limited to 4
	$queryUnread = "SELECT * FROM staff_notifications WHERE status = 'unread' AND user_id = :user_id ORDER BY id DESC LIMIT 4";
	$stmtUnread = $pdo->prepare($queryUnread);
	$stmtUnread->execute([':user_id' => $userId]);
	$notificationsUnread = $stmtUnread->fetchAll(PDO::FETCH_ASSOC);
	$notificationCount = count($notificationsUnread); // Total number of unread notifications
} else {
	// Handle case when `user_id` is not set
	$notificationsUnread = [];
	$notificationCount = 0;
}

require_once __DIR__ . '/../app/helpers/ChatHelper.php';
$chatHelper = ChatHelper::fromSession($pdo);
$messagesCount = $chatHelper ? $chatHelper->unreadCount() : 0;
?>



<ul class="navbar-nav navbar-align">
	<li class="nav-item dropdown">
		<a class="nav-icon dropdown-toggle" href="#" id="alertsDropdown" data-bs-toggle="dropdown">
			<div class="position-relative">
				<i class="align-middle" data-feather="bell"></i>

				<?php if ($notificationCount > 0) { ?>
					<span class="indicator"><?php echo $notificationCount; ?></span>
				<?php } ?>
			</div>
		</a>
		<div class="dropdown-menu dropdown-menu-lg dropdown-menu-end py-0" aria-labelledby="alertsDropdown">
			<div class="dropdown-menu-header">
				<?php if ($notificationCount > 0) { ?>
					<?php echo $notificationCount; ?> New Notifications
					<div class="dropdown-item text-center">
						<a href="processes/teachers/notifications/read_all.php"><button id="readAll"
								class="btn btn-link">Read All</button></a>
						<a href="processes/teachers/notifications/delete_all.php">
							<button id="deleteAll" class="btn btn-link text-danger">Delete All</button>
						</a>
					</div>
				<?php } else { ?>
					<?php echo "No new notifications" ?>
				<?php } ?>
			</div>
			<div class="list-group">
				<?php foreach ($notificationsUnread as $notification): ?>
					<div class="list-group-item">
						<div class="row g-0 align-items-center">
							<div class="col-2">
								<h1 class="bi bi-info-circle-fill"></h1>
							</div>

							<div class="col-8">
								<a href="<?php echo $notification['link'] ?>">
									<div class="text-dark"><?php echo htmlspecialchars($notification['title']); ?></div>
									<div class="text-muted small mt-1">
										<?php echo htmlspecialchars($notification['description']); ?>
									</div>
									<div class="text-muted small mt-1">
										<?php echo htmlspecialchars($notification['date']); ?>
									</div>
								</a>
							</div>

							<div class="col-2 text-end">
								<form action="processes/teachers/notifications/read.php" method="POST" class="d-inline">
									<input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
									<button type="submit" class="btn btn-link p-0">Read</button>
								</form>
								<form action="processes/teachers/notifications/delete.php" method="POST" class="d-inline">
									<input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
									<button type="submit" class="btn btn-link text-danger p-0">Delete</button>
								</form>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>
			<?php if ($notifCount > 0) { ?>
				<div class="dropdown-menu-footer">
					<a href="#" class="text-muted" data-bs-toggle="modal" data-bs-target="#notificationsModal">Show all
						notifications</a>
				</div>
			<?php } ?>
		</div>
	</li>


	<li class="nav-item dropdown">
		<a class="nav-icon" href="#" data-csms-chat-open aria-label="Messages" title="Messages">
            <div class="position-relative">
                <i class="align-middle" data-feather="message-square"></i>
                <span class="indicator" data-csms-chat-badge<?php echo $messagesCount > 0 ? '' : ' style="display:none"'; ?>><?php echo $messagesCount > 99 ? '99+' : $messagesCount; ?></span>
            </div>
        </a>
	</li>


	<div class="modal fade" id="notificationsModal" tabindex="-1" aria-labelledby="notificationsModalLabel"
		aria-hidden="true">
		<div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
			<div class="modal-content">
				<div class="modal-header">
					<h5 class="modal-title" id="notificationsModalLabel">All Notifications</h5>
					<button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
				</div>
				<div class="modal-body">
					<div class="list-group">
						<?php foreach ($notifications as $notification): ?>
							<div class="list-group-item list-group-item-action">
							<a href="<?php echo $notification['link'] ?>" style="text-decoration: none">
									<div class="row d-flex justify-content-center align-items-center">
										<div class="col text-center ">
											<h1 class="bi bi-info-circle-fill"></h1>
										</div>
										<div class="col-8">
											<div class="text-dark"><?php echo htmlspecialchars($notification['title']); ?>
											</div>
											<div class="text-muted small mt-1">
												<?php echo htmlspecialchars($notification['description']); ?>
											</div>
											<div class="text-muted small mt-1">
												<?php echo htmlspecialchars($notification['date']); ?>
											</div>
										</div>
										<div class="col-2 text-end">
											<?php if ($notification['status'] == 'unread') { ?>
												<form action="processes/teachers/notifications/read.php" method="POST"
													class="d-inline">
									  
													<input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
													<button type="submit" class="btn btn-link p-0">Read</button>
												</form>
											<?php } ?>
											<form action=""processes/teachers/notifications/delete.php" method="POST"
												class="d-inline">
												<input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
												<button type="submit" class="btn btn-link p-0">Delete</button>
											</form>
										</div>
									</div>
								</a>
							</div>
						<?php endforeach; ?>
					</div>
				</div>
				<div class="modal-footer">
					<button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>

					<a href="processes/teachers/notifications/delete_all.php"><button type="button"
							class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAllModal">Delete
							All</button></a>
					<a href="processes/teachers/notifications/read_all.php"><button type="button" class="btn btn-info"
							data-bs-toggle="modal" data-bs-target="#readAllModal">Read
							All</button></a>

				</div>
			</div>
		</div>
	</div>

    <style>
        .linkism{
            border: 1px solid grey;
            border-top-left-radius: 5px;
            border-top-right-radius: 5px;
            margin-right: 5px;
            color: black;
        }
        .active{
        color: grey !important;
        }
      
    </style>
    
	<li class="nav-item dropdown">
		<a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
			<i class="align-middle" data-feather="settings"></i>
		</a>

		<a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
			<span class="text-light">Staff</span>
		</a>
		<div class="dropdown-menu dropdown-menu-end">


			<a class="dropdown-item" href="processes/teachers/account/logout.php">
				Log out
			</a>
		</div>



	</li>
</ul>

<?php include __DIR__ . '/../includes/floating-chat-widget.php'; ?>
