<?php
$current_page = basename($_SERVER['PHP_SELF']);
$teacher_id = $_SESSION['teacher_id'];

try {
    $stmt = $pdo->prepare("SELECT * FROM staff_accounts WHERE id = :teacher_id");
    $stmt->execute(['teacher_id' => $teacher_id]);
    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$teacher) {
        echo "Teacher not found.";
        exit;
    }

    $firstName = $teacher['first_name'];
    $middleName = $teacher['middle_name'];
    $lastName = $teacher['last_name'];

    $fullName = $teacher['fullName'];
    $email = $teacher['email'];
    $department = $teacher['department'];
    $phone_number = $teacher['phone_number'];
    $gender = $teacher['gender'];
    $avatar = $teacher['avatar'] ?? '';
    $avatarUrl = '';
    if (!empty($avatar)) {
        // The file name stays the same when the picture changes, so bust the browser cache
        $avatarPath = __DIR__ . '/../uploads/profile_pictures/' . $avatar;
        $avatarUrl = '../uploads/profile_pictures/' . rawurlencode($avatar) . (is_file($avatarPath) ? '?v=' . filemtime($avatarPath) : '');
    }
} catch (PDOException $e) {
    echo "An error occurred while fetching the teacher's data: " . $e->getMessage();
}
?>

<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="au-portal-brand" href="index.php">
            <img src="../external/img/dp_logo.avif" alt="Don Pablo Lorenzo Memorial High School seal">
            <span><strong>DPLMHS</strong><small>SHS Faculty Portal</small></span>
        </a>

        <div class="au-portal-user">
            <img src="<?php echo !empty($avatarUrl) ? htmlspecialchars($avatarUrl) : '../external/img/AU_CCS_Logo.png'; ?>" class="au-portal-avatar" alt="">
            <h1 class="au-portal-name"><?php echo htmlspecialchars(!empty($fullName) ? $fullName : 'Faculty Member'); ?></h1>
            <span class="au-portal-role">Faculty</span>
            <?php if (!empty($department)): ?>
                <span class="au-portal-clock"><?php echo htmlspecialchars($department); ?></span>
            <?php endif; ?>
            <span class="au-portal-clock time" id="currentTime"></span>
            <div class="au-portal-actions">
                <button type="button" class="btn btn-csms" data-bs-toggle="modal" data-bs-target="#editStaffModal">
                    <i class="bi bi-pencil-square"></i> Edit Profile
                </button>
                <button type="button" class="btn btn-csms" data-bs-toggle="modal" data-bs-target="#viewStaffModal">
                    <i class="bi bi-person-vcard"></i> View
                </button>
            </div>
        </div>

        <ul class="sidebar-nav">
            <li class="sidebar-header">Overview</li>
            <li class="sidebar-item <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="index.php">
                    <i class="bi bi-speedometer2 align-middle"></i> <span class="align-middle">Dashboard</span>
                </a>
            </li>

            <li class="sidebar-header">Teaching</li>
            <li class="sidebar-item <?php echo ($current_page == 'class_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="class_management.php">
                    <i class="bi bi-people align-middle"></i> <span class="align-middle">Class Management</span>
                </a>
            </li>
            <li class="sidebar-item <?php echo ($current_page == 'subject_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="subject_management.php">
                    <i class="bi bi-journal-bookmark align-middle"></i> <span class="align-middle">Subject Management</span>
                </a>
            </li>

            <li class="sidebar-header">Account</li>
            <li class="sidebar-item">
                <a class="sidebar-link" href="#" data-bs-toggle="modal" data-bs-target="#viewStaffModal">
                    <i class="bi bi-person-badge align-middle"></i> <span class="align-middle">My Profile</span>
                </a>
            </li>
        </ul>

        <div class="au-portal-foot">
            <a href="../index.php" target="_blank"><i class="bi bi-globe2"></i> View public website</a>
            <a href="processes/teachers/account/logout.php"><i class="bi bi-box-arrow-right"></i> Sign out</a>
        </div>
    </div>
</nav>

<?php
$profileInitials = strtoupper(substr((string) $firstName, 0, 1) . substr((string) $lastName, 0, 1));
$departmentOptions = ['Department of Information Technology', 'Department of Computer Science'];
if (!empty($department) && !in_array($department, $departmentOptions, true)) {
    // Keep a department set elsewhere (e.g. by an admin) selectable
    array_unshift($departmentOptions, $department);
}
$genderOptions = ['Male', 'Female', 'Other'];
?>
<!-- Modal for Editing Staff Information -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable modal-lg">
        <form class="modal-content au-profile" method="POST" action="update_details.php" enctype="multipart/form-data">
            <div class="au-profile-banner">
                <button type="button" class="btn-close btn-close-white au-profile-close" data-bs-dismiss="modal" aria-label="Close"></button>

                <label class="au-profile-avatar-edit" for="profileAvatarInput" title="Change profile picture">
                    <?php if (!empty($avatar)): ?>
                        <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="" class="au-profile-avatar" id="profileAvatarPreview">
                    <?php else: ?>
                        <img src="" alt="" class="au-profile-avatar d-none" id="profileAvatarPreview">
                        <span class="au-profile-avatar au-profile-initials" id="profileAvatarInitials" aria-hidden="true"><?php echo htmlspecialchars($profileInitials ?: '?'); ?></span>
                    <?php endif; ?>
                    <span class="au-profile-avatar-badge"><i class="bi bi-camera-fill" aria-hidden="true"></i></span>
                    <span class="visually-hidden">Change profile picture</span>
                </label>
                <input type="file" class="d-none" id="profileAvatarInput" name="avatar" accept="image/jpeg,image/png,image/gif,image/webp">

                <h5 class="au-profile-name" id="editStaffModalLabel">Edit Profile</h5>
                <p class="au-profile-hint" id="profileAvatarHint">Click the photo to upload a new one (JPG, PNG, GIF or WEBP, max 2 MB)</p>
            </div>

            <div class="modal-body au-profile-body">
                <fieldset class="au-profile-section">
                    <legend><i class="bi bi-person" aria-hidden="true"></i> Personal Information</legend>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label for="profileFirstName" class="form-label">First Name</label>
                            <input type="text" class="form-control" id="profileFirstName" name="firstName"
                                value="<?php echo htmlspecialchars($firstName); ?>" autocomplete="given-name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="profileMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="profileMiddleName" name="middleName"
                                value="<?php echo htmlspecialchars($middleName); ?>" autocomplete="additional-name" required>
                        </div>
                        <div class="col-md-4">
                            <label for="profileLastName" class="form-label">Last Name</label>
                            <input type="text" class="form-control" id="profileLastName" name="lastName"
                                value="<?php echo htmlspecialchars($lastName); ?>" autocomplete="family-name" required>
                        </div>
                        <div class="col-md-6">
                            <label for="profileGender" class="form-label">Gender</label>
                            <select class="form-select" id="profileGender" name="gender" required>
                                <?php foreach ($genderOptions as $option): ?>
                                    <option value="<?php echo $option; ?>" <?php echo ($gender === $option) ? 'selected' : ''; ?>><?php echo $option; ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label for="profileDepartment" class="form-label">Department</label>
                            <select class="form-select" id="profileDepartment" name="department" required>
                                <?php foreach ($departmentOptions as $option): ?>
                                    <option value="<?php echo htmlspecialchars($option); ?>" <?php echo ($department === $option) ? 'selected' : ''; ?>><?php echo htmlspecialchars($option); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="au-profile-section">
                    <legend><i class="bi bi-telephone" aria-hidden="true"></i> Contact</legend>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label for="profileEmail" class="form-label">Email</label>
                            <input type="email" class="form-control" id="profileEmail" name="email"
                                value="<?php echo htmlspecialchars($email); ?>" autocomplete="email" required>
                        </div>
                        <div class="col-md-6">
                            <label for="profilePhone" class="form-label">Phone Number</label>
                            <input type="tel" class="form-control" id="profilePhone" name="phone_number"
                                value="<?php echo htmlspecialchars($phone_number); ?>" autocomplete="tel" required>
                        </div>
                    </div>
                </fieldset>

                <fieldset class="au-profile-section">
                    <legend><i class="bi bi-shield-lock" aria-hidden="true"></i> Security</legend>
                    <label for="profilePassword" class="form-label">New Password</label>
                    <div class="input-group">
                        <input type="password" class="form-control" id="profilePassword" name="password"
                            autocomplete="new-password" placeholder="Leave blank to keep your current password">
                        <button type="button" class="btn btn-outline-secondary" id="profilePasswordToggle" aria-label="Show password">
                            <i class="bi bi-eye" aria-hidden="true"></i>
                        </button>
                    </div>
                    <div class="form-text">This also changes the password you log in with.</div>
                </fieldset>
            </div>

            <div class="modal-footer au-profile-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-csms"><i class="bi bi-check2-circle"></i> Save Changes</button>
            </div>
        </form>
    </div>
</div>

<script>
    (function () {
        var input = document.getElementById('profileAvatarInput');
        var preview = document.getElementById('profileAvatarPreview');
        var initials = document.getElementById('profileAvatarInitials');
        var hint = document.getElementById('profileAvatarHint');
        var hintText = hint.textContent;

        // Show the chosen picture right away
        input.addEventListener('change', function () {
            var file = input.files[0];
            if (!file) return;
            if (!/^image\/(jpeg|png|gif|webp)$/.test(file.type) || file.size > 2 * 1024 * 1024) {
                input.value = '';
                hint.textContent = 'Please choose a JPG, PNG, GIF or WEBP image no larger than 2 MB.';
                return;
            }
            preview.src = URL.createObjectURL(file);
            preview.classList.remove('d-none');
            if (initials) initials.classList.add('d-none');
            hint.textContent = 'New photo selected: ' + file.name;
        });

        var password = document.getElementById('profilePassword');
        var toggle = document.getElementById('profilePasswordToggle');
        toggle.addEventListener('click', function () {
            var show = password.type === 'password';
            password.type = show ? 'text' : 'password';
            toggle.setAttribute('aria-label', show ? 'Hide password' : 'Show password');
            toggle.querySelector('i').className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
        });

        // Reset unsaved changes when the modal is closed
        document.getElementById('editStaffModal').addEventListener('hidden.bs.modal', function () {
            this.querySelector('form').reset();
            password.type = 'password';
            toggle.querySelector('i').className = 'bi bi-eye';
            hint.textContent = hintText;
            <?php if (!empty($avatar)): ?>
            preview.src = <?php echo json_encode($avatarUrl, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT); ?>;
            <?php else: ?>
            preview.classList.add('d-none');
            initials.classList.remove('d-none');
            <?php endif; ?>
        });
    })();
</script>


<?php
$profileInitials = strtoupper(substr((string) $firstName, 0, 1) . substr((string) $lastName, 0, 1));
$profileStatus = strtolower(trim((string) ($teacher['status'] ?? '')));
$profileSince = !empty($teacher['date_created']) ? date('F j, Y', strtotime($teacher['date_created'])) : '';
$profileDetails = [
    ['icon' => 'bi-envelope', 'label' => 'Email', 'value' => $email, 'href' => 'mailto:' . $email],
    ['icon' => 'bi-telephone', 'label' => 'Phone Number', 'value' => $phone_number, 'href' => 'tel:' . preg_replace('/[^\d+]/', '', (string) $phone_number)],
    ['icon' => 'bi-building', 'label' => 'Department', 'value' => $department],
    ['icon' => 'bi-gender-ambiguous', 'label' => 'Gender', 'value' => $gender],
    ['icon' => 'bi-person-lines-fill', 'label' => 'Full Name', 'value' => trim($firstName . ' ' . $middleName . ' ' . $lastName)],
    ['icon' => 'bi-calendar-check', 'label' => 'Member Since', 'value' => $profileSince],
];
?>
<div class="modal fade" id="viewStaffModal" tabindex="-1" aria-labelledby="viewStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content au-profile">
            <div class="au-profile-banner">
                <button type="button" class="btn-close btn-close-white au-profile-close" data-bs-dismiss="modal" aria-label="Close"></button>
                <?php if (!empty($avatar)): ?>
                    <img src="<?php echo htmlspecialchars($avatarUrl); ?>" alt="" class="au-profile-avatar">
                <?php else: ?>
                    <span class="au-profile-avatar au-profile-initials" aria-hidden="true"><?php echo htmlspecialchars($profileInitials ?: '?'); ?></span>
                <?php endif; ?>
                <h5 class="au-profile-name" id="viewStaffModalLabel"><?php echo htmlspecialchars(!empty($fullName) ? $fullName : 'Faculty Member'); ?></h5>
                <div class="au-profile-tags">
                    <span class="au-portal-role">Faculty</span>
                    <?php if ($profileStatus !== ''): ?>
                        <span class="au-profile-status <?php echo $profileStatus === 'active' ? 'is-active' : 'is-inactive'; ?>">
                            <?php echo htmlspecialchars(ucfirst($profileStatus)); ?>
                        </span>
                    <?php endif; ?>
                </div>
            </div>

            <div class="modal-body au-profile-body">
                <dl class="au-profile-details">
                    <?php foreach ($profileDetails as $detail): ?>
                        <div class="au-profile-detail">
                            <i class="bi <?php echo $detail['icon']; ?>" aria-hidden="true"></i>
                            <div>
                                <dt><?php echo $detail['label']; ?></dt>
                                <dd>
                                    <?php if (empty($detail['value'])): ?>
                                        <span class="text-muted">Not set</span>
                                    <?php elseif (!empty($detail['href'])): ?>
                                        <a href="<?php echo htmlspecialchars($detail['href']); ?>"><?php echo htmlspecialchars($detail['value']); ?></a>
                                    <?php else: ?>
                                        <?php echo htmlspecialchars($detail['value']); ?>
                                    <?php endif; ?>
                                </dd>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </dl>
            </div>

            <div class="modal-footer au-profile-footer">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
                <button type="button" class="btn btn-csms" data-bs-toggle="modal" data-bs-target="#editStaffModal">
                    <i class="bi bi-pencil-square"></i> Edit Profile
                </button>
            </div>
        </div>
    </div>
</div>