<?php $current_page = basename($_SERVER['PHP_SELF']); ?>

<nav id="sidebar" class="sidebar js-sidebar">
    <div class="sidebar-content js-simplebar">
        <div class="text-center">
            <p class="text-light time" id="currentTime"></p>
        </div>
        <div class="sidebar-brand text-center" href="index.html">
            <?php
            $stmt = $pdo->prepare("SELECT picture FROM student_pictures WHERE user_id = :user_id");
            $stmt->execute([':user_id' => $_SESSION['user_id']]);
            $student = $stmt->fetch(PDO::FETCH_ASSOC);

            $profilePicture = !empty($student['picture']) ? $student['picture'] : 'ADNU_Logo.png';
            $path = "ADNU_CCS_Logo.png" . $profilePicture;
            ?>

            <img src="../uploads/profile_pictures/<?php echo $profilePicture; ?>" class="img-fluid logo" alt="Profile Picture">




            <?php
            if (isset($_SESSION['student_id'])) {
                $student_id = $_SESSION['student_id'];

                try {
                    $stmt = $pdo->prepare("SELECT fullName FROM students WHERE student_id = :student_id");
                    $stmt->bindParam(':student_id', $student_id, PDO::PARAM_INT);
                    $stmt->execute();
                    $student = $stmt->fetch(PDO::FETCH_ASSOC);

                    if ($student) {
                        echo '<h3 class="align-middle text-light bold">Welcome, ' . htmlspecialchars($student['fullName']) . '!</h3>';
                    } else {
                        echo '<h1 class="align-middle text-light bold">Welcome, Student!</h1>';
                    }
                } catch (PDOException $e) {
                    echo "Error: " . $e->getMessage();
                }
            } else {
                echo '<h1 class="align-middle text-light bold">Welcome, Student!</h1>';
            }
            ?>

            <!-- Four Buttons -->
            <div class="mt-3">
                <button type="button" class="btn btn-csms w-100 mb-2" data-bs-toggle="modal"
                    data-bs-target="#updateProfileModal">
                    <i class="bi bi-pencil-square"></i> Update Profile
                </button>
                <button type="button" class="btn btn-csms w-100 mb-2" data-bs-toggle="modal"
                    data-bs-target="#changePasswordModal">
                    <i class="bi bi-lock"></i> Change Password
                </button>

                <button type="button" class="btn btn-csms w-100 mb-2" data-bs-toggle="modal"
                    data-bs-target="#updateProfilePictureModal">
                    <i class="bi bi-person-circle"></i> Update Profile Picture
                </button>


            </div>

        </div>
        <ul class="sidebar-nav">
            <li class="sidebar-header">
                Pages
            </li>

            <li class="sidebar-item <?php echo ($current_page == 'student_dashboard.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="student_dashboard.php">
                    <i class="bi bi-sliders align-middle"></i> <span class="align-middle">Index</span>
                </a>
            </li>
            <hr style="border-bottom: 1px solid white;">

            <li class="sidebar-item <?php echo ($current_page == 'student_classes_dashboard.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="student_classes_dashboard.php">
                    <i class="bi bi-people align-middle"></i> <span class="align-middle">Class</span>
                </a>
            </li>

            <li class="sidebar-item <?php echo ($current_page == 'student_subjects.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="student_subjects.php">
                    <i class="bi bi-journal align-middle"></i> <span class="align-middle">Subjects</span>
                </a>
            </li>





            <hr style="border-bottom: 1px solid white;">

            <li class="sidebar-item <?php echo ($current_page == 'student_dashboard.php') ? 'active' : ''; ?>">
                <a class="sidebar-link" href="#">
                    <i class="bi bi-person-badge align-middle"></i> <span class="align-middle">Student User</span>
                </a>
            </li>
        </ul>

    </div>
</nav>

<?php



// Check if student_id is in session
if (isset($_SESSION['student_id'])) {
    $studentId = $_SESSION['student_id'];

    $sql = "SELECT * FROM students WHERE student_id = :studentId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':studentId', $studentId);
    $stmt->execute();

    $prevStudentData = $stmt->fetch(PDO::FETCH_ASSOC);

    $first_name = $prevStudentData['first_name'];
    $middle_name = $prevStudentData['middle_name'];
    $last_name = $prevStudentData['last_name'];

    // Fetch student data from the database
    $sql = "SELECT * FROM student_info WHERE student_id = :studentId";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':studentId', $studentId);
    $stmt->execute();

    // Assuming there is one row of data
    $studentData = $stmt->fetch(PDO::FETCH_ASSOC);

    // If no data is found, redirect or handle the error
    if (!$studentData) {
    }
}
?>

<!-- Modals -->
<!-- Update Profile Modal -->
<div class="modal fade" id="updateProfileModal" tabindex="-1" aria-labelledby="updateProfileModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateProfileModalLabel">Update Profile</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="update.php" method="POST">
                    <!-- Student ID Field (Read-only) -->
                    <div class="mb-3">
                        <label for="studentId" class="form-label">Student ID</label>
                        <input type="text" class="form-control" id="studentId" name="studentId" required
                            value="<?php echo $_SESSION['student_id'] ?? 'N/A'; ?>" readonly>
                    </div>

                    <!-- Student Name Field -->
                    <div class="mb-3">
                        <label for="studentName" class="form-label">First Name</label>
                        <input type="text" class="form-control" id="studentName" name="first_name" required
                            value="<?php echo htmlspecialchars($first_name ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="studentName" class="form-label">Middle Name</label>
                        <input type="text" class="form-control" id="studentName" name="middle_name" required
                            value="<?php echo htmlspecialchars($middle_name ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="studentName" class="form-label">Last Name</label>
                        <input type="text" class="form-control" id="studentName" name="last_name" required
                            value="<?php echo htmlspecialchars($last_name); ?>">
                    </div>

                    <!-- Student Email Field -->
                    <div class="mb-3">
                        <label for="studentEmail" class="form-label">ADDU Email</label>
                        <input type="email" class="form-control" readonly
                            value="<?php echo htmlspecialchars($prevStudentData['email'] ?? ''); ?>">
                    </div>

                    <div class="mb-3">
                        <label for="studentEmail" class="form-label">Email</label>
                        <input type="email" class="form-control" id="studentEmail" name="studentEmail" required
                            value="<?php echo htmlspecialchars($studentData['email'] ?? ''); ?>">
                    </div>

                    <!-- Student Phone Number Field -->
                    <div class="mb-3">
                        <label for="studentPhone" class="form-label">Phone Number</label>
                        <input type="text" class="form-control" id="studentPhone" name="studentPhone" required
                            value="<?php echo htmlspecialchars($studentData['phone_number'] ?? ''); ?>">
                    </div>

                    <!-- Course Year Field -->
                    <div class="mb-3">
                        <label for="course_year" class="form-label bold">Course & Year</label>
                        <select class="form-select" id="course_year" name="course_year" required>
                            <option value="" disabled <?php echo empty($prevStudentData['course']) || empty($prevStudentData['year_level']) ? 'selected' : ''; ?>>Select Course & Year
                            </option>
                            <!-- Bachelor of Science in Information Technology (BSIT) -->
                            <optgroup label="Bachelor of Science in Information Technology (BSIT)">
                                <option value="BSIT-1" <?php echo ($prevStudentData['course'] == 'BSIT' && $prevStudentData['year_level'] == '1st Year') ? 'selected' : ''; ?>>BSIT - 1st Year
                                </option>
                                <option value="BSIT-2" <?php echo ($prevStudentData['course'] == 'BSIT' && $prevStudentData['year_level'] == '2nd Year') ? 'selected' : ''; ?>>BSIT - 2nd Year
                                </option>
                                <option value="BSIT-3" <?php echo ($prevStudentData['course'] == 'BSIT' && $prevStudentData['year_level'] == '3rd Year') ? 'selected' : ''; ?>>BSIT - 3rd Year
                                </option>
                                <option value="BSIT-4" <?php echo ($prevStudentData['course'] == 'BSIT' && $prevStudentData['year_level'] == '4th Year') ? 'selected' : ''; ?>>BSIT - 4th Year
                                </option>
                            </optgroup>
                            <!-- Bachelor of Science in Computer Science (BSCS) -->
                            <optgroup label="Bachelor of Science in Computer Science (BSCS)">
                                <option value="BSCS-1" <?php echo ($prevStudentData['course'] == 'BSCS' && $prevStudentData['year_level'] == '1st Year') ? 'selected' : ''; ?>>BSCS - 1st Year
                                </option>
                                <option value="BSCS-2" <?php echo ($prevStudentData['course'] == 'BSCS' && $prevStudentData['year_level'] == '2nd Year') ? 'selected' : ''; ?>>BSCS - 2nd Year
                                </option>
                                <option value="BSCS-3" <?php echo ($prevStudentData['course'] == 'BSCS' && $prevStudentData['year_level'] == '3rd Year') ? 'selected' : ''; ?>>BSCS - 3rd Year
                                </option>
                                <option value="BSCS-4" <?php echo ($prevStudentData['course'] == 'BSCS' && $prevStudentData['year_level'] == '4th Year') ? 'selected' : ''; ?>>BSCS - 4th Year
                                </option>
                            </optgroup>
                        </select>
                    </div>

                    <!-- Address Field -->
                    <div class="mb-3">
                        <label for="studentAddress" class="form-label">Address</label>
                        <input type="text" class="form-control" id="studentAddress" name="studentAddress" required
                            value="<?php echo htmlspecialchars($studentData['address'] ?? ''); ?>">
                    </div>

                    <!-- Emergency Contact Field -->
                    <div class="mb-3">
                        <label for="emergencyContact" class="form-label">Emergency Contact</label>
                        <input type="text" class="form-control" id="emergencyContact" name="emergencyContact" required
                            value="<?php echo htmlspecialchars($studentData['emergency_contact'] ?? ''); ?>">
                    </div>

                    <!-- Gender Field -->
                    <div class="mb-3">
                        <label for="studentGender" class="form-label">Gender</label>
                        <select class="form-select" id="studentGender" name="studentGender" required>
                            <option value="" disabled <?php echo empty($studentData['gender']) ? 'selected' : ''; ?>>
                                Select Gender</option>
                            <option value="Male" <?php echo ($studentData['gender'] ?? '') === 'Male' ? 'selected' : ''; ?>>Male</option>
                            <option value="Female" <?php echo ($studentData['gender'] ?? '') === 'Female' ? 'selected' : ''; ?>>Female</option>
                            <option value="Other" <?php echo ($studentData['gender'] ?? '') === 'Other' ? 'selected' : ''; ?>>Other</option>
                        </select>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>


<!-- Change Password Modal -->
<div class="modal fade" id="changePasswordModal" tabindex="-1" aria-labelledby="changePasswordModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="changePasswordModalLabel">Change Password</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="changePasswordForm" action="change_password.php" method="POST">
                    <!-- New Password Field -->
                    <div class="mb-3">
                        <label for="newPassword" class="form-label">New Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="newPassword" name="newPassword" required>
                            <button type="button" class="input-group-text" id="toggleNewPassword"
                                onclick="togglePassword('newPassword')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Confirm Password Field -->
                    <div class="mb-3">
                        <label for="confirmPassword" class="form-label">Confirm Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control" id="confirmPassword" name="confirmPassword"
                                required>
                            <button type="button" class="input-group-text" id="toggleConfirmPassword"
                                onclick="togglePassword('confirmPassword')">
                                <i class="bi bi-eye"></i>
                            </button>
                        </div>
                    </div>

                    <!-- Submit Button -->
                    <button type="submit" class="btn btn-primary">Update Password</button>
                </form>

                <!-- Bootstrap Icons for the Eye toggle -->
                <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons/font/bootstrap-icons.css" rel="stylesheet">

                <script>
                    // Toggle password visibility
                    function togglePassword(passwordFieldId) {
                        var passwordField = document.getElementById(passwordFieldId);
                        var icon = document.getElementById('toggle' + passwordFieldId.charAt(0).toUpperCase() + passwordFieldId.slice(1));

                        // Toggle the type attribute and the icon
                        if (passwordField.type === "password") {
                            passwordField.type = "text";
                            icon.innerHTML = "<i class='bi bi-eye-slash'></i>"; // Change to "eye-slash"
                        } else {
                            passwordField.type = "password";
                            icon.innerHTML = "<i class='bi bi-eye'></i>"; // Change to "eye"
                        }
                    }
                </script>

            </div>
        </div>
    </div>
</div>

<script>
    // Event listener for form submission
    document.getElementById('changePasswordForm').addEventListener('submit', function(event) {
        var newPassword = document.getElementById('newPassword').value;
        var confirmPassword = document.getElementById('confirmPassword').value;

        // Check if passwords match
        if (newPassword !== confirmPassword) {
            alert("Passwords do not match.");
            event.preventDefault(); // Prevent form submission
        }
    });
</script>


<!-- Update Course & Year Modal -->
<div class="modal fade" id="updateCourseYearModal" tabindex="-1" aria-labelledby="updateCourseYearModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateCourseYearModalLabel">Update Course & Year</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="update_course_year.php" method="POST">
                    <div class="mb-3">
                        <label for="course" class="form-label">Course</label>
                        <input type="text" class="form-control" id="course" name="course" required>
                    </div>
                    <div class="mb-3">
                        <label for="year" class="form-label">Year</label>
                        <input type="number" class="form-control" id="year" name="year" min="1" max="5" required>
                    </div>
                    <button type="submit" class="btn btn-primary">Save Changes</button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- Update Profile Picture Modal -->
<div class="modal fade" id="updateProfilePictureModal" tabindex="-1" aria-labelledby="updateProfilePictureModalLabel"
    aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateProfilePictureModalLabel">Update Profile Picture</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="new_profile_picture.php" method="POST" enctype="multipart/form-data">
                    <div class="mb-3">
                        <label for="profilePicture" class="form-label">Upload Profile Picture</label>
                        <input type="file" class="form-control" id="student_picture" name="student_picture" required
                            accept="image/*">
                    </div>
                    <button type="submit" class="btn btn-primary">Upload</button>

                </form>
                <a href="reset_profile_picture.php"><button class="btn btn-warning">Reset</button></a>

            </div>
        </div>
    </div>
</div>

<script>
    <?php if (isset($_SESSION['STATUS'])): ?>
        <?php
        $status = $_SESSION['STATUS'];
        unset($_SESSION['STATUS']); // unset immediately
        ?>

        <?php if ($status === "PICTURE_REMOVED_SUCCESFUL"): ?>
            Swal.fire({
                icon: 'success',
                title: 'Success',
                text: 'Picture was removed successfully!',
                confirmButtonColor: '#3085d6'
            });
        <?php elseif ($status === "PICTURE_REMOVED_UNSUCCESFUL"): ?>
            Swal.fire({
                icon: 'error',
                title: 'Failed',
                text: 'No picture found or removal failed!',
                confirmButtonColor: '#d33'
            });
        <?php endif; ?>

    <?php endif; ?>
</script>