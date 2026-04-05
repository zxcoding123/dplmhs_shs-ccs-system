<?php


if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_NOT_LOGGED_IN') {
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
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'READ_ALL_NOTIFICATIONS') {
    echo "
    <script>
        Swal.fire({
            title: 'All notiifcations read succesfully!',
            text: 'All of your notifications have been read succesfully!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}  elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'EVERYTHING_FALSE') {
    echo "
    <script>
        Swal.fire({
            title: 'All notiifcations have been reset successfully!',
            text: 'All of your autonamic notifications have been reset successfully!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'CLASS_DUPLICATE') {
    echo "
    <script>
        Swal.fire({
           title: 'Class advisory already exists!',
            text: 'Please choose another class to advise for this staff profile! ',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);

} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'NEW_STATUS_SUCCESFUL') {
    echo "
    <script>
        Swal.fire({
           title: 'Account status updated succesfully!',
            text: 'You have succesfully edited this account\'s status! ',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);


} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'DELETE_ALL_NOTIFICATIONS') {
    echo "
    <script>
        Swal.fire({
           title: 'All notiifcations deleted succesfully!',
            text: 'All of your notifications have been deleted succesfully!',
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
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "ADMIN_DUPLICATE_ACCOUNT") {
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
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "ADMIN_DELETED_SUCCESS") {
    echo "
    <script>
        Swal.fire({
            title: 'Account deletion succesful!',
            text: 'The admin account has just been succesfully deleted!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == "ADMIN_EDIT_SUCCESFUL") {
    echo "
    <script>
        Swal.fire({
            title: 'Account edition succesful!',
            text: 'The admin account credentials and details have just been succesfully edited!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}


// Handling the ADMIN_CREATE_FAILED status
elseif (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_CREATE_FAILED') {
    echo "
    <script>
        Swal.fire({
            title: 'Admin account creation failed!',
            text: 'There was an error while creating the admin account. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}

// Handling the ADMIN_CREATE_ERROR status
elseif (isset($_SESSION['STATUS']) && strpos($_SESSION['STATUS'], 'ADMIN_CREATE_ERROR') !== false) {
    $error_message = str_replace('ADMIN_CREATE_ERROR: ', '', $_SESSION['STATUS']); // Extract error message
    echo "
    <script>
        Swal.fire({
            title: 'Admin account creation error!',
            text: 'An error occurred: $error_message. Please check your input and try again.',
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
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADD_REMINDER_SUCCESS') {
    echo "
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
    echo "
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


} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STAFF_ALREADY_EXISTS') {
    echo "
    <script>
        Swal.fire({
            title: 'Staff name already exists!',
            text: 'This name has already been in use!',
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
            title: 'Staff deletion success!',
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
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'STAFF_DEPT_CLASS_EXISTS') {
    echo "
    <script>
        Swal.fire({
            title: 'Staff update error!',
            text: 'The class and department already exists for advising!',
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
            text: 'The semester already exists and is not archived. Please choose a different year.',
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
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_YEAR_MISMATCH') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester School Year Mismatch!',
            text: 'The semester year does not match, please try again!.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}
 else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'TEACHER_ALREADY_ASSIGNED_TO_CLASS') {
    echo "
    <script>
        Swal.fire({
            title: 'Class Assignment Error!',
            text: 'The teacher is already assigned to this class! As default, it will be equated to none!',
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
    elseif ($_SESSION['STATUS'] == 'CLASS_STATUS_ACCEPTED') {
        echo "
        <script>
            Swal.fire({
                title: 'Class accepted!',
                text: 'This class has been accepted for teaching!',
                icon: 'success'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    }
    elseif ($_SESSION['STATUS'] == 'CLASS_STATUS_DISAPPROVED') {
        echo "
        <script>
            Swal.fire({
                title: 'Class rejected!',
                text: 'This class has been rejected for teaching!',
                icon: 'error'
            });
        </script>
        ";
        unset($_SESSION['STATUS']);
    
    }
        else if($_SESSION['STATUS'] == 'SCHEDULE_CONFLICT'){
            echo "
            <script>
                Swal.fire({
                    title: 'Subject schedule conflict!',
                    text: 'The current subject schedule is in conflict with other subjects. Please try again!',
                    icon: 'error'
                });
            </script>
            ";
        }
        unset($_SESSION['STATUS']);
    }
    
      

?>

