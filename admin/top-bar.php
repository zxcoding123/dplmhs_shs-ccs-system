<?php
require __DIR__ . '/../database/connection.php';
date_default_timezone_set('Asia/Manila');
$user_id = $_SESSION['user_id'];
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
            <span class="text-light">Admin</span>
        </a>
        <div class="dropdown-menu dropdown-menu-end">


            <a class="dropdown-item" href="processes/admin/account/logout.php">
                Log out
            </a>
        </div>



    </li>
</ul>

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
