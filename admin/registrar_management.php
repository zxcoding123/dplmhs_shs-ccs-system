<?php
session_start();
include __DIR__ . '/../database/connection.php';
require '../app/helpers/auth.php';
requireRole('admin');
require_once __DIR__ . '/includes/ui.php';
require_once __DIR__ . '/../includes/registrar.php';

$registrars = $pdo->query("
    SELECT r.*, u.email, u.status, u.last_login
    FROM registrar_accounts r
    JOIN users u ON u.id = r.user_id
    ORDER BY r.last_name, r.first_name
")->fetchAll(PDO::FETCH_ASSOC);

$activeCount = count(array_filter($registrars, fn($r) => $r['status'] === 'active'));
$genders = ['Male', 'Female', 'Other'];
$csrf = registrar_csrf_token();

function registrar_ago(?string $datetime): string
{
    if (!$datetime) return 'Never';
    $days = (int) floor((time() - strtotime($datetime)) / 86400);
    if ($days < 1) return 'Today, ' . date('g:i A', strtotime($datetime));
    if ($days === 1) return 'Yesterday';
    if ($days < 30) return $days . ' days ago';
    return date('M j, Y', strtotime($datetime));
}

// Shared registrar form fields (create + edit). $r is the account being edited, or null.
function registrar_fields(?array $r, array $genders, string $prefix): void
{
    ?>
    <div class="row g-3">
        <div class="col-md-4">
            <label for="<?= $prefix ?>First" class="form-label">First name</label>
            <input type="text" class="form-control" id="<?= $prefix ?>First" name="first_name" value="<?= admin_e($r['first_name'] ?? '') ?>" maxlength="100" required>
        </div>
        <div class="col-md-4">
            <label for="<?= $prefix ?>Middle" class="form-label">Middle name</label>
            <input type="text" class="form-control" id="<?= $prefix ?>Middle" name="middle_name" value="<?= admin_e($r['middle_name'] ?? '') ?>" maxlength="100">
        </div>
        <div class="col-md-4">
            <label for="<?= $prefix ?>Last" class="form-label">Last name</label>
            <input type="text" class="form-control" id="<?= $prefix ?>Last" name="last_name" value="<?= admin_e($r['last_name'] ?? '') ?>" maxlength="100" required>
        </div>
        <div class="col-md-6">
            <label for="<?= $prefix ?>Email" class="form-label">Email</label>
            <input type="email" class="form-control" id="<?= $prefix ?>Email" name="email" value="<?= admin_e($r['email'] ?? '') ?>" autocomplete="off" required>
        </div>
        <div class="col-md-3">
            <label for="<?= $prefix ?>Phone" class="form-label">Phone <span class="text-muted fw-normal">(optional)</span></label>
            <input type="tel" class="form-control" id="<?= $prefix ?>Phone" name="phone_number" value="<?= admin_e($r['phone_number'] ?? '') ?>" pattern="^\+63\d{10}$" maxlength="13" placeholder="+639xxxxxxxxx">
        </div>
        <div class="col-md-3">
            <label for="<?= $prefix ?>Gender" class="form-label">Gender</label>
            <select class="form-select" id="<?= $prefix ?>Gender" name="gender" required>
                <?php foreach ($genders as $gender): ?>
                    <option value="<?= $gender ?>" <?= ($r['gender'] ?? '') === $gender ? 'selected' : '' ?>><?= $gender ?></option>
                <?php endforeach; ?>
            </select>
        </div>
    </div>
    <?php if ($r): ?>
        <p class="ui-section-label">Change password <span class="text-muted fw-normal text-lowercase">(leave blank to keep the current one)</span></p>
    <?php endif; ?>
    <div class="row g-3 <?= $r ? '' : 'mt-0' ?>">
        <div class="col-md-6">
            <label for="<?= $prefix ?>Password" class="form-label"><?= $r ? 'New password' : 'Password' ?></label>
            <input type="password" class="form-control" id="<?= $prefix ?>Password" name="password" minlength="8" autocomplete="new-password" <?= $r ? '' : 'required' ?>>
            <?php if (!$r): ?><small class="text-muted">At least 8 characters.</small><?php endif; ?>
        </div>
        <div class="col-md-6">
            <label for="<?= $prefix ?>Confirm" class="form-label">Confirm password</label>
            <input type="password" class="form-control" id="<?= $prefix ?>Confirm" name="confirm_password" autocomplete="new-password" data-confirms="#<?= $prefix ?>Password" <?= $r ? '' : 'required' ?>>
        </div>
    </div>
    <?php
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Registrars | DPLMHS-SHS Admin Portal</title>
    <?php include(__DIR__ . '/../includes/favicon-links.php'); ?>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet"
        integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
    <link href="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('css/app.css', __DIR__); ?>" rel="stylesheet">
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
                    <?php admin_page_header('Registrars', 'Staff who keep student records and enrollment in the registrar portal', 'bi-person-vcard',
                        '<button type="button" class="btn btn-ui-gold" data-bs-toggle="modal" data-bs-target="#createRegistrar"><i class="bi bi-person-plus"></i> New registrar</button>'); ?>

                    <?php admin_stat_tiles([
                        ['label' => 'Registrar accounts', 'value' => count($registrars), 'icon' => 'bi-people', 'filter' => ''],
                        ['label' => 'Active', 'value' => $activeCount, 'icon' => 'bi-person-check', 'tone' => 'ok', 'filter' => 'Active', 'filter_col' => 'Status'],
                        ['label' => 'Inactive', 'value' => count($registrars) - $activeCount, 'icon' => 'bi-person-dash', 'tone' => 'muted', 'filter' => 'Inactive', 'filter_col' => 'Status'],
                    ]); ?>

                    <section class="ui-panel">
                        <div class="ui-panel-head">
                            <div>
                                <h2>All registrars</h2>
                                <p>Registrars sign in on the normal login page and land in the registrar portal. Deactivate an account to stop it signing in.</p>
                            </div>
                        </div>
                        <?php if ($registrars): ?>
                            <table id="registrarTable" class="display">
                                <thead>
                                    <tr>
                                        <th>Registrar</th>
                                        <th>Phone</th>
                                        <th>Status</th>
                                        <th>Created</th>
                                        <th>Last sign-in</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($registrars as $r):
                                        $id = (int) $r['id'];
                                        $isActive = $r['status'] === 'active';
                                        $initials = strtoupper(mb_substr($r['first_name'], 0, 1) . mb_substr($r['last_name'], 0, 1)); ?>
                                        <tr>
                                            <td>
                                                <div class="person">
                                                    <span class="person-avatar"><?= admin_e($initials ?: '?') ?></span>
                                                    <div>
                                                        <span class="person-name"><?= admin_e($r['fullName']) ?></span>
                                                        <span class="person-meta"><?= admin_e($r['email']) ?></span>
                                                    </div>
                                                </div>
                                            </td>
                                            <td><?= admin_e($r['phone_number'] ?: '—') ?></td>
                                            <td><span class="ui-pill <?= $isActive ? 'is-ok' : '' ?>"><?= $isActive ? 'Active' : 'Inactive' ?></span></td>
                                            <td data-order="<?= strtotime($r['created_at']) ?>"><?= date('M j, Y', strtotime($r['created_at'])) ?></td>
                                            <td data-order="<?= $r['last_login'] ? strtotime($r['last_login']) : 0 ?>"><?= admin_e(registrar_ago($r['last_login'])) ?></td>
                                            <td>
                                                <div class="ui-acts">
                                                    <button type="button" class="ui-act" data-bs-toggle="modal" data-bs-target="#editRegistrar<?= $id ?>" title="Edit" aria-label="Edit <?= admin_e($r['fullName']) ?>"><i class="bi bi-pencil"></i></button>
                                                    <button type="button" class="ui-act <?= $isActive ? 'is-warn' : 'is-ok' ?>" onclick="setStatus(<?= $id ?>, '<?= $isActive ? 'inactive' : 'active' ?>', <?= admin_e(json_encode($r['fullName'])) ?>)"
                                                        title="<?= $isActive ? 'Deactivate' : 'Activate' ?>" aria-label="<?= $isActive ? 'Deactivate' : 'Activate' ?> <?= admin_e($r['fullName']) ?>">
                                                        <i class="bi <?= $isActive ? 'bi-person-dash' : 'bi-person-check' ?>"></i>
                                                    </button>
                                                    <button type="button" class="ui-act is-danger" onclick="deleteRegistrar(<?= $id ?>, <?= admin_e(json_encode($r['fullName'])) ?>)" title="Delete" aria-label="Delete <?= admin_e($r['fullName']) ?>"><i class="bi bi-trash"></i></button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <div class="ui-empty">
                                <i class="bi bi-person-vcard"></i>
                                <h3>No registrar accounts yet</h3>
                                <p>Create one for the school registrar so they can manage student records and enrollment.</p>
                            </div>
                        <?php endif; ?>
                    </section>
                </div>
            </main>
        </div>
    </div>

    <!-- Create registrar -->
    <div class="modal fade" id="createRegistrar" tabindex="-1" aria-labelledby="createRegistrarLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <form class="modal-content" action="processes/admin/registrars/save.php" method="POST" data-password-form>
                <div class="modal-header">
                    <span class="ui-modal-icon"><i class="bi bi-person-plus"></i></span>
                    <h1 class="modal-title" id="createRegistrarLabel">New registrar</h1>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="csrf" value="<?= admin_e($csrf) ?>">
                    <?php registrar_fields(null, $genders, 'new'); ?>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Create registrar</button>
                </div>
            </form>
        </div>
    </div>

    <?php foreach ($registrars as $r):
        $id = (int) $r['id']; ?>
        <!-- Edit registrar -->
        <div class="modal fade" id="editRegistrar<?= $id ?>" tabindex="-1" aria-labelledby="editRegistrarLabel<?= $id ?>" aria-hidden="true">
            <div class="modal-dialog modal-dialog-centered modal-lg">
                <form class="modal-content" action="processes/admin/registrars/save.php" method="POST" data-password-form>
                    <div class="modal-header">
                        <span class="ui-modal-icon"><i class="bi bi-pencil"></i></span>
                        <h1 class="modal-title" id="editRegistrarLabel<?= $id ?>">Edit <?= admin_e($r['fullName']) ?></h1>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="csrf" value="<?= admin_e($csrf) ?>">
                        <input type="hidden" name="id" value="<?= $id ?>">
                        <?php registrar_fields($r, $genders, 'edit' . $id); ?>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                        <button type="submit" class="btn btn-primary"><i class="bi bi-check2"></i> Save changes</button>
                    </div>
                </form>
            </div>
        </div>
    <?php endforeach; ?>

    <form id="deleteRegistrarForm" method="POST" action="processes/admin/registrars/delete.php" hidden>
        <input type="hidden" name="csrf" value="<?= admin_e($csrf) ?>">
        <input type="hidden" name="id" id="deleteRegistrarId">
    </form>

    <form id="statusForm" method="POST" action="processes/admin/registrars/status.php" hidden>
        <input type="hidden" name="csrf" value="<?= admin_e($csrf) ?>">
        <input type="hidden" name="id" id="statusId">
        <input type="hidden" name="status" id="statusValue">
    </form>

    <script src="<?php require_once __DIR__ . '/../includes/asset-version.php'; echo csms_asset('js/app.js', __DIR__); ?>"></script>
    <script src="<?= admin_asset('js/admin-ui.js') ?>"></script>
    <script>
        $(function () {
            $('#registrarTable').DataTable({
                order: [[0, 'asc']],
                columnDefs: [{ targets: 5, orderable: false, searchable: false }]
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

        function setStatus(id, status, name) {
            Swal.fire({
                title: (status === 'active' ? 'Activate ' : 'Deactivate ') + name + '?',
                text: status === 'active' ? 'They will be able to sign in again.' : 'They will no longer be able to sign in.',
                icon: 'question',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: status === 'active' ? 'Activate' : 'Deactivate'
            }).then(function (result) {
                if (!result.isConfirmed) return;
                document.getElementById('statusId').value = id;
                document.getElementById('statusValue').value = status;
                document.getElementById('statusForm').submit();
            });
        }

        function deleteRegistrar(id, name) {
            Swal.fire({
                title: 'Delete ' + name + '?',
                text: 'Their registrar account is removed for good. Records they entered stay, still showing their name.',
                icon: 'warning',
                showCancelButton: true,
                reverseButtons: true,
                confirmButtonText: 'Delete account',
                customClass: { popup: 'ui-swal-danger' }
            }).then(function (result) {
                if (!result.isConfirmed) return;
                document.getElementById('deleteRegistrarId').value = id;
                document.getElementById('deleteRegistrarForm').submit();
            });
        }
    </script>

    <?php registrar_render_flash(); ?>
    <?php include('processes/server/alerts.php'); ?>
</body>

</html>
