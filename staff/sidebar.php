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
} catch (PDOException $e) {
    echo "An error occurred while fetching the teacher's data: " . $e->getMessage();
}
?>

<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <a class="au-portal-brand" href="index.php">
            <img src="../external/img/AU_Logo.png" alt="Aurelius University seal">
            <span><strong>Aurelius University</strong><small>CCS Faculty Portal</small></span>
        </a>

        <div class="au-portal-user">
            <img src="<?php echo !empty($avatar) ? '../uploads/profile_pictures/' . htmlspecialchars($avatar) : '../external/img/AU_CCS_Logo.png'; ?>" class="au-portal-avatar" alt="">
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

<!-- Modal for Editing Staff Information -->
<div class="modal fade" id="editStaffModal" tabindex="-1" aria-labelledby="editStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editStaffModalLabel">Edit Staff Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="update_details.php" enctype="multipart/form-data">

                    <!-- Profile Picture -->
                    <div class="mb-3 text-center">
                        <?php if (!empty($avatar)): ?>
                            <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($avatar); ?>" alt="Profile Picture" class="rounded-circle mb-2" width="100" height="100">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/100" alt="Profile Picture" class="rounded-circle mb-2">
                        <?php endif; ?>
                        <input class="form-control mt-2" type="file" name="avatar" accept="image/*">
                        <small class="text-muted">Choose a new profile picture (optional)</small>
                    </div>

                    <!-- Full Name -->
                    <div class="mb-3">
                        <label for="firstName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="firstName" name="firstName"
                            value="<?php echo htmlspecialchars($firstName); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="middleName" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="middleName" name="middleName"
                            value="<?php echo htmlspecialchars($middleName); ?>" required>
                    </div>
                    <div class="mb-3">
                        <label for="lastName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="lastName" name="lastName"
                            value="<?php echo htmlspecialchars($lastName); ?>" required>
                    </div>

                    <!-- Email -->
                    <div class="mb-3">
                        <label for="email" class="form-label">Email</label>
                        <input type="email" class="form-control" id="email" name="email"
                            value="<?php echo htmlspecialchars($email); ?>" required>
                    </div>

                    <!-- Password -->
                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <input type="password" class="form-control" id="password" name="password">
                        <small class="text-muted">Leave blank to keep current password</small>
                    </div>

                    <!-- Department -->
                    <div class="mb-3">
                        <label for="department" class="form-label">Department</label>
                        <select class="form-select" id="department" name="department" required>
                            <option value="Department of Information Technology" <?php echo ($department == 'Department of Information Technology') ? 'selected' : ''; ?>>Department of Information Technology</option>
                            <option value="Department of Computer Science" <?php echo ($department == 'Department of Computer Science') ? 'selected' : ''; ?>>Department of Computer Science</option>
                        </select>
                    </div>

                    <!-- Phone Number -->
                    <div class="mb-3">
                        <label for="phone_number" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="phone_number" name="phone_number"
                            value="<?php echo htmlspecialchars($phone_number); ?>" required>
                    </div>

                    <!-- Gender -->
                    <div class="mb-3">
                        <label for="gender" class="form-label">Gender</label>
                        <select class="form-select" id="gender" name="gender" required>
                            <option value="Male" <?php echo ($gender == 'Male') ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($gender == 'Female') ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($gender == 'Other') ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


<div class="modal fade" id="viewStaffModal" tabindex="-1" aria-labelledby="viewStaffModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="viewStaffModalLabel">Staff Information</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3 text-center">
                        <?php if (!empty($avatar)): ?>
                            <img src="../uploads/profile_pictures/<?php echo htmlspecialchars($avatar); ?>" alt="Profile Picture" class="rounded-circle mb-2" width="100" height="100">
                        <?php else: ?>
                            <img src="https://via.placeholder.com/100" alt="Profile Picture" class="rounded-circle mb-2">
                        <?php endif; ?>
                        <br>
                        <small class="text-muted">Profile picture </small>
                    </div>
                <!-- Full Name -->
                <div class="mb-3">
                    <label class="form-label"><b>First Name</b></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($firstName); ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label"><b>Middle Name</b></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($middleName); ?></p>
                </div>
                <div class="mb-3">
                    <label class="form-label"><b>Last Name</b></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($lastName); ?></p>
                </div>

                <!-- Email -->
                <div class="mb-3">
                    <label class="form-label"><b>Email</b></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($email); ?></p>
                </div>

                <!-- Password (optional, display placeholder text for security) -->
                <div class="mb-3">
                    <label class="form-label"><b>Password</b></label>
                    <p class="form-control-plaintext">********</p>
                </div>

                <!-- Department -->
                <div class="mb-3">
                    <label class="form-label"><b>Department</b></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($department); ?></p>
                </div>

                <!-- Phone Number -->
                <div class="mb-3">
                    <label class="form-label"><b>Phone Number</b></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($phone_number); ?></p>
                </div>

                <!-- Gender -->
                <div class="mb-3">
                    <label class="form-label"><b>Gender</b></label>
                    <p class="form-control-plaintext"><?php echo htmlspecialchars($gender); ?></p>
                </div>

                <!-- Close Button -->
                <div class="text-end">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>
</div>