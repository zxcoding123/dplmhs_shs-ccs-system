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
} elseif (isset($_SESSION['STATUS'])  && $_SESSION['STATUS'] == "ACCOUNT_C_SUCCESFUL") {
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
} elseif (isset($_SESSION['STATUS'])  && $_SESSION['STATUS'] == "ADMIN_INVALID_LOGIN") {
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
} elseif (isset($_SESSION['STATUS'])  && $_SESSION['STATUS'] == "LOG_OUT_SUCCESFUL") {
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
}
elseif (isset($_SESSION['STATUS'])  && $_SESSION['STATUS'] == "DUPLICATE_ACCOUNT") {
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
}

elseif (isset($_SESSION['STATUS'])  && $_SESSION['STATUS'] == "SAVED_GRADES") {
    echo "
        <script>
            Swal.fire({
                title: 'Grades Saved!',
                text: 'All grades have been successfully saved. You can make further changes before submitting.',
                icon: 'success'
            });
        </script>
        ";
    unset($_SESSION['STATUS']);
}

elseif (isset($_SESSION['STATUS'])  && $_SESSION['STATUS'] == "SUBMISSION_FOR_APPROVAL") {
    echo "
    <script>
        Swal.fire({
            title: 'Grades Submitted for Approval!',
            text: 'The grades are now pending approval. Once approved, they will be finalized.',
            icon: 'success'
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
if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_ADDED_SUCCESFULLY') {
    echo "
    <script>
        Swal.fire({
            title: 'Admin addition succesful!',
            text: 'You have succesfully added a new admin!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_ADDED_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Admin addition error!',
            text: 'There was an error in adding a new admin. Please try again!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_DELETE_SUCCESS') {
    echo "
    <script>
        Swal.fire({
            title: 'Admin deletion success!',
            text: 'You have succesfully deleted an existing admin!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_DELETE_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Admin deletion error!',
            text: 'There was an error in deleting an existing admin. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_EDIT_SUCCESFULLY') {
    echo "
    <script>
        Swal.fire({
            title: 'Admin update success!',
            text: 'You have succesfully updated the existing admin details',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADMIN_EDIT_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Admin update error!',
            text: 'There was an error in updating the admin details. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}
?>


<?php
if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADD_SEMESTER_SUCCESFUL') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester addition succesful!',
            text: 'You have succesfully added a new semester!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'ADD_SEMESTER_FAIL') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester addition error!',
            text: 'There was an error in adding a new semester. Please try again!',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_DELETED_SUCCESFULLY') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester deletion success!',
            text: 'You have succesfully deleted an existing semester!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_DELETION_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester deletion error!',
            text: 'There was an error in deleting an existing semester. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_EDITION_SUCCESFUL') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester update success!',
            text: 'You have succesfully updated the existing semester details',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'SEMESTER_EDITION_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester update error!',
            text: 'There was an error in updating the semester details. Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'UPDATE_SEMESTER_SUCCESSFUL') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester update succesful!',
            text: 'You have succesfully updated the current semester!',
            icon: 'success'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
} else if (isset($_SESSION['STATUS']) && $_SESSION['STATUS'] == 'UPDATE_SEMESTER_ERROR') {
    echo "
    <script>
        Swal.fire({
            title: 'Semester update error!',
            text: 'There was an error in updating the current semester Please try again.',
            icon: 'error'
        });
    </script>
    ";
    unset($_SESSION['STATUS']);
}






?>






