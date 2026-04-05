<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <div class="text-center">
            <p class="text-light time" id="currentTime"> </p>
        </div>
        <div class="sidebar-brand text-center" href="index.html">
            <img src="external/img/ADNU_Logo.png" class="img-fluid logo">
            <?php
            // Start the session to access the teacher's session ID

            // Check if the teacher's ID is set in the session
            if (isset($_SESSION['teacher_id'])) {
                $teacher_id = $_SESSION['teacher_id'];

                // Assuming $pdo is the PDO connection to the database
                try {
                    // Query to select the teacher's full name from staff_accounts based on the session teacher ID
                    $stmt = $pdo->prepare("SELECT fullName FROM staff_accounts WHERE id = :teacher_id");
                    $stmt->bindParam(':teacher_id', $teacher_id);
                    $stmt->execute();

                    // Fetch the teacher's details
                    $teacher = $stmt->fetch(PDO::FETCH_ASSOC);

                    // Check if the teacher's data was found
                    if ($teacher) {
                        // Display the teacher's name in the greeting
                        echo '<h3 class="align-middle text-light bold">Welcome, ' . htmlspecialchars($teacher['fullName']) . '!</h3>';
                    } else {
                        // Handle the case where no teacher was found (optional)
                        echo '<h1 class="align-middle text-light bold">Welcome, Teacher!</h1>';
                    }
                } catch (PDOException $e) {
                    // Error handling (optional)
                    echo "Error: " . $e->getMessage();
                }
            } else {
                // If the session does not contain a teacher ID, show a default message
                echo '<h1 class="align-middle text-light bold">Welcome, Teacher!</h1>';
            }
            ?>

            <!-- Edit Profile Button -->
            <button type="button" class="btn btn-csms mt-2" data-bs-toggle="modal" data-bs-target="#editStaffModal">
                <i class="bi bi-pencil-square"></i> Edit Profile
            </button>
        </div>




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

            <li class="sidebar-item <?php echo ($current_page == 'class_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="class_management.php">
                    <i class="bi bi-people align-middle"></i> <span class="align-middle">Class Management</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo ($current_page == 'subject_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="subject_management.php">
                    <i class="bi bi-journal align-middle"></i> <span class="align-middle">Subject Management</span>
                </a>
            </li>





            <hr style="border-bottom: 1px solid white;">

            <li class="sidebar-item <?php echo ($current_page == 'teacher_management.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="#" data-bs-toggle="modal" data-bs-target="#viewStaffModal">
                    <i class="bi bi-person-badge align-middle"></i> <span class="align-middle">Teacher User</span>
                </a>
            </li>
        </ul>
    </div>
</nav>

<?php
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
    $avatar = $teacher['avatar'];
} catch (PDOException $e) {
    echo "An error occurred while fetching the teacher's data: " . $e->getMessage();
}
?>

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