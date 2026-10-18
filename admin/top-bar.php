<?php
require __DIR__ . '/../database/connection.php';
date_default_timezone_set('Asia/Manila');
$user_id = $_SESSION['user_id'];
$query = "SELECT * FROM admin_notifications ORDER BY id DESC ";
$stmt = $pdo->prepare($query);
$stmt->execute();
$notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
$notifCount = count($notifications);


$query = "SELECT * FROM admin_notifications WHERE status = 'unread' ORDER BY id DESC LIMIT 5";
$stmt = $pdo->prepare($query);
$stmt->execute();
$notificationsUnread = $stmt->fetchAll(PDO::FETCH_ASSOC);
$notificationCount = count($notificationsUnread);

require_once __DIR__ . '/../app/helpers/ChatHelper.php';
$chatHelper = ChatHelper::fromSession($pdo);
$messagesCount = $chatHelper ? $chatHelper->unreadCount() : 0;

?>


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
                        <a href="processes/admin/notifications/read_all.php">
                            <button id="readAll" class="btn btn-link">Read All</button></a>
                        <a href="processes/admin/notifications/delete_all.php">
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
                                <a href="https://ccs-sms.com/<?php echo $notification['link'] ?>">
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
                                <form action="processes/admin/notifications/read.php" method="POST" class="d-inline">
                                    <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                    <button type="submit" class="btn btn-link p-0">Read</button>
                                </form>
                                <form action="processes/admin/notifications/delete.php" method="POST" class="d-inline">
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



    <li class="nav-item dropdown">
        <a class="nav-icon dropdown-toggle d-inline-block d-sm-none" href="#" data-bs-toggle="dropdown">
            <i class="align-middle" data-feather="settings"></i>
        </a>

        <a class="nav-link dropdown-toggle d-none d-sm-inline-block" href="#" data-bs-toggle="dropdown">
            <span class="text-light">Admin</span>
        </a>
        <div class="dropdown-menu dropdown-menu-end">


            <a class="dropdown-item" href="processes/admin/account/logout.php">
                Log out
            </a>
        </div>



    </li>
</ul>

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
                                        <div class="text-dark"><?php echo htmlspecialchars($notification['title']); ?></div>
                                        <div class="text-muted small mt-1">
                                            <?php echo htmlspecialchars($notification['description']); ?>
                                        </div>
                                        <div class="text-muted small mt-1">
                                            <?php echo htmlspecialchars($notification['date']); ?>
                                        </div>
                                    </div>
                                    <div class="col-2 text-end">
                                        <?php if ($notification['status'] == 'unread') { ?>
                                            <form action="processes/admin/notifications/read.php" method="POST"
                                                class="d-inline">
                                                <input type="hidden" name="id" value="<?php echo $notification['id']; ?>">
                                                <button type="submit" class="btn btn-link p-0">Read</button>
                                            </form>
                                        <?php } ?>
                                        <form action="processes/admin/notifications/delete.php" method="POST"
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
                <button type="button" class="btn btn-danger" data-bs-toggle="modal"
                    data-bs-target="#deleteAllModal">Delete All</button>

                <button type="button" class="btn btn-info" data-bs-toggle="modal" data-bs-target="#readAllModal">Read
                    All</button>

            </div>
        </div>
    </div>
</div>

<!-- Delete All Confirmation Modal -->
<div class="modal fade" id="deleteAllModal" tabindex="-1" aria-labelledby="deleteAllModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteAllModalLabel">Delete All Notifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete all notifications?
            </div>
            <div class="modal-footer">
                <form action="processes/admin/notifications/delete_all.php" method="POST">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-danger">Delete All</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Read All Confirmation Modal -->
<div class="modal fade" id="readAllModal" tabindex="-1" aria-labelledby="readAllModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="readAllModalLabel">Read All Notifications</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to mark all notifications as read?
            </div>
            <div class="modal-footer">
                <form action="processes/admin/notifications/read_all.php" method="POST">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-info">Read All</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Profile Modal -->
<div class="modal fade" id="profileModal" tabindex="-1" aria-labelledby="profileModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="profileModalLabel">Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Profile content here -->
                <p>Your profile information...</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>




<script>
    function gotoLink() {
        window.location.href = "processes/server/reset_notifications.php";
    }

    $(document).ready(function() {
        // Set all notifications to false when button is clicked
        $('#setAllFalse').on('click', function() {
            $.ajax({
                url: 'process_notifications.php', // Change this to your PHP file
                type: 'POST',
                data: {
                    action: 'set_all_false'
                },
                success: function(response) {
                    alert('All notifications set to false!');
                }
            });
        });

        // Handle setting notifications individually
        $('#setIndividually').on('click', function() {
            $.ajax({
                url: 'process_notifications.php', // Change this to your PHP file
                type: 'POST',
                data: {
                    action: 'set_individual'
                },
                success: function(response) {
                    alert('Manage notifications individually.');
                }
            });
        });
    });
</script>

<?php include __DIR__ . '/../includes/floating-chat-widget.php'; ?>
