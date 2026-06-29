<?php
if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'TOO_MANY_ATTEMPTS') {
    echo "
    <script>
        Swal.fire({
            title: 'Too many failed attempts!',
            text: 'Your account has been temporarily locked. Please wait 5 minutes before trying again.',
            icon: 'warning'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_NOT_LOGGED_IN') {
    echo "
    <script>
        Swal.fire({
            title: 'Admin not logged in!',
            text: 'Please login your credentials as admin! If you are not the admin, please redirect somewhere.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_ACC_FOUND') {
    echo "
    <script>
        Swal.fire({
            title: 'There is already an admin account made!',
            text: 'Please login with your admin account details!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'INVALID_OR_EXPIRED_TOKEN') {
    echo "
    <script>
        Swal.fire({
            title: 'Invalid or Expired Token',
            text: 'The link you used is no longer valid or has already expired. Please request a new one.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'EMAIL_NOT_ADDU') {
    echo "
    <script>
        Swal.fire({
          title: 'Email creation error!',
        text: 'The email you have used is not accredited to ADDU. Please use your ADDU email for registering as a student!',
        icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'NOT_SAME_ID') {
    echo "
    <script>
        Swal.fire({
          title: 'Account creation error!',
        text: 'The student ID does not match the student ID in the email. Please try again!',
        icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'PASSWORDS_DO_NOT_MATCH') {
    echo "
    <script>
        Swal.fire({
          title: 'Password reset error!',
        text: 'The passwords do not match, please try again!',
        icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'INACTIVE_ACCOUNT_STAFF') {
    echo "
    <script>
        Swal.fire({
          title: 'Account login error!',
        text: 'Please wait until the admin approves of your account! Please notify admin as soon as possible!',
        icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STUDENT_NOT_LOGGED_IN') {
    echo "
        <script>
            Swal.fire({
              title: 'Student not logged in!',
            text: 'Please login your credentials as a student to proceed normally!.',
            icon: 'error'
            });
        </script>
        ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'TEACHER_NOT_LOGGED_IN') {
    echo "
            <script>
                Swal.fire({
                    title: 'Teacher not logged in!',
            text: 'Please login your credentials as a teacher/staff to proceed normally!.',
            icon: 'error'
                });
            </script>
            ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'LOGIN_ERROR') {
    echo "
            <script>
                Swal.fire({
                    title: 'Login error!',
            text: 'The credentials are wrong, please try again.',
            icon: 'error'
                });
            </script>
            ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'PASSWORD_RESET_SUCCESS') {
    echo "
      <script>
                Swal.fire({
                    title: 'Password reset succesfully!',
            text: 'Your password has been reset succesfully!',
            icon: 'success'
                });
            </script>
        ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'INACTIVE_ACCOUNT') {
    echo "
          <script>
                    Swal.fire({
                        title: 'Account error!',
                text: 'This account has not been activated yet! Please activate it via the registered email!',
                icon: 'error'
                    });
                </script>
            ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'EMAIL_NONE_EXISTENCE') {
    echo "
      <script>
                Swal.fire({
                    title: 'Login error!',
            text: 'The email has not been found in the database!',
            icon: 'error'
                });
            </script>
        ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'INVALID_CREDENTIALS') {
    echo "
      <script>
                Swal.fire({
                    title: 'Login error!',
            text: 'The credentials are wrong, please try again.',
            icon: 'error'
                });
            </script>
        ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ACCOUNT_NOT_FOUND') {
    echo "
                <script>
                    Swal.fire({
                        title: 'Login error!',
                text: 'The account doesn't exist in the database! Please create it based on your role!',
                icon: 'error'
                    });
                </script>
                ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SUCCESSFUL_LOG_OUT') {
    echo "
        <script>
            Swal.fire({
                title: 'You have succesfully logged out!',
        text: 'Thank you for using the system, we hope to see you soon again!',
        icon: 'success'
            });
        </script>
        ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "ACCOUNT_C_SUCCESFUL") {
    echo "
    <script>
        Swal.fire({
            title: 'Account created succesfully!',
            text: 'You have succesfully created an admin account!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "ADMIN_INVALID_LOGIN") {
    echo "
    <script>
        Swal.fire({
            title: 'Account login error!',
            text: 'Please check your account credentials and login again!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "LOG_OUT_SUCCESFUL") {
    echo "
    <script>
        Swal.fire({
            title: 'Account logout succesful!',
            text: 'You have succesfully logged out of your account!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "DUPLICATE_ACCOUNT") {
    echo "
    <script>
        Swal.fire({
            title: 'Account already made!',
            text: 'This account already exists as a duplicate. Please create a new one entirely!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "RESET_LINK_SENT") {
    echo "
    <script>
        Swal.fire({
            title: 'Password reset sent succesfully!',
            text: 'You have succesfully sent a reset link!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "EMAIL_ACTIVATED_SUCCESSFULLY") {
    echo "
    <script>
        Swal.fire({
            title: 'Email activated succesfully!',
            text: 'You have succesfully activated your email!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "EMAIL_ALREADY_ACTIVE") {
    echo "
    <script>
        Swal.fire({
            title: 'This email has already been activated!',
            text: 'Please login your credentials with the said email!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "STUDENT_ID_EXISTS") {
    echo "
    <script>
        Swal.fire({
            title: 'Student ID already associated with the email used for registration!',
            text: 'The email has already been used! Reset it by using the Forget Password option?',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "REGISTRATION_SUCCESSFUL_ACTIVATION_PLEASE_STAFF") {
    echo "
    <script>
        Swal.fire({
            title: 'Account registered succesfully!',
            text: 'Please ask the admin to activate your account!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "REGISTRATION_SUCCESSFUL_ACTIVATION_PLEASE") {
    echo "
    <script>
        Swal.fire({
            title: 'Account registered succesfully!',
            text: 'Please head to your email to activate your account!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "EMAIL_ALREADY_EXISTS") {
    echo "
    <script>
        Swal.fire({
            title: 'Account already made!',
            text: 'This account already exists as a duplicate. Please create a new one entirely!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "PASSWORD_NOT_SAME") {
    echo "
    <script>
        Swal.fire({
            title: 'Password confirmation error!',
            text: 'Password isn't the same! Please check it.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}
?>







<?php
if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_LOGIN_SUCCESFUL') {
    echo "
    <script>
        Swal.fire({
            title: 'Login succesful!',
            text: 'You have succesfully logged into your account as admin!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADD_NOTES_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Addition of note succesful!',
            text: 'You have succesfully added a note!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADD_NOTES_FAIL') {
    echo "
    <script>
        Swal.fire({
            title: 'Addition of note failed!',
            text: 'There was an error in adding of notes, please try again!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'NOTES_DELETED_SUCCESFULLY') {
    echo "
    <script>
        Swal.fire({
            title: 'Deletion of note succesful!',
            text: 'You have succesfully deleted a note!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'NOTES_EDITION_SUCCESFUL') {
    echo "
    <script>
        Swal.fire({
            title: 'Edition of note succesful!',
            text: 'You have succesfully edited your note!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'REMINDERS_DELETED_SUCCESFULLY') {
    echo "W
    <script>
        Swal.fire({
            title: 'Edition of note succesful!',
            text: 'You have succesfully edited your note!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADD_REMINDER_SUCCESS') {
    echo "W
    <script>
        Swal.fire({
            title: 'Addition of remninder succesful!',
            text: 'You have succesfully added a reminder!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'REMINDERS_DELETED_SUCCESFULLY') {
    echo "W
    <script>
        Swal.fire({
            title: 'Deletion of remninder succesful!',
            text: 'You have succesfully deleted a reminder!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}
?>


<?php
if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_SUBJECT_ADD_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Subject addition succesful!',
            text: 'You have succesfully added a new subject!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_SUBJECT_ADD_FAIL') {
    echo "
    <script>
        Swal.fire({
            title: 'Subject addition error!',
            text: 'There was an error in deleting the subject. Please try again!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_SUBJECT_DELETE_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Subject deletion success!',
            text: 'You have succesfully deleted an existing subject!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_SUBJECT_DELETE_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Subject deletion error!',
            text: 'There was an error in deleting the subject. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_SUBJECT_UPDATE_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Subject update success!',
            text: 'You have succesfully updated the existing subject details',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_SUBJECT_UPDATE_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Subject update error!',
            text: 'There was an error in updating the subject details. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_SUBJECT_EXISTS') {
    echo "
    <script>
        Swal.fire({
            title: 'Subject exists error!',
            text: 'The subject already exists. Please enter a new one!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}
?>




<?php
if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADDED_NEW_CLASS_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Class addition succesful!',
            text: 'You have succesfully added a new class!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADDED_NEW_CLASS_FAILED') {
    echo "
    <script>
        Swal.fire({
            title: 'Class addition error!',
            text: 'There was an error in adding the class. Please try again!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'DELETE_CLASS_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Class deletion success!',
            text: 'You have succesfully deleted the existing class!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'DELETE_CLASS_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Class deletion error!',
            text: 'There was an error in deleting the Class. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'EDIT_CLASS_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Class update success!',
            text: 'You have succesfully updated the existing class details',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'EDIT_CLASS_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Class update error!',
            text: 'There was an error in updating the class details. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'NEW_CLASS_EXISTS') {
    echo "
    <script>
        Swal.fire({
            title: 'Class exists error!',
            text: 'The subject already exists. Please enter a new one!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}
?>

<?php
if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STAFF_ADDED_SUCCESSFULLY') {
    echo "
    <script>
        Swal.fire({
            title: 'Staff addition succesful!',
            text: 'You have succesfully added a new staff!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STAFF_ADDED_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Staff addition error!',
            text: 'There was an error in adding the staff. Please try again!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STAFF_EMAIL_EXISTS') {
    echo "
    <script>
        Swal.fire({
            title: 'Staff account exists!',
            text: 'This email has already been in use! Please use a new one!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STAFF_DELETE_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Subject deletion success!',
            text: 'You have succesfully deleted an existing staff!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STAFF_DELETE_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Staff deletion error!',
            text: 'There was an error in deleting the staff. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STAFF_ACCOUNT_UPDATED') {
    echo "
    <script>
        Swal.fire({
            title: 'Staff update success!',
            text: 'You have succesfully updated the existing staff details',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STAFF_ACCOUNT_FAIL_UPDATE') {
    echo "
    <script>
        Swal.fire({
            title: 'Staff update error!',
            text: 'There was an error in updating the staff details. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}
?>
<?php
if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_FIELDS_EMPTY') {
    echo "
    <script>
        Swal.fire({
            title: 'Empty fields!',
            text: 'Please fill out all fields before submitting.',
            icon: 'warning'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_DATE_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Date Error!',
            text: 'The start and end dates cannot be the same. Please select different dates.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_NAME_EXISTS') {
    echo "
    <script>
        Swal.fire({
            title: 'Duplicate Semester!',
            text: 'The semester name already exists and is not archived. Please choose a different name.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_ADDED_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Success!',
            text: 'The semester was successfully added.',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_ADD_FAILED') {
    echo "
    <script>
        Swal.fire({
            title: 'Addition Failed!',
            text: 'There was an error adding the semester. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_DATABASE_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Database Error!',
            text: 'An error occurred while connecting to the database. Please try again later.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_ADD_INVALID_REQUEST') {
    echo "
    <script>
        Swal.fire({
            title: 'Invalid Request!',
            text: 'The request method was invalid. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}
?>
<?php

// Check for semester archive status
if (isset($_SESSION['STATUS'])) {
    if ($_SESSION['STATUS'] == 'SEMESTER_ARCHIVED_SUCCESS') {
        echo "
        <script>
            Swal.fire({
                title: 'Archive Successful!',
                text: 'The semester and its related classes and subjects have been successfully archived.',
                icon: 'success'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    } elseif ($_SESSION['STATUS'] == 'SEMESTER_ARCHIVE_FAILED') {
        echo "
        <script>
            Swal.fire({
                title: 'Archive Failed!',
                text: 'An error occurred while archiving the semester. Please try again.',
                icon: 'error'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    }
}

// Check for semester deletion status
if (isset($_SESSION['STATUS'])) {
    if ($_SESSION['STATUS'] == 'SEMESTER_DELETE_SUCCESS') {
        echo "
        <script>
            Swal.fire({
                title: 'Deleted!',
                text: 'Semester deleted successfully.',
                icon: 'success'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    } elseif ($_SESSION['STATUS'] == 'SEMESTER_DELETE_FAILED') {
        echo "
        <script>
            Swal.fire({
                title: 'Deletion Failed!',
                text: 'Failed to delete semester.',
                icon: 'error'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    } elseif ($_SESSION['STATUS'] == 'SEMESTER_DELETE_ERROR' && isset($_SESSION['ERROR_MESSAGE'])) {
        $errorMessage = $_SESSION['ERROR_MESSAGE'];
        echo "
        <script>
            Swal.fire({
                title: 'Error!',
                text: 'Error occurred: {$errorMessage}',
                icon: 'error'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
        unset($_SESSION['ERROR_MESSAGE']);
    }
}

// Check for semester update status
if (isset($_SESSION['STATUS'])) {
    if ($_SESSION['STATUS'] == 'SEMESTER_UPDATE_SUCCESS') {
        echo "
        <script>
            Swal.fire({
                title: 'Updated!',
                text: 'Semester updated successfully.',
                icon: 'success'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    } elseif ($_SESSION['STATUS'] == 'SEMESTER_UPDATE_FAILED') {
        echo "
        <script>
            Swal.fire({
                title: 'Update Failed!',
                text: 'Failed to update semester.',
                icon: 'error'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    } elseif ($_SESSION['STATUS'] == 'SEMESTER_UPDATE_ERROR' && isset($_SESSION['ERROR_MESSAGE'])) {
        $errorMessage = $_SESSION['ERROR_MESSAGE'];
        echo "
        <script>
            Swal.fire({
                title: 'Error!',
                text: 'Error occurred: {$errorMessage}',
                icon: 'error'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
        unset($_SESSION['ERROR_MESSAGE']);
    } elseif ($_SESSION['STATUS'] == 'SEMESTER_FIELDS_EMPTY') {
        echo "
        <script>
            Swal.fire({
                title: 'Empty Fields!',
                text: 'All fields are required.',
                icon: 'warning'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    }
}

// Check for semester activation status
if (isset($_SESSION['STATUS'])) {
    if ($_SESSION['STATUS'] == 'SEMESTER_ACTIVATED') {
        echo "
        <script>
            Swal.fire({
                title: 'Activated!',
                text: 'Semester activated successfully.',
                icon: 'success'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    } elseif ($_SESSION['STATUS'] == 'SEMESTER_ACTIVATION_ERROR' && isset($_SESSION['ERROR_MESSAGE'])) {
        $errorMessage = $_SESSION['ERROR_MESSAGE'];
        echo "
        <script>
            Swal.fire({
                title: 'Activation Failed!',
                text: 'Error: {$errorMessage}',
                icon: 'error'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
        unset($_SESSION['ERROR_MESSAGE']);
    } elseif ($_SESSION['STATUS'] == 'INVALID_REQUEST') {
        echo "
        <script>
            Swal.fire({
                title: 'Invalid Request!',
                text: 'Please try again with a valid request.',
                icon: 'warning'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    }
}

// Check for semester inactivity status
if (isset($_SESSION['STATUS'])) {
    if ($_SESSION['STATUS'] == 'SEMESTER_INACTIVE') {
        echo "
        <script>
            Swal.fire({
                title: 'Inactive!',
                text: 'Semester made inactive successfully.',
                icon: 'success'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    } elseif ($_SESSION['STATUS'] == 'SEMESTER_INACTIVE_ERROR' && isset($_SESSION['ERROR_MESSAGE'])) {
        $errorMessage = $_SESSION['ERROR_MESSAGE'];
        echo "
        <script>
            Swal.fire({
                title: 'Operation Failed!',
                text: 'Error: {$errorMessage}',
                icon: 'error'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
        unset($_SESSION['ERROR_MESSAGE']);
    } elseif ($_SESSION['STATUS'] == 'INVALID_REQUEST') {
        echo "
        <script>
            Swal.fire({
                title: 'Invalid Request!',
                text: 'Please try again with a valid request.',
                icon: 'warning'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    }
}
?>