<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php';
requireRole('admin');
require_once __DIR__ . '/includes/ui.php';

$admins = $pdo->query("SELECT * FROM admin ORDER BY first_name, last_name")->fetchAll(PDO::FETCH_ASSOC);
$currentAdminId = (int) ($_SESSION['admin_id'] ?? 0);

$activeCount = count(array_filter($admins, fn($a) => strcasecmp((string) $a['status'], 'Active') === 0));
$recentLogins = count(array_filter($admins, fn($a) => $a['last_login'] && strtotime($a['last_login']) >= strtotime('-30 days')));
$genders = ['Male', 'Female', 'Other'];

function admin_ago(?string $datetime): string
{
    if (!$datetime) return 'Never';
    $days = (int) floor((time() - strtotime($datetime)) / 86400);
    if ($days < 1) return 'Today, ' . date('g:i A', strtotime($datetime));
    if ($days === 1) return 'Yesterday';
    if ($days < 30) return $days . ' days ago';
    return date('M j, Y', strtotime($datetime));
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admin Users | DPLMHS-SHS Admin Portal</title>
    <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="css/app.css" rel="stylesheet">
    <link href="<?= admin_asset('css/admin-ui.css') ?>" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.11.3/css/jquery.dataTables.min.css">
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.datatables.net/1.11.3/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .person { display: flex; align-items: center; gap: 0.65rem; }
        .person-avatar { display: inline-flex; align-items: center; justify-content: center; flex-shrink: 0; width: 2.2rem; height: 2.2rem; border-radius: 50%; background: var(--ui-navy); color: var(--ui-gold-light); font-size: 0.8rem; font-weight: 700; }
        .person-name { display: block; font-weight: 700; color: var(--ui-navy); }
        .person-meta { display: block; font-size: 0.8rem; color: var(--ui-muted); }
    </style>
</head>

<body>
    <div class="wrapper">
        <?php include('sidebar.php'); ?>

        <div class="main">
            <nav class="navbar navbar-expand navbar-light navbar-bg">
                <a class="sidebar-toggle js-sidebar-toggle">
                    <i class="hamburger align-self-center"></i>
                </a>
                <img src="../external/img/dp_logo.avif" class="logo-small" alt="DPLMHS logo">
                <span class="text-white d-none d-md-inline"><b>DPLMHS</b> - Student Management System</span>
                <div class="navbar-collapse collapse">
                    <?php include('top-bar.php'); ?>
                </div>
            </nav>

            <main class="content">
                <div class="container-fluid p-0">
                    <?php admin_page_header('Admin Users', 'People who can sign in to this administration portal', 'bi-shield-lock',
                        '<button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#createAdmin"><i class="bi bi-person-plus"></i> New admin</button>'); ?>

                    <?php admin_stat_tiles([
                        ['label' => 'Admin accounts', 'value' => count($admins), 'icon' => 'bi-people', 'filter' => ''],
                        ['label' => 'Active', 'value' => $activeCount, 'icon' => 'bi-person-check', 'tone' => 'ok', 'filter' => 'Active', 'filter_col' => 'Status'],
                        ['label' => 'Inactive', 'value' => count($admins) - $activeCount, 'icon' => 'bi-person-dash', 'tone' => 'muted', 'filter' => 'Inactive', 'filter_col' => 'Status'],
                        ['label' => 'Signed in (30 days)', 'value' => $recentLogins, 'icon' => 'bi-box-arrow-in-right'],
                    ]); ?>

                    <section class="ui-panel">
                        <div class="ui-panel-head">
                            <div>
                                <h2>All administrators</h2>
                                <p>You can't deactivate or delete your own account while signed in.</p>
                            </div>
                        </div>
                        <table id="adminTable" class="display">
                            <thead>
                                <tr>
                                    <th>Administrator</th>
                                    <th>Phone</th>
                                    <th>Gender</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th>Last sign-in</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($admins as $admin):
                                    $id = (int) $admin['id'];
                                    $isActive = strcasecmp((string) $admin['status'], 'Active') === 0;
                                    $isSelf = $id === $currentAdminId;
                                    $name = trim($admin['first_name'] . ' ' . $admin['last_name']);
                                    $initials = strtoupper(mb_substr($admin['first_name'], 0, 1) . mb_substr($admin['last_name'], 0, 1)); ?>
                                    <tr>
                                        <td>
                                            <div class="person">
                                                <span class="person-avatar"><?= admin_e($initials ?: '?') ?></span>
                                                <div>
                                                    <span class="person-name"><?= admin_e($name) ?><?= $isSelf ? ' <span class="ui-tag">You</span>' : '' ?></span>
                                                    <span class="person-meta"><?= admin_e($admin['email']) ?></span>
                                                </div>
                                            </div>
                                        </td>
                                        <td><?= admin_e($admin['phone_number'] ?: '—') ?></td>
                                        <td><?= admin_e(ucfirst(strtolower((string) $admin['gender'])) ?: '—') ?></td>
                                        <td><span class="ui-pill <?= $isActive ? 'is-ok' : '' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></td>
                                        <td data-order="<?= strtotime($admin['date_created']) ?>"><?= date('M j, Y', strtotime($admin['date_created'])) ?></td>
                                        <td data-order="<?= $admin['last_login'] ? strtotime($admin['last_login']) : 0 ?>"><?= admin_e(admin_ago($admin['last_login'])) ?></td>
                                        <td>
                                            <div class="ui-acts">
                                                <button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#editAdmin<?= $id ?>" title="Edit" aria-label="Edit"><i class="bi bi-pencil"></i></button>
                                                <?php if (!$isSelf): ?>
                                                    <button type="button" class="ui-act <?= $isActive ? 'is-warn' : 'is-ok' ?>" onclick="updateStatus(<?= $id ?>, '<?= $isActive ? 'Inactive' : 'Active' ?>', <?= admin_e(json_encode($name)) ?>)"
                                                        title="<?= $isActive ? 'Deactivate' : 'Activate' ?>" aria-label="<?= $isActive ? 'Deactivate' : 'Activate' ?>">
                                                        <i class="bi <?= $isActive ? 'bi-person-dash' : 'bi-person-check' ?>"></i>
                                                    </button>
                                                    <button type="button" class="ui-act is-danger" onclick="confirmDelete(<?= $id ?>, <?= admin_e(json_encode($name)) ?>)" title="Delete" aria-label="Delete"><i class="bi bi-trash"></i></button>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <!-- Create admin -->
    <div class="modal fade" id="createAdmin" tabindex="-1" aria-labelledby="createAdminLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" action="processes/admin/account/add.php" method="POST" data-password-form>
                <div class="modal-header">
                    <span class="ui-modal-icon"><i class="bi bi-person-plus"></i></span>
                    <h1 class="modal-title" id="createAdminLabel">New administrator</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="newFirst" class="form-label">First name</label>
                            <input type="text" class="form-control" id="newFirst" name="first_name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="newMiddle" class="form-label">Middle name</label>
                            <input type="text" class="form-control" id="newMiddle" name="middle_name">
                        </div>
                        <div class="col-md-4">
                            <label for="newLast" class="form-label">Last name</label>
                            <input type="text" class="form-control" id="newLast" name="last_name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="newEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="newEmail" name="email" autocomplete="off" required>
                        </div>
                        <div class="col-md-3">
                            <label for="newPhone" class="form-label">Phone</label>
                            <input type="tel" class="form-control" id="newPhone" name="phone_number" value="+63" pattern="^\+63\d{10}$" maxlength="13" placeholder="+639xxxxxxxxx" required>
                        </div>
                        <div class="col-md-3">
                            <label for="newGender" class="form-label">Gender</label>
                            <select class="form-select" id="newGender" name="gender" required>
                                <?php foreach ($genders as $gender): ?><option value="<?= $gender ?>"><?= $gender ?></option><?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="newPassword" class="form-label">Password</label>
                            <input type="password" class="form-control" id="newPassword" name="password" minlength="8" autocomplete="new-password" required>
                            <small class="text-muted">At least 8 characters.</small>
                        </div>
                        <div class="col-md-6">
                            <label for="newConfirm" class="form-label">Confirm password</label>
                            <input type="password" class="form-control" id="newConfirm" name="confirm_password" minlength="8" autocomplete="new-password" required data-confirms="#newPassword">
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Create admin</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Edit admin (one per account) -->
    <?php foreach ($admins as $admin):
        $id = (int) $admin['id'];
        $storedGender = ucfirst(strtolower((string) $admin['gender'])); ?>
        <div class="modal fade" id="editAdmin<?= $id ?>" tabindex="-1" aria-labelledby="editAdminLabel<?= $id ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form class="modal-content" action="processes/admin/account/edit.php" method="POST" data-password-form>
                    <div class="modal-header">
                        <span class="ui-modal-icon"><i class="bi bi-pencil"></i></span>
                        <h1 class="modal-title" id="editAdminLabel<?= $id ?>">Edit <?= admin_e(trim($admin['first_name'] . ' ' . $admin['last_name'])) ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label for="first_name<?= $id ?>" class="form-label">First name</label>
                                <input type="text" class="form-control" id="first_name<?= $id ?>" name="first_name" value="<?= admin_e($admin['first_name']) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="middle_name<?= $id ?>" class="form-label">Middle name</label>
                                <input type="text" class="form-control" id="middle_name<?= $id ?>" name="middle_name" value="<?= admin_e($admin['middle_name']) ?>">
                            </div>
                            <div class="col-md-4">
                                <label for="last_name<?= $id ?>" class="form-label">Last name</label>
                                <input type="text" class="form-control" id="last_name<?= $id ?>" name="last_name" value="<?= admin_e($admin['last_name']) ?>" required>
                            </div>
                            <div class="col-md-6">
                                <label for="email<?= $id ?>" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email<?= $id ?>" name="email" value="<?= admin_e($admin['email']) ?>" required>
                            </div>
                            <div class="col-md-3">
                                <label for="phone_number<?= $id ?>" class="form-label">Phone</label>
                                <input type="tel" class="form-control" id="phone_number<?= $id ?>" name="phone_number" value="<?= admin_e($admin['phone_number']) ?>">
                            </div>
                            <div class="col-md-3">
                                <label for="gender<?= $id ?>" class="form-label">Gender</label>
                                <select class="form-select" id="gender<?= $id ?>" name="gender" required>
                                    <?php foreach ($genders as $gender): ?>
                                        <option value="<?= $gender ?>" <?= $storedGender === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <p class="ui-section-label">Change password <span class="text-muted fw-normal text-lowercase">(leave blank to keep the current one)</span></p>
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label for="password<?= $id ?>" class="form-label">New password</label>
                                <input type="password" class="form-control" id="password<?= $id ?>" name="password" minlength="8" autocomplete="new-password">
                            </div>
                            <div class="col-md-6">
                                <label for="confirm_password<?= $id ?>" class="form-label">Confirm new password</label>
                                <input type="password" class="form-control" id="confirm_password<?= $id ?>" name="confirm_password" autocomplete="new-password" data-confirms="#password<?= $id ?>">
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <script src="js/app.js"></script>
    <script src="<?= admin_asset('js/admin-ui.js') ?>"></script>
    <script>
        $(function () {
            $('#adminTable').DataTable({
                order: [[0, 'asc']],
                columnDefs: [{ targets: 6, orderable: false, searchable: false }]
            });
        });

        // Password and confirmation must match before the form is sent
        document.querySelectorAll('form[data-password-form]').forEach(function (form) {
            form.addEventListener('submit', function (e) {
                const confirm = form.querySelector('[data-confirms]');
                const password = form.querySelector(confirm.dataset.confirms);
                if (password.value !== confirm.value) {
                    e.preventDefault();
                    Swal.fire('Passwords don\'t match', 'Type the same password in both fields.', 'warning');
                }
            });
        });

        function updateStatus(adminId, newStatus, name) {
            Swal.fire({
                title: (newStatus === 'Active' ? 'Activate ' : 'Deactivate ') + name + '?',
                text: newStatus === 'Active' ? 'They will be able to sign in again.' : 'They will no longer be able to sign in.',
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: newStatus === 'Active' ? 'Activate' : 'Deactivate'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                fetch('update_admin_status.php', { method: 'POST', body: new URLSearchParams({ admin_id: adminId, new_status: newStatus }) })
                    .then(function (response) {
                        if (!response.ok) return response.text().then(function (t) { throw new Error(t); });
                        location.reload();
                    })
                    .catch(function (error) { Swal.fire('Error', error.message || 'The status could not be changed.', 'error'); });
            });
        }

        function confirmDelete(adminId, name) {
            Swal.fire({
                title: 'Delete ' + name + '?',
                text: 'This admin account will be removed permanently.',
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Delete account',
                customClass: { popup: 'ui-swal-danger' }
            }).then(function (result) {
                if (result.isConfirmed) window.location.href = 'processes/admin/account/delete_admin.php?id=' + adminId;
            });
        }
    </script>

    <?php
    $adminAlerts = [
        'ACCOUNT_C_SUCCESSFUL' => ['Account created', 'The admin account has been created.', 'success'],
        'ADMIN_DUPLICATE_ACCOUNT' => ['Account already exists', 'An admin with this email already exists.', 'error'],
        'DUPLICATE_ACCOUNT' => ['Email already in use', 'Another admin already uses this email address.', 'error'],
        'ADMIN_DELETED_SUCCESS' => ['Account deleted', 'The admin account has been deleted.', 'success'],
        'ADMIN_DELETED_FAILED' => ['Error', 'The admin account could not be deleted.', 'error'],
        'ADMIN_SELF_DELETE' => ['Not allowed', "You can't delete the account you're signed in with.", 'warning'],
        'ADMIN_EDIT_SUCCESSFUL' => ['Saved', 'The admin account has been updated.', 'success'],
        'ADMIN_EDIT_SUCCESFUL' => ['Saved', 'The admin account has been updated.', 'success'],
        'ADMIN_CREATE_FAILED' => ['Error', 'The admin account could not be created. Please try again.', 'error'],
        'ADMIN_NEW_STATUS_SUCCESFUL' => ['Status updated', 'The admin account status has been changed.', 'success'],
        'INVALID_EMAIL' => ['Invalid email', 'Enter a valid email address.', 'error'],
        'MISSING_REQUIRED_FIELDS' => ['Missing details', 'First name, last name and password are required.', 'error'],
    ];
    if (isset($_SESSION['STATUS'], $adminAlerts[$_SESSION['STATUS']])) {
        [$title, $text, $icon] = $adminAlerts[$_SESSION['STATUS']];
        echo '<script>Swal.fire(' . json_encode($title) . ', ' . json_encode($text) . ', ' . json_encode($icon) . ');</script>';
        unset($_SESSION['STATUS']);
    }
    ?>
    <?php include('processes/server/alerts.php'); ?>
</body>

</html>
